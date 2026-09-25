<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestQuotationDocument extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['path', 'upload_key'];
}
