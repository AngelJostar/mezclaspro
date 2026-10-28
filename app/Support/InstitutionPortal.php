<?php

namespace App\Support;

class InstitutionPortal
{
    public const PERMISSIONS = [
        'menu.solicitudes', 'nutricionales_solicitudes_index', 'nutricionales_solicitudes_show',
        'oncologicos_solicitudes_index', 'oncologicos_mezclas_index', 'oncologicos_mezclas_show',
    ];

    // Only audited read endpoints are available. GET forms and all business writes are denied.
    public const READ_ROUTES = [
        'admin.dashboard', 'admin.solicitudes.index', 'admin.solicitudes.exportar',
        'admin.nutricionales.solicitudes.index', 'admin.nutricionales.solicitudes.show',
        'admin.nutricionales.solicitudes.solicitud', 'admin.nutricionales.solicitudes.exportar',
        'admin.oncologicos.solicitudes.index', 'admin.antibioticos.solicitudes.index',
        'admin.oncologicos.solicitudes.exportar', 'admin.oncologicos.mezclas.index',
        'admin.oncologicos.mezclas.show', 'admin.oncologicos.mezclas.solicitudCompleta',
        'admin.oncologicos.mezclas.ordenPreparacion', 'admin.oncologicos.mezclas.inspeccion', 'admin.oncologicos.mezclas.envio',
        'admin.solicitudes.cotizacion.index', 'admin.solicitudes.cotizacion.export',
        'admin.solicitudes.cotizacion.show', 'admin.solicitudes.cotizacion.attachment',
        'admin.solicitudes.cotizacion.pdf', 'admin.solicitudes.cotizacion.documents.index',
        'admin.solicitudes.cotizacion.documents.download', 'admin.herramientas.index',
        'admin.herramientas.conciliaciones.detail', 'admin.herramientas.conciliaciones.download',
        'admin.solicitudes.mensajes.summary', 'admin.solicitudes.mensajes.show', 'admin.solicitudes.ajustes.show',
    ];
}
