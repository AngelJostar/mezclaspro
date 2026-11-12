<?php

namespace Tests\Feature;

use App\Livewire\Admin\OperationalAgentPanel;
use App\Models\{AiAgent, AiAgentDelivery, AiAgentDeliveryEmail, ConciliationPeriod, HospitalConciliationSubmission, User};
use App\Services\Agents\{AutomaticConciliation, ConciliationDeliveryMailer, OperationalAgents, PatientNutritionComparison};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalAgentsTest extends TestCase
{
    private User $actor;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array']);
        DB::purge('sqlite');
        $this->actor = \Tests\Fixtures\OperationalAgentData::seed();
        $this->actingAs($this->actor);
        Http::preventStrayRequests(); Mail::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-01 10:00:00', 'America/Mexico_City'));
        $this->mock(\App\Services\InstitutionBillingPricingService::class, function ($mock) {
            $mock->shouldReceive('priceOncoMix', 'priceNutritionRequest')->andReturn(['total_iva_included'=>200.25]);
        });
    }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function automatic(array $settings = []): AiAgent
    {
        $agent = AiAgent::where('integration_key', OperationalAgents::CONCILIATION)->firstOrFail();
        $agent->forceFill(['is_active'=>true,'configured_by'=>$this->actor->id,'configuration'=>array_replace(OperationalAgents::defaults(),[
            'hospital_ids'=>[1,2], 'hospital_emails'=>[1=>'hospital1@example.test',2=>'hospital2@example.test'],
            'institution_emails'=>[1=>'institution@example.test'],
        ],$settings)])->save();
        return $agent;
    }
    private function nutrition(): AiAgent
    {
        $agent = AiAgent::where('integration_key', OperationalAgents::NUTRITION)->firstOrFail();
        $agent->update(['is_active'=>true]); return $agent;
    }
    private function patient(array $extra=[]): array
    {
        return array_replace(['hospital_id'=>1,'registry'=>'EXP-PRUEBA','from'=>'2026-09-01','to'=>'2026-09-30'],$extra);
    }

    public function test_monthly_delivery_splits_hospitals_is_idempotent_and_tracks_their_responses(): void
    {
        $agent = $this->automatic();
        $run = app(AutomaticConciliation::class)->run($agent,$this->actor);
        $this->assertSame('completed',$run->status, json_encode($run->result));
        $this->assertDatabaseCount('conciliation_periods',2);
        $this->assertDatabaseCount('hospital_conciliation_submissions',2);
        foreach (AiAgentDelivery::with('submission')->get() as $delivery) {
            $this->assertSame('2026-09-01',$delivery->period_from->toDateString());
            $this->assertSame('2026-09-30',$delivery->period_to->toDateString());
            $this->assertSame((int)$delivery->hospital_id, $delivery->submission->hospital_id);
            $items = \App\Models\ConciliationPeriodItem::where('conciliation_period_id',$delivery->conciliation_period_id)->get();
            $this->assertTrue($items->every(fn($item)=>(int)$item->snapshot['hospital_id']===(int)$delivery->hospital_id));
        }
        app(AutomaticConciliation::class)->run($agent,$this->actor);
        $this->assertDatabaseCount('conciliation_periods',2); $this->assertDatabaseCount('hospital_conciliation_submissions',2);
        $sent = AiAgentDelivery::where('hospital_id',1)->first()->submission;
        HospitalConciliationSubmission::create(['submission_key'=>(string)\Illuminate\Support\Str::uuid(),'direction'=>'received','hospital_id'=>1,'hospital_name'=>$sent->hospital_name,
            'sender_name'=>'Respuesta del hospital','filters'=>['source_submission_id'=>$sent->id,'response_type'=>'accepted'],'snapshot'=>$sent->snapshot,'mixture_count'=>count($sent->snapshot),'conciliable_count'=>count($sent->snapshot)]);
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->assertSee('Aceptada')->assertSee('Respuesta del hospital')->assertSee('Pendiente de respuesta');
        Mail::assertNothingSent();
    }

    public function test_notification_switch_notifies_both_recipients_once_without_new_periods(): void
    {
        config(['mail.default'=>'smtp','mail.mailers.smtp.transport'=>'smtp']);
        $agent = $this->automatic(['email_enabled'=>true]);
        app(AutomaticConciliation::class)->run($agent,$this->actor);
        Mail::assertSent(\App\Mail\AgentConciliationSent::class,4);
        Mail::assertSent(\App\Mail\AgentConciliationSent::class, fn($mail)=>$mail->hasTo('hospital1@example.test') && $mail->submission->hospital_id===1);
        $this->assertSame(4,AiAgentDeliveryEmail::where('status','sent')->count());
        app(ConciliationDeliveryMailer::class)->pending();
        app(AutomaticConciliation::class)->run($agent,$this->actor);
        Mail::assertSent(\App\Mail\AgentConciliationSent::class,4);
        $this->assertDatabaseCount('conciliation_periods',2);
    }

    public function test_failed_emails_retry_after_recovery_and_off_switch_stops_pending_mail(): void
    {
        config(['mail.default'=>'smtp','mail.mailers.smtp.transport'=>'smtp']);
        $fake = Mail::getFacadeRoot();
        $broken = \Mockery::mock();
        $broken->shouldReceive('to')->andReturnSelf();
        $broken->shouldReceive('send')->andThrow(new \RuntimeException('Simulated transport outage'));
        Mail::swap($broken);
        $agent = $this->automatic(['email_enabled'=>true]);
        app(AutomaticConciliation::class)->run($agent,$this->actor);
        $this->assertSame(4,AiAgentDeliveryEmail::where('status','failed')->where('attempts',1)->count());
        $agent->forceFill(['configuration'=>array_replace($agent->configuration,['email_enabled'=>false])])->save();
        Mail::swap($fake);
        $this->travel(16)->minutes();
        app(ConciliationDeliveryMailer::class)->pending();
        Mail::assertNothingSent();
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->call('retryEmail',AiAgentDeliveryEmail::first()->id)->assertHasErrors('settings');
        $agent->forceFill(['configuration'=>array_replace($agent->configuration,['email_enabled'=>true])])->save();
        app(ConciliationDeliveryMailer::class)->pending();
        app(ConciliationDeliveryMailer::class)->pending();
        Mail::assertSent(\App\Mail\AgentConciliationSent::class,4);
        $this->assertSame(4,AiAgentDeliveryEmail::where('status','sent')->where('attempts',2)->count());
        $this->assertDatabaseCount('conciliation_periods',2);
    }

    public function test_scheduler_retries_the_original_month_and_does_not_duplicate_successful_hospitals(): void
    {
        $agent = $this->automatic();
        $agent->forceFill(['next_run_at'=>\Carbon\Carbon::parse('2026-10-01 09:00:00','America/Mexico_City')])->save();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 10:00:00','America/Mexico_City'));
        DB::table('institution_billings')->where('hospital_id',1)->update(['precio_total'=>'importe inválido']);
        $this->artisan('agents:run-due')->assertSuccessful();
        $this->assertSame('partial',$agent->runs()->latest('id')->first()->status);
        $this->assertSame('2026-10-01',$agent->fresh()->next_run_at->toDateString());
        $this->assertDatabaseCount('conciliation_periods',1);
        $this->artisan('agents:run-due')->assertSuccessful();
        $this->assertSame(1,$agent->runs()->count());
        DB::table('institution_billings')->where('hospital_id',1)->update(['precio_total'=>'200.25']);
        $this->travel(16)->minutes();
        $this->artisan('agents:run-due')->assertSuccessful();
        $this->assertSame('completed',$agent->runs()->latest('id')->first()->status);
        $this->assertSame(['desde'=>'2026-09-01','hasta'=>'2026-09-30'],$agent->runs()->latest('id')->first()->result['period']);
        $this->assertDatabaseCount('conciliation_periods',2);
        $this->assertSame('2026-11-01',$agent->fresh()->next_run_at->toDateString());
        $this->artisan('agents:run-due')->assertSuccessful();
        $this->assertDatabaseCount('conciliation_periods',2);
        $this->assertSame('2026-12-01',$agent->fresh()->next_run_at->toDateString());
    }

    public function test_hospital_viewer_does_not_receive_other_hospitals_configuration(): void
    {
        $agent = $this->automatic(['email_enabled'=>true]);
        $other = User::find(2); $other->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Cliente','web'));
        $agent->accessGrants()->create(['subject_type'=>'user','subject_key'=>(string)$other->id,'can_view'=>true,'can_run'=>false,'can_edit'=>false]);
        $this->actingAs($other);
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->assertSet('settings.hospital_ids',[])
            ->assertSet('settings.hospital_emails',[])->assertSet('settings.institution_emails',[])->call('saveSettings')->assertForbidden();
    }

    public function test_granted_execution_uses_saved_configuration_and_only_the_users_hospitals(): void
    {
        $agent = $this->automatic();
        $other = User::find(2); $other->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Cliente','web'));
        $grant = $agent->accessGrants()->create(['subject_type'=>'user','subject_key'=>(string)$other->id,'can_view'=>true,'can_run'=>true,'can_edit'=>false]);
        $this->actingAs($other);
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->call('sendPreviousPeriod')->assertHasNoErrors();
        $this->assertDatabaseCount('conciliation_periods',1);
        $this->assertSame(2,(int)ConciliationPeriod::first()->hospital_id);
        $grant->update(['can_run'=>false]);
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->call('sendPreviousPeriod')->assertForbidden();
    }

    public function test_institution_can_use_its_granted_agent_over_http_but_not_other_livewire_components(): void
    {
        $agent = $this->nutrition();
        $user = User::find(2); $user->institution_id = 1; $user->save();
        $user->syncRoles(\Spatie\Permission\Models\Role::findOrCreate('Institucion','web'));
        $agent->accessGrants()->create(['subject_type'=>'user','subject_key'=>(string)$user->id,'can_view'=>true,'can_run'=>true,'can_edit'=>false]);
        $this->actingAs($user);
        $component = Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id]);
        preg_match('/wire:snapshot="([^"]+)"/',$component->html(),$matches);
        $snapshot = html_entity_decode($matches[1], ENT_QUOTES);
        $this->assertSame('admin.operational-agent-panel',data_get(json_decode($snapshot,true),'memo.name'));
        $payload = ['components'=>[['snapshot'=>$snapshot,'updates'=>['patient'=>$this->patient()],
            'calls'=>[['path'=>'','method'=>'analyzePatient','params'=>[]]]]]];
        $response = $this->postJson('/livewire/update',$payload,['X-Livewire'=>'true']);
        $this->assertSame(200,$response->status(),$response->getContent());
        $this->assertSame(1,$agent->runs()->count());
        $this->get(route('admin.agents.nutrition-report',$agent->runs()->first()))->assertOk();
        $altered = json_decode($snapshot,true); $altered['memo']['name'] = 'admin.agent-center';
        $payload['components'][0]['snapshot'] = json_encode($altered);
        $this->postJson('/livewire/update',$payload,['X-Livewire'=>'true'])->assertForbidden();
    }

    public function test_missing_prices_roll_back_only_the_affected_hospital_and_no_empty_periods_are_created(): void
    {
        $agent = $this->automatic();
        DB::table('institution_billings')->where('hospital_id',1)->update(['precio_total'=>'importe inválido']);
        $run = app(AutomaticConciliation::class)->run($agent,$this->actor);
        $this->assertSame('partial',$run->status);
        $this->assertDatabaseCount('conciliation_periods',1);
        $this->assertSame(2,(int)ConciliationPeriod::first()->hospital_id);
        $this->assertNotEmpty($run->result['issues']);
        $this->travelTo(\Carbon\Carbon::parse('2026-11-01 10:00:00','America/Mexico_City'));
        app(AutomaticConciliation::class)->run($agent,$this->actor);
        $this->assertDatabaseCount('conciliation_periods',1);
    }

    public function test_configuration_select_all_schedule_and_switch_require_super_admin(): void
    {
        $agent = $this->automatic(); $agent->update(['is_active'=>false]);
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->call('selectAllHospitals',true)
            ->assertSet('settings.hospital_ids',['1','2'])->call('saveSettings')->assertHasNoErrors()->assertSee('Programación guardada');
        $this->assertSame('2026-11-01 09:00',$agent->fresh()->next_run_at->timezone('America/Mexico_City')->format('Y-m-d H:i'));
        $component = Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id]);
        $this->actingAs(User::find(2)); $component->call('toggleActive')->assertForbidden();
        $this->assertFalse($agent->fresh()->is_active);
    }

    public function test_scheduler_does_not_send_early_or_when_disabled_and_uses_calendar_months(): void
    {
        $agent = $this->automatic(); $agent->next_run_at = now()->addDay(); $agent->save();
        $this->assertNull(app(AutomaticConciliation::class)->run($agent,$this->actor,'scheduled'));
        $this->assertDatabaseCount('conciliation_periods',0);
        $range = OperationalAgents::period(OperationalAgents::defaults(),CarbonImmutable::parse('2028-03-01 09:00','America/Mexico_City'));
        $this->assertSame(['desde'=>'2028-02-01','hasta'=>'2028-02-29'],$range);
        $agent->update(['is_active'=>false]);
        $this->artisan('agents:run-due')->assertSuccessful();
        $this->assertDatabaseCount('conciliation_periods',0);
    }

    public function test_nutrition_report_compares_actual_prescriptions_without_mixing_hospitals_or_missing_values(): void
    {
        $run = app(PatientNutritionComparison::class)->run($this->nutrition(),$this->actor,$this->patient());
        $report = $run->result['nutrition_report'];
        $this->assertSame(2,$report['summary']['count']);
        $this->assertEquals(2,$report['summary']['weight_change']);
        $this->assertEquals(3.33,$report['summary']['weight_percent']);
        $this->assertEquals(100,$report['rows'][0]['components']['1|g/día']['quantity']);
        $this->assertEquals(20,$report['rows'][1]['components']['1|g/día']['change']);
        $this->assertEquals(10,$report['rows'][0]['components']['1|g/día']['concentration']);
        $this->assertEquals(6,$report['rows'][1]['components']['1|g/día']['concentration']);
        $this->assertEquals(-4,$report['rows'][1]['components']['1|g/día']['concentration_change']);
        $this->assertArrayNotHasKey('3|mEq/día',$report['rows'][1]['components']);
        $this->assertStringNotContainsString('999',json_encode($report));
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$run->ai_agent_id])->call('openReport',$run->id)->assertSee('Comparativo por día y nutriente')->assertSee('Descargar PDF');
        $response = $this->get(route('admin.agents.nutrition-report',$run))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        if (getenv('AGENT_PDF_SAMPLE')) file_put_contents(base_path('output/pdf/comparativo-nutriciones-prueba.pdf'),$response->getContent());
    }

    public function test_pediatric_concentrations_use_recorded_weight_and_preserve_missing_weight(): void
    {
        DB::table('solicitud_details')->update(['npt'=>'INF']);
        DB::table('solicitud_patients')->where('id',1)->update(['peso'=>'2']);
        DB::table('solicitud_patients')->where('id',3)->update(['peso'=>null]);
        DB::table('solicitud_inputs')->where('input_id',1)->update(['valor'=>'4']);
        $report = app(PatientNutritionComparison::class)->run($this->nutrition(),$this->actor,$this->patient())->result['nutrition_report'];
        $this->assertEquals(8,$report['rows'][0]['components']['1|g/Kg']['total']);
        $this->assertEquals(.8,$report['rows'][0]['components']['1|g/Kg']['concentration']);
        $this->assertNull($report['rows'][1]['components']['1|g/Kg']['concentration']);
        $this->assertNull($report['rows'][1]['weight_change']);
    }

    public function test_ambiguous_registry_cannot_combine_patients(): void
    {
        DB::table('solicitud_patients')->where('id',3)->update(['nombre_paciente'=>'Otra persona']);
        $this->expectException(ValidationException::class);
        app(PatientNutritionComparison::class)->run($this->nutrition(),$this->actor,$this->patient());
    }

    public function test_reports_and_patient_analysis_enforce_grants_and_hospital_scope(): void
    {
        $agent = $this->nutrition();
        $run = app(PatientNutritionComparison::class)->run($agent,$this->actor,$this->patient());
        $other = User::find(2); $other->assignRole(\Spatie\Permission\Models\Role::findOrCreate('Cliente','web'));
        $grant = $agent->accessGrants()->create(['subject_type'=>'user','subject_key'=>(string)$other->id,'can_view'=>true,'can_run'=>true,'can_edit'=>false]);
        $this->actingAs($other)->get(route('admin.agents.nutrition-report',$run))->assertForbidden();
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->set('patient',$this->patient())->call('analyzePatient')->assertForbidden();
        $grant->delete();
        Livewire::test(OperationalAgentPanel::class,['agentId'=>$agent->id])->assertForbidden();
    }
}
