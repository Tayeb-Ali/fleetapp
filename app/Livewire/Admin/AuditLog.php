<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/** Admin audit log reading vehicle_activities; empty when the table is missing. */
class AuditLog extends Component
{
    use AuthorizesRequests;

    public function render()
    {
        $this->authorize('viewAny', User::class);

        /** @var array<int, array<string, mixed>> $entries */
        $entries = [];
        try {
            if (Schema::hasTable('vehicle_activities')) {
                $entries = DB::table('vehicle_activities')
                    ->orderByDesc('id')
                    ->limit(300)
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }
        } catch (\Throwable) {
            $entries = [];
        }

        $columns = $entries !== [] ? array_keys($entries[0]) : [];

        return view('livewire.admin.audit-log', [
            'entries' => $entries,
            'columns' => $columns,
        ]);
    }
}
