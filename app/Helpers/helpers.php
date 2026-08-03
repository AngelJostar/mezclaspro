<?php

use Carbon\Carbon;

/**
 * Convierte cualquier valor a float de forma robusta:
 * - Acepta strings con comas, espacios, "ml", etc.
 * - Soporta notación científica.
 * - null, vacío o no numérico => 0.0
 */
function toFloat($value): float
{
    if ($value === null) {
        return 0.0;
    }

    $v = preg_replace('/[^\d\.\-eE]/', '', (string) $value);

    if ($v === '' || $v === '.' || $v === '-') {
        return 0.0;
    }

    return (float) $v;
}

/**
 * Formatea cualquier valor a 3 decimales.
 */
function fmt3($value): string
{
    return number_format(toFloat($value), 3, '.', '');
}

/**
 * Valor crudo del input.
 */
function renderInputSection($id, $inputs_solicitud)
{
    $inputValue = '';

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id) {
            $inputValue = $inputItem->valor;
            break;
        }
    }

    return $inputValue;
}

/**
 * Valor ML limpio.
 */
function renderInputMLSection($id, $inputs_solicitud): float
{
    $value = null;

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id) {
            $value = $inputItem->valor_ml;
            break;
        }
    }

    return toFloat($value);
}

/**
 * Valor sobrellenado limpio.
 */
function renderInputMLSobrellenadoSection($id, $inputs_solicitud): float
{
    $value = null;

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id) {
            $value = $inputItem->valor_sobrellenado;
            break;
        }
    }

    return toFloat($value);
}

/**
 * Lote del input.
 */
function renderLoteSection($id, $inputs_solicitud)
{
    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id && !empty($inputItem->lote)) {
            return $inputItem->lote;
        }
    }

    return '';
}

/**
 * Caducidad del input.
 */
function renderCaducidadSection($id, $inputs_solicitud)
{
    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id && !empty($inputItem->caducidad)) {
            return Carbon::parse($inputItem->caducidad)->format('Y-m-d');
        }
    }

    return '';
}

/**
 * Guarda/retorna la bolsa EVA seleccionada.
 */
function renderBolsaEvaInputSection($id, $inputs_solicitud)
{
    global $inputBolsaEva;

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $id) {
            $inputBolsaEva = $inputItem->input_id;

            return $inputItem->input_id;
        }
    }

    return null;
}

/**
 * Lote bolsa EVA.
 */
function renderLoteBolsaEvaSection($inputs_solicitud)
{
    global $inputBolsaEva;

    $inputLote = '';

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $inputBolsaEva) {
            $inputLote = $inputItem->lote;
            break;
        }
    }

    return $inputLote;
}

/**
 * Caducidad bolsa EVA.
 */
function renderCaducidadBolsaEvaSection($inputs_solicitud)
{
    global $inputBolsaEva;

    $inputCaducidad = '';

    foreach ($inputs_solicitud as $inputItem) {
        if ($inputItem->input_id == $inputBolsaEva) {
            $inputCaducidad = $inputItem->caducidad;
            break;
        }
    }

    return $inputCaducidad;
}
