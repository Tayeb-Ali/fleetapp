<?php

declare(strict_types=1);

namespace App\Domain\Vehicle\Support;

use App\Domain\Vehicle\Enums\VehicleStage;
use App\Domain\Vehicle\Transitions\CompleteClearanceTransition;
use App\Domain\Vehicle\Transitions\ConfirmArrivalTransition;
use App\Domain\Vehicle\Transitions\DeliverVehicleTransition;
use App\Domain\Vehicle\Transitions\IssueSaleOrderTransition;
use App\Domain\Vehicle\Transitions\ReceiveIntoStockTransition;
use App\Domain\Vehicle\Transitions\ShipVehicleTransition;
use App\Domain\Vehicle\Transitions\StartClearanceTransition;
use App\Domain\Vehicle\Transitions\TransitionInterface;
use InvalidArgumentException;

class TransitionRegistry
{
    /** @var array<string, class-string<TransitionInterface>> */
    protected array $map = [
        'ship' => ShipVehicleTransition::class,
        'arrive' => ConfirmArrivalTransition::class,
        'clear1' => StartClearanceTransition::class,
        'clear2' => CompleteClearanceTransition::class,
        'receive' => ReceiveIntoStockTransition::class,
        'sell' => IssueSaleOrderTransition::class,
        'deliver' => DeliverVehicleTransition::class,
    ];

    /** @return array<string, TransitionInterface> key => instance */
    public function all(): array
    {
        $out = [];
        foreach ($this->map as $key => $class) {
            $out[$key] = app($class);
        }

        return $out;
    }

    public function get(string $key): TransitionInterface
    {
        $class = $this->map[$key] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Unknown transition [{$key}].");
        }

        return app($class);
    }

    public function has(string $key): bool
    {
        return isset($this->map[$key]);
    }

    /**
     * Transitions available from the given stage.
     *
     * @return array<string, TransitionInterface>
     */
    public function forStage(VehicleStage|string $stage): array
    {
        $value = $stage instanceof VehicleStage ? $stage->value : $stage;

        return array_filter(
            $this->all(),
            fn (TransitionInterface $t) => in_array($value, $t->from(), true)
        );
    }

    /** Shape useful for API docs / meta endpoints (legacy meta().trans). */
    public function definitionForDocs(): array
    {
        return array_values(array_map(
            fn (TransitionInterface $t) => [
                'key' => $t->key(),
                'label' => $t->labelKey(),
                'from' => $t->from(),
                'to' => $t->to()->value,
                'roles' => $t->allowedRoles(),
                'fields' => $t->fields(),
                'required' => $t->requiredFields(),
                'notify' => $t->notifyRoles(),
            ],
            $this->all()
        ));
    }
}
