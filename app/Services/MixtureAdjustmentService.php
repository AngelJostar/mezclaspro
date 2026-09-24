<?php

namespace App\Services;

use App\Models\MixtureAdjustment;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\AdministrationRoute;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\MedicineOnco;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use App\Notifications\MixtureAdjustmentRequested;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MixtureAdjustmentService
{
    private const LABELS = [
        'paciente_nombre' => 'Paciente', 'nombre_paciente' => 'Nombre del paciente',
        'apellidos_paciente' => 'Apellidos', 'servicio' => 'Servicio', 'registro' => 'Registro',
        'sexo' => 'Sexo', 'fecha_nacimiento' => 'Fecha de nacimiento', 'peso' => 'Peso',
        'piso' => 'Piso', 'cama' => 'Cama', 'diagnostico' => 'Diagnostico',
        'medico_nombre' => 'Medico', 'medico_cedula' => 'Cedula', 'nombre_medico' => 'Medico',
        'cedula' => 'Cedula', 'fecha_entrega' => 'Entrega programada',
        'fecha_hora_entrega' => 'Entrega programada', 'observaciones' => 'Observaciones',
        'via_administracion' => 'Via de administracion', 'tiempo_infusion_min' => 'Tiempo de infusion',
        'sobrellenado_ml' => 'Sobrellenado (ml)', 'volumen_total' => 'Volumen total (ml)',
        'npt' => 'NPT', 'bolsa_eva' => 'Bolsa EVA (ID)', 'velocidad_infusion' => 'Velocidad de infusion',
        'hospital_destino' => 'Hospital destino',
    ];

    public function assertCentral(User $user, Model $target): void
    {
        $permission = $target instanceof Solicitud ? 'nutricionales_solicitudes_update' : 'oncologicos_mezclas_update';
        abort_unless($user->hasAnyRole(['Admin', 'Super Admin']) && $user->can($permission), 403);
    }

    public function canView(User $user, Model $target): bool
    {
        if ($user->hasAnyRole(['Admin', 'Super Admin'])) {
            return $user->can($target instanceof Solicitud ? 'nutricionales_solicitudes_index' : 'oncologicos_solicitudes_index');
        }
        return $user->hasAnyRole(['Cliente', 'Institucion']) && $user->hospital_id
            && (int) $user->hospital_id === $this->hospitalId($target);
    }

    public function hospitalId(Model $target): int
    {
        return (int) ($target instanceof Solicitud ? $target->hospital_id : $target->solicitud?->hospital_id);
    }

    public function requestAdjustment(Model $target, Request $request): MixtureAdjustment
    {
        $this->assertCentral($request->user(), $target);
        $request->validate(['adjustment_description' => 'required|string|min:5|max:4000']);

        return DB::transaction(function () use ($target, $request) {
            $target = $target->newQuery()->lockForUpdate()->findOrFail($target->id);
            $this->assertPending($target);
            $this->assertWritable($target, $request);
            $hospitalId = $this->hospitalId($target);
            if (! $hospitalId) {
                $this->fail('La mezcla debe tener un hospital asignado.');
            }
            $keys = $target instanceof Mezcla
                ? ['paciente_nombre', 'servicio', 'registro', 'sexo', 'fecha_nacimiento', 'peso', 'piso', 'cama', 'diagnostico', 'medico_nombre', 'medico_cedula', 'fecha_entrega', 'observaciones']
                : array_diff(array_keys(self::LABELS), ['paciente_nombre', 'medico_nombre', 'medico_cedula', 'fecha_entrega']);
            $proposal = array_replace(array_fill_keys($keys, null), $request->only($keys));
            $review = [];
            foreach ($proposal as $key => $value) {
                if (! is_scalar($value) && $value !== null) {
                    $this->fail('El formulario contiene un valor no valido.');
                }
                $review[] = ['label' => self::LABELS[$key], 'value' => (string) ($value ?? '')];
            }
            if ($target instanceof Mezcla) {
                $mix = json_decode($request->input('mezcla_json', ''), true);
                Validator::make(['mix' => $mix], [
                    'mix' => 'required|array', 'mix.volumen_dilucion' => 'required|numeric|gt:0',
                    'mix.tiempo_infusion' => 'required', 'mix.medicamentos' => 'required|array|min:1|max:100',
                    'mix.medicamentos.*.medicamento_id' => 'required|integer',
                    'mix.medicamentos.*.dosis' => 'required|numeric|gt:0',
                    'mix.medicamentos.*.diluyente_id' => 'required|integer',
                    'mix.medicamentos.*.via_administracion_id' => 'required|integer',
                    'mix.medicamentos.*.charge_by' => 'nullable|in:mg,ml,frasco,pieza',
                    'mix.medicamentos.*.precio_mg' => 'nullable|numeric|min:0',
                ])->validate();
                $review[] = ['label' => 'Volumen de dilucion (ml)', 'value' => (string) $mix['volumen_dilucion']];
                $review[] = ['label' => 'Tiempo de infusion', 'value' => (string) $mix['tiempo_infusion']];
                $review[] = ['label' => 'Set de infusion', 'value' => ! empty($mix['set_infusion']) ? 'Si' : 'No'];
                $review[] = ['label' => 'Infusor (ID)', 'value' => (string) ($mix['infusor_id'] ?? '')];
                foreach ($mix['medicamentos'] as $index => &$medicine) {
                    $record = MedicineOnco::with('catalog')->where('catalog_id', $medicine['medicamento_id'])->first()
                        ?? MedicineOnco::with('catalog')->find($medicine['medicamento_id']);
                    $diluent = Diluent::find($medicine['diluyente_id']);
                    $route = AdministrationRoute::find($medicine['via_administracion_id']);
                    if (! $record?->catalog || ! $diluent || ! $route) {
                        $this->fail('Revisa el medicamento, el diluyente y la via de administracion.');
                    }
                    // Store catalog IDs and canonical names, never a label supplied by the browser.
                    $medicine = Arr::only($medicine, ['medicamento_id', 'dosis', 'diluyente_id', 'via_administracion_id', 'charge_by', 'precio_mg']);
                    $medicine['medicamento_id'] = $record->catalog_id;
                    $medicine['nombre'] = $record->catalog->denominacion;
                    $review[] = ['label' => 'Medicamento '.($index + 1), 'value' => $medicine['nombre'].'; dosis: '.(float) $medicine['dosis'].' mg; diluyente: '.$diluent->denominacion_generica.'; via: '.$route->name];
                    $review[] = ['label' => 'Medicamento '.($index + 1).' / cobro', 'value' => ($medicine['charge_by'] ?? $record->catalog->charge_by ?? 'mg').'; precio por mg: '.($medicine['precio_mg'] ?? '')];
                }
                unset($medicine);
                $mix = Arr::only($mix, ['volumen_dilucion', 'tiempo_infusion', 'set_infusion', 'infusor_id', 'medicamentos']);
                $proposal['mezcla_json'] = json_encode($mix, JSON_THROW_ON_ERROR);
                $proposal['production_attempt'] = $target->production_attempt;
            } else {
                foreach ($request->all() as $key => $value) {
                    if (preg_match('/^([iplc])_(\d+)$/', $key, $matches)) {
                        Validator::make([$key => $value], [$key => in_array($matches[1], ['i', 'p'], true)
                            ? 'nullable|numeric|min:0' : 'nullable|string|max:100'])->validate();
                        $proposal[$key] = $value;
                        if ($matches[1] === 'i' && filled($value)) {
                            $input = Input::findOrFail($matches[2]);
                            $review[] = ['label' => $input->description, 'value' => (float) $value.' '.$input->unidad];
                            $review[] = ['label' => $input->description.' / presentacion, lote y caducidad', 'value' => implode(' / ', [
                                $request->input('p_'.$input->id, ''), $request->input('l_'.$input->id, ''), $request->input('c_'.$input->id, ''),
                            ])];
                        }
                    }
                }
                if ($bag = Input::find($proposal['bolsa_eva'] ?? null)) {
                    $review[] = ['label' => $bag->description.' / presentacion, lote y caducidad', 'value' => implode(' / ', [
                        $request->input('p_'.$bag->id, ''), $request->input('l_'.$bag->id, ''), $request->input('c_'.$bag->id, ''),
                    ])];
                }
            }
            $original = $this->originalReview($target);
            foreach ($review as &$field) {
                $field['before'] = $original[$field['label']] ?? '';
                $field['changed'] = $this->normalized($field['value']) !== $this->normalized($field['before']);
                unset($original[$field['label']]);
            }
            unset($field);
            foreach ($original as $label => $value) {
                if (filled($value)) {
                    $review[] = ['label' => $label, 'value' => '', 'before' => $value, 'changed' => true];
                }
            }
            $adjustment = MixtureAdjustment::create([
                'kind' => $target instanceof Solicitud ? 'nutricionales' : $target->solicitud->tipo_solicitud,
                'target_id' => $target->id, 'hospital_id' => $hospitalId, 'status' => 'requested',
                'description' => $request->input('adjustment_description'), 'proposal' => $proposal,
                'review' => $review, 'baseline_hash' => $this->fingerprint($target),
                'requested_by' => $request->user()->id,
            ]);
            $target->adjustment_id = $adjustment->id;
            $target->save();
            $recipients = User::whereHas('roles', fn ($query) => $query->whereIn('name', ['Cliente', 'Institucion']))->where('hospital_id', $hospitalId)
                ->where('is_active', true)->get();
            if ($recipients->isEmpty()) {
                $this->fail('El hospital no tiene un usuario activo para autorizar el ajuste.');
            }
            foreach ($recipients as $recipient) {
                $recipient->notify(new MixtureAdjustmentRequested($adjustment));
                $recipient->increment('notification');
            }
            return $adjustment;
        });
    }

    public function authorize(MixtureAdjustment $adjustment, User $user, ?string $response = null, bool $accepted = true): void
    {
        $this->transition($adjustment, function ($current, $target) use ($user, $response, $accepted) {
            abort_unless($user->hasAnyRole(['Cliente', 'Institucion']) && $user->hospital_id
                && (int) $user->hospital_id === (int) $current->hospital_id
                && $this->hospitalId($target) === (int) $current->hospital_id, 403);
            abort_unless($current->status === 'requested', 409, 'El ajuste ya fue atendido.');
            $this->assertPending($target);
            $this->assertUnchanged($current, $target);
            $current->update(['status' => $accepted ? 'authorized' : 'declined', 'authorized_by' => $user->id,
                'authorized_at' => now(), 'hospital_response' => $response]);
            $requester = User::find($current->requested_by);
            if ($requester?->is_active) {
                $requester->notify(new MixtureAdjustmentRequested($current));
                $requester->increment('notification');
            }
        });
    }

    public function reject(MixtureAdjustment $adjustment, User $user, string $reason): void
    {
        $this->transition($adjustment, function ($current, $target) use ($user, $reason) {
            $this->assertCentral($user, $target);
            abort_unless($current->isPending(), 409);
            $this->assertPending($target);
            $current->update(['status' => 'rejected', 'cancelled_by' => $user->id,
                'cancelled_at' => now(), 'central_response' => $reason]);
            $target->estado = $target instanceof Solicitud ? 'no_aprobada' : 'cancelada';
            $target->save();
        });
    }

    public function cancel(MixtureAdjustment $adjustment, User $user): void
    {
        $this->transition($adjustment, function ($current, $target) use ($user) {
            $this->assertCentral($user, $target);
            abort_unless($current->isPending(), 409);
            $current->update(['status' => 'cancelled', 'cancelled_by' => $user->id, 'cancelled_at' => now()]);
        });
    }

    private function transition(MixtureAdjustment $adjustment, callable $transition): void
    {
        DB::transaction(function () use ($adjustment, $transition) {
            $target = $adjustment->target();
            $target = $target->newQuery()->lockForUpdate()->findOrFail($target->id);
            $current = MixtureAdjustment::lockForUpdate()->findOrFail($adjustment->id);
            abort_unless((int) $target->adjustment_id === (int) $current->id, 409);
            $transition($current, $target);
        });
    }

    public function assertWritable(Model $target, Request $request): void
    {
        app(QuotationPreparationService::class)->guardChanges($target, $request);
        $adjustment = $target->currentAdjustment();
        if ($adjustment?->status === 'approved') {
            if ($request->input('accion', 'actualizar') === 'actualizar') {
                $this->fail('La version aprobada con ajuste no se puede modificar.');
            }
            if ($target instanceof Mezcla && $request->input('accion') === 'dispensar') {
                $expected = json_decode($adjustment->proposal['mezcla_json'], true);
                $submitted = json_decode($request->input('mezcla_json', ''), true);
                if (! is_array($submitted) || $this->formula($submitted) !== $this->formula($expected)) {
                    $this->fail('La dispensacion debe conservar la formula aprobada por el hospital.');
                }
            }
        }
        if (! $adjustment || ! $adjustment->isPending()) {
            return;
        }
        // Only an internal request attribute may authorize the final immutable proposal.
        if ($request->attributes->get('authorized_adjustment_id') !== $adjustment->id
            || $request->input('accion') !== 'aprobar' || $adjustment->status !== 'authorized') {
            $this->fail('El ajuste esta pendiente. Revisa su autorizacion antes de continuar.');
        }
        $this->assertCentral($request->user(), $target);
        $this->assertPending($target);
        $this->assertUnchanged($adjustment, $target);
    }

    private function formula(array $mix): array
    {
        $medicines = collect($mix['medicamentos'] ?? [])->map(fn ($item) => [
            'id' => (int) ($item['medicamento_id'] ?? 0), 'dose' => (float) ($item['dosis'] ?? 0),
            'diluent' => (int) ($item['diluyente_id'] ?? 0), 'route' => (int) ($item['via_administracion_id'] ?? 0),
        ])->sortBy(fn ($item) => json_encode($item))->values()->all();
        return [(float) ($mix['volumen_dilucion'] ?? 0), (string) ($mix['tiempo_infusion'] ?? ''),
            (bool) ($mix['set_infusion'] ?? false), (int) ($mix['infusor_id'] ?? 0), $medicines];
    }

    public function completeApproval(Model $target, Request $request): void
    {
        if (! $request->attributes->has('authorized_adjustment_id')) {
            return;
        }
        $adjustment = MixtureAdjustment::lockForUpdate()->findOrFail($target->adjustment_id);
        abort_unless($request->attributes->get('authorized_adjustment_id') === $adjustment->id
            && $adjustment->status === 'authorized', 409);
        $adjustment->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $target->setRelation('adjustment', $adjustment);
    }

    public function assertPending(Model $target): void
    {
        $status = $target instanceof Mezcla ? $target->operational_status : $target->estado;
        if ($status !== 'pendiente') {
            $this->fail('Solo una mezcla pendiente puede iniciar o autorizar un ajuste.');
        }
    }

    private function assertUnchanged(MixtureAdjustment $adjustment, Model $target): void
    {
        if (! hash_equals($adjustment->baseline_hash, $this->fingerprint($target))) {
            $this->fail('La solicitud cambio desde la propuesta. Cancela el ajuste y envia una nueva propuesta.');
        }
    }

    public function fingerprint(Model $target): string
    {
        $attributes = fn (Model $model) => Arr::except($model->getAttributes(), ['updated_at', 'adjustment_id']);
        $data = [$attributes($target)];
        if ($target instanceof Mezcla) {
            $data[] = Arr::except($target->solicitud()->lockForUpdate()->firstOrFail()->getAttributes(), ['updated_at', 'estado']);
            $data[] = $target->medicamentos()->orderBy('id')->get()->map($attributes)->all();
        } else {
            $data[] = $attributes($target->solicitud_patient()->firstOrFail());
            $data[] = $attributes($target->solicitud_detail()->firstOrFail());
            $data[] = $target->input()->orderBy('id')->get()->map($attributes)->all();
        }
        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function normalized(mixed $value): string
    {
        $value = trim((string) $value);
        if (is_numeric($value)) {
            return (string) (float) $value;
        }
        $value = preg_replace('/\s+/', ' ', str_replace('T', ' ', $value));
        return preg_replace('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}):00$/', '$1', $value);
    }

    private function originalReview(Model $target): array
    {
        $data = [];
        if ($target instanceof Mezcla) {
            $source = $target->solicitud;
            $aliases = ['paciente_nombre' => 'nombre_paciente', 'registro' => 'registro_paciente',
                'medico_nombre' => 'nombre_medico', 'medico_cedula' => 'cedula_medico'];
            foreach (['paciente_nombre', 'servicio', 'registro', 'sexo', 'fecha_nacimiento', 'peso', 'piso', 'cama',
                'diagnostico', 'medico_nombre', 'medico_cedula', 'fecha_entrega', 'observaciones'] as $key) {
                $value = $key === 'fecha_entrega' ? ($target->fecha_entrega ?? $source->fecha_entrega) : $source->{$aliases[$key] ?? $key};
                $data[self::LABELS[$key]] = $this->displayValue($value, $key);
            }
            $data['Volumen de dilucion (ml)'] = (string) $target->volumen_dilucion;
            $data['Tiempo de infusion'] = (string) $target->tiempo_infusion;
            $data['Set de infusion'] = $target->set_infusion ? 'Si' : 'No';
            $data['Infusor (ID)'] = (string) $target->infusor_id;
            foreach ($target->medicamentos()->with(['medicamentoOnco.catalog', 'diluyente', 'viaAdministracion'])->orderBy('id')->get() as $index => $medicine) {
                $name = $medicine->medicamentoOnco?->catalog?->denominacion ?? $medicine->nombre_medicamento;
                $data['Medicamento '.($index + 1)] = $name.'; dosis: '.(float) $medicine->dosis.' mg; diluyente: '.$medicine->diluyente?->denominacion_generica.'; via: '.$medicine->viaAdministracion?->name;
                $data['Medicamento '.($index + 1).' / cobro'] = ($medicine->charge_by ?? 'mg').'; precio por mg: '.($medicine->precio_mg_snapshot ?? '');
            }
        } else {
            $patient = $target->solicitud_patient;
            $detail = $target->solicitud_detail;
            foreach (self::LABELS as $key => $label) {
                if (in_array($key, ['paciente_nombre', 'medico_nombre', 'medico_cedula', 'fecha_entrega'], true)) {
                    continue;
                }
                $data[$label] = $this->displayValue($patient->getAttribute($key) ?? $detail->getAttribute($key), $key);
            }
            foreach ($target->input()->with('input')->get() as $input) {
                if ((int) $input->input->category_id === 6) {
                    $data[self::LABELS['bolsa_eva']] = (string) $input->input_id;
                } else {
                    $data[$input->input->description] = (float) $input->valor.' '.$input->input->unidad;
                }
                $data[$input->input->description.' / presentacion, lote y caducidad'] = implode(' / ', [
                    $input->nutrition_medicine_presentation_id, $input->lote, $input->caducidad?->format('Y-m-d'),
                ]);
            }
        }
        return $data;
    }

    private function displayValue(mixed $value, string $key): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($key === 'fecha_nacimiento' ? 'Y-m-d' : 'Y-m-d\\TH:i');
        }
        return (string) ($value ?? '');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['ajuste' => $message]);
    }
}
