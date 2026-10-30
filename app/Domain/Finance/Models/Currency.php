<?php

declare(strict_types=1);

namespace App\Domain\Finance\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Client-managed currency catalogue entry.
 *
 * The base currency is stored in settings.currency and always converts at 1.
 */
class Currency extends Model
{
    protected $table = 'currencies';

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'decimals' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $name = $locale === 'ar' ? $this->name_ar : $this->name_en;

        return (string) ($name ?: $this->code);
    }
}
