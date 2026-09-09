<?php

namespace Tests\Feature;

use App\Models\InspectionWaste;
use App\Models\Oncologicos\Mezcla;
use App\Services\MixtureLotService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\InspectionWorkflow;
use Tests\TestCase;

class MixtureLotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        InspectionWorkflow::seed();
        $this->travelTo(Carbon::parse('2026-09-08 12:00:00'));
    }

    public function test_new_lots_follow_the_highest_current_or_rejected_lot_not_the_creation_date(): void
    {
        DB::table('mezclas')->where('id', 1)->update(['lote' => 'L08SEP26008', 'created_at' => '2026-01-01']);
        InspectionWaste::create([
            'mezcla_id' => 1, 'production_attempt' => 1, 'reason' => 'Fuga',
            'snapshot' => ['mixture' => ['lote' => 'L08SEP26012']],
        ]);
        $service = app(MixtureLotService::class);
        $this->assertSame('L08SEP26013', $service->next());
        $this->assertSame('L08SEP26014', $service->next());
        $this->assertSame('L08SEP26008', Mezcla::find(1)->lote);
        $this->assertSame('L08SEP26012', InspectionWaste::first()->snapshot['mixture']['lote']);
    }

    public function test_the_sequence_survives_lot_removal_and_starts_another_day(): void
    {
        $service = app(MixtureLotService::class);
        $this->assertSame('L08SEP26001', $service->next());
        $this->assertSame('L08SEP26002', $service->next());
        $this->travelTo(Carbon::parse('2026-09-09 12:00:00'));
        $this->assertSame('L09SEP26001', $service->next());
        $this->travelTo(Carbon::parse('2026-09-08 12:00:00'));
        $this->assertSame('L08SEP26003', $service->next());
    }

    public function test_numeric_sequence_supports_more_than_999_lots(): void
    {
        DB::table('mezclas')->where('id', 1)->update(['lote' => 'L08SEP26999']);
        $this->assertSame('L08SEP261000', app(MixtureLotService::class)->next());
    }
}
