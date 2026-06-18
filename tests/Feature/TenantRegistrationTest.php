<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_registers_a_new_tenant_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'company_name'          => 'Acme Corp',
            'subdomain'             => 'acme',
            'owner_name'            => 'John Doe',
            'email'                 => 'john@acme.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'tenant' => ['id', 'name', 'domain', 'plan', 'trial_ends_at'],
                     'user'   => ['id', 'name', 'email', 'roles'],
                     'token',
                 ]);

        $this->assertDatabaseHas('tenants', ['email' => 'john@acme.com']);
        $this->assertDatabaseHas('domains', ['domain' => 'acme.localhost']);
    }

    /** @test */
    public function it_rejects_duplicate_subdomain(): void
    {
        // First registration
        $this->postJson('/api/register', [
            'company_name' => 'Acme', 'subdomain' => 'acme',
            'owner_name' => 'John', 'email' => 'john@acme.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        // Second registration with same subdomain
        $response = $this->postJson('/api/register', [
            'company_name' => 'Acme 2', 'subdomain' => 'acme',
            'owner_name' => 'Jane', 'email' => 'jane@acme2.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['subdomain']);
    }

    /** @test */
    public function it_rejects_invalid_subdomain_format(): void
    {
        $response = $this->postJson('/api/register', [
            'company_name' => 'Test', 'subdomain' => 'My Company!',
            'owner_name' => 'Test', 'email' => 'test@test.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['subdomain']);
    }

    /** @test */
    public function health_check_returns_ok(): void
    {
        $this->getJson('/health')->assertStatus(200)->assertJson(['status' => 'ok']);
    }
}
