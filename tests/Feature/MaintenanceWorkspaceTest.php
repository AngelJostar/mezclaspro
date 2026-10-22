<?php

namespace Tests\Feature;

use App\Models\{MaintenanceAppointment, MaintenanceDocument, MaintenanceProvider, MaintenanceService, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\MaintenanceWorkspace;
use Tests\TestCase;

class MaintenanceWorkspaceTest extends TestCase
{
    private array $data;
    protected function setUp(): void
    {
        parent::setUp();
        $this->data = MaintenanceWorkspace::seed();
        $this->actingAs($this->data['manager']);
    }
    private function url(string $name, $parameters=[]): string { return route('admin.maintenance-qualifications.'.$name, $parameters); }

    public function test_four_sections_render_real_records_and_filter_by_central_date_and_search(): void
    {
        foreach (['calendario'=>'Calendario de servicios','servicios'=>'Catálogo de servicios','proveedores'=>'Proveedores de servicios','programacion'=>'Programación de servicios y mantenimientos'] as $section=>$title) {
            $response = $this->get($this->url('index', ['seccion'=>$section,'fecha'=>'2026-10-21']));
            $response->assertOk()->assertSee($title)->assertSee('data-maintenance-workspace', false);
        }
        $this->get($this->url('index', ['fecha'=>'2026-10-21','laboratory_id'=>$this->data['central']->id]))->assertOk()->assertViewHas('appointments', fn ($rows) => $rows->count()===1 && $rows->first()->id===$this->data['appointment']->id);
        $this->get($this->url('index', ['fecha'=>'2026-11-21']))->assertOk()->assertViewHas('appointments', fn ($rows) => $rows->isEmpty());
        $this->get($this->url('index', ['seccion'=>'servicios','q'=>'SRV-001']))->assertOk()->assertViewHas('visibleServices', fn ($rows) => $rows->count()===1);
        $this->get($this->url('index', ['seccion'=>'programacion','fecha'=>'2026-10-21','q'=>'MTO-00002']))->assertOk()->assertViewHas('appointments', fn ($rows) => $rows->count()===1 && $rows->first()->id===$this->data['second']->id);
        $this->get($this->url('index', ['vista'=>'mes','fecha'=>'2026-10-21']))->assertOk()->assertSee('mw-month',false);
        $this->getJson($this->url('index', ['fecha'=>'invalid']))->assertUnprocessable();
    }

    public function test_service_and_provider_edits_persist_links_and_preserve_legacy_fields(): void
    {
        $service = $this->data['service'];
        $service->update(['identification'=>'Equipo legado', 'qualification_stages'=>'Revisión']);
        $payload = ['service'=>'Servicio actualizado','type'=>'Correctivo','frequency'=>'A demanda','report_required'=>false,'is_active'=>true,'provider_ids'=>[$this->data['provider']->id]];
        $this->patchJson($this->url('services.update',$service),$payload)->assertOk();
        $this->assertSame('Equipo legado', $service->fresh()->identification);
        $this->assertCount(1,$service->fresh()->serviceProviders);
        $this->assertFalse($service->fresh()->report_required);
        $this->postJson($this->url('services.store'),array_merge($payload,['service'=>'']))->assertUnprocessable();
        $providerPayload=['name'=>'Proveedor nuevo','is_active'=>true,'service_ids'=>[$service->id],'email'=>'nuevo@example.test'];
        $id=$this->postJson($this->url('providers.store'),$providerPayload)->assertOk()->json('id');
        $this->assertCount(1,MaintenanceProvider::findOrFail($id)->services);
        $this->patchJson($this->url('providers.update',$id),array_merge($providerPayload,['service_ids'=>[]]))->assertOk();
        $this->assertCount(0,MaintenanceProvider::findOrFail($id)->services);
    }

    private function appointmentPayload(): array
    {
        return ['service_id'=>$this->data['service']->id,'provider_id'=>$this->data['provider']->id,'laboratory_id'=>$this->data['central']->id,'asset'=>'RF-02','starts_at'=>'2026-10-22T09:30','ends_at'=>'2026-10-22T11:00','status'=>'scheduled'];
    }

    public function test_scheduling_validates_provider_links_central_and_time_and_supports_reprogramming(): void
    {
        $payload=$this->appointmentPayload();
        $id=$this->postJson($this->url('appointments.store'),$payload)->assertOk()->json('id');
        $this->assertSame('09:30',MaintenanceAppointment::findOrFail($id)->starts_at->format('H:i'));
        $this->patchJson($this->url('appointments.update',$id),array_merge($payload,['starts_at'=>'2026-10-23T10:00','ends_at'=>'2026-10-23T11:00']))->assertOk();
        $this->postJson($this->url('appointments.store'),array_merge($payload,['ends_at'=>'2026-10-21T09:00']))->assertUnprocessable();
        $this->postJson($this->url('appointments.store'),array_merge($payload,['service_id'=>$this->data['otherService']->id]))->assertUnprocessable();
        $this->postJson($this->url('appointments.store'),array_merge($payload,['service_id'=>$this->data['otherService']->id,'laboratory_id'=>$this->data['otherCentral']->id]))->assertUnprocessable();
        $this->data['provider']->update(['is_active'=>false]);
        $this->postJson($this->url('appointments.store'),$payload)->assertUnprocessable();
    }

    public function test_private_reports_are_uploaded_downloaded_and_closed_only_when_complete(): void
    {
        Storage::fake('local');
        $a=$this->data['appointment'];
        $this->postJson($this->url('appointments.close',$a))->assertUnprocessable();
        $upload=$this->url('documents.store',['kind'=>'appointment','id'=>$a->id]);
        $this->postJson($upload,['file'=>UploadedFile::fake()->create('reporte.pdf',4,'application/pdf'),'description'=>'Reporte de prueba'])->assertOk();
        $doc=MaintenanceDocument::firstOrFail();
        Storage::disk('local')->assertExists($doc->path);
        $this->assertSame('review',$a->fresh()->report_status);
        $this->get($this->url('documents.show',$doc))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->postJson($this->url('appointments.close',$a))->assertOk();
        $this->assertSame($this->data['manager']->id,$a->fresh()->reviewed_by);
        $this->assertSame('closed',$a->fresh()->report_status);
        $this->patchJson($this->url('appointments.update',$a),$this->appointmentPayload())->assertConflict();
        $this->postJson($upload,['file'=>UploadedFile::fake()->create('otro.pdf',4,'application/pdf')])->assertConflict();
        $this->assertCount(1,Storage::disk('local')->allFiles());
        $this->postJson($this->url('appointments.close',$this->data['second']))->assertUnprocessable();
    }

    public function test_exports_respect_filters_and_clients_cannot_access_workspace_or_documents(): void
    {
        $export=$this->get($this->url('export',['fecha'=>'2026-10-21','laboratory_id'=>$this->data['central']->id]))->assertOk();
        $content=$export->streamedContent();
        $this->assertStringContainsString('RF-01',$content);
        $this->assertStringNotContainsString('BAL-01',$content);
        $doc=$this->data['provider']->documents()->create(['name'=>'privado.pdf','path'=>'mantenimiento/documentos/privado.pdf','mime'=>'application/pdf','size'=>1]);
        $client=User::create(['name'=>'Cliente','username'=>'cliente']);$client->assignRole('Cliente');
        $this->actingAs($client->fresh());
        $this->getJson($this->url('index'))->assertForbidden();
        $this->postJson($this->url('appointments.store'),$this->appointmentPayload())->assertForbidden();
        $this->getJson($this->url('documents.show',$doc))->assertForbidden();
    }
}
