<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Route /buat-link-test dulu publik: siapa pun bisa memicunya untuk membuat
 * user dengan password tetap lalu membaca access token penilaian.
 */
class RemovedTestRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_pembuat_link_uji_coba_sudah_tidak_ada(): void
    {
        $this->get('/buat-link-test')->assertNotFound();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('assessment_requests', 0);
    }
}
