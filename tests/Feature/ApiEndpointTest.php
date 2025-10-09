<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiEndpointTest extends TestCase
{
    /** @test */
    public function api_routes_exist()
    {
        // Test auth routes without authentication
        $response = $this->postJson('/api/auth/login');
        $this->assertNotEquals(404, $response->getStatusCode());

        // Test that protected routes require authentication
        $response = $this->getJson('/api/laudos');
        $this->assertEquals(401, $response->getStatusCode());

        $response = $this->getJson('/api/usuarios');
        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function public_laudo_consultation_exists()
    {
        // Test public laudo consultation endpoint
        $response = $this->getJson('/api/laudos/consultar/some-id');
        // Should not be 404 (route exists), but might be 401 or 422
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}