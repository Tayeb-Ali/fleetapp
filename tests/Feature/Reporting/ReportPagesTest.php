<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

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
}
