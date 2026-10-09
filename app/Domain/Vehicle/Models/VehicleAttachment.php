<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * File attached to a vehicle (legacy files table).
 */
class VehicleAttachment extends Model
{
    protected $fillable = [
        'vin',
        'path',
        'original_name',
        'mime',
        'size',
        'user_id',
    ];

    protected $casts = [
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
}
