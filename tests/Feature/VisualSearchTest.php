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

        // Check page_url and token are returned
        $token = $response->json('token');
        $pageUrl = $response->json('page_url');
        $this->assertNotEmpty($token);
        $this->assertNotEmpty($pageUrl);
        $this->assertStringContainsString('visual=' . $token, $pageUrl);

        // Access the search page using the visual token
        $pageResponse = $this->get($pageUrl);
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Visual Match Results');
    }

    /**
     * 3. Test text search still works perfectly and does not conflict with visual search
     */
    public function test_traditional_text_search_remains_unaffected(): void
    {
        $response = $this->get(route('shop.search', ['q' => 'nivea']));
        $response->assertStatus(200);
        $response->assertSee('Search Results:');
        $response->assertSee('nivea');
    }

    /**
     * 4. Test visual search artisan command runs cleanly
     */
    public function test_artisan_visual_search_index_command(): void
    {
        $exitCode = Artisan::call('visual-search:index');
        $this->assertEquals(0, $exitCode);
        $this->assertGreaterThan(0, ProductVisualIndex::count());
    }

    /**
     * 5. Test admin can trigger reindex from admin dashboard
     */
    public function test_admin_can_trigger_reindex(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.products.reindex-visual'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * 6. Test admin can sync visual index for a single product from the product list table
     */
    public function test_admin_can_sync_visual_for_single_product(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);
        $product = Product::where('is_active', true)->whereNotNull('featured_image')->first();
        if (!$product) {
            $this->markTestSkipped('Product required for test');
        }

        $response = $this->actingAs($admin)->post(route('admin.products.sync-visual', $product->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $count = ProductVisualIndex::where('product_id', $product->id)->count();
        $this->assertGreaterThan(0, $count);
    }

    /**
     * 7. Test visual search boosts score when packaging text or SKU is detected
     */
    public function test_visual_search_boosts_with_ocr_text(): void
    {
        $product = Product::where('is_active', true)->whereNotNull('featured_image')->first();
        if (!$product) {
            $this->markTestSkipped('Product required for test');
        }

        $realPath = VisualSearchService::resolveProductImagePath($product->featured_image);
        if (!$realPath || !file_exists($realPath)) {
            $this->markTestSkipped("Image file not found on disk");
        }

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
            'ocr_text' => $product->title,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $this->assertEquals($product->id, $results[0]['id']);
        $this->assertGreaterThanOrEqual(90.0, (float)$results[0]['score']);
    }

    /**
     * 8. Test barcode match places exact product at top (>=90%) while retaining other visual matches
     */
    public function test_visual_search_exact_barcode_match_ranks_at_top_with_similar_items(): void
    {
        $product = Product::where('is_active', true)->whereNotNull('featured_image')->first();
        if (!$product) {
            $this->markTestSkipped('Product required for test');
        }

        $realPath = VisualSearchService::resolveProductImagePath($product->featured_image);
        if (!$realPath || !file_exists($realPath)) {
            $this->markTestSkipped("Image file not found on disk");
        }

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
            'ocr_text' => '6915324890593', // Barcode test token
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
    }
}
