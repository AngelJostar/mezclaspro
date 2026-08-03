<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('mvi_mayor_que_peso', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            return $value > 3.5 * $peso;
        }, 'El valor de MVI debe ser mayor que el peso del paciente.');


        Validator::extend('aminoacidos_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 3 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 2.5 veces el peso.',
                'INF' => 'El valor debe ser mayor a 2.3 veces el peso.',
                'ADOL' => 'El valor debe estar entre 1 y 1.5 veces el peso.',
                'ADULT' => 'El valor debe estar entre 0.8 y 2 veces el peso.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 2.5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 2.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 1 && $value <= 1.5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 0.8 && $value <= 2 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('dextrosa_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            return $value > 5.04 * $peso;
        }, 'El valor de Dextrosa debe ser mayor que 5.04 veces el peso del paciente');



        Validator::extend('lipidos_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            return $value >= 1 && $value < 1.5 * $peso;
        }, 'Asegúrate de que el valor sea mayor o igual a 1 y menor que 1.5 veces el peso');

        Validator::extend('sodio_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            for ($i = 2; $i <= 4; $i++) {
                if ($parameters[$i] == null) {
                    $parameters[$i] = 0;
                }
            }

            $cloruro_sodio = $parameters[2];
            $acetato_sodio = $parameters[3];
            $fosfato_sodio = $parameters[4];

            $mensajes = [
                'RNPT' => 'La suma de cloruro de sodio, acetato de sodio y fosfato de sodio debe estar entre 2 y 6 veces el peso.',
                'LACT' => 'La suma de cloruro de sodio, acetato de sodio y fosfato de sodio debe estar entre 2 y 4 veces el peso.',
                'INF' => 'La suma de cloruro de sodio, acetato de sodio y fosfato de sodio debe estar entre 2 y 3 veces el peso.',
                'ADOL' => 'La suma de cloruro de sodio, acetato de sodio y fosfato de sodio debe estar entre 60 y 150.',
                'ADULT' => 'La suma de cloruro de sodio, acetato de sodio y fosfato de sodio debe estar entre 60 y 150.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $cloruro_sodio + $acetato_sodio + $fosfato_sodio >= 2 && $cloruro_sodio + $acetato_sodio + $fosfato_sodio <= 6 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $cloruro_sodio + $acetato_sodio + $fosfato_sodio >= 2 && $cloruro_sodio + $acetato_sodio + $fosfato_sodio <= 4 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $cloruro_sodio + $acetato_sodio + $fosfato_sodio >= 2 && $cloruro_sodio + $acetato_sodio + $fosfato_sodio <= 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $cloruro_sodio + $acetato_sodio + $fosfato_sodio >= 60 && $cloruro_sodio + $acetato_sodio + $fosfato_sodio <= 150 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $cloruro_sodio + $acetato_sodio + $fosfato_sodio >= 60 && $cloruro_sodio + $acetato_sodio + $fosfato_sodio <= 150 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });


        Validator::extend('potasio_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            for ($i = 2; $i <= 4; $i++) {
                if ($parameters[$i] == null) {
                    $parameters[$i] = 0;
                }
            }

            $cloruro_potasio = $parameters[2];
            $acetato_potasio = $parameters[3];
            $fosfato_potasio = $parameters[4];

            $mensajes = [
                'RNPT' => 'El valor de cloruro, acetato y fosfato de potasio debe estar entre 1 y 3 veces el peso.',
                'LACT' => 'El valor de cloruro, acetato y fosfato de potasio debe estar entre 2 y 3 veces el peso.',
                'INF' => 'El valor de cloruro, acetato y fosfato de potasio debe estar entre 2 y 3 veces el peso.',
                'ADOL' => 'El valor de cloruro, acetato y fosfato de potasio debe estar entre 60 y 150.',
                'ADULT' => 'El valor de cloruro, acetato y fosfato de potasio debe estar entre 60 y 150.',
                'default' => 'El tipo de paciente no es válido.',
            ];

            switch ($npt) {
                case 'RNPT':
                    return $cloruro_potasio + $acetato_potasio + $fosfato_potasio >= 1 && $cloruro_potasio + $acetato_potasio + $fosfato_potasio <= 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $cloruro_potasio + $acetato_potasio + $fosfato_potasio >= 2 && $cloruro_potasio + $acetato_potasio + $fosfato_potasio <= 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $cloruro_potasio + $acetato_potasio + $fosfato_potasio >= 2 && $cloruro_potasio + $acetato_potasio + $fosfato_potasio <= 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $cloruro_potasio + $acetato_potasio + $fosfato_potasio >= 60 && $cloruro_potasio + $acetato_potasio + $fosfato_potasio <= 150 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $cloruro_potasio + $acetato_potasio + $fosfato_potasio >= 60 && $cloruro_potasio + $acetato_potasio + $fosfato_potasio <= 150 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });


        Validator::extend('magnesio_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe estar entre 0.25 y 0.65 veces el peso.',
                'LACT' => 'El valor debe estar entre 0.25 y 0.5 veces el peso.',
                'INF' => 'El valor debe estar entre 0.25 y 0.5 veces el peso.',
                'ADOL' => 'El valor debe estar entre 9 y 21.',
                'ADULT' => 'El valor debe estar entre 9 y 21.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value >= .25 && $value <= .65 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value >= .25 && $value <= .5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value >= .25 && $value <= .5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 9 && $value <= 21 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 9 && $value <= 21 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('calcio_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe estar entre 2 y 4.5 veces el peso.',
                'LACT' => 'El valor debe estar entre 1 y 1.25 veces el peso.',
                'INF' => 'El valor debe estar entre 0.5 y 1 veces el peso.',
                'ADOL' => 'El valor debe estar entre 4.5 y 9.',
                'ADULT' => 'El valor debe estar entre 4.5 y 9.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value >= 2 && $value <= 4.5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value >= 1 && $value <= 1.25 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value >= .5 && $value <= 1 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 4.5 && $value <= 9 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 4.5 && $value <= 9 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('albumina_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $value_ml = $parameters[1];
            return $value_ml >= 500 && $value_ml <= 100 * $peso;
        }, 'El valor en ml de Albumina debe estar entre 500 y 100 veces el peso del paciente.');

        Validator::extend('glutamina_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $value_ml = $parameters[1];
            return $value_ml >= 1.5 && $value_ml <= 2 * $peso;
        }, 'El valor en ml de Glutamina debe estar entre 1.5 y 2 veces el peso del paciente.');

        Validator::extend('cromo_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 0.2 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 0.2 veces el peso.',
                'INF' => 'El valor debe ser mayor a 0.2 veces el peso.',
                'ADOL' => 'El valor debe estar entre 10 y 20.',
                'ADULT' => 'El valor debe estar entre 10 y 20.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 0.2 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 0.2 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 0.2 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 10 && $value <= 20 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 10 && $value <= 20 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('heparina_validaciones', function ($attribute, $value, $parameters, $validator) {
            return $value >= 0.5 && $value <= 1;
        }, 'El valor debe estar entre 0.5 y 1.');

        Validator::extend('carnitina_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $value_ml = $parameters[1];
            return $value_ml >= 500 && $value_ml <= 100 * $peso;
        }, 'El valor en ml de carnitina debe estar entre 500 y 100 veces el peso del paciente.');

        Validator::extend('insulina_validaciones', function ($attribute, $value, $parameters, $validator) {
            return $value >= 1.2 && $value <= 2.4;
        }, 'El valor debe estar entre 1.2 y 2.4.');

        Validator::extend('manganeso_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe estar entre 2 y 10 veces el peso.',
                'LACT' => 'El valor debe estar entre 2 y 10 veces el peso.',
                'INF' => 'El valor debe estar entre 2 y 10 veces el peso.',
                'ADOL' => 'El valor debe estar entre 150 y 800 veces el peso.',
                'ADULT' => 'El valor debe estar entre 150 y 800 veces el peso.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value >= 2 && $value <= 10 * $peso  ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value >= 2 && $value <= 10 * $peso  ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value >= 2 && $value <= 10 * $peso  ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 150 && $value <= 800 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 150 && $value <= 800 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('multivitaminico_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 3.5 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 5 veces el peso.',
                'INF' => 'El valor debe ser mayor a 5 veces el peso.',
                'ADOL' => 'El valor debe estar entre 5 y 10.',
                'ADULT' => 'El valor debe estar entre 5 y 10.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 3.5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 5 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 5 && $value <= 10 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 5 && $value <= 10 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('oligoelementos_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 0.3 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 0.3 veces el peso.',
                'INF' => 'El valor debe ser mayor a 0.3 veces el peso.',
                'ADOL' => 'El valor debe ser mayor a 0.3 veces el peso.',
                'ADULT' => 'El valor debe ser mayor a 20.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 0.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 0.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 0.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value > 0.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value > 20 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });

        Validator::extend('selenio_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 3 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 3 veces el peso.',
                'INF' => 'El valor debe ser mayor a 3 veces el peso.',
                'ADOL' => 'El valor debe ser mayor a 3 veces el peso.',
                'ADULT' => 'El valor debe estar entre 20 y 400.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value > 3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 20 && $value <= 400 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });


        Validator::extend('zinc_validaciones', function ($attribute, $value, $parameters, $validator) {
            $peso = $parameters[0];
            $npt = $parameters[1];

            $mensajes = [
                'RNPT' => 'El valor debe ser mayor a 0.3 veces el peso.',
                'LACT' => 'El valor debe ser mayor a 0.1 veces el peso.',
                'INF' => 'El valor debe ser mayor a 0.1 veces el peso.',
                'ADOL' => 'El valor debe estar entre 2.5 y 12.',
                'ADULT' => 'El valor debe estar entre 2.5 y 12.',
                'default' => 'El valor no cumple con los criterios de validación.'
            ];

            switch ($npt) {
                case 'RNPT':
                    return $value > 0.3 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'LACT':
                    return $value > 0.1 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'INF':
                    return $value > 0.1 * $peso ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADOL':
                    return $value >= 2.5  && $value <= 12 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                case 'ADULT':
                    return $value >= 2.5  && $value <= 12 ? true : $validator->errors()->add($attribute, $mensajes[$npt]);
                default:
                    return $validator->errors()->add($attribute, $mensajes['default']);
            }
        });
    }
}
