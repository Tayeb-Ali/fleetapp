<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Finance\Services\CurrencyService;
use App\Domain\Vehicle\Actions\AttachFileAction;
use App\Domain\Vehicle\Actions\DeleteAttachmentAction;
use App\Domain\Vehicle\Enums\AttachmentCategory;
use App\Domain\Vehicle\Models\VehicleAttachment;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Proof documents for one financial operation (purchase, shipping, ...).
 *
 * Accepts images or PDF, up to 5 files per upload and 6MB each. Every file is
 * stored against the vehicle with its category so the operation is auditable.
 */
class FinancialProofs extends Component
{
    use WithFileUploads;

    public string $vin = '';

    public string $category = 'general';

    /** @var array<int, mixed> */
    public array $files = [];

    public string $note = '';

    public string $amount = '';

    public string $currency = '';

    public function mount(string $vin, string $category = 'general'): void
    {
        $this->vin = $vin;
        $this->category = AttachmentCategory::tryFromKey($category)->value;
    }

    public function upload(AttachFileAction $action): void
    {
        $this->authorizeUpload();

        $this->validate(AttachFileAction::proofRules());

        $amount = $this->amount !== '' ? (float) $this->amount : null;
        $category = AttachmentCategory::tryFromKey($this->category);

        foreach ($this->files as $file) {
            $action->execute(
                $this->vin,
                $file,
                auth()->id(),
                $category,
                $this->note,
                $amount,
                $this->currency,
            );
        }

        $this->reset('files', 'note', 'amount', 'currency');
        session()->flash('attachments-ok-'.$this->category, __('attachments.attached'));
    }

    public function delete(int $id, DeleteAttachmentAction $action): void
    {
        $attachment = VehicleAttachment::query()->findOrFail($id);
        abort_if($attachment->vin !== $this->vin, 404);
        abort_if($attachment->category->value !== $this->category, 404);

        $action->execute($attachment, auth()->user());

        session()->flash('attachments-ok-'.$this->category, __('attachments.deleted'));
    }

    protected function authorizeUpload(): void
    {
        $role = (string) (auth()->user()->role ?? 'viewer');
        abort_if(in_array($role, ['viewer'], true), 403, __('attachments.forbidden'));
    }

    public function render(CurrencyService $currencies)
    {
        $attachments = VehicleAttachment::query()
            ->with('user')
            ->where('vin', $this->vin)
            ->where('category', $this->category)
            ->orderByDesc('id')
            ->get();

        return view('livewire.vehicles.financial-proofs', [
            'attachments' => $attachments,
            'canUpload' => ! in_array((string) (auth()->user()->role ?? 'viewer'), ['viewer'], true),
            'currencyOptions' => $currencies->options(),
            'categoryLabel' => AttachmentCategory::tryFromKey($this->category)->label(),
        ]);
    }
}
