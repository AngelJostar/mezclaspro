<?php

namespace App\Services\Agents;

use App\Models\AiAgent;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AgentConfiguration
{
    public const ACTIVATIONS = ['manual' => 'Manual', 'hourly' => 'Cada hora', 'daily' => 'Cada 24 horas', 'changes' => 'Al cambiar los registros (revisión cada minuto)'];
    public const SOURCES = [
        'movements' => 'Movimientos de medicamentos', 'batches' => 'Lotes de medicamentos',
        'nutrition' => 'Existencias nutricionales', 'diluents' => 'Diluyentes', 'consumables' => 'Consumibles',
        'onco' => 'Mezclas oncológicas y antibióticos', 'nutri' => 'Solicitudes nutricionales',
        'supply_requests' => 'Abasto a producción', 'billing' => 'Facturación', 'orders' => 'Compras',
        'maintenance' => 'Servicios y equipos', 'deliveries' => 'Entregas', 'findings' => 'Hallazgos de agentes',
        'conciliations' => 'Conciliaciones recibidas de hospitales',
    ];
    public const TOOLS = ['audit' => 'Consultar y calcular', 'alerts' => 'Registrar alertas internas'];
    public const RESULTS = ['evidence' => 'Folios y evidencia', 'impact' => 'Impacto y cálculo', 'action' => 'Acción sugerida'];

    public static function rules(): array
    {
        return [
            'conciliation' => ['name' => 'Agente de conciliación', 'label' => 'Revisar conciliaciones recibidas y datos faltantes', 'sources' => ['conciliations'], 'owner' => 'Administración / Conciliaciones', 'limit' => 'Revisión de los datos guardados al enviar. No aprueba conciliaciones ni valida precios. Las mezclas reenviadas en distintos folios se cuentan por envío.'],
            'consumption' => ['name' => 'Auditoría de consumos', 'label' => 'Conciliar salidas y existencias de medicamentos', 'sources' => ['movements'], 'owner' => 'Producción / Almacén', 'limit' => 'No reconstruye reprocesos ni consumos de insumos sin movimientos vinculados.'],
            'production' => ['name' => 'Producción y tiempos', 'label' => 'Detectar entregas próximas o atrasadas', 'sources' => ['onco', 'nutri'], 'owner' => 'Coordinación de producción', 'limit' => 'No estima duración de etapas ni capacidad de producción.'],
            'inventory' => ['name' => 'Inventarios y abasto', 'label' => 'Revisar reservas y abasto aprobado a producción', 'sources' => ['batches', 'nutrition', 'diluents', 'consumables', 'supply_requests'], 'owner' => 'Almacén / Abasto', 'limit' => 'La demanda considera requisiciones de insumos aprobadas; no convierte prescripciones en necesidades futuras de medicamentos.'],
            'expiry' => ['name' => 'Caducidades y mermas', 'label' => 'Identificar lotes con existencia próximos a vencer', 'sources' => ['batches', 'nutrition', 'diluents', 'consumables'], 'owner' => 'Almacén / Responsable sanitario', 'limit' => 'No calcula mermas ni determina sustituciones clínicas o disponibilidad FEFO.'],
            'quality' => ['name' => 'Calidad y trazabilidad', 'label' => 'Comprobar lote e inspección de entregas', 'sources' => ['onco', 'nutri'], 'owner' => 'Responsable sanitario', 'limit' => 'Control documental; no realiza validaciones químicas ni médicas.'],
            'purchases' => ['name' => 'Compras y proveedores', 'label' => 'Detectar pedidos sin fecha de entrega propuesta', 'sources' => ['orders'], 'owner' => 'Compras', 'limit' => 'No presume recepción ni compara precios de condiciones comerciales diferentes.'],
            'costs' => ['name' => 'Costos y rentabilidad', 'label' => 'Identificar lotes sin costo unitario registrado', 'sources' => ['batches'], 'owner' => 'Administración / Costos', 'limit' => 'No calcula margen: faltan costos consolidados de insumos, preparación y distribución.'],
            'invoicing' => ['name' => 'Remisiones y facturación', 'label' => 'Conciliar entregas con datos de facturación', 'sources' => ['onco', 'nutri', 'billing'], 'owner' => 'Facturación', 'limit' => 'Comprueba los campos de factura del sistema, no la validez fiscal externa.'],
            'collection' => ['name' => 'Cobranza', 'label' => 'Revisar facturas sin compensación según antigüedad', 'sources' => ['billing'], 'owner' => 'Cobranza', 'limit' => 'Antigüedad no equivale a vencimiento contractual; el importe no es un saldo conciliado con pagos parciales.'],
            'distribution' => ['name' => 'Distribución y entregas', 'label' => 'Detectar entregas programadas sin confirmación', 'sources' => ['deliveries'], 'owner' => 'Distribución', 'limit' => 'Se usa la fecha programada; no hay estimación de ruta ni horario de llegada.'],
            'maintenance' => ['name' => 'Mantenimiento y equipos', 'label' => 'Comprobar identificación y frecuencia de servicios', 'sources' => ['maintenance'], 'owner' => 'Mantenimiento', 'limit' => 'El catálogo no registra la próxima fecha de servicio ni equipos asignados a producción.'],
            'summary' => ['name' => 'Resumen de dirección', 'label' => 'Consolidar hallazgos abiertos por prioridad', 'sources' => ['findings'], 'owner' => 'Dirección', 'limit' => 'Depende de las últimas revisiones de los otros agentes; no vuelve a auditar sus fuentes.'],
        ];
    }

    public static function defaults(?AiAgent $agent = null): array
    {
        $rule = collect(self::rules())->search(fn ($r) => $r['name'] === $agent?->name);
        $definition = self::rules()[$rule] ?? null;
        return [
            'objective' => $agent?->description ?? '', 'rules' => $definition ? [$rule] : [],
            'scope_all' => false, 'institutions' => [], 'laboratories' => [], 'warehouses' => [],
            'sources' => $definition['sources'] ?? [], 'tools' => ['audit', 'alerts'],
            'activation' => 'manual', 'owner' => $definition['owner'] ?? '',
            'priority' => 'medium',
            'analysis' => 'rules',
            'results' => array_keys(self::RESULTS),
            'thresholds' => ['expiry_days' => 30, 'delivery_minutes' => 60, 'collection_days' => 30],
        ];
    }

    public static function validate(array $config, bool $executable = false): array
    {
        $rules = [
            'objective' => [$executable ? 'required' : 'nullable', 'string', 'max:2000'],
            'owner' => [$executable ? 'required' : 'nullable', 'string', 'max:160'],
            'scope_all' => ['required', 'boolean'], 'activation' => ['required', Rule::in(array_keys(self::ACTIVATIONS))],
            'priority' => ['required', Rule::in(['high', 'medium', 'low'])],
            'analysis' => ['required', Rule::in(['rules', 'openai'])],
            'rules' => ['present', 'array', 'max:13'], 'rules.*' => ['string', 'distinct', Rule::in(array_keys(self::rules()))],
            'sources' => ['present', 'array'], 'sources.*' => ['string', 'distinct', Rule::in(array_keys(self::SOURCES))],
            'tools' => ['present', 'array'], 'tools.*' => ['string', 'distinct', Rule::in(array_keys(self::TOOLS))],
            'results' => ['present', 'array'], 'results.*' => ['string', 'distinct', Rule::in(array_keys(self::RESULTS))],
            'thresholds.expiry_days' => ['required', 'integer', 'between:0,365'],
            'thresholds.delivery_minutes' => ['required', 'integer', 'between:0,10080'],
            'thresholds.collection_days' => ['required', 'integer', 'between:1,3650'],
        ];
        foreach (['institutions' => 'clientes', 'laboratories' => 'laboratories', 'warehouses' => 'warehouses'] as $field => $table) {
            $rules[$field] = ['present', 'array', 'max:500'];
            $rules[$field.'.*'] = ['integer', 'distinct', Rule::exists($table, 'id')];
        }
        $validated = Validator::make($config, $rules)->validate();
        if ($executable && (empty($config['rules']) || ! in_array('audit', $config['tools']))) {
            throw ValidationException::withMessages(['agentConfig' => 'Selecciona reglas y permite consultar y calcular.']);
        }
        if ($executable && ! $config['scope_all'] && ! $config['institutions'] && ! $config['laboratories'] && ! $config['warehouses']) {
            throw ValidationException::withMessages(['agentConfig' => 'Selecciona un alcance antes de ejecutar.']);
        }
        if ($executable && ! in_array('evidence', $config['results'])) {
            throw ValidationException::withMessages(['agentConfig' => 'Los resultados deben incluir folios y evidencia.']);
        }
        return $validated;
    }
}
