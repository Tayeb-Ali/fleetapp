<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_route_action(): void
    {
        $route = Route::getRoutes()->getByName('attachments.download');
        file_put_contents(
            storage_path('logs/probe2.log'),
            'ACTION: '.var_export($route ? $route->action : null, true)."\n"
        );
        $this->assertTrue(true);
    }
}
