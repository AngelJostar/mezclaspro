<?php

namespace App\Models\Concerns;

use App\Models\RequestQuotation;

trait HasSourceQuotation
{
    public function quotation()
    {
        return $this->belongsTo(RequestQuotation::class, 'request_quotation_id');
    }

    public function getRequestFolioAttribute(): string
    {
        return ($this->request_quotation_id ? 'COT-' : '').$this->id;
    }
}
