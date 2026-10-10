<?php

declare(strict_types=1);

namespace App\Livewire\Vehicles;

use App\Domain\Vehicle\Actions\AttachFileAction;
use App\Domain\Vehicle\Actions\DeleteAttachmentAction;
use App\Domain\Vehicle\Models\VehicleAttachment;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithFileUploads;

/** List / upload / delete attachments for one VIN. */
class VehicleAttachments extends Component
{
    use WithFileUploads;

    public string $vin = '';

    public $file = null;

    /** @var array<string, string> */
    protected array $messages = [];

    public function mount(string $vin): void
    {
        $this->vin = $vin;
    }

    public function upload(AttachFileAction $action): void
    {
        $this->validate(AttachFileAction::rules());

        $action->execute($this->vin, $this->file, auth()->id());

        $this->reset('file');
        session()->flash('attachments-ok', __('attachments.attached'));
    }

    public function delete(int $id, DeleteAttachmentAction $action): void
    {
        $attachment = VehicleAttachment::findOrFail($id);
        abort_if($attachment->vin !== $this->vin, 404);

        $action->execute($attachment, auth()->user());

        session()->flash('attachments-ok', __('attachments.deleted'));
    }

    public function render()
    {
        $attachments = collect();
        try {
            if (Schema::hasTable('vehicle_attachments')) {
                $attachments = VehicleAttachment::where('vin', $this->vin)
                    ->orderByDesc('id')->get();
            }
        } catch (\Throwable) {
            $attachments = collect();
        }

        return view('livewire.vehicles.vehicle-attachments', [
            'attachments' => $attachments,
        ]);
    }
}
