<?php

namespace App\Livewire\Oncologicos;

use App\Models\User;
use App\Models\Oncologicos\InspeccionMezcla as OncologicosInspeccionMezcla;
use App\Models\Oncologicos\Mezcla;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use App\Services\InspectionRejectionService;

class InspeccionMezcla extends Component
{
    private const APPROVER_POSITIONS = [
        'Responsable sanitario',
        'Auxiliar de responsable sanitario',
    ];

    private const CONTENT_DEFAULTS = [
        'esta_rotulado' => 1,
        'medicamento' => 1,
        'volumen_medicamento' => 1,
        'sello_seguridad' => 1,
        'esta_roto' => 0,
        'contenido_homogeneo' => 1,
        'presenta_turbidez' => 0,
        'aprueba_contenedor' => 1,
        'numero_lote' => 1,
        'dosis_volumen_total' => 1,
        'rubrica_preparador' => 1,
        'presenta_fugas' => 0,
        'coloracion_apropiada' => 1,
        'presenta_particulas' => 0,
        'aprueba_contenido' => 1,
    ];

    public $mostrarModalInspeccion = false;

    #[\Livewire\Attributes\Locked]
    public bool $mostrarModalRechazo = false;
    #[\Livewire\Attributes\Locked]
    public bool $rechazoGuardado = false;
    public string $motivoRechazo = '';

    #[\Livewire\Attributes\Locked]
    public $mezclaId;
    #[\Livewire\Attributes\Locked]
    public int $productionAttempt = 1;
    public $lote_mezcla = '';
    #[\Livewire\Attributes\Locked]
    public string $mixtureContext = '';
    #[\Livewire\Attributes\Locked]
    public array $inspectionSummary = [];

    // checks
    public $es_limpia = 0;
    public $es_libre = 0;
    public $tipo_contenedor = '';
    public $esta_rotulado = 0;
    public $numero_lote = 0;
    public $medicamento = 0;
    public $dosis_volumen_total = 0;
    public $volumen_medicamento = 0;
    public $rubrica_preparador = 0;
    public $sello_seguridad = 0;
    public $presenta_grietas = 0;
    public $presenta_fugas = 0;
    public $esta_roto = 0;

    public $coloracion_apropiada = 0;
    public $contenido_homogeneo = 0;
    public $presenta_particulas = 0;
    public $presenta_turbidez = 0;
    public $volumen_correcto = 0;
    public $aprueba_contenido = 0;
    public $aprueba_contenedor = 0;
    public $mezcla_aprobada = 0;

    // num/texto
    public $dosis_volumen;
    public $peso_mezcla;
    public $observaciones = '';

    // firmas visibles en el modal
    public $reviso_nombre = '';
    public $aprobo_nombre = '';
    public $aprobadores = [];

    private function nombreUsuarioActual(): string
    {
        $user = Auth::user();
        $nombreCompleto = trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? ''));

        return $user?->username
            ?: ($nombreCompleto !== '' ? $nombreCompleto : '');
    }

    private function cargarAprobadores(): void
    {
        $aprobadores = User::query()
            ->whereHas('personnelProfile', function ($query) {
                $query->where(function ($positionsQuery) {
                    foreach (self::APPROVER_POSITIONS as $position) {
                        $positionsQuery->orWhereJsonContains('positions', $position);
                    }
                });
            })
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();

        // Los perfiles de personal son la fuente oficial. Durante la transición,
        // mantiene disponibles solo las dos personas sanitarias ya autorizadas.
        if ($aprobadores->isEmpty()) {
            $aprobadores = User::query()
                ->whereIn('username', ['gcortes', 'hcarbajal'])
                ->orderBy('name')
                ->orderBy('lastname')
                ->get();
        }

        $this->aprobadores = $aprobadores
            ->mapWithKeys(function (User $usuario) {
                $nombreCompleto = trim(($usuario->name ?? '') . ' ' . ($usuario->lastname ?? ''));
                $valor = trim((string) ($usuario->username ?: $nombreCompleto));
                $etiqueta = $nombreCompleto !== '' ? $nombreCompleto : $valor;

                return [$valor => $valor !== '' ? "{$etiqueta} ({$valor})" : $etiqueta];
            })
            ->filter(fn ($etiqueta, $valor) => $valor !== '')
            ->all();
    }

    public function mount()
    {
        $this->cargarAprobadores();
        $this->reviso_nombre = $this->nombreUsuarioActual();
        $this->observaciones = 'N.A.';
    }

    private function cargarResumen(Mezcla $mezcla): void
    {
        $solicitud = $mezcla->solicitud;
        $text = static fn ($value) => trim((string) $value) !== '' ? trim((string) $value) : '—';
        $quantity = static fn ($value, string $unit) => is_numeric($value)
            ? number_format((float) $value, 2).' '.$unit : '—';

        $this->inspectionSummary = [
            'destination' => \App\Support\MixtureWorkflowContext::destination($solicitud?->hospital),
            'type' => $solicitud?->tipo_solicitud === 'antibioticos' ? 'Antibiótica' : 'Oncológica',
            'patient' => [
                'name' => $text($solicitud?->nombre_paciente),
                'record' => $text($solicitud?->registro_paciente),
                'sex' => match (strtoupper((string) $solicitud?->sexo)) {
                    'M' => 'Masculino', 'F' => 'Femenino', default => $text($solicitud?->sexo),
                },
                'age' => $solicitud?->edad !== null ? $solicitud->edad.' años' : '—',
                'weight' => $quantity($solicitud?->peso, 'kg'),
                'service' => $text($solicitud?->servicio),
                'location' => $text(collect([$solicitud?->piso, $solicitud?->cama])->filter(fn ($value) => filled($value))->implode(' / ')),
                'doctor' => $text($solicitud?->nombre_medico),
            ],
            'total_volume' => $quantity($mezcla->volumen_dilucion, 'mL'),
            'medications' => $mezcla->medicamentos->map(fn ($medication) => [
                'name' => $text($medication->denominacion_snapshot ?: $medication->nombre_medicamento),
                'dose' => $quantity($medication->dosis, 'mg'),
                'volume' => $quantity($medication->dosis_ml, 'mL'),
                'diluent' => $text($medication->diluyente?->denominacion_generica
                    ?: $mezcla->diluentPresentation?->diluent?->denominacion_generica),
                'route' => $text($medication->viaAdministracion?->name),
            ])->all(),
        ];
    }

    #[On('abrir-modal-inspeccion')]
    public function abrirModalInspeccion($mezclaId)
    {
        // si llega como array [id]
        if (is_array($mezclaId)) {
            $mezclaId = $mezclaId[0] ?? null;
        }

        if (!$mezclaId) return;

        $mezcla = Mezcla::with([
            'solicitud.hospital.instituciones', 'medicamentos.diluyente',
            'medicamentos.viaAdministracion', 'diluentPresentation.diluent',
        ])->findOrFail((int) $mezclaId);
        $user = Auth::user();
        abort_unless($user?->hasAnyRole(['Admin', 'Super Admin'])
            || ((int) $user?->hospital_id > 0 && (int) $user->hospital_id === (int) $mezcla->solicitud?->hospital_id), 403);

        $this->resetExcept('aprobadores');
        $this->reviso_nombre = $this->nombreUsuarioActual();
        $this->observaciones = 'N.A.';
        $this->mezclaId = (int) $mezclaId;
        $this->mostrarModalInspeccion = true;
        $this->productionAttempt = (int) ($mezcla?->production_attempt ?? 1);
        $this->resetValidation();
        $this->lote_mezcla = (string) ($mezcla?->lote ?? '');
        $this->mixtureContext = \App\Support\MixtureWorkflowContext::label($this->mezclaId, $mezcla?->solicitud?->hospital);
        $this->cargarResumen($mezcla);

        // Hidratar con la inspección existente (creada en "Aprobar")
        $ins = OncologicosInspeccionMezcla::where('mezcla_id', $mezclaId)->first();

        if ($ins) {
            // checks
            $this->es_limpia = (int) $ins->es_limpia;
            $this->es_libre = (int) $ins->es_libre;
            $this->tipo_contenedor = (string) ($ins->tipo_contenedor ?? '');
            $this->esta_rotulado = (bool) $ins->esta_rotulado;
            $this->numero_lote = (bool) $ins->numero_lote;
            $this->medicamento = (bool) $ins->medicamento;
            $this->dosis_volumen_total = (bool) $ins->dosis_volumen_total;
            $this->volumen_medicamento = (bool) $ins->volumen_medicamento;
            $this->rubrica_preparador = (bool) $ins->rubrica_preparador;
            $this->sello_seguridad = (bool) $ins->sello_seguridad;
            $this->presenta_grietas = (int) $ins->presenta_grietas;
            $this->presenta_fugas = (bool) $ins->presenta_fugas;
            $this->esta_roto = (bool) $ins->esta_roto;

            $this->coloracion_apropiada = (bool) $ins->coloracion_apropiada;
            $this->contenido_homogeneo = (bool) $ins->contenido_homogeneo;
            $this->presenta_particulas = (bool) $ins->presenta_particulas;
            $this->presenta_turbidez = (bool) $ins->presenta_turbidez;
            $this->volumen_correcto = (int) $ins->volumen_correcto;
            $this->aprueba_contenido = (bool) $ins->aprueba_contenido;
            $this->aprueba_contenedor = (bool) $ins->aprueba_contenedor;
            $this->mezcla_aprobada = (int) $ins->mezcla_aprobada;

            $this->dosis_volumen = $ins->dosis_volumen;
            $this->peso_mezcla   = $ins->peso_mezcla;
            $this->observaciones = $ins->observaciones ?? 'N.A.';

            // No pisar si ya existen en BD
            $this->reviso_nombre = $ins->reviso_nombre ?: ($this->reviso_nombre ?: $this->nombreUsuarioActual());
            $this->aprobo_nombre = $ins->aprobo_nombre ?: '';
        } else {
            // Defaults si por alguna razón aún no existe
            if (blank($this->reviso_nombre)) {
                $this->reviso_nombre = $this->nombreUsuarioActual();
            }
            $this->aprobo_nombre = '';
        }

        // La fila se crea antes de inspeccionar; no sustituir respuestas ya firmadas.
        if (! $ins || (blank($ins->reviso_nombre) && blank($ins->aprobo_nombre)
            && ! $ins->mezcla_aprobada && blank($ins->fecha_aprobacion))) {
            foreach (self::CONTENT_DEFAULTS as $field => $value) {
                $this->{$field} = (bool) $value;
            }
        }
    }

    public function guardarInspeccion()
    {
        $this->registrarResultado(true);
    }

    public function rechazarInspeccion()
    {
        Gate::authorize('oncologicos_mezclas_update');
        if (! $this->mostrarModalInspeccion) return;

        $this->resetValidation();
        $this->motivoRechazo = '';
        $this->mostrarModalRechazo = true;
    }

    public function cancelarRechazo(): void
    {
        $this->mostrarModalRechazo = false;
        $this->motivoRechazo = '';
        $this->resetValidation();
    }

    public function guardarRechazo(): void
    {
        Gate::authorize('oncologicos_mezclas_update');
        $this->motivoRechazo = trim($this->motivoRechazo);
        $this->validate(['motivoRechazo' => 'required|string|max:2000'], [
            'motivoRechazo.required' => 'Registra el motivo del rechazo.',
            'motivoRechazo.max' => 'El motivo no debe exceder los 2000 caracteres.',
        ]);
        if (in_array(mb_strtolower($this->motivoRechazo), ['n.a.', 'n.a', 'na', 'n/a'], true)) {
            throw ValidationException::withMessages(['motivoRechazo' => 'Registra el motivo del rechazo.']);
        }
        $this->registrarResultado(false);
    }

    public function volverASolicitudes(): void
    {
        if ($this->rechazoGuardado) {
            $this->redirectRoute('admin.solicitudes.index');
        }
    }

    private function registrarResultado(bool $approved): void
    {
        Gate::authorize('oncologicos_mezclas_update');
        $this->mezcla_aprobada = $approved ? 1 : 0;
        $this->reviso_nombre = $this->nombreUsuarioActual();
        if (! $approved) {
            $this->aprobo_nombre = '';
            $this->dosis_volumen = $this->dosis_volumen ?: 0;
            $this->peso_mezcla = $this->peso_mezcla ?: 0;
        }

        // Refuerza valores de nombres visibles (sin tocar preparo/libero)
        $this->reviso_nombre = $this->reviso_nombre ?: $this->nombreUsuarioActual();

        $this->validate([
            'tipo_contenedor' => 'nullable|string|in:Frasco,Bolsa,Jeringa,Infusor,Otro',
            'esta_rotulado' => 'required|boolean',
            'numero_lote' => 'required|boolean',
            'medicamento' => 'required|boolean',
            'dosis_volumen_total' => 'required|boolean',
            'volumen_medicamento' => 'required|boolean',
            'rubrica_preparador' => 'required|boolean',
            'sello_seguridad' => 'required|boolean',
            'presenta_grietas' => 'required|boolean',
            'presenta_fugas' => 'required|boolean',
            'esta_roto' => 'required|boolean',
            'coloracion_apropiada' => 'required|boolean',
            'contenido_homogeneo' => 'required|boolean',
            'presenta_particulas' => 'required|boolean',
            'presenta_turbidez' => 'required|boolean',
            'volumen_correcto' => 'required|boolean',
            'aprueba_contenido' => 'required|boolean',
            'aprueba_contenedor' => 'required|boolean',
            'mezcla_aprobada' => 'required|boolean',
            'dosis_volumen' => $approved ? 'required|numeric|gt:0' : 'required|numeric|min:0',
            'peso_mezcla' => $approved ? 'required|numeric|gt:0' : 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
            'reviso_nombre' => 'required|string|max:255',
            'aprobo_nombre' => 'nullable|required_if:mezcla_aprobada,1|string|max:255',
        ], [
            'dosis_volumen.required' => 'El campo dosis / volumen total es obligatorio.',
            'dosis_volumen.numeric' => 'El campo dosis / volumen total debe ser numerico.',
            'dosis_volumen.gt' => 'El campo dosis / volumen total debe ser mayor a 0.',
            'peso_mezcla.required' => 'El campo peso de la mezcla es obligatorio.',
            'peso_mezcla.numeric' => 'El campo peso de la mezcla debe ser numerico.',
            'peso_mezcla.gt' => 'El campo peso de la mezcla debe ser mayor a 0.',
            'aprobo_nombre.required_if' => 'Selecciona a la persona que aprobó la mezcla.',
        ]);

        DB::transaction(fn () => $this->persistResult($approved));

        $this->mostrarModalInspeccion = false;
        if ($approved) {
            $this->dispatch('mezcla-inspeccionada');
        } else {
            $this->mostrarModalRechazo = false;
            $this->rechazoGuardado = true;
        }
    }

    private function persistResult(bool $approved): void
    {
        $mezcla = Mezcla::query()->lockForUpdate()->findOrFail($this->mezclaId);
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['Admin', 'Super Admin'])
            || ((int) $user->hospital_id > 0 && (int) $user->hospital_id === (int) $mezcla->solicitud?->hospital_id), 403);
        if ($mezcla->operational_status !== 'preparada'
            || (int) $mezcla->production_attempt !== $this->productionAttempt) {
            throw ValidationException::withMessages(['mezclaId' => 'La mezcla cambió de proceso. Cierra y vuelve a abrir la inspección.']);
        }

        // Cargar existente o crear en memoria.
        $ins = OncologicosInspeccionMezcla::firstOrNew(['mezcla_id' => $this->mezclaId]);

        // La inspección solo queda fechada cuando realmente se guarda desde este modal.
        $ins->fecha_inspeccion = now()->toDateString();
        $ins->hora_inspeccion  = now()->format('H:i:s');

        // Checks y campos
        $ins->es_limpia = (bool) $this->es_limpia;
        $ins->es_libre = (bool) $this->es_libre;
        $ins->tipo_contenedor = $this->tipo_contenedor ?: null;
        $ins->tipo_contenedor_otro = $this->tipo_contenedor === 'Otro' ? 'Otro' : null;

        $ins->esta_rotulado = (bool) $this->esta_rotulado;
        $ins->numero_lote = (bool) $this->numero_lote;
        $ins->medicamento = (bool) $this->medicamento;
        $ins->dosis_volumen_total = (bool) $this->dosis_volumen_total;
        $ins->volumen_medicamento = (bool) $this->volumen_medicamento;
        $ins->rubrica_preparador = (bool) $this->rubrica_preparador;
        $ins->sello_seguridad = (bool) $this->sello_seguridad;
        $ins->presenta_grietas = (bool) $this->presenta_grietas;
        $ins->presenta_fugas = (bool) $this->presenta_fugas;
        $ins->esta_roto = (bool) $this->esta_roto;

        $ins->coloracion_apropiada = (bool) $this->coloracion_apropiada;
        $ins->contenido_homogeneo = (bool) $this->contenido_homogeneo;
        $ins->presenta_particulas = (bool) $this->presenta_particulas;
        $ins->presenta_turbidez = (bool) $this->presenta_turbidez;
        $ins->volumen_correcto = (bool) $this->volumen_correcto;
        $ins->aprueba_contenido = (bool) $this->aprueba_contenido;
        $ins->aprueba_contenedor = (bool) $this->aprueba_contenedor;

        $ins->dosis_volumen = $this->dosis_volumen;
        $ins->peso_mezcla   = $this->peso_mezcla;
        $ins->mezcla_aprobada = (bool) $this->mezcla_aprobada;
        $ins->observaciones = $this->observaciones;

        // Firmas del modal
        $ins->reviso_nombre = $this->reviso_nombre;
        if ((bool) $this->mezcla_aprobada) {
            $ins->aprobo_nombre = $this->aprobo_nombre;
            $ins->fecha_aprobacion = now()->toDateString();
            $ins->hora_aprobacion = now()->format('H:i:s');
        } else {
            $ins->aprobo_nombre = '';
            $ins->fecha_aprobacion = null;
            $ins->hora_aprobacion = null;
        }

        $ins->save();

        // Guardar mediante el modelo mantiene sincronizado el estado de la solicitud.
        if ($approved) {
            $mezcla->update(['estado' => 'revisada']);
        } else {
            app(InspectionRejectionService::class)->reject($mezcla, $ins, $this->motivoRechazo);
        }
    }


    public function render()
    {
        return view('livewire.oncologicos.inspeccion-mezcla');
    }
}
