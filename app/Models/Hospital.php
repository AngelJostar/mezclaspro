<?php

namespace App\Models;

use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hospital extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_code',
        'name',
        'adress',
        'short_name',
        'internal_key',
        'unit_type',
        'care_level',
        'fiscal_name',
        'rfc',
        'fiscal_regime',
        'cfdi_use',
        'billing_email',
        'billing_phone',
        'clues',
        'free_text',
        'google_maps_url',
        'country',
        'state',
        'municipality',
        'postal_code',
        'neighborhood',
        'street_number',
        'latitude',
        'longitude',
        'utm_zone',
        'utm_hemisphere',
        'utm_easting',
        'utm_northing',
        'contact_name',
        'contact_position',
        'phone',
        'email',
        'reception_hours',
        'operation_days',
        'service_oncology',
        'service_antibiotics',
        'service_nutrition',
        'service_lines_configured',
        'enabled_services',
        'laboratory_id',
        'is_active',
        'access_is_active',
        'nutri_medicine_list_id',
        'onco_medicine_list_id',
        'antibiotic_medicine_list_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'access_is_active' => 'boolean',
        'operation_days' => 'array',
        'service_oncology' => 'boolean',
        'service_antibiotics' => 'boolean',
        'service_nutrition' => 'boolean',
        'service_lines_configured' => 'boolean',
        'enabled_services' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'utm_zone' => 'integer',
        'utm_easting' => 'float',
        'utm_northing' => 'float',
    ];

    public function offersService(string $service): bool
    {
        $legacyList = match ($service) {
            'oncology' => $this->onco_medicine_list_id,
            'nutrition' => $this->nutri_medicine_list_id,
            default => null,
        };

        // Preserve legacy defaults until the hospital's service selection is explicitly saved.
        return (bool) $this->getAttribute('service_'.$service)
            || (! $this->service_lines_configured && (bool) $legacyList);
    }

    public function scopeOfferingService(Builder $query, string $service): Builder
    {
        $legacyColumn = match ($service) {
            'oncology' => 'onco_medicine_list_id',
            'nutrition' => 'nutri_medicine_list_id',
            default => null,
        };

        return $query->where(function (Builder $query) use ($service, $legacyColumn) {
            $query->where('hospitals.service_'.$service, true);
            if ($legacyColumn) {
                $query->orWhere(fn (Builder $legacy) => $legacy
                    ->where('hospitals.service_lines_configured', false)
                    ->whereNotNull('hospitals.'.$legacyColumn));
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function salespeople(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hospital_salesperson')->withTimestamps();
    }

    public function distributionRoutes(): BelongsToMany
    {
        return $this->belongsToMany(DistributionRoute::class, 'distribution_route_hospital')
            ->withPivot(['id', 'stop_order', 'completed_at'])
            ->withTimestamps();
    }

    public function clientes()
    {
        return $this->belongsToMany(\App\Models\Cliente::class, 'cliente_hospital');
    }

    public function instituciones()
    {
        return $this->belongsToMany(\App\Models\Institucion::class, 'cliente_hospital', 'hospital_id', 'cliente_id');
    }

    public function oncoMedicineList()
    {
        return $this->belongsTo(\App\Models\Oncologicos\MedicineList::class, 'onco_medicine_list_id');
    }

    public function antibioticMedicineList()
    {
        return $this->belongsTo(\App\Models\Oncologicos\MedicineList::class, 'antibiotic_medicine_list_id');
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function nutriMedicineList()
    {
        return $this->belongsTo(NutriMedicineList::class, 'nutri_medicine_list_id');
    }
}
