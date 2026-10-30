<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Models;

use App\Domain\Vehicle\Enums\AttachmentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File attached to a vehicle (legacy files table).
 *
 * `category` identifies the financial operation the proof belongs to.
 */
class VehicleAttachment extends Model
{
    protected $fillable = [
        'vin',
        'category',
        'path',
        'original_name',
        'note',
        'amount',
        'currency',
        'mime',
        'size',
        'user_id',
    ];

    protected $casts = [
        'category' => AttachmentCategory::class,
        'amount' => 'decimal:2',
        'size' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin', 'vin');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
