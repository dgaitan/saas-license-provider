<?php

namespace Tests\Feature\Api\V1\Brand;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\Product;
use App\Models\LicenseKey;
use App\Models\License;
use App\Enums\LicenseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\V1\Brand\WithBrandAuthentication;

class LicenseControllerListingTest extends TestCase
{
    use RefreshDatabase, WithBrandAuthentication;

    private Brand $brand;
    private Product $product1;
    private Product $product2;
    private LicenseKey $licenseKey1;
    private LicenseKey $licenseKey2;
    private License $license1;
    private License $license2;
    private License $license3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->brand = $this->createTestBrand();
        $this->product1 = $this->createTestProduct($this->brand);
        $this->product2 = $this->createTestProduct($this->brand);
        
        $this->licenseKey1 = $this->createTestLicenseKey($this->brand);
        $this->licenseKey2 = $this->createTestLicenseKey($this->brand);
        
        $this->license1 = $this->createTestLicense($this->product1, $this->licenseKey1);
        $this->license2 = $this->createTestLicense($this->product1, $this->licenseKey1);
        $this->license3 = $this->createTestLicense($this->product2, $this->licenseKey2);
    }

    /** @test */
    public function it_can_list_licenses_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/licenses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'uuid',
                            'license_key_id',
                            'product_id',
                            'status',
                            'status_label',
                            'expires_at',
                            'max_seats',
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function it_returns_unauthorized_without_brand_authentication()
    {
        $response = $this->getJson('/api/v1/licenses');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson('/api/v1/licenses');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_get_licenses_summary_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/licenses/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_licenses',
                    'valid_licenses',
                    'suspended_licenses',
                    'cancelled_licenses',
                    'expired_licenses',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_licenses']);
        $this->assertEquals(3, $data['valid_licenses']);
        $this->assertEquals(0, $data['suspended_licenses']);
        $this->assertEquals(0, $data['cancelled_licenses']);
        $this->assertEquals(0, $data['expired_licenses']);
    }

    /** @test */
    public function it_returns_unauthorized_for_summary_without_brand_authentication()
    {
        $response = $this->getJson('/api/v1/licenses/summary');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_summary_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson('/api/v1/licenses/summary');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_get_license_details_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson("/api/v1/licenses/{$this->license1->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'license_key_id',
                    'product_id',
                    'status',
                    'status_label',
                    'expires_at',
                    'max_seats',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertEquals($this->license1->uuid, $response->json('data.uuid'));
    }

    /** @test */
    public function it_returns_unauthorized_for_license_details_without_brand_authentication()
    {
        $response = $this->getJson("/api/v1/licenses/{$this->license1->uuid}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_license_details_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson("/api/v1/licenses/{$this->license1->uuid}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_license()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/licenses/nonexistent-uuid');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_unauthorized_for_nonexistent_license_without_brand_authentication()
    {
        $response = $this->getJson('/api/v1/licenses/nonexistent-uuid');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_nonexistent_license_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson('/api/v1/licenses/nonexistent-uuid');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_create_license_with_brand_authentication()
    {
        $licenseData = [
            'license_key_uuid' => $this->licenseKey1->uuid,
            'product_uuid' => $this->product1->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'max_seats' => 5,
        ];

        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->postJson('/api/v1/licenses', $licenseData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'license_key_id',
                    'product_id',
                    'status',
                    'status_label',
                    'expires_at',
                    'max_seats',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('licenses', [
            'license_key_id' => $this->licenseKey1->id,
            'product_id' => $this->product1->id,
            'max_seats' => 5,
        ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_create_license_without_brand_authentication()
    {
        $licenseData = [
            'license_key_uuid' => $this->licenseKey1->uuid,
            'product_uuid' => $this->product1->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'max_seats' => 5,
        ];

        $response = $this->postJson('/api/v1/licenses', $licenseData);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_create_license_with_invalid_brand_api_key()
    {
        $licenseData = [
            'license_key_uuid' => $this->licenseKey1->uuid,
            'product_uuid' => $this->product1->uuid,
            'expires_at' => now()->addYear()->format('Y-m-d'),
            'max_seats' => 5,
        ];

        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->postJson('/api/v1/licenses', $licenseData);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }
}
