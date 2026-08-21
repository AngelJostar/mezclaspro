<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class DistributionRoute extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_ROUTE = 'in_route';

    public const STATUS_DELAYED = 'delayed';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'name',
        'code',
        'schedule_start',
        'schedule_end',
        'status',
        'qr_token',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $route) {
            $route->qr_token ??= (string) Str::uuid();
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Por iniciar',
            self::STATUS_IN_ROUTE => 'En ruta',
            self::STATUS_DELAYED => 'Con retraso',
            self::STATUS_COMPLETED => 'Finalizada',
        ];
    }

    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'distribution_route_hospital')
            ->withPivot(['stop_order', 'completed_at'])
            ->withTimestamps()
            ->orderByPivot('stop_order');
    }

    public function messengers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'distribution_route_messenger')
            ->withTimestamps()
            ->orderBy('name')
            ->orderBy('lastname');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeMatching(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $nested) use ($search) {
            $nested
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhereHas('hospitals', fn (Builder $hospitalQuery) => $hospitalQuery->where('name', 'like', "%{$search}%"))
                ->orWhereHas('messengers', function (Builder $messengerQuery) use ($search) {
                    $messengerQuery->whereRaw("TRIM(CONCAT(COALESCE(name, ''), ' ', COALESCE(lastname, ''))) LIKE ?", ["%{$search}%"]);
                });
        });
    }

    public function scheduleLabel(): string
    {
        return substr((string) $this->schedule_start, 0, 5).'–'.substr((string) $this->schedule_end, 0, 5);
    }
}
