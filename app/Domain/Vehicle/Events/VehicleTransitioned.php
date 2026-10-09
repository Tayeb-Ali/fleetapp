<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Events;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class VehicleTransitioned
{
    use Dispatchable;

    public function __construct(
        public Vehicle $vehicle,
        public string $transitionKey,
        public VehicleStage|string $from,
        public VehicleStage|string $to,
        public ?User $user = null,
    ) {}

    public function fromValue(): string
    {
        return $this->from instanceof VehicleStage ? $this->from->value : (string) $this->from;
    }

    public function toValue(): string
    {
        return $this->to instanceof VehicleStage ? $this->to->value : (string) $this->to;
    }
}
