<?php

namespace App\Livewire\Nutricionales;

use App\Models\Nutricionales\InspeccionNutricional as NutricionalesInspeccionNutricional;
use App\Models\Nutricionales\Solicitud;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class InspeccionNutricional extends Component
{
    public $mostrarModalInspeccion = false;

    public $solicitudId;
    public $lote_mezcla = '';
    #[\Livewire\Attributes\Locked]
    public string $mixtureContext = '';

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

    public $dosis_volumen;
    public $peso_mezcla;
    public $observaciones = '';

    public $reviso_nombre = '';
    public $aprobo_nombre = '';

    private function nombreUsuarioActual(): string
    {
        $user = Auth::user();
        $nombreCompleto = trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? ''));

        return $user?->username
            ?: ($nombreCompleto !== '' ? $nombreCompleto : '');
    }

    public function mount()
    {
        $this->reviso_nombre = $this->nombreUsuarioActual();
        $this->aprobo_nombre = $this->nombreUsuarioActual();
        $this->observaciones = 'N.A.';
    }

    #[On('abrir-modal-inspeccion-nutricional')]
    public function abrirModalInspeccion($solicitudId)
    {
        if (is_array($solicitudId)) {
            $solicitudId = $solicitudId[0] ?? null;
        }

        if (!$solicitudId) {
            return;
        }

        $this->solicitudId = (int) $solicitudId;
        $this->mostrarModalInspeccion = true;
        $solicitud = Solicitud::with('user.hospital.instituciones')->find($this->solicitudId);
        $this->lote_mezcla = (string) ($solicitud?->lote ?? '');
        $this->mixtureContext = \App\Support\MixtureWorkflowContext::label($this->solicitudId, $solicitud?->user?->hospital);

        $ins = NutricionalesInspeccionNutricional::where('solicitud_id', $this->solicitudId)->first();

        if ($ins) {
            $this->es_limpia = (int) $ins->es_limpia;
            $this->es_libre = (int) $ins->es_libre;
            $this->tipo_contenedor = (string) ($ins->tipo_contenedor ?? '');

            $this->esta_rotulado = (int) $ins->esta_rotulado;
            $this->numero_lote = (int) $ins->numero_lote;
            $this->medicamento = (int) $ins->medicamento;
            $this->dosis_volumen_total = (int) $ins->dosis_volumen_total;
            $this->volumen_medicamento = (int) $ins->volumen_medicamento;
            $this->rubrica_preparador = (int) $ins->rubrica_preparador;
            $this->sello_seguridad = (int) $ins->sello_seguridad;
            $this->presenta_grietas = (int) $ins->presenta_grietas;
            $this->presenta_fugas = (int) $ins->presenta_fugas;
            $this->esta_roto = (int) $ins->esta_roto;

            $this->coloracion_apropiada = (int) $ins->coloracion_apropiada;
            $this->contenido_homogeneo = (int) $ins->contenido_homogeneo;
            $this->presenta_particulas = (int) $ins->presenta_particulas;
            $this->presenta_turbidez = (int) $ins->presenta_turbidez;
            $this->volumen_correcto = (int) $ins->volumen_correcto;
            $this->aprueba_contenido = (int) $ins->aprueba_contenido;
            $this->aprueba_contenedor = (int) $ins->aprueba_contenedor;
            $this->mezcla_aprobada = (int) $ins->mezcla_aprobada;

            $this->dosis_volumen = $ins->dosis_volumen;
            $this->peso_mezcla = $ins->peso_mezcla;
            $this->observaciones = $ins->observaciones ?? 'N.A.';

            $this->reviso_nombre = $ins->reviso_nombre ?: ($this->reviso_nombre ?: $this->nombreUsuarioActual());
            $this->aprobo_nombre = $ins->aprobo_nombre ?: ($this->aprobo_nombre ?: $this->nombreUsuarioActual());
        } else {
            $this->reviso_nombre = $this->nombreUsuarioActual();
            $this->aprobo_nombre = $this->nombreUsuarioActual();
            $this->observaciones = 'N.A.';
        }
    }

    public function guardarInspeccion()
    {
        $this->reviso_nombre = $this->reviso_nombre ?: $this->nombreUsuarioActual();
        $this->aprobo_nombre = $this->aprobo_nombre ?: $this->nombreUsuarioActual();

        $this->validate([
            'tipo_contenedor' => 'nullable|string|in:Frasco,Bolsa,Jeringa,Infusor',
            'dosis_volumen' => 'required|numeric|gt:0',
            'peso_mezcla' => 'required|numeric|gt:0',
            'observaciones' => 'nullable|string',
            'reviso_nombre' => 'required|string|max:255',
            'aprobo_nombre' => 'required|string|max:255',
        ], [
            'dosis_volumen.required' => 'El campo dosis / volumen total es obligatorio.',
            'dosis_volumen.numeric' => 'El campo dosis / volumen total debe ser numerico.',
            'dosis_volumen.gt' => 'El campo dosis / volumen total debe ser mayor a 0.',
            'peso_mezcla.required' => 'El campo peso de la mezcla es obligatorio.',
            'peso_mezcla.numeric' => 'El campo peso de la mezcla debe ser numerico.',
            'peso_mezcla.gt' => 'El campo peso de la mezcla debe ser mayor a 0.',
        ]);

        $solicitud = Solicitud::findOrFail($this->solicitudId);

        if ($solicitud->estado !== 'preparada') {
            $this->addError('estado', 'Solo una solicitud preparada puede inspeccionarse.');
            return;
        }

        NutricionalesInspeccionNutricional::updateOrCreate(
            ['solicitud_id' => $this->solicitudId],
            [
                'es_limpia' => (bool) $this->es_limpia,
                'es_libre' => (bool) $this->es_libre,
                'fecha_inspeccion' => now()->toDateString(),
                'hora_inspeccion' => now()->format('H:i:s'),
                'tipo_contenedor' => $this->tipo_contenedor ?: null,
                'esta_rotulado' => (bool) $this->esta_rotulado,
                'numero_lote' => (bool) $this->numero_lote,
                'medicamento' => (bool) $this->medicamento,
                'dosis_volumen_total' => (bool) $this->dosis_volumen_total,
                'volumen_medicamento' => (bool) $this->volumen_medicamento,
                'rubrica_preparador' => (bool) $this->rubrica_preparador,
                'sello_seguridad' => (bool) $this->sello_seguridad,
                'presenta_grietas' => (bool) $this->presenta_grietas,
                'presenta_fugas' => (bool) $this->presenta_fugas,
                'esta_roto' => (bool) $this->esta_roto,
                'coloracion_apropiada' => (bool) $this->coloracion_apropiada,
                'contenido_homogeneo' => (bool) $this->contenido_homogeneo,
                'presenta_particulas' => (bool) $this->presenta_particulas,
                'presenta_turbidez' => (bool) $this->presenta_turbidez,
                'volumen_correcto' => (bool) $this->volumen_correcto,
                'aprueba_contenido' => (bool) $this->aprueba_contenido,
                'aprueba_contenedor' => (bool) $this->aprueba_contenedor,
                'dosis_volumen' => $this->dosis_volumen,
                'peso_mezcla' => $this->peso_mezcla,
                'mezcla_aprobada' => (bool) $this->mezcla_aprobada,
                'observaciones' => $this->observaciones ?: 'N.A.',
                'reviso_nombre' => $this->reviso_nombre,
                'aprobo_nombre' => $this->aprobo_nombre,
            ]
        );

        $solicitud->estado = 'revisada';
        $solicitud->save();

        $this->mostrarModalInspeccion = false;

        $this->dispatch('nutricional-inspeccionada');
    }

    public function render()
    {
        return view('livewire.nutricionales.inspeccion-nutricional');
    }
}
