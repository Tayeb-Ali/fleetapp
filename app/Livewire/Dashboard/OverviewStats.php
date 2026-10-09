<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\VehicleMeta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Overview grid: counts per stage + tasks waiting per role (mirrors legacy ov()). */
class OverviewStats extends Component
{
    public function counts(): array
    {
        try {
            if (class_exists(Vehicle::class) && Schema::hasTable('vehicles')) {
                $rows = Vehicle::query()->get(['stage'])->toArray();
            } elseif (Schema::hasTable('vehicles')) {
                $rows = DB::table('vehicles')->get(['stage'])->map(fn ($r) => (array) $r)->all();
            } else {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        $counts = [];
        foreach ($rows as $row) {
            $stage = (string) ($row['stage'] ?? '');
            $counts[$stage] = ($counts[$stage] ?? 0) + 1;
        }

        return $counts;
    }

    public function tasks(): array
    {
        $counts = $this->counts();
        $transitions = VehicleMeta::transitions();
        $role = auth()->user()->role ?? 'viewer';
        $isAdmin = ($role === 'admin');

        $tasks = [];
        foreach ($transitions as $key => $t) {
            if (! $isAdmin && ! in_array($role, (array) ($t['roles'] ?? []), true)) {
                continue;
            }
            $n = 0;
            foreach ((array) ($t['from'] ?? []) as $from) {
                $n += (int) ($counts[$from] ?? 0);
            }
            if ($n > 0) {
                $tasks[] = ['key' => $key, 'label' => $t['label'] ?? $key, 'from' => $t['from'] ?? [], 'count' => $n];
            }
        }

        return $tasks;
    }

    public function render()
    {
        $counts = $this->counts();
        $stages = VehicleMeta::stages();
        $colors = VehicleMeta::stageColors();
        $total = array_sum($counts);

        return view('livewire.dashboard.overview-stats', [
            'counts' => $counts,
            'tasks' => $this->tasks(),
            'stages' => $stages,
            'colors' => $colors,
            'total' => $total,
        ]);
    }
}
