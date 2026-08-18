<?php

namespace App\Models\Oncologicos;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaboratoryPurchaseOrder extends Model
{
    protected $fillable = [
        'laboratory_id',
        'delivery_laboratory_id',
        'warehouse_id',
        'folio',
        'department',
        'supplier',
        'supplier_rfc',
        'supplier_bank_details',
        'supplier_address',
        'supplier_contact',
        'supplier_phone',
        'quotation_number',
        'order_type',
        'supplier_email',
        'supplier_fax',
        'requested_at',
        'proposed_delivery_at',
        'urgent_delivery_time',
        'invoice_to',
        'invoice_address',
        'invoice_rfc',
        'invoice_emails',
        'delivery_attention',
        'delivery_address',
        'delivery_schedule',
        'details',
        'items',
        'subtotal',
        'discount',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'prepared_by',
        'status',
        'created_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'proposed_delivery_at' => 'date',
        'items' => 'array',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function deliveryLaboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class, 'delivery_laboratory_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
