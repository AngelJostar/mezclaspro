<?php

namespace Tests\Feature;

use App\Models\RequestQuotation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\Fixtures\RequestQuotationCaptureData;
use Tests\TestCase;

class RequestQuotationCommercialTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = RequestQuotationCaptureData::seed();
        $this->actingAs($this->user);
        config(['request-quotations.base_lists' => []]);
        Schema::table('hospitals', fn (Blueprint $table) => $table->unsignedBigInteger('antibiotic_medicine_list_id')->nullable());
        DB::table('medicine_lists')->insert(['id' => 2, 'name' => 'Lista antibioticos', 'catalog_category' => 'antibioticos', 'charge_by' => 'mg']);
        DB::table('medicines_catalog')->insert(['id' => 2, 'denominacion' => 'Antibiotico de prueba', 'state' => 1, 'catalog_category' => 'antibioticos']);
        DB::table('medicine_presentations')->insert(['id' => 3, 'catalog_id' => 2, 'presentacion' => 'Frasco 500 mg', 'contenido_valor' => 500, 'contenido_unidad' => 'mg', 'is_available' => 1]);
        DB::table('medicine_list_presentation')->insert(['medicine_list_id' => 2, 'medicine_presentation_id' => 3, 'precio' => 250, 'charge_by' => 'mg']);
        DB::table('hospitals')->where('id', 1)->update(['antibiotic_medicine_list_id' => 2]);
    }

    private function payload(string $category = 'oncologicos'): array
    {
        return ['flow' => 'commercial', 'category' => $category, 'hospital_id' => 1, 'institution_id' => 1,
            'no_commercial_relationship' => false, 'submission_key' => (string) Str::uuid(), 'action' => 'save',
            'items' => [['presentation_id' => $category === 'antibioticos' ? 3 : 1, 'concentration' => 50]]];
    }

    private function preview(array $data)
    {
        return $this->postJson(route('admin.solicitudes.cotizacion.preview'), $data);
    }

    private function store(array $data)
    {
        return $this->postJson(route('admin.solicitudes.cotizacion.store'), $data);
    }

    public function test_all_categories_use_fixed_units_and_server_prices_and_persist_for_filters(): void
    {
        foreach (['oncologicos' => [131, 'mg'], 'nutricionales' => [125, 'ml'], 'antibioticos' => [25, 'mg']] as $category => [$total, $unit]) {
            $data = $this->payload($category);
            $before = RequestQuotation::count();
            $review = $this->preview($data + ['total' => 1, 'price_list_id' => 999])->assertOk()
                ->assertJsonPath('pricing_snapshot.total', $total)
                ->assertJsonPath('pricing_snapshot.concentration_unit', $unit);
            $review->assertJsonPath('document.date_iso', now()->toDateString())
                ->assertJsonPath('document.total_in_words', \App\Support\QuotationDocument::amountInWords($total));
            $this->assertSame($before, RequestQuotation::count());
            $data['pricing_token'] = $review->json('pricing_token');
            $this->store($data)->assertOk()->assertJsonPath('total', number_format($total, 2, '.', ''));
            $quote = RequestQuotation::latest('id')->first();
            $this->assertSame($category, $quote->category);
            $this->assertSame('commercial', $quote->clinical_data['flow']);
            $this->assertSame($unit, $quote->clinical_data['items'][0]['unit']);
            $this->store($data)->assertOk()->assertJsonPath('folio', $quote->folio);
            $this->get(route('admin.solicitudes.cotizacion.index', ['tipo' => $category, 'buscar' => $quote->folio]))
                ->assertOk()->assertSee($quote->folio);
        }
    }

    public function test_new_quotes_accumulate_and_return_to_the_complete_list_without_automatic_filters(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 23));
        foreach (['oncologicos', 'nutricionales', 'antibioticos'] as $category) {
            foreach (['save', 'send'] as $action) {
                $previous = RequestQuotation::orderBy('id')->get()->mapWithKeys(fn ($quote) => [$quote->id => $quote->getAttributes()])->all();
                $data = $this->payload($category);
                $data['action'] = $action;
                $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
                $response = $this->store($data)->assertOk()
                    ->assertJsonPath('redirect_url', route('admin.solicitudes.cotizacion.index'));
                $created = RequestQuotation::latest('id')->first();
                $this->assertCount(count($previous) + 1, RequestQuotation::all());
                $this->assertSame($previous, RequestQuotation::whereKey(array_keys($previous))->orderBy('id')->get()
                    ->mapWithKeys(fn ($quote) => [$quote->id => $quote->getAttributes()])->all());
                $this->get($response->json('redirect_url'))->assertOk()
                    ->assertViewHas('selectedType', 'todas')->assertViewHas('statusFilter', 'todas')
                    ->assertViewHas('filters', fn ($filters) => $filters['buscar'] === '')
                    ->assertViewHas('quotations', fn ($rows) => $rows->count() === count($previous) + 1 && $rows->first()->id === $created->id)
                    ->assertSee($created->folio);
                $this->store($data)->assertOk()->assertJsonPath('folio', $created->folio)
                    ->assertJsonPath('redirect_url', route('admin.solicitudes.cotizacion.index'));
                $this->assertSame(count($previous) + 1, RequestQuotation::count());
            }
        }
    }

    public function test_numbered_mixtures_keep_independent_prices_and_charge_preparation_per_mixture(): void
    {
        foreach (['oncologicos' => [131, 15], 'nutricionales' => [125, 25], 'antibioticos' => [25, 0]] as $category => [$firstTotal, $charge]) {
            $data = $this->payload($category);
            $data['mixture_count'] = 2;
            $data['items'] = [
                $data['items'][0] + ['mixture_number' => 1],
                $data['items'][0] + ['mixture_number' => 2, 'unit_price_override' => 1],
            ];
            $review = $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.mixtures', 2)
                ->assertJsonPath('pricing_snapshot.lines.0.mixture_number', 1)
                ->assertJsonPath('pricing_snapshot.lines.1.mixture_number', 2)
                ->assertJsonPath('pricing_snapshot.lines.1.unit_price', 1);
            $expected = $firstTotal + ($category === 'oncologicos' ? 58 : 50) + $charge;
            $this->assertEquals($expected, $review->json('pricing_snapshot.total'));
            $services = collect($review->json('pricing_snapshot.lines'))->where('unit', 'servicio');
            $this->assertEquals($charge * 2, $services->sum('total'));
            $data['pricing_token'] = $review->json('pricing_token');
            $this->store($data)->assertOk();
            $quote = RequestQuotation::latest('id')->first();
            $this->assertSame(2, $quote->clinical_data['mixture_count']);
            $this->assertSame([1, 2], array_column($quote->clinical_data['items'], 'mixture_number'));
            $this->assertSame($review->json('pricing_snapshot'), $quote->pricing_snapshot);
            $pdf = app(\App\Services\RequestQuotationPdf::class)->data($quote);
            $html = view('admin.solicitudes.quotations.pdf', $pdf)->render();
            $this->assertStringContainsString('Mezcla 1', $html);
            $this->assertStringContainsString('Mezcla 2', $html);
        }
    }

    public function test_mixture_numbers_and_duplicate_medicines_are_validated_and_bound_to_the_review(): void
    {
        $base = $this->payload();
        $base['mixture_count'] = 2;
        $item = $base['items'][0];
        $base['items'] = [$item + ['mixture_number' => 1], $item + ['mixture_number' => 2]];
        foreach ([
            ['mixture_count', 3], ['mixture_count', 0], ['items.0.mixture_number', 0],
            ['items.0.mixture_number', 3], ['items.1.mixture_number', 1], ['items.0.mixture_number', 1.5],
        ] as [$field, $value]) {
            $data = $base; data_set($data, $field, $value);
            $this->preview($data)->assertUnprocessable();
        }
        $data = $base; unset($data['items'][1]['mixture_number']);
        $this->preview($data)->assertUnprocessable();
        $review = $this->preview($base)->assertOk();
        $base['pricing_token'] = $review->json('pricing_token');
        $base['items'][0]['unit_price_override'] = 1;
        $this->store($base)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
    }

    public function test_requirements_are_saved_per_mixture_without_changing_prices_and_can_be_cleared(): void
    {
        foreach (['oncologicos', 'nutricionales', 'antibioticos'] as $category) {
            $data = $this->payload($category);
            $data['mixture_count'] = 2;
            $data['items'] = [$data['items'][0] + ['mixture_number' => 1], $data['items'][0] + ['mixture_number' => 2]];
            $original = $this->preview($data)->assertOk()->json('pricing_snapshot');
            $data['requirements'] = [
                ['mixture_number' => 2, 'medicine' => ' Segundo medicamento ', 'concentration' => '12.3456'],
                ['mixture_number' => 1, 'medicine' => ' Medicamento solicitado ', 'concentration' => 150],
            ];
            $review = $this->preview($data)->assertOk();
            $this->assertSame($original['lines'], $review->json('pricing_snapshot.lines'));
            $this->assertSame($original['total'], $review->json('pricing_snapshot.total'));
            $data['pricing_token'] = $review->json('pricing_token');
            $this->store($data)->assertOk();
            $quote = RequestQuotation::latest('id')->first();
            $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()
                ->assertJsonPath('clinical_data.requirements.0.mixture_number', 1)
                ->assertJsonPath('clinical_data.requirements.0.medicine', 'Medicamento solicitado')
                ->assertJsonPath('clinical_data.requirements.0.concentration', 150)
                ->assertJsonPath('clinical_data.requirements.0.unit', $category === 'nutricionales' ? 'ml' : 'mg')
                ->assertJsonPath('clinical_data.requirements.1.concentration', 12.3456);
            $this->assertSame($quote->clinical_data['requirements'], $quote->pricing_snapshot['requirements']);
            $data['requirements'] = [];
            $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
            $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertOk();
            $this->assertSame([], $quote->refresh()->clinical_data['requirements']);
            $this->assertSame($original, $quote->pricing_snapshot);
        }
    }

    public function test_invalid_or_unreviewed_requirements_are_rejected(): void
    {
        $before = RequestQuotation::count();
        $base = $this->payload();
        $base['mixture_count'] = 1; $base['items'][0]['mixture_number'] = 1;
        $base['requirements'] = [['mixture_number' => 1, 'medicine' => 'Medicamento', 'concentration' => 100]];
        foreach (['', ' ', null, [], str_repeat('a', 256)] as $medicine) {
            $data = $base; $data['requirements'][0]['medicine'] = $medicine;
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('requirements.0.medicine');
        }
        foreach ([0, -1, null, 'invalid', 1000001, 1.23456] as $concentration) {
            $data = $base; $data['requirements'][0]['concentration'] = $concentration;
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('requirements.0.concentration');
        }
        foreach ([0, 2, 1.5] as $number) {
            $data = $base; $data['requirements'][0]['mixture_number'] = $number;
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('requirements.0.mixture_number');
        }
        $data = $base; $data['requirements'][] = $data['requirements'][0];
        $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('requirements.0.mixture_number');
        $data = $base; $data['requirements'][0]['unit'] = 'frasco';
        $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('requirements.0');
        $base['pricing_token'] = $this->preview($base)->assertOk()->json('pricing_token');
        $base['requirements'][0]['concentration'] = 101;
        $this->store($base)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
        $this->assertSame($before, RequestQuotation::count());
    }

    public function test_two_medicines_in_one_mixture_generate_only_one_preparation_charge(): void
    {
        DB::table('medicine_presentations')->where('id', 2)->update(['is_available' => 1]);
        $data = $this->payload();
        $data['mixture_count'] = 2;
        $data['items'] = [
            ['presentation_id' => 1, 'mixture_number' => 1, 'concentration' => 50],
            ['presentation_id' => 2, 'mixture_number' => 1, 'concentration' => 50],
            ['presentation_id' => 1, 'mixture_number' => 2, 'concentration' => 50, 'unit_price_override' => 1],
        ];
        $review = $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.total', 320)
            ->assertJsonPath('pricing_snapshot.mixtures', 2);
        $this->assertEquals(30, collect($review->json('pricing_snapshot.lines'))->where('unit', 'servicio')->sum('total'));
        $data['pricing_token'] = $review->json('pricing_token');
        $data['mixture_count'] = 3; $data['items'][1]['mixture_number'] = 3;
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
    }

    public function test_hospital_mode_cannot_be_overridden_and_bottles_are_quoted_directly(): void
    {
        $this->preview($this->payload() + ['billing_mode' => 'frasco'])->assertUnprocessable()->assertJsonValidationErrors('billing_mode');
        DB::table('medicine_list_presentation')->where('medicine_list_id', 1)->update(['charge_by' => 'frasco']);
        DB::table('nutri_medicine_list_items')->where('nutrition_medicine_presentation_id', 1)->update(['charge_by' => 'frasco']);
        DB::table('medicine_list_presentation')->where('medicine_list_id', 2)->update(['charge_by' => 'frasco']);
        foreach (['oncologicos' => [479, 200], 'nutricionales' => [425, 200], 'antibioticos' => [500, 250]] as $category => [$total, $price]) {
            $data = $this->payload($category);
            $data['items'] = [['presentation_id' => $category === 'antibioticos' ? 3 : 1, 'bottle_count' => 2]];
            $review = $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.lines.0.quantity', 2)
                ->assertJsonPath('pricing_snapshot.lines.0.unit', 'frasco')
                ->assertJsonPath('pricing_snapshot.lines.0.unit_price', $price)->assertJsonPath('pricing_snapshot.total', $total);
            $this->assertArrayNotHasKey('concentration', $review->json('pricing_snapshot.lines.0'));
            $data['pricing_token'] = $review->json('pricing_token');
            $this->store($data)->assertOk();
            $quote = RequestQuotation::latest('id')->first();
            $this->assertSame(2, $quote->clinical_data['items'][0]['bottle_count']);
            $this->assertSame('frasco', $quote->clinical_data['items'][0]['unit']);
            $this->assertArrayNotHasKey('concentration', $quote->clinical_data['items'][0]);
            $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()
                ->assertJsonPath('clinical_data.items.0.bottle_count', 2)->assertJsonPath('pricing_snapshot.lines.0.quantity', 2);
        }
    }

    public function test_special_prices_are_scoped_to_each_quote_and_keep_original_tariffs(): void
    {
        foreach (['oncologicos' => 2, 'nutricionales' => 2, 'antibioticos' => .5] as $category => $listPrice) {
            foreach (['unit', 'frasco'] as $mode) {
                $nutrition = $category === 'nutricionales';
                $table = $nutrition ? 'nutri_medicine_list_items' : 'medicine_list_presentation';
                DB::table($table)->update(['charge_by' => $mode === 'unit' ? ($nutrition ? 'ml' : 'mg') : 'frasco']);
                $tariffs = DB::table($table)->get()->toJson();
                $data = $this->payload($category);
                $price = $mode === 'unit' ? .1234 : 123.4567;
                $quantity = $mode === 'unit' ? 50 : 2;
                $original = $mode === 'unit' ? $listPrice : ($category === 'antibioticos' ? 250 : 200);
                $data['items'] = [['presentation_id' => $category === 'antibioticos' ? 3 : 1,
                    $mode === 'unit' ? 'concentration' : 'bottle_count' => $quantity, 'unit_price_override' => $price]];
                $review = $this->preview($data)->assertOk()
                    ->assertJsonPath('pricing_snapshot.lines.0.unit_price', $price)
                    ->assertJsonPath('pricing_snapshot.lines.0.list_unit_price', $original)
                    ->assertJsonPath('pricing_snapshot.lines.0.price_adjusted', true)
                    ->assertJsonPath('pricing_snapshot.lines.0.price_adjusted_by', $this->user->id)
                    ->assertJsonPath('pricing_snapshot.lines.0.subtotal', round($price * $quantity, 2));
                $data['pricing_token'] = $review->json('pricing_token');
                $this->store($data)->assertOk();
                $quote = RequestQuotation::latest('id')->first();
                $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()
                    ->assertJsonPath('clinical_data.items.0.unit_price_override', $price)
                    ->assertJsonPath('pricing_snapshot.lines.0.unit_price', $price);
                $document = app(\App\Services\RequestQuotationPdf::class)->data($quote);
                $this->assertEquals($price, $document['lines'][0]['unit_price']);
                $this->assertEquals($quote->total, $document['total']);
                $this->assertSame($tariffs, DB::table($table)->get()->toJson());
                unset($data['items'][0]['unit_price_override']);
                $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.lines.0.unit_price', $original)
                    ->assertJsonPath('pricing_snapshot.lines.0.price_adjusted', false);
            }
        }
    }

    public function test_special_prices_require_review_and_cannot_modify_authorized_quotes(): void
    {
        $data = $this->payload();
        $data['items'][0]['unit_price_override'] = 1.2345;
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $data['items'][0]['unit_price_override'] = .5;
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $this->store($data)->assertOk()->assertJsonPath('total', '44.00');
        $quote = RequestQuotation::latest('id')->first();
        $data['items'][0]['unit_price_override'] = 0;
        $data['pricing_token'] = $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.total', 15)->json('pricing_token');
        $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertOk();
        $quote->forceFill(['status' => 'autorizada'])->save();
        $data['items'][0]['unit_price_override'] = 1;
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertForbidden();
        $this->assertEquals(0, $quote->refresh()->pricing_snapshot['lines'][0]['unit_price']);
    }

    public function test_invalid_special_prices_are_rejected_before_review_or_save(): void
    {
        foreach ([null, '', -1, 'invalid', 1000000000, .12345, [], true] as $price) {
            $data = $this->payload(); $data['items'][0]['unit_price_override'] = $price;
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price_override');
            $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_price_override');
        }
    }

    public function test_optional_patient_fields_are_saved_restored_searchable_and_can_be_cleared(): void
    {
        foreach (['oncologicos', 'nutricionales', 'antibioticos'] as $category) {
            $data = $this->payload($category) + [
                'patient_name' => ' Maria Elena ', 'patient_paternal_surname' => ' Garcia ',
                'patient_maternal_surname' => ' Lopez ', 'patient_platform_id' => '000123-A',
                'observations' => 'Observacion de prueba',
            ];
            $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
            $this->store($data)->assertOk();
            $quote = RequestQuotation::latest('id')->first();
            $this->assertSame('Maria Elena Garcia Lopez', $quote->patient_name);
            $this->getJson(route('admin.solicitudes.cotizacion.show', $quote))->assertOk()
                ->assertJsonPath('clinical_data.patient_name', 'Maria Elena')
                ->assertJsonPath('clinical_data.patient_paternal_surname', 'Garcia')
                ->assertJsonPath('clinical_data.patient_maternal_surname', 'Lopez')
                ->assertJsonPath('clinical_data.patient_platform_id', '000123-A')
                ->assertJsonPath('clinical_data.observations', 'Observacion de prueba');
            $this->get(route('admin.solicitudes.cotizacion.index', ['tipo' => $category, 'buscar' => 'Garcia Lopez']))
                ->assertOk()->assertSee($quote->folio)->assertSee('Maria Elena Garcia Lopez');

            foreach (['patient_name', 'patient_paternal_surname', 'patient_maternal_surname', 'patient_platform_id', 'observations'] as $field) {
                $data[$field] = '';
            }
            $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertOk();
            $this->assertSame('', $quote->refresh()->patient_name);
            $this->assertSame('', $quote->clinical_data['patient_paternal_surname']);
            $this->assertSame('', $quote->clinical_data['patient_maternal_surname']);
            $this->assertSame('', $quote->clinical_data['patient_platform_id']);
        }
    }

    public function test_legacy_names_remain_whole_and_new_patient_fields_are_validated(): void
    {
        $data = $this->payload() + ['patient_name' => 'Paciente anterior con nombre completo'];
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $this->store($data)->assertOk();
        $quote = RequestQuotation::latest('id')->first();
        $this->assertSame($data['patient_name'], $quote->patient_name);
        $this->assertSame($data['patient_name'], $quote->clinical_data['patient_name']);
        foreach (['patient_paternal_surname', 'patient_maternal_surname', 'patient_platform_id'] as $field) {
            $this->assertSame('', $quote->clinical_data[$field]);
            foreach ([['not a string'], str_repeat('a', 101)] as $invalid) {
                $this->preview(array_replace($data, [$field => $invalid]))->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
        $data['patient_name'] = str_repeat('a', 255);
        $this->preview($data)->assertOk();
        $data['patient_paternal_surname'] = 'Apellido';
        $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('patient_name');
        $data['submission_key'] = (string) Str::uuid();
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('patient_name');
    }

    public function test_bottle_counts_must_be_positive_integers_and_match_the_server_billing_unit(): void
    {
        DB::table('medicine_list_presentation')->where('medicine_list_id', 1)->update(['charge_by' => 'frasco']);
        foreach ([null, 0, -1, 1.5, 'invalid', 1000001] as $count) {
            $data = $this->payload(); $data['items'] = [['presentation_id' => 1, 'bottle_count' => $count]];
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.bottle_count');
            $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.bottle_count');
        }
        foreach ([['presentation_id' => 1], ['presentation_id' => 1, 'concentration' => 101],
            ['presentation_id' => 1, 'bottle_count' => 2, 'concentration' => 101]] as $item) {
            $data = $this->payload(); $data['items'] = [$item];
            $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.bottle_count');
        }
        DB::table('medicine_list_presentation')->where('medicine_list_id', 1)->update(['charge_by' => 'mg']);
        $data['items'] = [['presentation_id' => 1, 'bottle_count' => 2]];
        $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('items.0.concentration');
        $data['items'] = [['presentation_id' => 1, 'concentration' => 2.5]];
        $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.lines.0.quantity', 2.5);
    }

    public function test_mixed_tariffs_validate_and_price_each_medication_in_its_own_unit(): void
    {
        DB::table('medicine_presentations')->where('id', 2)->update(['is_available' => 1]);
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 2)->update(['is_active' => 1, 'charge_by' => 'frasco', 'precio' => 300]);
        $data = $this->payload(); $data['items'][] = ['presentation_id' => 2, 'bottle_count' => 3];
        $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.billing_mode', 'mixed')
            ->assertJsonPath('pricing_snapshot.lines.0.quantity', 50)->assertJsonPath('pricing_snapshot.lines.0.unit', 'mg')
            ->assertJsonPath('pricing_snapshot.lines.1.quantity', 3)->assertJsonPath('pricing_snapshot.lines.1.unit', 'frasco')
            ->assertJsonPath('pricing_snapshot.lines.1.subtotal', 900);
    }

    public function test_generic_tariff_uses_only_explicit_config_and_manual_mode(): void
    {
        $data = $this->payload(); $data['no_commercial_relationship'] = true; $data['billing_mode'] = 'unit';
        $this->preview($data)->assertUnprocessable()->assertJsonValidationErrors('no_commercial_relationship');
        DB::table('medicine_lists')->insert(['id' => 3, 'name' => 'Base oncologica', 'catalog_category' => 'oncologicos', 'charge_by' => 'mg']);
        DB::table('medicine_list_presentation')->insert(['medicine_list_id' => 3, 'medicine_presentation_id' => 1, 'precio' => 300, 'precio_mg_override' => 4, 'charge_by' => 'mg']);
        config(['request-quotations.base_lists.oncologicos' => 3]);
        $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.total', 200)
            ->assertJsonPath('pricing_snapshot.price_list.source', 'generic')->assertJsonPath('pricing_snapshot.price_list.id', 3);
        $data['billing_mode'] = 'frasco';
        $data['items'] = [['presentation_id' => 1, 'bottle_count' => 1]];
        $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.total', 300)->assertJsonPath('pricing_snapshot.lines.0.unit', 'frasco');
        config(['request-quotations.base_lists.oncologicos' => 2]);
        $this->preview($data)->assertUnprocessable();
    }

    public function test_changed_price_requires_review_and_stored_snapshot_is_stable(): void
    {
        $data = $this->payload();
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => 400]);
        $this->store($data)->assertUnprocessable()->assertJsonValidationErrors('pricing_token');
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $this->store($data)->assertOk()->assertJsonPath('total', '247.00');
        $quote = RequestQuotation::latest('id')->first();
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => 600]);
        $this->assertSame('247.00', $quote->refresh()->total);
        $data['pricing_token'] = $this->preview($data)->assertOk()->json('pricing_token');
        $this->putJson(route('admin.solicitudes.cotizacion.update', $quote), $data)->assertOk()->assertJsonPath('total', '363.00');
    }

    public function test_invalid_products_concentrations_institutions_and_prices_are_rejected(): void
    {
        foreach ([['items.0.presentation_id', 2], ['items.0.presentation_id', 3], ['items.0.concentration', 0],
            ['items.0.concentration', -1], ['items.0.unit_price', .01], ['institution_id', 2], ['items', []]] as [$key, $value]) {
            $data = $this->payload(); data_set($data, $key, $value);
            $this->preview($data)->assertUnprocessable();
        }
        $data = $this->payload(); $data['items'][] = $data['items'][0];
        $this->preview($data)->assertUnprocessable();
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => null]);
        $this->preview($this->payload())->assertUnprocessable()->assertJsonValidationErrors('items.0.presentation_id');
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['precio' => 0]);
        $this->preview($this->payload())->assertOk()->assertJsonPath('pricing_snapshot.total', 15);
        DB::table('medicine_list_presentation')->where('medicine_presentation_id', 1)->update(['is_active' => 0]);
        $this->preview($this->payload())->assertUnprocessable();
        $this->assertSame(5, RequestQuotation::count());
    }

    public function test_antibiotics_and_generic_quotes_keep_hospital_and_permission_boundaries(): void
    {
        $this->user->syncRoles(Role::findOrCreate('Cliente', 'web'));
        $data = $this->payload('antibioticos'); $data['hospital_id'] = 2;
        $this->preview($data)->assertForbidden();
        $data['no_commercial_relationship'] = true; $data['billing_mode'] = 'unit';
        config(['request-quotations.base_lists.antibioticos' => 2]);
        $this->preview($data)->assertForbidden();
        $this->user->revokePermissionTo('oncologicos_solicitudes_create');
        $this->preview($this->payload('antibioticos'))->assertForbidden();
        $this->preview($this->payload('nutricionales'))->assertOk();
    }

    public function test_generic_nutrition_and_antibiotics_can_be_sent_without_changing_requests_or_stock(): void
    {
        config(['request-quotations.base_lists' => ['nutricionales' => 1, 'antibioticos' => 2]]);
        $tables = ['solicitud_oncos', 'solicituds', 'mezclas', 'medicine_batch_movements'];
        $before = array_map(fn ($table) => DB::table($table)->count(), $tables);
        foreach (['nutricionales' => 225, 'antibioticos' => 250] as $category => $total) {
            $data = $this->payload($category);
            $data['no_commercial_relationship'] = true;
            $data['billing_mode'] = 'frasco';
            $data['items'] = [['presentation_id' => $category === 'antibioticos' ? 3 : 1, 'bottle_count' => 1]];
            $data['action'] = 'send';
            $data['pricing_token'] = $this->preview($data)->assertOk()->assertJsonPath('pricing_snapshot.total', $total)->json('pricing_token');
            $this->store($data)->assertOk()->assertJsonPath('status', 'enviada');
            $quote = RequestQuotation::latest('id')->first();
            $this->assertNotNull($quote->sent_at);
            $this->assertTrue($quote->clinical_data['no_commercial_relationship']);
            $this->assertStringNotContainsString('Concentracion solicitada:', \App\Support\QuotationMessage::summary($quote));
            $this->assertStringContainsString('1 frasco', \App\Support\QuotationMessage::summary($quote));
        }
        $this->assertSame($before, array_map(fn ($table) => DB::table($table)->count(), $tables));
    }
}
