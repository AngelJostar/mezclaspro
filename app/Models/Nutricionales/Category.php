<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function nutritionMedicineCatalogs()
    {
        return $this->hasMany(NutritionMedicineCatalog::class, 'category_id');
    }

    public function inputs()
    {
        return $this->hasMany(Input::class, 'category_id');
    }
}
