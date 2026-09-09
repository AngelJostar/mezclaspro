<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiAgent extends Model
{
    protected $fillable = ['name', 'description', 'instructions'];
}
