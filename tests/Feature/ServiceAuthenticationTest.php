<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Services\ServiceAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceAuthService $serviceAuthService;
    protected Service $testService;
    protected string $clientSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceAuthService = app(ServiceAuthService::class);

        // Create test service
        $result = $this->serviceAuthService->register([
            'name' => 'Test Service',
            'slug' => 'test-service',
            'default_bucket' => 'drive',
            'allowed_scopes' => ['filesystem.*'],
        ]);

        $this->testService = $result['service'];
        $this->clientSecret = $result['credentials']['client_secret'];
    }

    public function test_service_can_authenticate_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/service/token', [
            'client_id' => $this->testService->client_id,
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'service',
            ]);

        $this->assertEquals('Bearer', $response->json('token_type'));
        $this->assertNotEmpty($response->json('access_token'));
    }

    public function test_service_cannot_authenticate_with_invalid_client_id(): void
    {
        $response = $this->postJson('/api/service/token', [
            'client_id' => 'invalid-uuid',
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertStatus(422);
    }

    public function test_service_cannot_authenticate_with_wrong_secret(): void
    {
        $response = $this->postJson('/api/service/token', [
            'client_id' => $this->testService->client_id,
            'client_secret' => 'wrong-secret',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['client_id']);
    }

    public function test_inactive_service_cannot_authenticate(): void
    {
        $this->serviceAuthService->disable($this->testService);

        $response = $this->postJson('/api/service/token', [
            'client_id' => $this->testService->client_id,
            'client_secret' => $this->clientSecret,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service']);
    }

    public function test_jwt_token_contains_required_claims(): void
    {
        $response = $this->postJson('/api/service/token', [
            'client_id' => $this->testService->client_id,
            'client_secret' => $this->clientSecret,
        ]);

        $token = $response->json('access_token');

        // Decode JWT (without verification for testing purposes)
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        $this->assertArrayHasKey('iss', $payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('type', $payload);
        $this->assertArrayHasKey('service', $payload);
        $this->assertArrayHasKey('bucket', $payload);
        $this->assertArrayHasKey('scopes', $payload);

        $this->assertEquals('service', $payload['type']);
        $this->assertEquals($this->testService->slug, $payload['service']);
        $this->assertEquals('drive', $payload['bucket']);
        $this->assertEquals(['filesystem.*'], $payload['scopes']);
    }

    public function test_jwks_endpoint_returns_valid_format(): void
    {
        $response = $this->getJson('/.well-known/jwks.json');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'keys' => [
                    '*' => [
                        'kty',
                        'use',
                        'alg',
                        'kid',
                        'n',
                        'e',
                    ],
                ],
            ]);

        $keys = $response->json('keys');
        $this->assertCount(1, $keys);
        $this->assertEquals('RSA', $keys[0]['kty']);
        $this->assertEquals('sig', $keys[0]['use']);
        $this->assertEquals('RS256', $keys[0]['alg']);
    }

    public function test_service_last_used_at_updated_on_login(): void
    {
        $this->assertNull($this->testService->fresh()->last_used_at);

        $this->postJson('/api/service/token', [
            'client_id' => $this->testService->client_id,
            'client_secret' => $this->clientSecret,
        ]);

        $this->assertNotNull($this->testService->fresh()->last_used_at);
    }
}
