<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Vehicle\Support\ActivityPresenter;
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

        /** @var array<int, array<string, mixed>> $rows */
        $rows = [];
        try {
            if (Schema::hasTable('vehicle_activities')) {
                $rows = DB::table('vehicle_activities')
                    ->orderByDesc('id')
                    ->limit(300)
                    ->get()
                    ->map(fn ($row) => (array) $row)
                    ->all();
            }
        } catch (\Throwable) {
            $rows = [];
        }

        $userIds = collect($rows)->pluck('user_id')->filter()->unique()->all();
        $users = $userIds !== [] ? User::whereIn('id', $userIds)->pluck('name', 'id') : collect();

        $entries = collect($rows)->map(function (array $row) use ($users) {
            $userId = $row['user_id'] ?? null;

            return [
                'id' => $row['id'] ?? null,
                'date' => ActivityPresenter::datetime($row['created_at'] ?? ''),
                'user' => is_numeric($userId)
                    ? (string) ($users[$userId] ?? __('audit.system'))
                    : __('audit.system'),
                'action' => (string) ($row['action'] ?? ''),
                'action_label' => ActivityPresenter::action($row['action'] ?? null),
                'vin' => (string) ($row['vin'] ?? ''),
                'from' => (string) ($row['from_stage'] ?? ''),
                'to' => (string) ($row['to_stage'] ?? ''),
                'note' => ActivityPresenter::note($row['note'] ?? null),
            ];
        })->values();

        return view('livewire.admin.audit-log', [
            'entries' => $entries,
        ]);
    }
}
