<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Models;

use App\Domain\Vehicle\Enums\VehicleStage;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Fleet vehicle (canonical schema — see 2026_10_09_000100 migration).
 *
 * Legacy short keys from server.js (pdate, voy, ship, ord_no, client, ...)
 * are accepted on write and exposed on read via LEGACY_MAP so the
 * Reporting domain, importer and Livewire forms keep working unchanged.
 */
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'vehicles';

    protected $primaryKey = 'vin';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected static function newFactory(): VehicleFactory
    {
        return VehicleFactory::new();
    }

    public function getRouteKeyName(): string
    {
        return 'vin';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => VehicleStage::class,
            'purchased_at' => 'date',
            'shipped_at' => 'date',
            'arrived_at' => 'date',
            'customs_date' => 'date',
            'warehouse_in_at' => 'date',
            'sold_at' => 'date',
            'delivered_at' => 'date',
            'cost' => 'decimal:2',
            'duty' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    /** Legacy short key => canonical DB column (server.js F keys). */
    public const LEGACY_MAP = [
        'pdate' => 'purchased_at',
        'voy' => 'voyage_no',
        'dest' => 'destination',
        'ship' => 'shipped_at',
        'arr' => 'arrived_at',
        'cust_no' => 'customs_no',
        'cust_date' => 'customs_date',
        'loc' => 'warehouse_location',
        'in_date' => 'warehouse_in_at',
        'ord_no' => 'sale_order_no',
        'client' => 'buyer_name',
        'phone' => 'buyer_phone',
        'price' => 'sale_price',
        'paid' => 'amount_paid',
        'sale_date' => 'sold_at',
        'recv' => 'receiver_name',
        'del_date' => 'delivered_at',
    ];

    /**
     * Translate legacy keys to canonical columns (drops unknown 'mod'-style keys).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function translateLegacyKeys(array $data): array
    {
        foreach (self::LEGACY_MAP as $legacy => $column) {
            if (array_key_exists($legacy, $data) && ! array_key_exists($column, $data)) {
                $data[$column] = $data[$legacy];
            }
            unset($data[$legacy]);
        }

        return $data;
    }

    public function getAttribute($key)
    {
        if (is_string($key) && array_key_exists($key, self::LEGACY_MAP)) {
            $key = self::LEGACY_MAP[$key];
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (is_string($key) && array_key_exists($key, self::LEGACY_MAP)) {
            $key = self::LEGACY_MAP[$key];
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Allow mass-assignment via legacy keys by translating them first.
     */
    public function fill(array $attributes): static
    {
        return parent::fill(self::translateLegacyKeys($attributes));
    }

    /**
     * Expose legacy aliases in toArray() so the Reporting domain
     * (which reads legacy keys like loc, client, ord_no, ...) keeps working.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        foreach (self::LEGACY_MAP as $legacy => $column) {
            if (! array_key_exists($legacy, $array)) {
                $array[$legacy] = $array[$column] ?? null;
            }
        }

        return $array;
    }

    /** @return HasMany<VehicleActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(VehicleActivity::class, 'vin', 'vin');
    }

    /** @param Builder<$this> $query */
    public function scopeByStage(Builder $query, VehicleStage|string $stage): Builder
    {
        $value = $stage instanceof VehicleStage ? $stage->value : $stage;

        return $query->where('stage', $value);
    }

    /** @param Builder<$this> $query */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('vin', 'like', $like)
                ->orWhere('brand', 'like', $like)
                ->orWhere('model', 'like', $like)
                ->orWhere('buyer_name', 'like', $like)
                ->orWhere('sale_order_no', 'like', $like)
                ->orWhere('bl', 'like', $like)
                ->orWhere('voyage_no', 'like', $like);
        });
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->sale_price ?? 0) - (float) ($this->amount_paid ?? 0);
    }

    public function stageValue(): string
    {
        return $this->stage instanceof VehicleStage ? $this->stage->value : (string) $this->stage;
    }

    /**
     * Financial records (sold / delivered) must be kept — only allow
     * deletion before a sale order exists.
     */
    public function isDeletable(): bool
    {
        return ! in_array($this->stageValue(), ['sold', 'delivered'], true);
    }
}
