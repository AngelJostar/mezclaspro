<?php

namespace App\Http\Controllers\Admin\Nutricionales;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\Medicine;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudInput;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelIgnition\Recorders\DumpRecorder\Dump;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SolicitudesExport;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\MedicineStockMovement;
use App\Models\Nutricionales\InspeccionNutricional;
use App\Models\Nutricionales\NutritionMedicinePresentation;

class SolicitudController extends Controller
{


    /**
     * Display a listing of the resource.
     */ public function index()
    {


        $user = Auth::user(); // Obtener el usuario actual
        $role = $user->roles[0]->name;
        if ($role === 'Admin' or $role === 'Super Admin') {
            // Si el usuario es un administrador, cargar todas las solicitudes
            $solicitudes = Solicitud::with('user', 'solicitud_detail', 'solicitud_patient', 'input', 'user.hospital')
                ->latest()
                ->paginate(30);
        } elseif (in_array($role, ['Cliente', 'Institucion'], true)) {
            // Si el usuario es un cliente, cargar solo sus propias solicitudes
            $solicitudes = Solicitud::where('user_id', $user->id)
                ->with('user', 'solicitud_detail', 'solicitud_patient', 'input', 'user.hospital')
                ->latest()
                ->paginate(10);
            //return $solicitudes;
        }

        return view('admin.nutricionales.solicitudes.index', compact('solicitudes'));
    }



    function calcularEdad($fechaNacimiento)
    {
        $fechaNacimiento = new DateTime($fechaNacimiento);
        $fechaActual = new DateTime();

        $edadAnios = $fechaActual->diff($fechaNacimiento)->y;
        $edadMeses = $fechaActual->diff($fechaNacimiento)->m;
        $edadDias = $fechaActual->diff($fechaNacimiento)->d;

        if ($edadDias < 0) {
            $edadMeses--;
            $ultimoDiaMesAnterior = $fechaActual->sub(new DateInterval('P1M'))->format('t');
            $edadDias = $ultimoDiaMesAnterior + $edadDias;
        }

        if ($edadMeses < 0) {
            $edadAnios--;
            $edadMeses = 12 + $edadMeses;
        }

        $edad = '';

        if ($edadAnios > 0) {
            $edad .= $edadAnios . ' año(s) ';
        }

        if ($edadMeses > 0) {
            $edad .= $edadMeses . ' mes(es) ';
        }

        if ($edadDias > 0) {
            $edad .= $edadDias . ' día(s)';
        }

        return $edad;
    }

    private function nombreUsuarioActual(): string
    {
        return $this->nombreUsuario(auth()->user());
    }

    private function nombreUsuario(?User $user): string
    {
        $nombreCompleto = trim(($user?->name ?? '') . ' ' . ($user?->lastname ?? ''));

        return $user?->username
            ?: ($nombreCompleto !== '' ? $nombreCompleto : ($user?->email ?? 'Usuario'));
    }

    private function nombreUsuarioDesdeTexto(?string $nombre): ?string
    {
        $nombre = trim((string) $nombre);

        if ($nombre === '') {
            return null;
        }

        $usuario = User::query()
            ->select('username')
            ->where('username', $nombre)
            ->orWhere('name', $nombre)
            ->orWhereRaw("TRIM(CONCAT(COALESCE(name, ''), ' ', COALESCE(lastname, ''))) = ?", [$nombre])
            ->first();

        return $usuario?->username ?: $nombre;
    }

    private function obtenerOCrearInspeccionNutricional(Solicitud $solicitud): InspeccionNutricional
    {
        return InspeccionNutricional::firstOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'fecha_inspeccion' => now()->toDateString(),
                'hora_inspeccion' => now()->format('H:i:s'),
                'aprobo_nombre' => null,
                'preparo_nombre' => null,
                'reviso_nombre' => null,
                'libero_nombre' => null,
            ]
        );
    }

    private function generarLoteNutricional(Solicitud $solicitud): void
    {
        if (!empty($solicitud->lote)) {
            return;
        }

        $conteoHoy = Solicitud::whereDate('created_at', today())
            ->whereNotNull('lote')
            ->lockForUpdate()
            ->count();

        $numeroFormateado = str_pad($conteoHoy + 1, 3, '0', STR_PAD_LEFT);

        $solicitud->lote = 'L' . now()->format('dmy') . $numeroFormateado;
    }

    private function generarRemisionNutricional(Solicitud $solicitud): void
    {
        if (!empty($solicitud->remision)) {
            return;
        }

        $maxRemision = Solicitud::whereNotNull('remision')
            ->lockForUpdate()
            ->max(DB::raw('CAST(remision AS UNSIGNED)'));

        $solicitud->remision = (string) (((int) $maxRemision) + 1);
    }

    private function devolverStockSolicitud(Solicitud $solicitud): void
    {
        $movimientosSalida = MedicineStockMovement::where('reference_type', 'Solicitud')
            ->where('reference_id', $solicitud->id)
            ->where('tipo', 'salida')
            ->get();

        foreach ($movimientosSalida as $movimiento) {
            $stock = MedicineLaboratoryStock::where('id', $movimiento->medicine_laboratory_stock_id)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                continue;
            }

            $stockAntes = (float) $stock->stock_ml_actual;
            $frascosAntes = (float) $stock->frascos_actuales;

            $cantidadMl = (float) $movimiento->cantidad_ml;
            $cantidadFrascos = (float) ($movimiento->cantidad_frascos ?? 0);

            $stockDespues = $stockAntes + $cantidadMl;
            $frascosDespues = $frascosAntes + $cantidadFrascos;

            $stock->update([
                'stock_ml_actual' => $stockDespues,
                'frascos_actuales' => $frascosDespues,
                'is_active' => true,
            ]);

            MedicineStockMovement::create([
                'medicine_laboratory_stock_id' => $stock->id,
                'user_id' => auth()->id(),
                'tipo' => 'entrada',
                'cantidad_ml' => $cantidadMl,
                'cantidad_frascos' => $cantidadFrascos,
                'stock_antes' => $stockAntes,
                'stock_despues' => $stockDespues,
                'frascos_antes' => $frascosAntes,
                'frascos_despues' => $frascosDespues,
                'reference_type' => 'SolicitudCancelada',
                'reference_id' => $solicitud->id,
                'notes' => 'Devolución automática de inventario por cancelación de solicitud nutricional',
            ]);
        }
    }

    private function solicitudYaTieneDevolucionInventario(Solicitud $solicitud): bool
    {
        return MedicineStockMovement::where('reference_type', 'SolicitudCancelada')
            ->where('reference_id', $solicitud->id)
            ->exists();
    }

    public function cancelar(Solicitud $solicitud)
    {
        DB::beginTransaction();

        try {

            $solicitud->loadMissing('user.hospital');

            if (
                $solicitud->estado === 'aprobada' ||
                $solicitud->estado === 'preparada' ||
                $solicitud->estado === 'revisada'
            ) {

                if (!$this->solicitudYaTieneDevolucionInventario($solicitud)) {
                    $this->devolverStockSolicitud($solicitud);
                }
            }

            // CLIENTE
            if (auth()->user()->hasAnyRole(['Cliente', 'Institucion'])) {

                if ($solicitud->estado !== 'pendiente') {
                    throw new \Exception('Solo puedes cancelar solicitudes pendientes.');
                }

                $solicitud->estado = 'cancelada';
            }

            // ADMIN / SUPER ADMIN
            else {

                $solicitud->estado = 'no_aprobada';
            }

            $solicitud->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Solicitud actualizada',
                'text' => 'La solicitud fue actualizada correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Throwable $e) {

            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function preparar(Solicitud $solicitud)
    {
        DB::beginTransaction();

        try {
            if ($solicitud->estado !== 'aprobada') {
                throw new \Exception('Solo una solicitud aprobada puede marcarse como preparada.');
            }

            $inspeccion = $this->obtenerOCrearInspeccionNutricional($solicitud);
            $inspeccion->preparo_nombre = $this->nombreUsuarioActual();
            $inspeccion->save();

            $fechaPreparada = now();

            $solicitud->estado = 'preparada';
            $solicitud->fecha_hora_preparacion = $fechaPreparada;
            $solicitud->fecha_hora_limite_uso = $fechaPreparada->copy()->addHours(48);
            $solicitud->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Solicitud preparada',
                'text' => 'La solicitud fue marcada como preparada.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function revisar(Solicitud $solicitud)
    {
        DB::beginTransaction();

        try {
            if ($solicitud->estado !== 'preparada') {
                throw new \Exception('Solo una solicitud preparada puede marcarse como revisada.');
            }

            $inspeccion = $this->obtenerOCrearInspeccionNutricional($solicitud);
            $inspeccion->reviso_nombre = $this->nombreUsuarioActual();
            $inspeccion->save();

            $solicitud->estado = 'revisada';
            $solicitud->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Solicitud revisada',
                'text' => 'La solicitud fue marcada como revisada.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function entregar(Solicitud $solicitud)
    {
        DB::beginTransaction();

        try {
            if ($solicitud->estado !== 'revisada') {
                throw new \Exception('Solo una solicitud revisada puede marcarse como entregada.');
            }

            $inspeccion = $this->obtenerOCrearInspeccionNutricional($solicitud);
            $inspeccion->libero_nombre = $this->nombreUsuarioActual();
            $inspeccion->save();

            $solicitud->estado = 'entregada';
            $solicitud->save();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Solicitud entregada',
                'text' => 'La solicitud fue marcada como entregada.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function create()
    {
        $user = Auth::user();
        $user->loadMissing('hospital');

        $hospital = $user->hospital;

        if (!$hospital) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'Tu usuario no tiene hospital asignado.']);
        }

        if (!$hospital->nutri_medicine_list_id) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'Tu hospital no tiene lista nutricional asignada.']);
        }

        if (!$hospital->laboratory_id) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'Tu hospital no tiene centro de mezclas asignado.']);
        }

        $inputs = Input::join('categories', 'inputs.category_id', '=', 'categories.id')
            ->where('inputs.is_active', 1)
            ->orderBy('orden_enum', 'asc')
            ->select(
                'inputs.*',
                'inputs.id AS input_id'
            )
            ->get();

        $listItems = \App\Models\Nutricionales\NutriMedicineListItem::with([
            'presentation.catalog',
            'presentation.stocks' => function ($query) use ($hospital) {
                $query->where('laboratory_id', $hospital->laboratory_id)
                    ->where('is_active', 1)
                    ->where('stock_ml_actual', '>', 0)
                    ->orderByRaw('CASE WHEN caducidad IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('caducidad')
                    ->orderBy('id');
            }
        ])
            ->where('nutri_medicine_list_id', $hospital->nutri_medicine_list_id)
            ->get();

        $presentationsByInput = $listItems
            ->filter(function ($item) {
                return $item->presentation && $item->presentation->catalog;
            })
            ->groupBy(function ($item) {
                return $item->presentation->catalog->input_id;
            });

        $activeSelections = \App\Models\Nutricionales\NutritionLaboratoryActivePresentation::with('presentation.catalog')
            ->where('laboratory_id', $hospital->laboratory_id)
            ->whereDate('selected_date', now()->toDateString())
            ->get()
            ->keyBy('nutrition_medicine_catalog_id');

        $inputs = $inputs->map(function ($input) use ($presentationsByInput, $activeSelections) {
            $items = $presentationsByInput->get($input->input_id, collect());

            $input->presentations_disponibles = $items
                ->map(function ($item) {
                    return $item->presentation;
                })
                ->filter()
                ->values();

            $input->presentation_activa_id = null;
            $input->presentation_precargada_id = null;

            $catalogId = optional(optional($items->first())->presentation)->nutrition_medicine_catalog_id;

            if ($catalogId && isset($activeSelections[$catalogId])) {
                $input->presentation_activa_id = $activeSelections[$catalogId]->nutrition_medicine_presentation_id;
                $input->presentation_precargada_id = $activeSelections[$catalogId]->nutrition_medicine_presentation_id;
            }

            return $input;
        });


        return view('admin.nutricionales.solicitudes.create', compact(
            'inputs',
            'presentationsByInput',
            'activeSelections'
        ));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $fecha_nacimiento = $request->input('fecha_nacimiento');
            $edad = $this->calcularEdad($fecha_nacimiento);

            $request->validate([
                'nombre_paciente' => 'required|string|max:255',
                'apellidos_paciente' => 'required|string|max:255',
                'servicio' => 'required|string|max:100',
                'cama' => 'nullable|string|max:50',
                'piso' => 'nullable|string|max:50',
                'registro' => 'nullable|string|max:50',
                'diagnostico' => 'nullable|string|max:255',
                'peso' => 'required|numeric',
                'fecha_nacimiento' => 'required|date',
                'edad' => 'nullable',
                'sexo' => 'nullable',
                'via_administracion' => 'required',
                'tiempo_infusion_min' => 'nullable|numeric',
                'sobrellenado_ml' => 'nullable|numeric',
                'volumen_total' => 'nullable|numeric',
                'npt' => 'required',
                'observaciones' => 'nullable|string|max:500',
                'fecha_hora_entrega' => 'required|date_format:Y-m-d\TH:i',
                'nombre_medico' => 'required|string|max:255',
                'cedula' => 'required|string|max:50',
                'velocidad_infusion' => 'nullable|numeric',
                'hospital_destino' => 'nullable|string|max:255'
            ]);

            $fechaHoraEntrega = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $request->input('fecha_hora_entrega'));
            $horaMinima = \Carbon\Carbon::now()->addMinutes(210);

            if ($fechaHoraEntrega->lt($horaMinima)) {
                return redirect()->back()->withErrors([
                    'fecha_hora_entrega' => 'La fecha y hora de entrega debe ser al menos 3 horas y 30 minutos después de la hora actual.'
                ])->withInput();
            }

            $solicitud_paciente = $request->only([
                'nombre_paciente',
                'apellidos_paciente',
                'servicio',
                'cama',
                'piso',
                'registro',
                'diagnostico',
                'peso',
                'fecha_nacimiento',
                'sexo'
            ]);

            $solicitud_detalles = $request->only([
                'via_administracion',
                'tiempo_infusion_min',
                'sobrellenado_ml',
                'volumen_total',
                'npt',
                'observaciones',
                'fecha_hora_entrega',
                'nombre_medico',
                'cedula',
                'velocidad_infusion',
                'hospital_destino'
            ]);

            $solicitud_paciente['edad'] = $edad;

            $solicitud_paciente_resp = SolicitudPatient::create($solicitud_paciente);
            $solicitud_detalles_resp = SolicitudDetail::create($solicitud_detalles);

            $peso_paciente = $solicitud_paciente_resp->peso;

            $user = Auth::user();
            $user->loadMissing('hospital');
            $hospital = $user->hospital;

            if (!$hospital) {
                throw new \Exception('Tu usuario no tiene hospital asignado.');
            }

            if (!$hospital->nutri_medicine_list_id) {
                throw new \Exception('Tu hospital no tiene lista nutricional asignada.');
            }

            if (!$hospital->laboratory_id) {
                throw new \Exception('Tu hospital no tiene centro de mezclas asignado.');
            }

            $solicitud = [];
            $solicitud['user_id'] = $user->id;
            $solicitud['solicitud_detail_id'] = $solicitud_detalles_resp->id;
            $solicitud['solicitud_patient_id'] = $solicitud_paciente_resp->id;

            $solicitud_nueva = Solicitud::create($solicitud);

            $only_inputs = $request->except([
                'nombre_paciente',
                'apellidos_paciente',
                'servicio',
                'cama',
                'piso',
                'registro',
                'diagnostico',
                'peso',
                'fecha_nacimiento',
                'sexo',
                'via_administracion',
                'tiempo_infusion_min',
                'sobrellenado_ml',
                'volumen_total',
                'npt',
                'observaciones',
                'fecha_hora_entrega',
                'nombre_medico',
                'cedula',
                'hospital_destino',
                'velocidad_infusion'
            ]);

            $filtered_inputs = array_filter($only_inputs, function ($value) {
                return $value !== null && $value !== '';
            });

            $registro = SolicitudDetail::find($solicitud_detalles_resp->id);
            $suma_volumen_ml = 0;

            foreach ($filtered_inputs as $key => $value) {
                preg_match('/_(\d+)_/', $key, $matches);

                if (isset($matches[1])) {
                    $numero = (int) $matches[1];

                    if ($numero == 40) {
                        if ($value == 1) {
                            $resultado = Input::select('id', 'description', 'mult', 'div')
                                ->where('id', $numero)
                                ->first();

                            $valor_ml = 1;
                            $suma_volumen_ml += 0;

                            $presentation = $this->obtenerPresentacionActivaPorInput($hospital, $numero);
                            $precioMlUnitario = $presentation
                                ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation)
                                : 0;

                            $solicitud_inputs = [];
                            $solicitud_inputs['solicitud_id'] = $solicitud_nueva->id;
                            $solicitud_inputs['valor'] = $value;
                            $solicitud_inputs['valor_ml'] = $valor_ml;
                            $solicitud_inputs['input_id'] = $numero;
                            $solicitud_inputs['nutrition_medicine_presentation_id'] = $presentation?->id;
                            $solicitud_inputs['precio_ml'] = $valor_ml * $precioMlUnitario;

                            SolicitudInput::create($solicitud_inputs);
                        }
                    } else {
                        if ($registro->npt == 'ADULT') {
                            $resultado = Input::select('id', 'description', 'mult', 'div')
                                ->where('id', $numero)
                                ->first();

                            if (!$resultado) {
                                continue;
                            }

                            $valor_ml = ($value) * $resultado->mult / $resultado->div;
                            $suma_volumen_ml += $valor_ml;

                            $presentation = $this->obtenerPresentacionActivaPorInput($hospital, $numero);
                            $precioMlUnitario = $presentation
                                ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation)
                                : 0;

                            $solicitud_inputs = [];
                            $solicitud_inputs['solicitud_id'] = $solicitud_nueva->id;
                            $solicitud_inputs['valor'] = $value;
                            $solicitud_inputs['valor_ml'] = $valor_ml;
                            $solicitud_inputs['input_id'] = $numero;
                            $solicitud_inputs['nutrition_medicine_presentation_id'] = $presentation?->id;
                            $solicitud_inputs['precio_ml'] = $valor_ml * $precioMlUnitario;

                            SolicitudInput::create($solicitud_inputs);
                        } else {
                            $resultado = Input::select('id', 'description', 'category_id', 'mult', 'div')
                                ->where('id', $numero)
                                ->first();

                            if (!$resultado) {
                                continue;
                            }

                            if (in_array($resultado->category_id, [1, 8, 2, 3, 4])) {
                                $valor_ml = ($value) * $peso_paciente * $resultado->mult / $resultado->div;
                            } else {
                                $valor_ml = ($value) * $resultado->mult / $resultado->div;
                            }

                            $suma_volumen_ml += $valor_ml;

                            $presentation = $this->obtenerPresentacionActivaPorInput($hospital, $numero);
                            $precioMlUnitario = $presentation
                                ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation)
                                : 0;

                            $solicitud_inputs = [];
                            $solicitud_inputs['solicitud_id'] = $solicitud_nueva->id;
                            $solicitud_inputs['valor'] = $value;
                            $solicitud_inputs['valor_ml'] = $valor_ml;
                            $solicitud_inputs['input_id'] = $numero;
                            $solicitud_inputs['nutrition_medicine_presentation_id'] = $presentation?->id;
                            $solicitud_inputs['precio_ml'] = $valor_ml * $precioMlUnitario;

                            SolicitudInput::create($solicitud_inputs);
                        }
                    }
                }
            }

            if ($registro->sobrellenado_ml != null) {
                if ($registro->volumen_total == null || $registro->volumen_total == 0) {
                    $porcentaje_sobrellenado = ($registro->sobrellenado_ml * 100) / $suma_volumen_ml;

                    $inputs_valores = SolicitudInput::select('id', 'valor_ml', 'valor_sobrellenado', 'input_id', 'nutrition_medicine_presentation_id')
                        ->where('solicitud_id', $solicitud_nueva->id)
                        ->get();

                    $suma_volumen_sobrellenado_ml = 0;

                    foreach ($inputs_valores as $input_val) {
                        $valor_en_ml = $input_val->valor_ml;
                        $valor_sobrellenado_ml = (($valor_en_ml * $porcentaje_sobrellenado) / 100) + $valor_en_ml;
                        $suma_volumen_sobrellenado_ml += $valor_sobrellenado_ml;

                        $registro_input = SolicitudInput::find($input_val->id);
                        $registro_input->valor_sobrellenado = $valor_sobrellenado_ml;

                        if ($input_val->nutrition_medicine_presentation_id) {
                            $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::find($input_val->nutrition_medicine_presentation_id);
                            if ($presentation) {
                                $precioMlUnitario = $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation);
                                $registro_input->precio_ml = $valor_sobrellenado_ml * $precioMlUnitario;
                            }
                        }

                        $registro_input->save();
                    }

                    $registro->suma_volumen_sobrellenado = $suma_volumen_sobrellenado_ml;
                    $registro->volumen_total_final = $suma_volumen_sobrellenado_ml;
                } else {
                    $porcentaje_sobrellenado = ($registro->sobrellenado_ml * 100) / $registro->volumen_total;

                    $inputs_valores = SolicitudInput::select('id', 'valor_ml', 'valor_sobrellenado', 'input_id', 'nutrition_medicine_presentation_id')
                        ->where('solicitud_id', $solicitud_nueva->id)
                        ->get();

                    $suma_volumen_sobrellenado_ml = 0;

                    foreach ($inputs_valores as $input_val) {
                        $valor_en_ml = $input_val->valor_ml;
                        $valor_sobrellenado_ml = (($valor_en_ml * $porcentaje_sobrellenado) / 100) + $valor_en_ml;
                        $suma_volumen_sobrellenado_ml += $valor_sobrellenado_ml;

                        $registro_input = SolicitudInput::find($input_val->id);
                        $registro_input->valor_sobrellenado = $valor_sobrellenado_ml;

                        if ($input_val->nutrition_medicine_presentation_id) {
                            $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::find($input_val->nutrition_medicine_presentation_id);
                            if ($presentation) {
                                $precioMlUnitario = $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation);
                                $registro_input->precio_ml = $valor_sobrellenado_ml * $precioMlUnitario;
                            }
                        }

                        $registro_input->save();
                    }

                    $registro->suma_volumen_sobrellenado = $suma_volumen_sobrellenado_ml;

                    $agua_inyectable_ml = $registro->volumen_total - $suma_volumen_ml;
                    $registro->volumen_total_final = $suma_volumen_ml + $agua_inyectable_ml + $registro->sobrellenado_ml;

                    $presentationAgua = $this->obtenerPresentacionActivaPorInput($hospital, 37);
                    $agua_valor_sobrellenado = (($agua_inyectable_ml * $porcentaje_sobrellenado) / 100) + $agua_inyectable_ml;

                    $solicitud_inputs = [];
                    $solicitud_inputs['solicitud_id'] = $solicitud_nueva->id;
                    $solicitud_inputs['valor'] = $agua_inyectable_ml;
                    $solicitud_inputs['valor_ml'] = $agua_inyectable_ml;
                    $solicitud_inputs['input_id'] = 37;
                    $solicitud_inputs['nutrition_medicine_presentation_id'] = $presentationAgua?->id;
                    $solicitud_inputs['valor_sobrellenado'] = $agua_valor_sobrellenado;

                    if ($presentationAgua) {
                        $precioMlAgua = $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentationAgua);
                        $solicitud_inputs['precio_ml'] = $agua_valor_sobrellenado * $precioMlAgua;
                    } else {
                        $solicitud_inputs['precio_ml'] = 0;
                    }

                    SolicitudInput::create($solicitud_inputs);
                }
            } else {
                if ($registro->volumen_total != null && $registro->volumen_total != 0) {
                    $agua_inyectable_ml = ($registro->volumen_total) - $suma_volumen_ml;

                    $presentationAgua = $this->obtenerPresentacionActivaPorInput($hospital, 37);

                    $solicitud_inputs = [];
                    $solicitud_inputs['solicitud_id'] = $solicitud_nueva->id;
                    $solicitud_inputs['valor'] = $agua_inyectable_ml;
                    $solicitud_inputs['valor_ml'] = $agua_inyectable_ml;
                    $solicitud_inputs['input_id'] = 37;
                    $solicitud_inputs['nutrition_medicine_presentation_id'] = $presentationAgua?->id;

                    if ($presentationAgua) {
                        $precioMlAgua = $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentationAgua);
                        $solicitud_inputs['precio_ml'] = $agua_inyectable_ml * $precioMlAgua;
                    } else {
                        $solicitud_inputs['precio_ml'] = 0;
                    }

                    $registro->volumen_total_final = $agua_inyectable_ml + $suma_volumen_ml;

                    SolicitudInput::create($solicitud_inputs);
                } else {
                    $registro->volumen_total_final = $suma_volumen_ml;
                }
            }

            $registro->suma_volumen = $suma_volumen_ml;
            $registro->save();

            $solicitudes = Solicitud::where('user_id', $user->id)
                ->with('user', 'user.hospital')
                ->latest()
                ->first();

            $nombreHospital = $solicitudes?->user?->hospital?->name ?? 'Sin hospital asignado';

            $message = Message::create([
                'sender_id' => auth()->id(),
                'subject' => 'Hay una nueva solicitud',
                'body' => $nombreHospital
            ]);

            $administradores = User::role('Admin')->get();
            $notification = new MessageSent($message);

            // Notification::send($administradores, $notification);

            DB::commit();

            session()->flash(
                'swal',
                [
                    'title' => "¡Bien hecho!",
                    'text' => "La solicitud se ha creado con éxito.",
                    'icon' => "success"
                ]
            );

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'store' => $e->getMessage()
            ])->withInput();
        }
    }

    public function edit(Solicitud $solicitud)
    {
        $user = Auth::user();
        $role = $user->roles[0]->name ?? null;

        if ($solicitud->estado !== 'pendiente' && !in_array($role, ['Admin', 'Super Admin'])) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permisos para editar esta solicitud.');
        }

        $solicitud = Solicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',
            'input',
        ])->findOrFail($solicitud->id);

        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)->get();

        $hospital = $solicitud->user?->hospital;

        if (!$hospital) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'La solicitud no tiene hospital asociado.']);
        }

        if (!$hospital->nutri_medicine_list_id) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'El hospital no tiene lista nutricional asignada.']);
        }

        if (!$hospital->laboratory_id) {
            return redirect()->route('admin.nutricionales.solicitudes.index')
                ->withErrors(['error' => 'El hospital no tiene centro de mezclas asignado.']);
        }

        $inputs = Input::join('categories', 'inputs.category_id', '=', 'categories.id')
            ->where('inputs.is_active', 1)
            ->orderBy('orden_enum', 'asc')
            ->select(
                'inputs.*',
                'inputs.id AS input_id'
            )
            ->get();

        $listItems = \App\Models\Nutricionales\NutriMedicineListItem::with([
            'presentation.catalog',
            'presentation.stocks' => function ($query) use ($hospital) {
                $query->where('laboratory_id', $hospital->laboratory_id)
                    ->where('is_active', 1)
                    ->where('stock_ml_actual', '>', 0)
                    ->orderByRaw('CASE WHEN caducidad IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('caducidad')
                    ->orderBy('id');
            },
        ])
            ->where('nutri_medicine_list_id', $hospital->nutri_medicine_list_id)
            ->get();

        $presentationsByInput = $listItems
            ->filter(fn($item) => $item->presentation && $item->presentation->catalog)
            ->groupBy(fn($item) => $item->presentation->catalog->input_id);

        $activeSelections = \App\Models\Nutricionales\NutritionLaboratoryActivePresentation::with('presentation.catalog')
            ->where('laboratory_id', $hospital->laboratory_id)
            ->whereDate('selected_date', now()->toDateString())
            ->get()
            ->keyBy('nutrition_medicine_catalog_id');

        $inputs = $inputs->map(function ($input) use ($inputs_solicitud, $presentationsByInput, $activeSelections) {
            $solicitudInput = $inputs_solicitud->firstWhere('input_id', $input->input_id);

            $tieneValor = $solicitudInput && (
                (!is_null($solicitudInput->valor) && $solicitudInput->valor !== '') ||
                (!is_null($solicitudInput->valor_ml) && $solicitudInput->valor_ml !== '')
            );

            $presentationsForInput = $presentationsByInput->get($input->input_id, collect());

            $input->presentations_disponibles = $presentationsForInput
                ->map(function ($item) {
                    $presentation = $item->presentation;

                    if (!$presentation) {
                        return null;
                    }

                    $presentation->precio_ml_lista = $item->precio_ml;

                    $presentation->stocks_disponibles = $presentation->stocks
                        ->map(function ($stock) {
                            return [
                                'id' => $stock->id,
                                'lote' => $stock->lote,
                                'caducidad' => $stock->caducidad
                                    ? \Carbon\Carbon::parse($stock->caducidad)->format('Y-m-d')
                                    : null,
                                'stock_ml_actual' => (float) $stock->stock_ml_actual,
                                'frascos_actuales' => (float) ($stock->frascos_actuales ?? 0),
                            ];
                        })
                        ->values();

                    return $presentation;
                })
                ->filter()
                ->values();

            $input->presentation_activa_id = null;
            $input->presentation_precargada_id = $solicitudInput?->nutrition_medicine_presentation_id ?: null;

            $input->lote_precargado = $solicitudInput?->lote ?: null;

            $input->caducidad_precargada = $solicitudInput?->caducidad
                ? \Carbon\Carbon::parse($solicitudInput->caducidad)->format('Y-m-d')
                : null;

            $catalogId = optional(optional($presentationsForInput->first())->presentation)->nutrition_medicine_catalog_id;

            if ($catalogId && isset($activeSelections[$catalogId])) {
                $input->presentation_activa_id = $activeSelections[$catalogId]->nutrition_medicine_presentation_id;
            }

            if (!$input->presentation_precargada_id && $input->presentation_activa_id) {
                $input->presentation_precargada_id = $input->presentation_activa_id;
            }

            if (!$input->presentation_precargada_id && $input->presentations_disponibles->count() > 0) {
                $input->presentation_precargada_id = $input->presentations_disponibles->first()->id;
            }

            if ($tieneValor && $input->presentation_precargada_id) {
                $presentationSeleccionada = $input->presentations_disponibles
                    ->firstWhere('id', $input->presentation_precargada_id);

                if ($presentationSeleccionada) {
                    $stock = $presentationSeleccionada->stocks->first();

                    if ($stock) {
                        if (!$input->lote_precargado) {
                            $input->lote_precargado = $stock->lote;
                        }

                        if (!$input->caducidad_precargada && $stock->caducidad) {
                            $input->caducidad_precargada = \Carbon\Carbon::parse($stock->caducidad)->format('Y-m-d');
                        }
                    }
                }
            }

            return $input;
        });

        $inventarioPorInput = $inputs->mapWithKeys(function ($input) {
            return [
                $input->input_id => [
                    'input_id' => $input->input_id,
                    'presentations' => ($input->presentations_disponibles ?? collect())->map(function ($presentation) {
                        return [
                            'id' => $presentation->id,
                            'denominacion_comercial' => $presentation->denominacion_comercial,
                            'presentacion' => $presentation->presentacion,
                            'presentacion_ml' => (float) $presentation->presentacion_ml,
                            'precio_ml_lista' => (float) ($presentation->precio_ml_lista ?? 0),
                            'stocks' => $presentation->stocks_disponibles ?? [],
                        ];
                    })->values(),
                ],
            ];
        });

        return view('admin.nutricionales.solicitudes.edit', compact(
            'solicitud',
            'inputs',
            'inputs_solicitud',
            'presentationsByInput',
            'activeSelections',
            'inventarioPorInput'
        ));
    }

    public function update(Request $request, Solicitud $solicitud)
    {
        DB::beginTransaction();

        try {
            $estadoAnterior = $solicitud->estado ?? 'pendiente';

            $accion = $request->input('accion', 'actualizar');

            if ($accion === 'cancelar') {
                if (in_array($solicitud->estado, ['aprobada', 'preparada', 'revisada'], true)) {
                    if (!$this->solicitudYaTieneDevolucionInventario($solicitud)) {
                        $this->devolverStockSolicitud($solicitud);
                    }
                }

                $solicitud->update([
                    'estado' => 'cancelada',
                ]);

                DB::commit();

                session()->flash('swal', [
                    'title' => 'Solicitud cancelada',
                    'text' => 'La solicitud se ha cancelado y el inventario fue devuelto si ya había sido descontado.',
                    'icon' => 'warning',
                ]);

                return redirect()->route('admin.nutricionales.solicitudes.index');
            }

            $fecha_nacimiento = $request->input('fecha_nacimiento');
            $edad = $this->calcularEdad($fecha_nacimiento);

            $request->validate([
                'nombre_paciente' => 'required|string|max:255',
                'apellidos_paciente' => 'required|string|max:255',
                'servicio' => 'required|string|max:100',
                'cama' => 'nullable|string|max:50',
                'piso' => 'nullable|string|max:50',
                'registro' => 'nullable|string|max:50',
                'diagnostico' => 'nullable|string|max:255',
                'peso' => 'required|numeric',
                'fecha_nacimiento' => 'required|date',
                'sexo' => 'nullable',
                'via_administracion' => 'required',
                'tiempo_infusion_min' => 'nullable|numeric',
                'sobrellenado_ml' => 'nullable|numeric',
                'volumen_total' => 'nullable|numeric',
                'npt' => 'required',
                'observaciones' => 'nullable|string|max:500',
                'fecha_hora_entrega' => 'required|date_format:Y-m-d\TH:i',
                'nombre_medico' => 'required|string|max:255',
                'cedula' => 'required|string|max:50',
                'bolsa_eva' => 'required',
                'velocidad_infusion' => 'nullable|numeric',
                'hospital_destino' => 'nullable|string|max:255',
            ]);

            $solicitud->loadMissing('user.hospital');
            $hospital = $solicitud->user?->hospital;

            if (!$hospital) {
                throw new \Exception('La solicitud no tiene hospital asociado.');
            }

            if (!$hospital->nutri_medicine_list_id) {
                throw new \Exception('El hospital no tiene lista nutricional asignada.');
            }

            if (!$hospital->laboratory_id) {
                throw new \Exception('El hospital no tiene centro de mezclas asignado.');
            }

            $bolsa_eva = (int) $request->input('bolsa_eva');

            $tiempo_infusion_min = $request->input('tiempo_infusion_min');

            if ($tiempo_infusion_min === null || $tiempo_infusion_min === '') {
                $tiempo_infusion_min = 24;
            }

            $solicitud_paciente = $request->only([
                'nombre_paciente',
                'apellidos_paciente',
                'servicio',
                'cama',
                'piso',
                'registro',
                'diagnostico',
                'peso',
                'fecha_nacimiento',
                'sexo',
            ]);

            $solicitud_detalles = $request->only([
                'via_administracion',
                'sobrellenado_ml',
                'volumen_total',
                'npt',
                'observaciones',
                'fecha_hora_entrega',
                'nombre_medico',
                'cedula',
                'velocidad_infusion',
                'hospital_destino',
            ]);

            $solicitud_paciente['edad'] = $edad;
            $solicitud_detalles['tiempo_infusion_min'] = $tiempo_infusion_min;

            $solicitud_patient_u = SolicitudPatient::findOrFail($solicitud->solicitud_patient_id);
            $solicitud_detail_u = SolicitudDetail::findOrFail($solicitud->solicitud_detail_id);

            $solicitud_patient_u->update($solicitud_paciente);
            $solicitud_detail_u->update($solicitud_detalles);

            $peso_paciente = (float) $solicitud_patient_u->peso;

            SolicitudInput::where('solicitud_id', $solicitud->id)->delete();

            $requestData = $request->all();
            $tripletas = [];

            foreach ($requestData as $key => $value) {
                if (preg_match('/^i_[0-9]+$/', $key) && $value !== null && $value !== '') {
                    $numero = (int) explode('_', $key)[1];

                    if ($numero === 40 && (int) $value !== 1) {
                        continue;
                    }

                    $tripletas[$numero] = [
                        "i_{$numero}" => $value,
                        "p_{$numero}" => $requestData["p_{$numero}"] ?? null,
                        "l_{$numero}" => $requestData["l_{$numero}"] ?? null,
                        "c_{$numero}" => $requestData["c_{$numero}"] ?? null,
                    ];
                }
            }

            $registro = SolicitudDetail::findOrFail($solicitud->solicitud_detail_id);
            $suma_volumen_ml = 0;

            $setInfusionActivo = isset($tripletas[40]) && (int) ($tripletas[40]['i_40'] ?? 0) === 1;

            foreach ($tripletas as $numero => $tripleta) {
                $numero = (int) $numero;

                $resultado = Input::select('description', 'category_id', 'mult', 'div')
                    ->where('id', $numero)
                    ->first();

                if (!$resultado) {
                    continue;
                }

                $valor_unidad = $tripleta["i_{$numero}"];

                if ($numero === 40) {
                    $valor_ml = 1;
                } else {
                    if ($registro->npt === 'ADULT') {
                        $valor_ml = ((float) $valor_unidad) * (float) $resultado->mult / (float) $resultado->div;
                    } else {
                        if (in_array((int) $resultado->category_id, [1, 8, 2, 3, 4], true)) {
                            $valor_ml = ((float) $valor_unidad) * $peso_paciente * (float) $resultado->mult / (float) $resultado->div;
                        } else {
                            $valor_ml = ((float) $valor_unidad) * (float) $resultado->mult / (float) $resultado->div;
                        }
                    }

                    $suma_volumen_ml += $valor_ml;
                }

                $presentationId = $tripleta["p_{$numero}"] ?? null;
                $presentation = null;
                $precioMlUnitario = 0;

                if ($presentationId) {
                    $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::find($presentationId);

                    if ($presentation) {
                        $precioMlUnitario = $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentation);
                    }
                }

                SolicitudInput::create([
                    'solicitud_id' => $solicitud->id,
                    'valor' => $valor_unidad,
                    'valor_ml' => $valor_ml,
                    'input_id' => $numero,
                    'nutrition_medicine_presentation_id' => $presentation?->id,
                    'precio_ml' => $precioMlUnitario,
                    'lote' => $tripleta["l_{$numero}"] ?? null,
                    'caducidad' => $tripleta["c_{$numero}"] ?? null,
                ]);
            }

            if ($registro->sobrellenado_ml != null) {
                if ($registro->volumen_total == null || $registro->volumen_total == 0) {
                    $porcentaje_sobrellenado = $suma_volumen_ml > 0
                        ? ((float) $registro->sobrellenado_ml * 100) / $suma_volumen_ml
                        : 0;

                    $inputs_valores = SolicitudInput::where('solicitud_id', $solicitud->id)
                        ->whereNotIn('input_id', [40])
                        ->get();

                    $suma_volumen_sobrellenado_ml = 0;

                    foreach ($inputs_valores as $input_val) {
                        $valor_en_ml = (float) $input_val->valor_ml;
                        $valor_sobrellenado_ml = (($valor_en_ml * $porcentaje_sobrellenado) / 100) + $valor_en_ml;

                        $suma_volumen_sobrellenado_ml += $valor_sobrellenado_ml;

                        $input_val->valor_sobrellenado = $valor_sobrellenado_ml;
                        $input_val->save();
                    }

                    $registro->suma_volumen_sobrellenado = $suma_volumen_sobrellenado_ml;
                    $registro->volumen_total_final = $suma_volumen_sobrellenado_ml;
                } else {
                    $porcentaje_sobrellenado = ((float) $registro->sobrellenado_ml * 100) / (float) $registro->volumen_total;

                    $inputs_valores = SolicitudInput::where('solicitud_id', $solicitud->id)
                        ->whereNotIn('input_id', [40])
                        ->get();

                    $suma_volumen_sobrellenado_ml = 0;
                    $sumaVolumenBaseSinAgua = 0;

                    foreach ($inputs_valores as $input_val) {
                        $valor_en_ml = (float) $input_val->valor_ml;
                        $sumaVolumenBaseSinAgua += $valor_en_ml;

                        $valor_sobrellenado_ml = (($valor_en_ml * $porcentaje_sobrellenado) / 100) + $valor_en_ml;
                        $suma_volumen_sobrellenado_ml += $valor_sobrellenado_ml;

                        $input_val->valor_sobrellenado = $valor_sobrellenado_ml;
                        $input_val->save();
                    }

                    $registro->suma_volumen_sobrellenado = $suma_volumen_sobrellenado_ml;

                    $agua_inyectable_ml = (float) $registro->volumen_total - $sumaVolumenBaseSinAgua;
                    $agua_valor_sobrellenado = (($agua_inyectable_ml * $porcentaje_sobrellenado) / 100) + $agua_inyectable_ml;

                    $registro->volumen_total_final = $sumaVolumenBaseSinAgua + $agua_inyectable_ml + (float) $registro->sobrellenado_ml;

                    $presentationAguaId = $request->input('p_37');
                    $presentationAgua = $presentationAguaId
                        ? \App\Models\Nutricionales\NutritionMedicinePresentation::find($presentationAguaId)
                        : $this->obtenerPresentacionActivaPorInput($hospital, 37);

                    $precioMlAgua = $presentationAgua
                        ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentationAgua)
                        : 0;

                    SolicitudInput::create([
                        'solicitud_id' => $solicitud->id,
                        'valor' => $agua_inyectable_ml,
                        'valor_ml' => $agua_inyectable_ml,
                        'input_id' => 37,
                        'nutrition_medicine_presentation_id' => $presentationAgua?->id,
                        'valor_sobrellenado' => $agua_valor_sobrellenado,
                        'precio_ml' => $precioMlAgua,
                        'lote' => $request->input('l_37'),
                        'caducidad' => $request->input('c_37'),
                    ]);
                }
            } else {
                if ($registro->volumen_total != null && $registro->volumen_total != 0) {
                    $agua_inyectable_ml = $setInfusionActivo
                        ? ((float) $registro->volumen_total - $suma_volumen_ml) + 1
                        : (float) $registro->volumen_total - $suma_volumen_ml;

                    $presentationAguaId = $request->input('p_37');
                    $presentationAgua = $presentationAguaId
                        ? \App\Models\Nutricionales\NutritionMedicinePresentation::find($presentationAguaId)
                        : $this->obtenerPresentacionActivaPorInput($hospital, 37);

                    $precioMlAgua = $presentationAgua
                        ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentationAgua)
                        : 0;

                    SolicitudInput::create([
                        'solicitud_id' => $solicitud->id,
                        'valor' => $agua_inyectable_ml,
                        'valor_ml' => $agua_inyectable_ml,
                        'input_id' => 37,
                        'nutrition_medicine_presentation_id' => $presentationAgua?->id,
                        'precio_ml' => $precioMlAgua,
                        'lote' => $request->input('l_37'),
                        'caducidad' => $request->input('c_37'),
                    ]);

                    $registro->volumen_total_final = $agua_inyectable_ml + $suma_volumen_ml;
                } else {
                    $registro->volumen_total_final = $suma_volumen_ml;
                }
            }

            $registro->suma_volumen = $suma_volumen_ml;

            $presentationBolsaEvaId = $request->input('p_' . $bolsa_eva);
            $presentationBolsaEva = $presentationBolsaEvaId
                ? \App\Models\Nutricionales\NutritionMedicinePresentation::find($presentationBolsaEvaId)
                : null;

            $precioBolsaEva = $presentationBolsaEva
                ? $this->obtenerPrecioMlHospitalPorPresentacion($hospital, $presentationBolsaEva)
                : 0;

            SolicitudInput::create([
                'solicitud_id' => $solicitud->id,
                'valor' => 0,
                'valor_ml' => 0,
                'input_id' => $bolsa_eva,
                'nutrition_medicine_presentation_id' => $presentationBolsaEva?->id,
                'lote' => $request->input('l_' . $bolsa_eva),
                'caducidad' => $request->input('c_' . $bolsa_eva),
                'precio_ml' => $precioBolsaEva,
            ]);

            if ($accion === 'aprobar' && $estadoAnterior === 'pendiente') {
                $inputsFinales = SolicitudInput::where('solicitud_id', $solicitud->id)
                    ->whereNotIn('input_id', function ($query) {
                        $query->select('id')
                            ->from('inputs')
                            ->where('category_id', '=', 6);
                    })
                    ->whereNotIn('input_id', [40])
                    ->get();

                foreach ($inputsFinales as $inputFinal) {
                    $presentationId = $inputFinal->nutrition_medicine_presentation_id;

                    if (!$presentationId) {
                        continue;
                    }

                    $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::find($presentationId);

                    if (!$presentation) {
                        continue;
                    }

                    $cantidadMlADescontar = $inputFinal->valor_sobrellenado ?? $inputFinal->valor_ml ?? 0;

                    if ((float) $cantidadMlADescontar > 0) {
                        $stockUsado = $this->descontarStockPresentacion(
                            $hospital,
                            $presentation,
                            (float) $cantidadMlADescontar,
                            (int) $solicitud->id
                        );

                        $inputFinal->lote = $stockUsado->lote;
                        $inputFinal->caducidad = $stockUsado->caducidad;
                        $inputFinal->save();
                    }
                }

                $insumosPorPieza = SolicitudInput::where('solicitud_id', $solicitud->id)
                    ->whereIn('input_id', [$bolsa_eva, 40])
                    ->whereNotNull('nutrition_medicine_presentation_id')
                    ->get();

                foreach ($insumosPorPieza as $insumoPieza) {
                    $presentation = \App\Models\Nutricionales\NutritionMedicinePresentation::with('catalog')
                        ->find($insumoPieza->nutrition_medicine_presentation_id);

                    if (!$presentation) {
                        continue;
                    }

                    $stockUsado = $this->descontarStockPresentacion(
                        $hospital,
                        $presentation,
                        max(1, (float) ($presentation->presentacion_ml ?? 0)),
                        (int) $solicitud->id,
                        1
                    );

                    $insumoPieza->lote = $stockUsado->lote;
                    $insumoPieza->caducidad = $stockUsado->caducidad;
                    $insumoPieza->save();
                }
            }

            $registro->save();

            if ($accion === 'aprobar' && $estadoAnterior === 'pendiente') {
                $solicitud->estado = 'aprobada';

                $this->generarLoteNutricional($solicitud);
                $this->generarRemisionNutricional($solicitud);

                $inspeccion = $this->obtenerOCrearInspeccionNutricional($solicitud);
                $inspeccion->aprobo_nombre = $this->nombreUsuarioActual();
                $inspeccion->fecha_inspeccion = $inspeccion->fecha_inspeccion ?: now()->toDateString();
                $inspeccion->hora_inspeccion = $inspeccion->hora_inspeccion ?: now()->format('H:i:s');
                $inspeccion->save();
            }

            $solicitud->save();

            if ($accion === 'aprobar') {
                session()->flash('swal', [
                    'title' => 'Solicitud Aprobada',
                    'text' => 'La solicitud se ha aprobado con éxito.',
                    'icon' => 'success',
                ]);
            } else {
                session()->flash('swal', [
                    'title' => 'Solicitud Actualizada',
                    'text' => 'La solicitud se ha editado con éxito.',
                    'icon' => 'success',
                ]);
            }

            DB::commit();

            return redirect()->route('admin.nutricionales.solicitudes.index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'stock' => $e->getMessage(),
            ])->withInput();
        }
    }

    private function obtenerPresentacionActivaPorInput($hospital, int $inputId): ?NutritionMedicinePresentation
    {
        if (!$hospital || !$hospital->laboratory_id) {
            return null;
        }

        $active = \App\Models\Nutricionales\NutritionLaboratoryActivePresentation::with('presentation.catalog')
            ->where('laboratory_id', $hospital->laboratory_id)
            ->whereDate('selected_date', now()->toDateString())
            ->get()
            ->first(function ($item) use ($inputId) {
                return optional(optional($item->presentation)->catalog)->input_id == $inputId;
            });

        return $active?->presentation;
    }

    private function obtenerPrecioMlHospitalPorPresentacion($hospital, NutritionMedicinePresentation $presentation): float
    {
        if (!$hospital || !$hospital->nutri_medicine_list_id) {
            throw new \Exception('El hospital no tiene lista nutricional asignada.');
        }

        $itemLista = NutriMedicineListItem::where('nutri_medicine_list_id', $hospital->nutri_medicine_list_id)
            ->where('nutrition_medicine_presentation_id', $presentation->id)
            ->first();

        if (!$itemLista) {
            throw new \Exception("La presentación {$presentation->denominacion_comercial} no existe en la lista nutricional del hospital.");
        }

        return (float) $itemLista->precio_ml;
    }

    private function usaInventarioPorPieza(NutritionMedicinePresentation $presentation): bool
    {
        $catalog = $presentation->catalog;
        $genericName = mb_strtolower((string) ($catalog->denominacion_generica ?? ''));
        $inputId = (int) ($catalog->input_id ?? 0);
        $categoryId = (int) ($catalog->category_id ?? 0);

        return $categoryId === 6
            || $inputId === 40
            || str_contains($genericName, 'bolsa eva')
            || str_contains($genericName, 'set de infusión')
            || str_contains($genericName, 'set de infusion');
    }

    private function descontarStockPresentacion(
        ?Hospital $hospital,
        NutritionMedicinePresentation $presentation,
        float $cantidadMl,
        int $solicitudId,
        ?float $cantidadPiezas = null
    ): MedicineLaboratoryStock {
        if (!$hospital || !$hospital->laboratory_id) {
            throw new \Exception('El hospital no tiene laboratorio asignado.');
        }

        if ($cantidadMl <= 0) {
            throw new \Exception('La cantidad a descontar debe ser mayor a 0.');
        }

        $presentacionMl = (float) ($presentation->presentacion_ml ?? 0);

        if ($presentacionMl <= 0) {
            throw new \Exception("La presentación {$presentation->denominacion_comercial} no tiene presentacion_ml configurado.");
        }

        $controlPorPieza = $this->usaInventarioPorPieza($presentation);
        $cantidadFrascos = $controlPorPieza
            ? max(1, (float) ($cantidadPiezas ?? 1))
            : ($cantidadMl / $presentacionMl);

        $stockQuery = MedicineLaboratoryStock::where('nutrition_medicine_presentation_id', $presentation->id)
            ->where('laboratory_id', $hospital->laboratory_id)
            ->where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('caducidad')
                    ->orWhereDate('caducidad', '>=', now()->toDateString());
            });

        if ($controlPorPieza) {
            $stockQuery->where('frascos_actuales', '>=', $cantidadFrascos);
        } else {
            $stockQuery->where('stock_ml_actual', '>=', $cantidadMl);
        }

        $stock = $stockQuery
            ->orderByRaw('CASE WHEN caducidad IS NULL THEN 1 ELSE 0 END')
            ->orderBy('caducidad')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            $unidad = $controlPorPieza ? 'pieza(s)' : 'mL';
            $cantidad = $controlPorPieza ? $cantidadFrascos : $cantidadMl;

            throw new \Exception("No hay stock suficiente para la presentación {$presentation->denominacion_comercial} en el laboratorio del hospital. Requerido: {$cantidad} {$unidad}.");
        }

        $stockAntes = (float) $stock->stock_ml_actual;
        $frascosAntes = (float) $stock->frascos_actuales;
        $stockDescontarMl = $controlPorPieza ? ($cantidadFrascos * $presentacionMl) : $cantidadMl;
        $stockDespues = max(0, $stockAntes - $stockDescontarMl);
        $frascosDespues = max(0, $frascosAntes - $cantidadFrascos);

        $stock->update([
            'stock_ml_actual' => $stockDespues,
            'frascos_actuales' => $frascosDespues,
            'is_active' => $stockDespues > 0,
        ]);

        \App\Models\Nutricionales\MedicineStockMovement::create([
            'medicine_laboratory_stock_id' => $stock->id,
            'user_id' => auth()->id(),
            'tipo' => 'salida',
            'cantidad_ml' => $stockDescontarMl,
            'cantidad_frascos' => $cantidadFrascos,
            'stock_antes' => $stockAntes,
            'stock_despues' => $stockDespues,
            'frascos_antes' => $frascosAntes,
            'frascos_despues' => $frascosDespues,
            'reference_type' => 'Solicitud',
            'reference_id' => $solicitudId,
            'notes' => $controlPorPieza
                ? 'Descuento automático por aprobación de solicitud nutricional (control por pieza)'
                : 'Descuento automático por aprobación de solicitud nutricional',
        ]);

        return $stock;
    }

    public function show(Solicitud $solicitud)
    {
        $user = Auth::user();
        $role = $user->roles[0]->name;

        if (!in_array($role, ['Admin', 'Super Admin']) && $solicitud->user_id != $user->id) {
            abort(Response::HTTP_NOT_FOUND, 'Página no encontrada');
        }

        $solicitud_detalles = Solicitud::with(
            'user',
            'solicitud_detail',
            'solicitud_patient',
            'input',
            'user.hospital'
        )->findOrFail($solicitud->id);

        $inputs = Input::join('categories', 'inputs.category_id', '=', 'categories.id')
            ->where('inputs.is_active', 1)
            ->orderBy('orden_enum', 'asc')
            ->select(
                'inputs.*',
                'inputs.id AS input_id'
            )
            ->get();

        $inputs_solicitud = SolicitudInput::with([
            'input',
            'presentation.catalog'
        ])
            ->where('solicitud_id', $solicitud->id)
            ->get();

        $inputs = $inputs->map(function ($input) use ($inputs_solicitud) {
            $solicitudInput = $inputs_solicitud->firstWhere('input_id', $input->input_id);

            $input->presentation_ml = optional(optional($solicitudInput)->presentation)->presentacion_ml;
            $input->denominacion_comercial = optional(optional($solicitudInput)->presentation)->denominacion_comercial;
            $input->denominacion_generica = optional(optional(optional($solicitudInput)->presentation)->catalog)->denominacion_generica;
            $input->lote = $solicitudInput?->lote;
            $input->caducidad = $solicitudInput?->caducidad;

            return $input;
        });

        return view('admin.nutricionales.solicitudes.show', compact(
            'solicitud',
            'inputs_solicitud',
            'solicitud_detalles',
            'inputs'
        ));
    }
    public function solicitud(Solicitud $solicitud)
    {
        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input',
                'presentation.catalog',
            ])
            ->get();

        $arreglo_resultado = [];
        foreach ($inputs_solicitud as $resultado) {
            $input_id = $resultado['input_id'];
            $valor = $resultado['valor'];
            $descripcion = $resultado['input']['description'];

            $arreglo_resultado[] = [
                'input_id' => $input_id,
                'valor' => $valor,
                'descripcion' => $descripcion
            ];
        }


        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input.nutritionMedicineCatalog.presentations',
                'presentation.catalog',
            ])
            ->get();

        // return $arreglo_resultado;
        //print_r($inputs_solicitud);
        //return $inputs_solicitud;
        $solicitud_detalles = Solicitud::with('user', 'solicitud_detail', 'solicitud_patient', 'input', 'user.hospital')
            ->find($solicitud->id);
        $set_infusion = SolicitudInput::where('solicitud_id', $solicitud['id'])
            ->where('input_id', 40) // Filtrar por input_id igual a 40
            ->with(['input', 'presentation.catalog'])
            ->first();

        //return $solicitud_detalles;
        $pdf = Pdf::loadView('pdfs.nutricionales.solicitud', \compact('solicitud_detalles', 'arreglo_resultado', 'inputs_solicitud', 'set_infusion'));

        return $pdf->stream();
    }


    public function ordenPreparacion(Solicitud $solicitud)
    {

        $solicitud->load('user.hospital.nutriMedicineList', 'inspeccionNutricional');
        $hospital = $solicitud->user?->hospital;
        $imprimirMarcas = (bool) optional($hospital?->nutriMedicineList)->active_brands;
        $inspeccion = $solicitud->inspeccionNutricional;
        $elaboroNombre = $this->nombreUsuario($solicitud->user);
        $validoNombre = $this->nombreUsuarioDesdeTexto($inspeccion?->aprobo_nombre);
        $preparoNombre = $this->nombreUsuarioDesdeTexto($inspeccion?->preparo_nombre);

        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input.nutritionMedicineCatalog.presentations.stocks' => function ($query) use ($solicitud) {
                    $query->where('laboratory_id', $solicitud->user->hospital->laboratory_id)
                        ->where('is_active', 1)
                        ->orderBy('caducidad')
                        ->orderBy('id');
                },
                'presentation.stocks' => function ($query) use ($solicitud) {
                    $query->where('laboratory_id', $solicitud->user->hospital->laboratory_id)
                        ->where('is_active', 1)
                        ->orderBy('caducidad')
                        ->orderBy('id');
                },
                'presentation.catalog',
            ])
            ->get();

        $solicitud_detalles = Solicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',
            'input.input.nutritionMedicineCatalog.presentations',
            'input.presentation.catalog',
        ])->findOrFail($solicitud->id);

        $bolsa_eva = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->with([
                'input.nutritionMedicineCatalog.presentations',
                'presentation.catalog',
            ])
            ->first();

        $set_infusion = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->where('input_id', 40)
            ->with([
                'input.nutritionMedicineCatalog.presentations',
                'presentation.catalog',
            ])
            ->first();

        $presentationIds = $inputs_solicitud
            ->concat([$bolsa_eva, $set_infusion])
            ->filter()
            ->pluck('nutrition_medicine_presentation_id')
            ->filter()
            ->unique()
            ->values();

        $lotesPorPresentacion = collect();

        if ($presentationIds->isNotEmpty() && $hospital?->laboratory_id) {
            $lotesPorPresentacion = MedicineLaboratoryStock::whereIn('nutrition_medicine_presentation_id', $presentationIds)
                ->where('laboratory_id', $hospital->laboratory_id)
                ->orderByRaw('CASE WHEN caducidad IS NULL THEN 1 ELSE 0 END')
                ->orderBy('caducidad')
                ->orderBy('id')
                ->get()
                ->groupBy('nutrition_medicine_presentation_id')
                ->map(fn($stocks) => $stocks->first());

            $lotesUsados = MedicineStockMovement::query()
                ->join('medicine_laboratory_stocks as mls', 'mls.id', '=', 'medicine_stock_movements.medicine_laboratory_stock_id')
                ->where('medicine_stock_movements.reference_type', 'Solicitud')
                ->where('medicine_stock_movements.reference_id', $solicitud->id)
                ->where('medicine_stock_movements.tipo', 'salida')
                ->whereIn('mls.nutrition_medicine_presentation_id', $presentationIds)
                ->select(
                    'mls.nutrition_medicine_presentation_id',
                    'mls.lote',
                    'mls.caducidad'
                )
                ->get()
                ->keyBy('nutrition_medicine_presentation_id');

            $lotesUsados->each(function ($lote, $presentationId) use ($lotesPorPresentacion) {
                $lotesPorPresentacion->put($presentationId, $lote);
            });
        }

        $pdf = Pdf::loadView('pdfs.nutricionales.orden-de-preparacion', compact(
            'solicitud_detalles',
            'inputs_solicitud',
            'bolsa_eva',
            'set_infusion',
            'imprimirMarcas',
            'lotesPorPresentacion',
            'inspeccion',
            'elaboroNombre',
            'validoNombre',
            'preparoNombre'
        ));

        return $pdf->stream();
    }

    public function remision(Solicitud $solicitud)
    {
        $solicitud->load('user.hospital.nutriMedicineList.distributor');

        $hospital = $solicitud->user?->hospital;
        $nutriMedicineListId = $hospital?->nutri_medicine_list_id;
        $imprimirMarcas = (bool) optional($hospital?->nutriMedicineList)->active_brands;
        $distributor = $hospital?->nutriMedicineList?->distributor;

        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->get();

        $solicitud_detalles = Solicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',

            'input.input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                $query->with([
                    'listItems' => function ($q) use ($nutriMedicineListId) {
                        if ($nutriMedicineListId) {
                            $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                        }
                    },
                ]);
            },
            'input.presentation.catalog',
            'input.presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                if ($nutriMedicineListId) {
                    $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                }
            },
        ])->findOrFail($solicitud->id);

        $bolsa_eva = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->first();

        $set_infusion = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->where('input_id', 40)
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->first();

        $servicio_preparacion = Medicine::where('id', 38)->first();

        $pdf = Pdf::loadView('pdfs.nutricionales.remision', compact(
            'solicitud_detalles',
            'inputs_solicitud',
            'bolsa_eva',
            'set_infusion',
            'servicio_preparacion',
            'imprimirMarcas',
            'distributor'
        ));

        return $pdf->stream();
    }

    public function envio(Solicitud $solicitud)
    {
        $solicitud->load('user.hospital.nutriMedicineList');

        $hospital = $solicitud->user?->hospital;
        $nutriMedicineListId = $hospital?->nutri_medicine_list_id;
        $imprimirMarcas = (bool) optional($hospital?->nutriMedicineList)->active_brands;

        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->get();

        $solicitud_detalles = Solicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient',

            'input.input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                $query->with([
                    'listItems' => function ($q) use ($nutriMedicineListId) {
                        if ($nutriMedicineListId) {
                            $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                        }
                    },
                ]);
            },
            'input.presentation.catalog',
            'input.presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                if ($nutriMedicineListId) {
                    $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                }
            },
        ])->findOrFail($solicitud->id);

        $bolsa_eva = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->first();

        $set_infusion = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->where('input_id', 40)
            ->with([
                'input.nutritionMedicineCatalog.presentations' => function ($query) use ($nutriMedicineListId) {
                    $query->with([
                        'listItems' => function ($q) use ($nutriMedicineListId) {
                            if ($nutriMedicineListId) {
                                $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                            }
                        },
                    ]);
                },
                'presentation.catalog',
                'presentation.listItems' => function ($q) use ($nutriMedicineListId) {
                    if ($nutriMedicineListId) {
                        $q->where('nutri_medicine_list_id', $nutriMedicineListId);
                    }
                },
            ])
            ->first();

        $servicio_preparacion = Medicine::where('id', 38)->first();

        $pdf = Pdf::loadView(
            'pdfs.nutricionales.envio',
            compact(
                'solicitud_detalles',
                'inputs_solicitud',
                'bolsa_eva',
                'set_infusion',
                'servicio_preparacion',
                'imprimirMarcas'
            )
        );

        return $pdf->stream();
    }


    public function etiqueta(Solicitud $solicitud)
    {
        $inputs_solicitud = SolicitudInput::where('solicitud_id', $solicitud->id)
            ->whereNotIn('input_id', function ($query) {
                $query->select('id')
                    ->from('inputs')
                    ->where('category_id', '=', 6);
            })
            ->whereNotIn('input_id', [40])
            ->with([
                'input',
                'presentation.catalog',
            ])
            ->get();

        $solicitud_detalles = Solicitud::with([
            'user.hospital',
            'solicitud_detail',
            'solicitud_patient'
        ])->findOrFail($solicitud->id);

        $customPaper = [0, 0, 368.50, 255.12];

        $pdf = Pdf::loadView('pdfs.nutricionales.etiqueta', compact(
            'solicitud_detalles',
            'inputs_solicitud'
        ))->setPaper($customPaper, 'landscape');

        return $pdf->stream();
    }


    public function exportarExcel()
    {
        return Excel::download(new SolicitudesExport, 'solicitudes.xlsx');
    }
}

