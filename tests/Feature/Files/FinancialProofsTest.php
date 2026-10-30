<?php

declare(strict_types=1);

namespace Tests\Feature\Files;

use App\Domain\Reporting\Queries\QualityCheckReport;
use App\Domain\Vehicle\Models\Vehicle;
use App\Domain\Vehicle\Models\VehicleAttachment;
use App\Livewire\Vehicles\FinancialProofs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FinancialProofsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role): User
    {
        return User::factory()->create([
            'username' => strtolower('fp_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_purchaser_uploads_proof_for_a_financial_operation(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-PROOF-1']);

        Livewire::actingAs($user)
            ->test(FinancialProofs::class, ['vin' => $vehicle->vin, 'category' => 'purchase'])
            ->set('files', [UploadedFile::fake()->image('transfer.jpg')])
            ->set('amount', '1500')
            ->set('currency', 'AED')
            ->call('upload')
            ->assertHasNoErrors();

        $attachment = VehicleAttachment::query()->where('vin', $vehicle->vin)->sole();

        $this->assertSame('purchase', $attachment->category->value);
        $this->assertSame('1500.00', (string) $attachment->amount);
        $this->assertSame('AED', $attachment->currency);
        Storage::disk('private')->assertExists($attachment->path);
    }

    public function test_only_images_and_pdf_are_accepted_as_proof(): void
    {
        Storage::fake('private');

        $user = $this->makeUser('purchaser');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-PROOF-2']);

        Livewire::actingAs($user)
            ->test(FinancialProofs::class, ['vin' => $vehicle->vin, 'category' => 'shipping'])
            ->set('files', [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')])
            ->call('upload')
            ->assertHasErrors('files.0');

        $this->assertSame(0, VehicleAttachment::query()->count());
    }

    public function test_viewer_cannot_upload_proof(): void
    {
        Storage::fake('private');

        $viewer = $this->makeUser('viewer');
        $vehicle = Vehicle::factory()->create(['vin' => 'VIN-PROOF-3']);

        Livewire::actingAs($viewer)
            ->test(FinancialProofs::class, ['vin' => $vehicle->vin, 'category' => 'purchase'])
            ->set('files', [UploadedFile::fake()->image('transfer.jpg')])
            ->call('upload')
            ->assertForbidden();

        $this->assertSame(0, VehicleAttachment::query()->count());
    }

    public function test_quality_report_flags_missing_proofs_and_fx(): void
    {
        app()->setLocale('en');

        Vehicle::factory()->create([
            'vin' => 'VIN-PROOF-4',
            'stage' => 'sold',
            'cost' => 500,
            'purchase_currency' => 'EUR',
            'purchase_fx_rate' => null,
            'purchase_base_amount' => null,
            'sale_price' => 1000,
            'amount_paid' => 100,
        ]);

        $issues = [];
        foreach (app(QualityCheckReport::class)->rows() as $row) {
            if ($row[0] === 'VIN-PROOF-4') {
                $issues[] = $row[3];
            }
        }

        $this->assertContains('No exchange rate for the purchase currency', $issues);
        $this->assertContains('No proof document for the purchase', $issues);
        $this->assertContains('No proof document for the sale', $issues);
        $this->assertContains('No proof document for the payment', $issues);
    }
}
