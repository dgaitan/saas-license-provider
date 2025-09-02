<?php

namespace Tests\Feature\Api\V1\Brand;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\Product;
use App\Enums\LicenseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\V1\Brand\WithBrandAuthentication;

class ProductListControllerTest extends TestCase
{
    use RefreshDatabase, WithBrandAuthentication;

    private Brand $brand;
    private Product $product1;
    private Product $product2;
    private Product $product3;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->brand = $this->createTestBrand();
        $this->product1 = $this->createTestProduct($this->brand);
        $this->product2 = $this->createTestProduct($this->brand);
        $this->product3 = $this->createTestProduct($this->brand);
    }

    /** @test */
    public function it_can_list_products_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'uuid',
                            'name',
                            'slug',
                            'description',
                            'max_seats',
                            'is_active',
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
        $response = $this->getJson('/api/v1/products');

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
            ->getJson('/api/v1/products');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_get_products_summary_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/products/summary');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total_products',
                    'active_products',
                    'inactive_products',
                    'products_by_seats' => [
                        'small',
                        'medium',
                        'large',
                        'unlimited'
                    ]
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_products']);
        $this->assertEquals(3, $data['active_products']);
        $this->assertEquals(0, $data['inactive_products']);
    }

    /** @test */
    public function it_returns_unauthorized_for_summary_without_brand_authentication()
    {
        $response = $this->getJson('/api/v1/products/summary');

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
            ->getJson('/api/v1/products/summary');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_get_product_details_with_brand_authentication()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson("/api/v1/products/{$this->product1->uuid}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'slug',
                    'description',
                    'max_seats',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertEquals($this->product1->uuid, $response->json('data.uuid'));
    }

    /** @test */
    public function it_returns_unauthorized_for_product_details_without_brand_authentication()
    {
        $response = $this->getJson("/api/v1/products/{$this->product1->uuid}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_product_details_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson("/api/v1/products/{$this->product1->uuid}");

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_product()
    {
        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->getJson('/api/v1/products/nonexistent-uuid');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_unauthorized_for_nonexistent_product_without_brand_authentication()
    {
        $response = $this->getJson('/api/v1/products/nonexistent-uuid');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_nonexistent_product_with_invalid_brand_api_key()
    {
        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->getJson('/api/v1/products/nonexistent-uuid');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }

    /** @test */
    public function it_can_create_product_with_brand_authentication()
    {
        $productData = [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'description' => 'A new test product',
            'max_seats' => 10,
            'is_active' => true,
        ];

        $response = $this->withHeaders($this->getBrandHeaders($this->brand))
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'slug',
                    'description',
                    'max_seats',
                    'is_active',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'brand_id' => $this->brand->id,
        ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_create_product_without_brand_authentication()
    {
        $productData = [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'description' => 'A new test product',
            'max_seats' => 10,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/products', $productData);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'X-Tenant header with Brand API Key is required',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_create_product_with_invalid_brand_api_key()
    {
        $productData = [
            'name' => 'New Test Product',
            'slug' => 'new-test-product',
            'description' => 'A new test product',
            'max_seats' => 10,
            'is_active' => true,
        ];

        $response = $this->withHeaders(['X-Tenant' => 'invalid_api_key'])
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid or inactive brand API key',
            ]);
    }
}
