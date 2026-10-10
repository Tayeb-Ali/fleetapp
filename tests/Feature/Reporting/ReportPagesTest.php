<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Domain\Vehicle\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role = 'viewer'): User
    {
        return User::factory()->create([
            'username' => strtolower('rp_'.$role.'_'.uniqid()),
            'role' => $role,
            'active' => true,
            'password' => 'secret123',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_view_reports_index(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/reports')->assertOk();
    }

    public function test_authenticated_user_can_view_summary_report(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/reports/summary')->assertOk();
    }

    public function test_unknown_report_returns_404(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/reports/nope')->assertNotFound();
    }

    public function test_export_xlsx_returns_spreadsheet(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->get(route('reports.export', ['key' => 'summary', 'format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_export_pdf_returns_pdf(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)
            ->get(route('reports.export', ['key' => 'summary', 'format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function test_export_bad_format_returns_400(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get(route('reports.export', ['key' => 'summary', 'format' => 'csv']))
            ->assertStatus(400);
    }

    public function test_index_shows_arabic_titles_by_default(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->get('/reports');

        $response->assertOk();
        $response->assertSee('ملخص مراحل المركبات');
        $response->assertSee('المبيعات حسب المشتري');
    }

    public function test_summary_report_localizes_columns_and_dates(): void
    {
        $user = $this->makeUser();
        Vehicle::factory()->create([
            'vin' => 'VIN-L1',
            'stage' => 'instock',
            'warehouse_in_at' => '2026-01-02',
        ]);

        $response = $this->actingAs($user)->get('/reports/summary');

        $response->assertOk();
        $response->assertSee('المرحلة');
        $response->assertSee('العدد');
        $response->assertSee('في المخزن - متاح للبيع');
    }

    public function test_stock_report_renders_localized_dates_in_english(): void
    {
        $user = $this->makeUser();
        Vehicle::factory()->create([
            'vin' => 'VIN-L2',
            'stage' => 'instock',
            'warehouse_in_at' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/reports/stock');

        $response->assertOk();
        $response->assertSee('Warehouse entry date');
        $response->assertSee('5 Oct 2026');
    }
}
