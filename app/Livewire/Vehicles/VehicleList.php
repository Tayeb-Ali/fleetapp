<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\VehicleMeta;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;

/** Search + stage filter chips + paginate (mirrors legacy veh()/list()). */
class VehicleList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $stage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'stage' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->search = (string) request()->query('q', request()->query('search', ''));
        $this->stage = (string) request()->query('stage', '');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
        $this->resetPage();
    }

    public function render()
    {
        $stages = VehicleMeta::stages();

        try {
            if (! Schema::hasTable('vehicles')) {
                return view('livewire.vehicles.vehicle-list', [
                    'vehicles' => new LengthAwarePaginator([], 0, 12),
                    'stages' => $stages,
                ]);
            }

            $query = class_exists(Vehicle::class)
                ? Vehicle::query()
                : DB::table('vehicles');

            if ($this->stage !== '') {
                $query->where('stage', $this->stage);
            }

            if (trim($this->search) !== '') {
                $q = '%'.trim($this->search).'%';
                try {
                    $columns = Schema::getColumnListing('vehicles');
                } catch (\Throwable) {
                    $columns = [];
                }
                $candidates = ['brand', 'model', 'vin', 'bl', 'client', 'buyer_name', 'loc', 'warehouse_location', 'ord_no', 'sale_order_no'];
                $searchable = array_values(array_filter($candidates, fn ($c) => in_array($c, $columns, true)));
                if ($searchable === []) {
                    $searchable = ['vin'];
                }
                $query->where(function ($w) use ($q, $searchable) {
                    foreach ($searchable as $i => $col) {
                        $i === 0 ? $w->where($col, 'like', $q) : $w->orWhere($col, 'like', $q);
                    }
                });
            }

            $vehicles = $query->orderBy('vin')->paginate(12);
        } catch (\Throwable) {
            $vehicles = new LengthAwarePaginator([], 0, 12);
        }

        return view('livewire.vehicles.vehicle-list', [
            'vehicles' => $vehicles,
            'stages' => $stages,
        ]);
    }
}
