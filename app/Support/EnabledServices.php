<?php

namespace App\Support;

use App\Models\Hospital;
use App\Models\Institucion;

final class EnabledServices
{
    public const QUOTATIONS = 'quotation_requests';
    public const LABELS = [self::QUOTATIONS => 'Solicitud de cotización'];

    public static function institution(Institucion $institution, string $service = self::QUOTATIONS): bool
    {
        // Existing institutions retain access until an administrator changes the setting.
        return (bool) ($institution->enabled_services[$service] ?? true);
    }

    public static function inherited(Hospital $hospital, string $service = self::QUOTATIONS): bool
    {
        // A shared hospital inherits the most restrictive institution setting; its own override wins.
        return $hospital->instituciones->every(fn ($institution) => self::institution($institution, $service));
    }

    public static function hospital(Hospital $hospital, string $service = self::QUOTATIONS): bool
    {
        return $hospital->enabled_services[$service] ?? self::inherited($hospital, $service);
    }
}
