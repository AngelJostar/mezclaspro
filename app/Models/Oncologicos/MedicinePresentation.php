<?php

namespace App\Models\Oncologicos;

use App\Models\MedicineRemainder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicinePresentation extends Model
{
    use HasFactory;

    protected $table = 'medicine_presentations';

    protected $fillable = [
        'external_code',
        'catalog_id',
        'presentacion',
        'contenido_valor',
        'contenido_unidad',
        'marca',
        'fabricante',
        'cantidad_medicamento',
        'volumen_diluyente',
        'is_available',
        'virtual_stock',
        'precio_frasco',

        // ✅ NUEVOS
        'legend',
        'forma_reconstitucion',
        'temp_min_c',
        'temp_max_c',
        'stability_hours',
    ];

    protected $casts = [
        'contenido_valor'   => 'decimal:2',
        'is_available'      => 'boolean',
        'virtual_stock'     => 'integer',
        'precio_frasco'     => 'decimal:4',

        // ✅ NUEVOS
        'temp_min_c'        => 'integer',
        'temp_max_c'        => 'integer',
        'stability_hours'   => 'integer',
    ];

    public function catalog()
    {
        return $this->belongsTo(MedicinesCatalog::class, 'catalog_id');
    }

    public function batches()
    {
        return $this->hasMany(MedicineBatch::class, 'medicine_presentation_id');
    }

    public function remainders()
    {
        return $this->hasMany(MedicineRemainder::class, 'medicine_presentation_id');
    }

    // Lote vigente (is_current=1)
    public function currentBatch()
    {
        return $this->hasOne(MedicineBatch::class, 'medicine_presentation_id')
            ->where('is_current', true);
    }

    public function contentInMilligrams(): ?float
    {
        return self::contentInMilligramsFrom(
            $this->contenido_valor,
            $this->contenido_unidad,
            $this->cantidad_medicamento,
            $this->presentacion
        );
    }

    public static function contentInMilligramsFrom($content, $unit, $medicineAmount, $presentation): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(mcg|ug|µg|mg|g)\b/iu', (string) $presentation, $matches)) {
            $value = (float) str_replace(',', '.', $matches[1]);
            $presentationUnit = mb_strtolower($matches[2], 'UTF-8');

            return match ($presentationUnit) {
                'g' => $value * 1000,
                'mcg', 'ug', 'µg' => $value / 1000,
                default => $value,
            };
        }

        $content = (float) ($content ?: 0);
        $unit = mb_strtolower(trim((string) $unit), 'UTF-8');

        if ($content > 0 && $unit === 'mg') {
            return $content;
        }

        if ($content > 0 && $unit === 'g') {
            return $content * 1000;
        }

        $medicineAmount = (float) ($medicineAmount ?: 0);
        if ($medicineAmount > 0) {
            return $medicineAmount;
        }

        return null;
    }

    public function lists()
    {
        return $this->belongsToMany(
            MedicineList::class,
            'medicine_list_presentation',
            'medicine_presentation_id',
            'medicine_list_id'
        )->withPivot([
            'charge_by',
            'precio',
            'precio_mg_override',
            'precio_ml_override',
            'iva_desglosado',
            'descripcion_remision',
        ])->withTimestamps();
    }
}
