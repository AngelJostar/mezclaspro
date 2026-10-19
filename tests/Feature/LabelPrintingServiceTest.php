<?php

namespace Tests\Feature;

use App\Models\LabelPrint;
use App\Models\User;
use App\Services\LabelPrintingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabelPrintingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_print_records_three_copies_without_super_admin_password(): void
    {
        $user = User::factory()->create(['hospital_id' => null]);

        $print = app(LabelPrintingService::class)->authorizeAndRecord(
            $user,
            LabelPrintingService::NUTRITION,
            $user,
            null,
            '127.0.0.1'
        );

        $this->assertSame(LabelPrint::TYPE_INITIAL, $print->print_type);
        $this->assertSame(LabelPrintingService::INITIAL_COPIES, $print->copies);
        $this->assertNull($print->authorized_by);
    }

    public function test_only_super_admin_with_correct_password_can_reprint(): void
    {
        $document = User::factory()->create(['hospital_id' => null]);
        $operator = User::factory()->create(['hospital_id' => null]);
        $superAdmin = User::factory()->create(['hospital_id' => null]);
        $superAdmin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'web']));
        $service = app(LabelPrintingService::class);

        $service->authorizeAndRecord(
            $document,
            LabelPrintingService::NUTRITION,
            $operator,
            null,
            null
        );

        try {
            $service->authorizeAndRecord(
                $document,
                LabelPrintingService::NUTRITION,
                $superAdmin,
                'incorrecta',
                null
            );
            $this->fail('La reimpresion debio rechazar una contrasena incorrecta.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('password', $exception->errors());
        }

        $print = $service->authorizeAndRecord(
            $document,
            LabelPrintingService::NUTRITION,
            $superAdmin,
            'password',
            '127.0.0.1'
        );

        $this->assertSame(LabelPrint::TYPE_REPRINT, $print->print_type);
        $this->assertSame(LabelPrintingService::REPRINT_COPIES, $print->copies);
        $this->assertSame($superAdmin->id, $print->authorized_by);
        $this->assertSame(4, $service->summary(LabelPrintingService::NUTRITION, $document->id)['copies']);
    }

    public function test_non_super_admin_cannot_reprint(): void
    {
        $document = User::factory()->create(['hospital_id' => null]);
        $operator = User::factory()->create(['hospital_id' => null]);
        $service = app(LabelPrintingService::class);

        $service->authorizeAndRecord(
            $document,
            LabelPrintingService::ONCOLOGY,
            $operator,
            null,
            null
        );

        $this->expectException(AuthorizationException::class);

        $service->authorizeAndRecord(
            $document,
            LabelPrintingService::ONCOLOGY,
            $operator,
            'password',
            null
        );
    }
}
