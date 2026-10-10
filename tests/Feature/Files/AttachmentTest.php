<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Domain\Vehicle\Actions\AttachFileAction;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Models\VehicleAttachment;
use App\Livewire\Vehicles\VehicleAttachments;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('at_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    protected function makeStoredAttachment(User $owner, string $vin): VehicleAttachment
    {
        return app(AttachFileAction::class)->execute(
            $vin,
            UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            $owner->id
        );
    }

    public function test_upload_via_livewire_stores_attachment(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create();

        Livewire::actingAs($user)->test(VehicleAttachments::class, ['vin' => $vehicle->vin])
            ->set('file', UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'))
            ->call('upload')
            ->assertHasNoErrors();

        $attachment = VehicleAttachment::where('vin', $vehicle->vin)->sole();

        $this->assertSame('report.pdf', $attachment->original_name);
        Storage::disk('private')->assertExists($attachment->path);
    }

    public function test_guest_download_is_unauthorized(): void
    {
        Storage::fake('private');

        $owner = $this->makeUser('purchaser');
        $attachment = $this->makeStoredAttachment($owner, 'VIN-DL-001');

        $this->getJson(route('attachments.download', $attachment))->assertUnauthorized();
    }

    public function test_viewer_non_owner_download_is_forbidden(): void
    {
        Storage::fake('private');

        $owner = $this->makeUser('purchaser');
        $viewer = $this->makeUser('viewer');
        $attachment = $this->makeStoredAttachment($owner, 'VIN-DL-002');

        $this->actingAs($viewer)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_owner_and_admin_can_download(): void
    {
        Storage::fake('private');

        $owner = $this->makeUser('purchaser');
        $admin = $this->makeUser('admin');
        $attachment = $this->makeStoredAttachment($owner, 'VIN-DL-003');

        $this->actingAs($owner)
            ->get(route('attachments.download', $attachment))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('attachments.download', $attachment))
            ->assertOk();
    }

    public function test_admin_can_delete_attachment(): void
    {
        Storage::fake('private');

        $owner = $this->makeUser('purchaser');
        $admin = $this->makeUser('admin');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-DL-004']);
        $attachment = $this->makeStoredAttachment($owner, $vehicle->vin);

        Livewire::actingAs($admin)->test(VehicleAttachments::class, ['vin' => $vehicle->vin])
            ->call('delete', $attachment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('vehicle_attachments', ['id' => $attachment->id]);
        Storage::disk('private')->assertMissing($attachment->path);
    }
}
