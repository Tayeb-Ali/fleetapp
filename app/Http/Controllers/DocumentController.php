<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Vehicle\Models\Vehicle;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Printable A4 documents (legacy docPdf: sale-order + delivery-note).
 *
 * Guards mirror legacy: sale-order requires sale_order_no (ord_no),
 * delivery-note requires stage == delivered.
 */
class DocumentController extends Controller
{
    protected function company(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $raw = DB::table('settings')->where('k', 's')->value('v');
                if ($raw) {
                    $data = json_decode((string) $raw, true);
                    if (! empty($data['company'])) {
                        return (string) $data['company'];
                    }
                }
            }
        } catch (\Throwable) {
            // fall through to config default
        }

        return (string) config('fleet.company', config('app.name', 'RASD Tech'));
    }

    protected function currency(): string
    {
        try {
            if (Schema::hasTable('settings')) {
                $raw = DB::table('settings')->where('k', 's')->value('v');
                if ($raw) {
                    $data = json_decode((string) $raw, true);
                    if (! empty($data['currency'])) {
                        return (string) $data['currency'];
                    }
                }
            }
        } catch (\Throwable) {
            // fall through to config default
        }

        return (string) config('fleet.currency', 'USD');
    }

    protected function findVehicle(string $vin): ?array
    {
        if (class_exists(Vehicle::class)) {
            $model = Vehicle::class;
            $found = $model::query()->where('vin', $vin)->first();

            return $found ? $found->toArray() : null;
        }

        try {
            if (! Schema::hasTable('vehicles')) {
                return null;
            }
            $row = DB::table('vehicles')->where('vin', $vin)->first();

            return $row ? (array) $row : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function saleOrder(string $vin)
    {
        $vehicle = $this->findVehicle($vin);
        abort_if(! $vehicle, 404, __('vehicles.not_found'));
        abort_if(empty($vehicle['ord_no']), 400, __('documents.sale_unavailable'));

        return Pdf::loadView('documents.sale-order', [
            'vehicle' => $vehicle,
            'company' => $this->company(),
            'currency' => $this->currency(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download('sale-order-'.preg_replace('/[^\w.-]/', '_', $vin).'.pdf');
    }

    public function deliveryNote(string $vin)
    {
        $vehicle = $this->findVehicle($vin);
        abort_if(! $vehicle, 404, __('vehicles.not_found'));
        abort_if(($vehicle['stage'] ?? null) !== 'delivered', 400, __('documents.delivery_unavailable'));

        return Pdf::loadView('documents.delivery-note', [
            'vehicle' => $vehicle,
            'company' => $this->company(),
            'currency' => $this->currency(),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download('delivery-note-'.preg_replace('/[^\w.-]/', '_', $vin).'.pdf');
    }
}
