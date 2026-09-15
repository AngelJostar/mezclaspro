<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MixtureMessage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Los mensajes no se pueden editar.'));
        static::deleting(fn () => throw new \LogicException('Los mensajes no se pueden borrar.'));
    }
}
