<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Queries;

use App\Domain\Reporting\Contracts\ReportQuery;
use App\Domain\Reporting\Queries\Concerns\FetchesVehicles;
use App\Domain\Reporting\Queries\Concerns\LocalizesReport;
use App\Domain\Vehicle\Models\VehicleAttachment;
use App\Domain\Vehicle\Services\FinancialFieldsResolver;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy REP.quality (lines 81-85) — data quality check.
 *
 * Besides the legacy date/stage checks this report flags financial gaps:
 * missing FX rates, missing purchase/shipping/landed/sale values and
 * financial operations without a proof document.
 */
class QualityCheckReport implements ReportQuery
{
    use FetchesVehicles;
    use LocalizesReport;

    public function key(): string
    {
        return 'quality';
    }

    public function titleKey(): string
    {
        return 'reports.titles.quality';
    }

    public function columns(): array
    {
        return [$this->field('vin'), $this->field('model'), $this->field('stage'), $this->column('issue')];
    }

    public function rows(): array
    {
        $today = date('Y-m-d');
        $pastShipping = ['shipped', 'arrived', 'clearing', 'cleared', 'instock', 'sold', 'delivered'];
        $pastClearance = ['cleared', 'instock', 'sold', 'delivered'];
        $sold = ['sold', 'delivered'];

        $proofs = $this->proofCategories();
        $rows = [];

        foreach ($this->vehiclesWithCosts() as $v) {
            $issues = [];
            $stage = $v['stage'] ?? '';
            $vin = (string) ($v['vin'] ?? '');

            if (($v['ship'] ?? '') !== '' && $v['ship'] > $today) {
                $issues[] = 'shipping_future';
            }
            if (($v['arr'] ?? '') !== '' && $v['arr'] > $today && ! in_array($stage, ['purchased', 'shipped'], true)) {
                $issues[] = 'arrival_future';
            }
            if (($v['ship'] ?? '') !== '' && ($v['arr'] ?? '') !== '' && $v['arr'] < $v['ship']) {
                $issues[] = 'arrival_before_shipping';
            }
            if (in_array($stage, $pastShipping, true) && ($v['bl'] ?? '') === '') {
                $issues[] = 'missing_bl';
            }
            $blRelease = FinancialFieldsResolver::normalizeYesNo($v['bl_release'] ?? null);
            if (in_array($stage, $pastShipping, true) && $blRelease === 'No') {
                $issues[] = 'bl_not_released';
            }
            if (in_array($stage, $pastShipping, true) && $blRelease === null && ($v['bl'] ?? '') !== '') {
                $issues[] = 'missing_bl_release';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ($v['client'] ?? '') === '') {
                $issues[] = 'sold_without_buyer';
            }
            if ($stage === 'delivered' && (($v['recv'] ?? '') === '' || ($v['del_date'] ?? '') === '')) {
                $issues[] = 'delivered_incomplete';
            }
            if (in_array($stage, ['instock', 'sold'], true) && ($v['loc'] ?? '') === '') {
                $issues[] = 'instock_without_location';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ((float) ($v['price'] ?? 0)) <= 0) {
                $issues[] = 'sold_without_price';
            }
            if (in_array($stage, ['sold', 'delivered'], true) && ($v['paid'] ?? '') === '') {
                $issues[] = 'sold_without_paid';
            }
            if ((float) ($v['paid'] ?? 0) > (float) ($v['price'] ?? 0) && ((float) ($v['price'] ?? 0)) > 0) {
                $issues[] = 'overpaid';
            }

            // Financial completeness.
            if (($v['purchase_native'] ?? null) === null && in_array($stage, $pastShipping, true)) {
                $issues[] = 'missing_purchase_cost';
            }
            if (($v['shipping_native'] ?? null) === null && in_array($stage, ['arrived', 'clearing', 'cleared', 'instock', 'sold', 'delivered'], true)) {
                $issues[] = 'missing_shipping_cost';
            }
            if (($v['landed_base'] ?? null) === null && in_array($stage, $pastClearance, true)) {
                $issues[] = 'missing_landed_cost';
            }
            if (($v['sale_native'] ?? null) === null && in_array($stage, $sold, true)) {
                $issues[] = 'missing_sale_value';
            }
            if (($v['mod'] ?? '') !== '' && ! preg_match('/^(yes|no)\b/i', trim((string) $v['mod']))) {
                $issues[] = 'invalid_modification_flag';
            }
            if (($v['modification_base'] ?? null) === null && FinancialFieldsResolver::isModified($v['mod'] ?? null)) {
                $issues[] = 'missing_modification_cost';
            }

            foreach ((array) ($v['fx_missing'] ?? []) as $component) {
                $issues[] = 'missing_fx_'.$component;
            }

            // Every financial operation should carry a proof document.
            $vinProofs = $proofs[$vin] ?? [];
            if (($v['purchase_native'] ?? null) !== null && ! in_array('purchase', $vinProofs, true)) {
                $issues[] = 'missing_purchase_proof';
            }
            if (($v['shipping_native'] ?? null) !== null && ! in_array('shipping', $vinProofs, true)) {
                $issues[] = 'missing_shipping_proof';
            }
            if (($v['landed_base'] ?? null) !== null && ! in_array('landed', $vinProofs, true)) {
                $issues[] = 'missing_landed_proof';
            }
            if (($v['modification_base'] ?? null) !== null && ! in_array('modification', $vinProofs, true)) {
                $issues[] = 'missing_modification_proof';
            }
            if (($v['sale_native'] ?? null) !== null && ! in_array('sale', $vinProofs, true)) {
                $issues[] = 'missing_sale_proof';
            }
            if ((float) ($v['paid'] ?? 0) > 0 && ! in_array('payment', $vinProofs, true)) {
                $issues[] = 'missing_payment_proof';
            }

            foreach (array_values(array_unique($issues)) as $code) {
                $rows[] = [$vin, $v['model'] ?? '', $this->stageLabel($stage), $this->issueLabel($code)];
            }
        }

        return $rows;
    }

    /**
     * Uploaded proof categories per VIN: vin => [category, ...].
     *
     * @return array<string, array<int, string>>
     */
    protected function proofCategories(): array
    {
        try {
            if (! Schema::hasTable('vehicle_attachments') || ! Schema::hasColumn('vehicle_attachments', 'category')) {
                return [];
            }

            $out = [];
            foreach (VehicleAttachment::query()->get(['vin', 'category']) as $attachment) {
                $value = (string) $attachment->getRawOriginal('category');
                $out[(string) $attachment->vin][] = $value;
            }

            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
