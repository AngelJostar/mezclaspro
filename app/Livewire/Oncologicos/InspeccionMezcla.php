<?php

namespace App\Livewire\Oncologicos;

use App\Models\Oncologicos\InspeccionMezcla as OncologicosInspeccionMezcla;
use App\Models\Oncologicos\Mezcla;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class InspeccionMezcla extends Component
{
    public $mostrarModalInspeccion = false;

    public $mezclaId;

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

    #[On('abrir-modal-inspeccion')]
    public function abrirModalInspeccion($mezclaId)
    {
        // si llega como array [id]
        if (is_array($mezclaId)) {
            $mezclaId = $mezclaId[0] ?? null;
        }

        if (!$mezclaId) return;

        $this->mezclaId = (int) $mezclaId;
        $this->mostrarModalInspeccion = true;

        // Hidratar con la inspección existente (creada en "Aprobar")
        $ins = OncologicosInspeccionMezcla::where('mezcla_id', $mezclaId)->first();

        if ($ins) {
            // checks
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
            $this->peso_mezcla   = $ins->peso_mezcla;
            $this->observaciones = $ins->observaciones ?? 'N.A.';

            // No pisar si ya existen en BD
            $this->reviso_nombre = $ins->reviso_nombre ?: ($this->reviso_nombre ?: $this->nombreUsuarioActual());
            $this->aprobo_nombre = $ins->aprobo_nombre ?: ($this->aprobo_nombre ?: $this->nombreUsuarioActual());
        } else {
            // Defaults si por alguna razón aún no existe
            if (blank($this->reviso_nombre)) {
                $this->reviso_nombre = $this->nombreUsuarioActual();
            }
            if (blank($this->aprobo_nombre)) {
                $this->aprobo_nombre = $this->nombreUsuarioActual();
            }
        }
    }

    public function guardarInspeccion()
    {
        // Refuerza valores de nombres visibles (sin tocar preparo/libero)
        $this->reviso_nombre = $this->reviso_nombre ?: $this->nombreUsuarioActual();
        $this->aprobo_nombre = $this->aprobo_nombre ?: $this->nombreUsuarioActual();

        $this->validate([
            'es_limpia' => 'required|boolean',
            'es_libre' => 'required|boolean',
            'tipo_contenedor' => 'nullable|string|in:Frasco,Bolsa,Jeringa,Otro',
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

        // Cargar existente o crear en memoria; SIEMPRE refrescamos fecha/hora
        $ins = OncologicosInspeccionMezcla::firstOrNew(['mezcla_id' => $this->mezclaId]);

        // ⬅️ siempre actualizar fecha/hora al momento de guardar
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

        // Firmas del modal (NO tocar preparo/libero que vienen del paso "aprobar")
        $ins->reviso_nombre = $this->reviso_nombre;
        $ins->aprobo_nombre = $this->aprobo_nombre;

        $ins->save();

        // Estado de la mezcla tras guardar inspección
        Mezcla::where('id', $this->mezclaId)->update(['estado' => 'revisada']);

        $this->mostrarModalInspeccion = false;
        $this->dispatch('mezcla-inspeccionada');
    }


    public function render()
    {
        return view('livewire.oncologicos.inspeccion-mezcla');
    }
}
