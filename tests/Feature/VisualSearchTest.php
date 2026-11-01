<?php

namespace Tests\Feature;

use App\Models\Product\Product;
use App\Models\ProductVisualIndex;
use App\Models\User;
use App\Services\VisualSearchService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VisualSearchTest extends TestCase
{
    /**
     * 1. Test validation error when no image is uploaded
     */
    public function test_visual_search_endpoint_validates_image(): void
    {
        $response = $this->postJson(route('api.search.image'), []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 2. Test searching with an actual product image matches that exact product with high confidence
     */
    public function test_visual_search_matches_product_image_accurately(): void
    {
        $product = Product::where('is_active', true)->whereNotNull('featured_image')->first();
        if (!$product) {
            $this->markTestSkipped('Active product with featured image required for test');
        }

        $realPath = VisualSearchService::resolveProductImagePath($product->featured_image);
        if (!$realPath || !file_exists($realPath)) {
            $this->markTestSkipped("Image file not found on disk: {$product->featured_image}");
        }

        // Ensure product is indexed
        VisualSearchService::indexProduct($product);

        $uploadedFile = new UploadedFile(
            $realPath,
            basename($realPath),
            mime_content_type($realPath),
            null,
            true
        );

        $response = $this->post(route('api.search.image'), [
            'image' => $uploadedFile,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $results = $response->json('results');
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);

        // The first match must be the exact product itself
        $firstMatch = $results[0];
        $this->assertEquals($product->id, $firstMatch['id']);
        $this->assertGreaterThanOrEqual(85.0, (float)$firstMatch['score']);
    }

    /**
     * 3. Test visual search artisan command runs cleanly
     */
    public function test_artisan_visual_search_index_command(): void
    {
        $exitCode = Artisan::call('visual-search:index');
        $this->assertEquals(0, $exitCode);
        $this->assertGreaterThan(0, ProductVisualIndex::count());
    }

    /**
     * 4. Test admin can trigger reindex from admin dashboard
     */
    public function test_admin_can_trigger_reindex(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.products.reindex-visual'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
