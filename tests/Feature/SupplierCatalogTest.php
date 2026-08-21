<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_superadministrator_can_open_and_filter_the_supplier_catalog(): void
    {
        Supplier::create([
            'name' => 'Farmaceutica Nacional, S.A. de C.V.',
            'rfc' => 'FNA180215KQ2',
            'contact_name' => 'Laura Mendoza',
            'phone' => '55 4890 2176',
            'email' => 'laura@farmnacional.mx',
            'category' => 'Medicamentos',
            'location' => 'CDMX',
            'status' => Supplier::STATUS_ACTIVE,
        ]);

        Supplier::create([
            'name' => 'Servicios Biomedicos Integrales',
            'category' => 'Servicios',
            'status' => Supplier::STATUS_INACTIVE,
        ]);

        $this->actingAs($this->superadministrator())
            ->get(route('admin.suppliers.index', ['search' => 'Farmaceutica']))
            ->assertOk()
            ->assertSee('Catalogo de proveedores')
            ->assertSee('Alta de proveedor')
            ->assertSee('Farmaceutica Nacional')
            ->assertSee('data-column="1"', false)
            ->assertSee('data-sort-column="1"', false)
            ->assertSee('js-supplier-filter-row', false)
            ->assertDontSee('Servicios Biomedicos Integrales');
    }

    public function test_superadministrator_can_create_and_update_a_supplier(): void
    {
        $manager = $this->superadministrator();
        Storage::fake('local');

        $this->actingAs($manager)
            ->post(route('admin.suppliers.store'), [
                'assigned_buyer_id' => $manager->id,
                'name' => 'MedSupply Mexico, S.A. de C.V.',
                'commercial_name' => 'MedSupply Mexico',
                'rfc' => 'msm200410ab7',
                'contact_name' => 'Carlos Robles',
                'phone' => '55 7612 4380',
                'email' => 'carlos@medsupply.mx',
                'category' => 'Material de curacion',
                'subcategory' => 'Material de curacion',
                'location' => 'Estado de Mexico',
                'municipality' => 'Naucalpan',
                'postal_code' => '53000',
                'address' => 'Av. Industria 100, Naucalpan, Estado de Mexico',
                'bank_name' => 'BBVA',
                'bank_account' => '1234567890',
                'bank_clabe' => '012345678901234567',
                'bank_reference' => 'MEDSUPPLY-COMPRAS',
                'status' => Supplier::STATUS_ACTIVE,
                'tax_certificate' => UploadedFile::fake()->create('constancia.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $supplier = Supplier::where('rfc', 'MSM200410AB7')->firstOrFail();
        Storage::disk('local')->assertExists($supplier->tax_certificate_path);

        $this->assertSame($manager->id, $supplier->assigned_buyer_id);
        $this->assertStringContainsString('CLABE: 012345678901234567', $supplier->bank_details);

        $this->actingAs($manager)
            ->put(route('admin.suppliers.update', $supplier), [
                'assigned_buyer_id' => $manager->id,
                'name' => $supplier->name,
                'commercial_name' => $supplier->commercial_name,
                'rfc' => $supplier->rfc,
                'contact_name' => $supplier->contact_name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'category' => $supplier->category,
                'subcategory' => $supplier->subcategory,
                'location' => $supplier->location,
                'municipality' => $supplier->municipality,
                'postal_code' => $supplier->postal_code,
                'address' => $supplier->address,
                'bank_name' => $supplier->bank_name,
                'bank_account' => $supplier->bank_account,
                'bank_clabe' => $supplier->bank_clabe,
                'bank_reference' => $supplier->bank_reference,
                'status' => Supplier::STATUS_REVIEW,
            ])
            ->assertRedirect(route('admin.suppliers.show', $supplier));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'status' => Supplier::STATUS_REVIEW,
        ]);
    }

    public function test_superadministrator_can_open_the_complete_supplier_registration_form(): void
    {
        $this->actingAs($this->superadministrator())
            ->get(route('admin.suppliers.create'))
            ->assertOk()
            ->assertSee('Nuevo proveedor')
            ->assertSee('Comprador asignado')
            ->assertSee('Contacto y domicilio')
            ->assertSee('Datos bancarios')
            ->assertSee('Documentaci&oacute;n', false)
            ->assertSee('Guardar proveedor');
    }

    private function superadministrator(): User
    {
        $user = User::factory()->create(['hospital_id' => null]);
        $user->assignRole(Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]));

        return $user;
    }
}
