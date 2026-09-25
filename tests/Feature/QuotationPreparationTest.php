<?php

namespace Tests\Feature;

use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\SolicitudOnco;
use App\Services\InstitutionBillingPricingService;
use App\Services\QuotationPreparationService;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\QuotationPreparationData as Data;
use Tests\TestCase;

class QuotationPreparationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(Data::seed());
    }

    public function test_all_categories_open_their_existing_clinical_form_with_quoted_items(): void
    {
        foreach (['oncologicos' => 'mg', 'antibioticos' => 'mg', 'nutricionales' => 'ml'] as $category => $unit) {
            $quote = Data::quotation($category, $unit);
            $this->get(route('admin.solicitudes.cotizacion.preparation', $quote))->assertOk()
                ->assertViewIs('admin.'.($category === 'antibioticos' ? 'oncologicos' : $category).'.solicitudes.create')
                ->assertSee($quote->folio)->assertSee('Hospital de prueba')->assertSee('data-quotation-preparation', false);
            $this->assertNull($quote->refresh()->request_id);
        }
    }

    public function test_each_category_and_billing_mode_preserves_prices_hospital_folio_and_is_idempotent(): void
    {
        $stock = DB::table('medicine_batches')->sum('stock_actual');
        $movements = DB::table('medicine_batch_movements')->count();
        foreach (['oncologicos' => 'mg', 'antibioticos' => 'mg', 'nutricionales' => 'ml'] as $category => $unit) {
            foreach ([$unit, 'frasco'] as $mode) {
                $quote = Data::quotation($category, $mode);
                $total = (float) $quote->total;
                DB::table('medicine_list_presentation')->update(['precio' => 99999, 'charge_by' => 'frasco']);
                DB::table('nutri_medicine_list_items')->update(['precio_ml' => 99999, 'charge_by' => 'frasco']);
                $url = route('admin.solicitudes.cotizacion.prepare', $quote);
                $this->post($url, Data::payload($category) + ['hospital_id' => 2, 'total' => 1])
                    ->assertSessionHasNoErrors()->assertRedirect(route('admin.solicitudes.index', ['tipo' => $category]));
                $quote->refresh();
                $this->assertSame('preparacion', $quote->status);
                $class = $category === 'nutricionales' ? Solicitud::class : SolicitudOnco::class;
                $request = $class::findOrFail($quote->request_id);
                $this->assertSame('COT-'.$request->id, $request->request_folio);
                $this->assertEquals($quote->id, $request->request_quotation_id);
                $this->assertEquals(1, $request->hospital_id);
                $this->assertEquals(auth()->id(), $request->user_id);
                $pricing = app(InstitutionBillingPricingService::class);
                $actual = $category === 'nutricionales' ? $pricing->priceNutritionRequest($request)
                    : $pricing->priceOncoMix($request->mezclas->first());
                $this->assertEquals($total, $actual['total_iva_included']);
                $this->assertSame($mode, $actual['lines'][0]['unit_label']);
                $this->assertEquals($mode === 'frasco' ? 2 : 50, $actual['lines'][0]['quantity']);
                if ($category === 'nutricionales') {
                    $this->assertEquals(50, $request->input->first()->valor_ml);
                    $this->assertEquals(5, $request->input->first()->valor);
                }
                $before = $class::count();
                $this->post($url, Data::payload($category))->assertSessionHasNoErrors()->assertRedirect();
                $this->assertSame($before, $class::count());
                $this->assertSame(0, DB::transactionLevel());
            }
        }
        $this->assertEquals($stock, DB::table('medicine_batches')->sum('stock_actual'));
        $this->assertSame($movements, DB::table('medicine_batch_movements')->count());
    }

    public function test_changed_dose_extra_medicine_extra_deliveries_and_invalid_diluent_are_rejected(): void
    {
        $quote = Data::quotation();
        $url = route('admin.solicitudes.cotizacion.prepare', $quote);
        foreach (['dose', 'medicine', 'delivery', 'diluent', 'infusor'] as $change) {
            $data = Data::payload();
            $mixtures = json_decode($data['mezclas'], true);
            if ($change === 'dose') $mixtures[0]['medicamentos'][0]['dosis'] = 51;
            if ($change === 'medicine') $mixtures[0]['medicamentos'][] = $mixtures[0]['medicamentos'][0];
            if ($change === 'delivery') $mixtures[0]['fechas_entrega'][] = now()->addDays(2)->format('Y-m-d\TH:i');
            if ($change === 'diluent') $mixtures[0]['medicamentos'][0]['diluyente_id'] = 999;
            if ($change === 'infusor') $mixtures[0]['infusor_id'] = 1;
            $data['mezclas'] = json_encode($mixtures);
            $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('quotation');
            $this->assertNull($quote->refresh()->request_id);
            $this->assertSame(0, DB::transactionLevel());
        }
    }

    public function test_special_quote_prices_survive_preparation_remission_and_billing_despite_tariff_changes(): void
    {
        foreach (['oncologicos' => 'mg', 'antibioticos' => 'mg', 'nutricionales' => 'ml'] as $category => $unit) {
            foreach ([$unit, 'frasco'] as $mode) {
                $specialPrice = $mode === 'frasco' ? 99.1234 : .1234;
                $quote = Data::quotation($category, $mode, 1, $specialPrice);
                $line = $quote->pricing_snapshot['lines'][0];
                $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload($category))->assertSessionHasNoErrors();
                DB::table('medicine_list_presentation')->update(['precio' => 9999]);
                DB::table('nutri_medicine_list_items')->update(['precio_ml' => 9999]);
                $quote->refresh();
                $pricing = app(InstitutionBillingPricingService::class);
                if ($category === 'nutricionales') {
                    $request = Solicitud::findOrFail($quote->request_id);
                    $actual = $pricing->priceNutritionRequest($request);
                    $snapshot = $request->quotation_pricing_snapshot;
                } else {
                    $mixture = SolicitudOnco::findOrFail($quote->request_id)->mezclas->first();
                    $actual = $pricing->priceOncoMix($mixture);
                    $snapshot = $mixture->quotation_pricing_snapshot;
                    $this->assertEquals($specialPrice, $mixture->medicamentos->first()->precio_unitario_calculado);
                    $this->assertEquals($line['total'], $mixture->medicamentos->first()->subtotal_iva_incluido);
                }
                $this->assertEquals($specialPrice, $snapshot['lines'][0]['unit_price']);
                $this->assertEquals($specialPrice, $actual['lines'][0]['unit_price']);
                $this->assertEquals($line['list_unit_price'], $snapshot['lines'][0]['list_unit_price']);
                $this->assertEquals($line['quantity'], $actual['lines'][0]['quantity']);
                $this->assertEquals($quote->total, $actual['total_iva_included']);
            }
        }
    }

    public function test_nutrition_rejects_changed_volume_and_rolls_back_all_patient_data(): void
    {
        $quote = Data::quotation('nutricionales', 'ml');
        $before = DB::table('solicitud_patients')->count();
        $data = Data::payload('nutricionales');
        $data['quoted_volumes'] = [51];
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $data)->assertSessionHasErrors();
        $this->assertNull($quote->refresh()->request_id);
        $this->assertSame($before, DB::table('solicitud_patients')->count());
        $this->assertSame(0, DB::transactionLevel());
        $data['quoted_volumes'] = [50];
        $data['fecha_hora_entrega'] = now()->format('Y-m-d\TH:i');
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $data)->assertSessionHasErrors();
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_numbered_mixtures_remain_separate_and_preserve_combined_billing_totals(): void
    {
        foreach (['oncologicos' => 'mg', 'antibioticos' => 'mg', 'nutricionales' => 'ml'] as $category => $unit) {
            foreach ([$unit, 'frasco'] as $mode) {
                $quote = Data::quotation($category, $mode, 2, 1, true);
                $payload = Data::payload($category, 2);
                if ($category === 'nutricionales') $payload['quoted_volumes'] = [50, 50];
                $url = route('admin.solicitudes.cotizacion.prepare', $quote);
                $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
                $quote->refresh();
                $targets = $category === 'nutricionales'
                    ? Solicitud::where('request_quotation_id', $quote->id)->orderBy('id')->get()
                    : SolicitudOnco::findOrFail($quote->request_id)->mezclas()->orderBy('id')->get();
                $this->assertCount(2, $targets);
                $totals = [];
                foreach ($targets as $index => $target) {
                    $this->assertEquals($index + 1, $target->quotation_pricing_snapshot['mixture_number']);
                    $pricing = app(InstitutionBillingPricingService::class);
                    $summary = $category === 'nutricionales' ? $pricing->priceNutritionRequest($target) : $pricing->priceOncoMix($target);
                    $this->assertEquals(1, $summary['lines'][0]['unit_price']);
                    $totals[] = $summary['total_iva_included'];
                    if ($category === 'nutricionales') {
                        $this->assertCount(1, $target->input);
                        $this->assertEquals(50, $target->input->first()->valor_ml);
                    }
                }
                $this->assertEquals((float) $quote->total, array_sum($totals));
                $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
                if ($category === 'nutricionales') $this->assertSame(2, Solicitud::where('request_quotation_id', $quote->id)->count());
            }
        }
    }

    public function test_multiple_medicines_remain_in_the_same_oncology_mixture(): void
    {
        DB::table('medicine_presentations')->where('id', 2)->update(['is_available' => 1]);
        $quote = Data::quotation('oncologicos', 'mg', 2, 1, true);
        $data = $quote->clinical_data;
        $data['items'][] = ['presentation_id' => 2, 'mixture_number' => 1, 'concentration' => 25, 'unit_price_override' => 2];
        $capture = app(\App\Services\RequestQuotationCaptureService::class)->capture(auth()->user(), $data, true);
        unset($capture['pricing_token']); $quote->forceFill($capture)->save();
        $service = app(QuotationPreparationService::class);
        $defaults = $service->defaults($quote, $service->items($quote));
        $this->assertCount(2, $defaults['mezclas']);
        $this->assertCount(2, $defaults['mezclas'][0]['medicamentos']);
        $payload = Data::payload('oncologicos', 2);
        $mixtures = json_decode($payload['mezclas'], true);
        $mixtures[0]['medicamentos'][] = array_replace($mixtures[0]['medicamentos'][0], ['dosis' => 25]);
        $payload['mezclas'] = json_encode($mixtures);
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $payload)->assertSessionHasNoErrors();
        $targets = SolicitudOnco::findOrFail($quote->refresh()->request_id)->mezclas()->orderBy('id')->get();
        $this->assertCount(2, $targets);
        $this->assertCount(2, $targets[0]->medicamentos);
        $this->assertCount(1, $targets[1]->medicamentos);
        $pricing = app(InstitutionBillingPricingService::class);
        $this->assertEquals((float) $quote->total, $pricing->priceOncoMix($targets[0])['total_iva_included'] + $pricing->priceOncoMix($targets[1])['total_iva_included']);
    }

    public function test_invalid_second_nutrition_mixture_rolls_back_the_whole_quotation(): void
    {
        $quote = Data::quotation('nutricionales', 'ml', 2, null, true);
        $before = Solicitud::count(); $patients = DB::table('solicitud_patients')->count();
        $payload = Data::payload('nutricionales'); $payload['quoted_volumes'] = [50, 51];
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $payload)->assertSessionHasErrors();
        $this->assertNull($quote->refresh()->request_id);
        $this->assertSame('autorizada', $quote->status);
        $this->assertSame($before, Solicitud::count());
        $this->assertSame($patients, DB::table('solicitud_patients')->count());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_concentrations_are_summed_by_medicine_but_doses_and_prices_stay_per_presentation(): void
    {
        $quote = Data::presentationGroups();
        $service = app(QuotationPreparationService::class);
        $items = $service->items($quote);
        $this->assertSame([1, 10, 11, 11], array_column($items, 'presentation_id'));
        $this->assertSame([200.0, 50.0, 75.0, 25.0], array_column($items, 'quoted_concentration'));
        $groups = $service->medicationGroups($items);
        $this->assertSame([250.0, 100.0], array_column($groups, 'quoted_concentration'));
        $this->assertCount(2, $groups[0]['items']);
        $this->assertCount(3, $service->defaults($quote, $items)['mezclas'][0]['medicamentos']);
        $this->get(route('admin.solicitudes.cotizacion.preparation', $quote))->assertOk()
            ->assertSee('250 mg')->assertSee('100 mg')->assertSee('Concentraci&oacute;n por presentaci&oacute;n', false);

        $payload = Data::payload('oncologicos', 2);
        $mixtures = json_decode($payload['mezclas'], true);
        $medicine = $mixtures[0]['medicamentos'][0];
        $mixtures[0]['medicamentos'] = [$medicine, array_replace($medicine, ['dosis' => 25]),
            array_replace($medicine, ['medicamento_id' => 10])];
        $mixtures[1]['medicamentos'] = [array_replace($medicine, ['medicamento_id' => 10, 'dosis' => 25])];
        $payload['mezclas'] = json_encode($mixtures);
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $payload)->assertSessionHasNoErrors();
        $mixtures = SolicitudOnco::findOrFail($quote->refresh()->request_id)->mezclas()->orderBy('id')->get();
        $this->assertCount(3, $mixtures[0]->medicamentos);
        $this->assertEquals([50, 25, 50], $mixtures[0]->medicamentos->pluck('dosis')->all());
        $lines = array_filter($mixtures[0]->quotation_pricing_snapshot['lines'], fn ($line) => $line['unit'] !== 'servicio');
        $this->assertSame([1, 10, 11], array_column($lines, 'presentation_id'));
        $this->assertEquals([3, 5, 7], array_column($lines, 'unit_price'));
        $this->assertEqualsWithDelta((float) $quote->total, $mixtures->sum(fn ($mixture) => app(InstitutionBillingPricingService::class)->priceOncoMix($mixture)['total_iva_included']), .00001);
    }

    public function test_milligram_totals_use_the_quoted_dose_not_the_number_or_capacity_of_presentations(): void
    {
        $quote = Data::presentationGroups('antibioticos', 'mg');
        $service = app(QuotationPreparationService::class);
        $items = $service->items($quote);
        $this->assertSame([40.0, 10.0, 20.0, 5.0], array_column($items, 'quoted_concentration'));
        $this->assertSame([50.0, 25.0], array_column($service->medicationGroups($items), 'quoted_concentration'));
        $this->assertSame([40.0, 10.0, 20.0], array_column($service->defaults($quote, $items)['mezclas'][0]['medicamentos'], 'dosis'));
    }

    public function test_presentation_content_is_frozen_for_new_quotes_and_unknown_legacy_content_is_not_counted_as_zero(): void
    {
        $service = app(QuotationPreparationService::class);
        foreach (['oncologicos', 'nutricionales'] as $category) {
            $quote = Data::quotation($category, 'frasco', 2, null, true);
            $this->assertEquals(100, $quote->pricing_snapshot['lines'][0]['presentation_content']);
            if ($category === 'nutricionales') {
                DB::table('nutrition_medicine_presentations')->where('id', 1)->update(['presentacion_ml' => null]);
            } else {
                DB::table('medicine_presentations')->where('id', 1)->update(['contenido_valor' => null, 'cantidad_medicamento' => null, 'presentacion' => 'Sin contenido']);
            }
            $items = $service->items($quote);
            $this->assertSame([200.0, 200.0], array_column($items, 'quoted_concentration'));
            $this->assertEquals(400, $service->medicationGroups($items)[0]['quoted_concentration']);
            $snapshot = $quote->pricing_snapshot;
            foreach ($snapshot['lines'] as &$line) unset($line['presentation_content']);
            unset($line);
            $quote->pricing_snapshot = $snapshot;
            $items = $service->items($quote);
            $this->assertNull($service->medicationGroups($items)[0]['quoted_concentration']);
        }
    }

    public function test_drafts_and_users_without_request_permission_cannot_prepare(): void
    {
        $quote = Data::quotation();
        foreach (['borrador', 'enviada'] as $status) {
            $quote->forceFill(['status' => $status])->save();
            $this->getJson(route('admin.solicitudes.cotizacion.preparation', $quote))->assertUnprocessable()->assertJsonValidationErrors('quotation');
            $this->postJson(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload())->assertUnprocessable()->assertJsonValidationErrors('quotation');
            $this->assertFalse($quote->canStartPreparationBy(auth()->user()));
            $this->assertNull($quote->refresh()->request_id);
        }
        $quote->forceFill(['status' => 'autorizada'])->save();
        auth()->user()->revokePermissionTo('oncologicos_solicitudes_store');
        $this->get(route('admin.solicitudes.cotizacion.preparation', $quote))->assertForbidden();
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload())->assertForbidden();
    }

    public function test_preparation_requires_its_own_saved_request_document_in_every_category(): void
    {
        Data::quotation();
        foreach (['oncologicos' => 'mg', 'antibioticos' => 'mg', 'nutricionales' => 'ml'] as $category => $unit) {
            $quote = Data::quotation($category, $unit, withDocument: false);
            $quote->forceFill(['attachment_path' => 'signature.pdf'])->save();
            $url = route('admin.solicitudes.cotizacion.preparation', $quote);
            $this->get(route('admin.solicitudes.cotizacion.index', ['buscar' => $quote->folio]))->assertOk()
                ->assertDontSee('href="'.$url.'"', false)->assertSee('Adjunta una foto o archivo de la solicitud');
            $this->getJson($url)->assertUnprocessable()->assertJsonValidationErrors('quotation');
            $this->postJson(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload($category))
                ->assertUnprocessable()->assertJsonValidationErrors('quotation');
            $this->assertNull($quote->refresh()->request_id);
            $this->assertSame(0, DB::transactionLevel());
            $this->postJson(route('admin.solicitudes.cotizacion.documents.store', $quote), [
                'file' => \Illuminate\Http\UploadedFile::fake()->image('solicitud.jpg'),
                'upload_key' => (string) \Illuminate\Support\Str::uuid(),
            ])->assertOk()->assertJsonPath('preparation_url', $url);
            $this->get(route('admin.solicitudes.cotizacion.index', ['buscar' => $quote->folio]))->assertOk()
                ->assertSee('href="'.$url.'"', false);
            $this->get($url)->assertOk();
            $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload($category))
                ->assertSessionHasNoErrors()->assertRedirect();
            $this->assertNotNull($quote->refresh()->request_id);
        }
    }

    public function test_multiple_mixtures_split_service_cents_without_changing_total(): void
    {
        $quote = Data::quotation('oncologicos', 'mg', 3);
        $snapshot = $quote->pricing_snapshot;
        $snapshot['lines'][3]['total'] += 0.01;
        $snapshot['lines'][3]['subtotal'] += 0.01;
        $snapshot['total'] += 0.01;
        $quote->forceFill(['pricing_snapshot' => $snapshot, 'total' => $snapshot['total']])->save();
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload('oncologicos', 3))->assertSessionHasNoErrors();
        $request = SolicitudOnco::findOrFail($quote->refresh()->request_id);
        $this->assertCount(3, $request->mezclas);
        $total = $request->mezclas->sum(fn ($mix) => app(InstitutionBillingPricingService::class)->priceOncoMix($mix)['total_iva_included']);
        $this->assertEqualsWithDelta((float) $quote->total, $total, 0.00001);
        foreach ($request->mezclas as $mix) {
            $summary = app(InstitutionBillingPricingService::class)->priceOncoMix($mix);
            $this->assertEqualsWithDelta($summary['total_iva_included'], $summary['subtotal_before_vat'] + $summary['vat_total'], 0.00001);
        }
    }

    public function test_list_shows_send_then_new_request_folio_and_source_quote(): void
    {
        $quote = Data::quotation('nutricionales', 'ml');
        $this->get(route('admin.solicitudes.cotizacion.index', ['buscar' => $quote->folio]))->assertOk()
            ->assertSee('Enviar a preparacion')->assertSee('Enviar '.$quote->folio.' a preparacion')
            ->assertSee(route('admin.solicitudes.cotizacion.preparation', $quote));
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload('nutricionales'))->assertSessionHasNoErrors();
        $quote->refresh();
        $this->get(route('admin.solicitudes.index'))->assertOk()->assertSee('COT-'.$quote->request_id)
            ->assertSee('Cotizacion '.$quote->folio);
        $hospitalUser = \App\Models\User::find(2);
        $hospitalUser->forceFill(['hospital_id' => 1])->save();
        $hospitalUser->assignRole(\Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Institucion', 'guard_name' => 'web']));
        $hospitalUser->givePermissionTo('nutricionales_solicitudes_index');
        $this->actingAs($hospitalUser)->get(route('admin.solicitudes.index'))->assertOk()->assertSee('COT-'.$quote->request_id);
        $hospitalUser->forceFill(['hospital_id' => 2])->save();
        $this->actingAs($hospitalUser)->get(route('admin.solicitudes.index'))->assertOk()->assertDontSee('Cotizacion '.$quote->folio);
    }

    public function test_post_creation_guard_blocks_changed_medications_but_keeps_quoted_unit_prices(): void
    {
        $quote = Data::quotation();
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), Data::payload())->assertSessionHasNoErrors();
        $mix = SolicitudOnco::findOrFail($quote->refresh()->request_id)->mezclas->first();
        $payload = json_decode(Data::payload()['mezclas'], true)[0];
        $payload['medicamentos'][0]['precio_mg'] = 9999;
        $payload['medicamentos'][0]['charge_by'] = 'frasco';
        $request = \Illuminate\Http\Request::create('/', 'POST', ['accion' => 'aprobar', 'mezcla_json' => json_encode($payload)]);
        app(QuotationPreparationService::class)->guardChanges($mix, $request);
        $checked = json_decode($request->input('mezcla_json'), true);
        $this->assertSame('mg', $checked['medicamentos'][0]['charge_by']);
        $this->assertEquals(2, $checked['medicamentos'][0]['precio_mg']);
        $payload['medicamentos'][0]['dosis'] = 51;
        $request->merge(['mezcla_json' => json_encode($payload)]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(QuotationPreparationService::class)->guardChanges($mix, $request);
    }

    public function test_bottle_supply_does_not_infer_a_dose_and_rejects_excess(): void
    {
        $quote = Data::quotation('oncologicos', 'frasco');
        $data = Data::payload();
        $mixtures = json_decode($data['mezclas'], true);
        foreach ([null, 201] as $dose) {
            $mixtures[0]['medicamentos'][0]['dosis'] = $dose;
            $data['mezclas'] = json_encode($mixtures);
            $this->postJson(route('admin.solicitudes.cotizacion.prepare', $quote), $data)->assertUnprocessable();
            $this->assertNull($quote->refresh()->request_id);
        }
    }

    public function test_fractional_quoted_dose_is_not_rounded_on_the_request(): void
    {
        $quote = Data::quotation();
        $data = $quote->clinical_data;
        $data['items'][0]['concentration'] = 50.125;
        $snapshot = $quote->pricing_snapshot;
        $snapshot['lines'][0]['quantity'] = 50.125;
        $quote->forceFill(['clinical_data' => $data, 'pricing_snapshot' => $snapshot])->save();
        $payload = Data::payload();
        $mixtures = json_decode($payload['mezclas'], true);
        $mixtures[0]['medicamentos'][0]['dosis'] = 50.125;
        $payload['mezclas'] = json_encode($mixtures);
        $this->post(route('admin.solicitudes.cotizacion.prepare', $quote), $payload)->assertSessionHasNoErrors();
        $medicine = SolicitudOnco::findOrFail($quote->refresh()->request_id)->mezclas->first()->medicamentos->first();
        $this->assertSame('50.1250', $medicine->dosis);
    }
}
