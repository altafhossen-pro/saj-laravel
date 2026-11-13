<?php

namespace Tests\Feature;

use App\Models\BuyingGuide;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuyingGuideTest extends TestCase
{
    protected function getSuperAdmin(): User
    {
        $admin = User::firstOrCreate(
            ['email' => 'superadmin_test_guides@example.com'],
            [
                'name' => 'Super Admin Test',
                'password' => bcrypt('password123'),
                'phone' => '01700000099',
            ]
        );

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole($role);
        }

        return $admin;
    }

    protected function tearDown(): void
    {
        // Clean up test guides
        BuyingGuide::withTrashed()->where('slug', 'like', 'test-%')->forceDelete();
        parent::tearDown();
    }

    public function test_guest_can_view_guides_index_page()
    {
        $guide = BuyingGuide::create([
            'title' => 'Test Skincare Routine Guide',
            'slug' => 'test-skincare-routine-guide',
            'topic' => 'Skincare',
            'excerpt' => 'A wonderful excerpt about skincare routines.',
            'content' => '<h2>Step 1: Cleanse</h2><p>Use a gentle cleanser.</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('guides.index'));

        $response->assertStatus(200);
        $response->assertSee('Buying Guides');
        $response->assertSee('Test Skincare Routine Guide');
        $response->assertSee('Skincare');
    }

    public function test_draft_guide_is_hidden_from_public_listing_and_show()
    {
        $draft = BuyingGuide::create([
            'title' => 'Test Secret Draft Article',
            'slug' => 'test-secret-draft-article',
            'content' => '<p>This is top secret draft.</p>',
            'is_published' => false,
            'published_at' => null,
        ]);

        // Public index should not show draft
        $indexResponse = $this->get(route('guides.index'));
        $indexResponse->assertDontSee('Test Secret Draft Article');

        // Direct show route should return 404
        $showResponse = $this->get(route('guides.show', $draft->slug));
        $showResponse->assertStatus(404);
    }

    public function test_guest_can_view_single_guide_with_seo_and_schema_markup()
    {
        $guide = BuyingGuide::create([
            'title' => 'Test How to Choose the Best Serum',
            'slug' => 'test-how-to-choose-the-best-serum',
            'topic' => 'Serum Guide',
            'excerpt' => 'Expert tips on choosing hyaluronic and vitamin c serums.',
            'content' => '<h2>1. Understanding Skin Types</h2><p>Dry vs oily skin.</p><h2>2. Active Ingredients</h2><p>Look for Niacinamide.</p>',
            'meta_title' => 'Custom Meta Title for Serum Guide',
            'meta_description' => 'Custom meta description for serum guide ranking.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('guides.show', $guide->slug));

        $response->assertStatus(200);
        $response->assertSee('Test How to Choose the Best Serum');
        $response->assertSee('Custom Meta Title for Serum Guide');
        $response->assertSee('Custom meta description for serum guide ranking.');

        // Schema.org Article & Breadcrumbs
        $response->assertSee('"@type": "Article"', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    public function test_guide_renders_attached_product_prices_correctly()
    {
        $unique = uniqid();
        $product = \App\Models\Product\Product::create([
            'title' => 'Test Glow Face Cream ' . $unique,
            'slug' => 'test-glow-face-cream-' . $unique,
            'base_price' => 450.00,
            'is_active' => true,
        ]);

        \App\Models\Product\ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TEST-CREAM-' . $unique,
            'current_price' => 450.00,
            'original_price' => 650.00,
            'is_active' => true,
        ]);

        $guide = BuyingGuide::create([
            'title' => 'Guide With Attached Product ' . $unique,
            'slug' => 'guide-with-attached-product-' . $unique,
            'content' => '<h2>Review</h2><p>Here is our test review.</p>',
            'related_product_ids' => [$product->id],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('guides.show', $guide->slug));
        $response->assertStatus(200);
        $response->assertSee('Test Glow Face Cream ' . $unique);
        $response->assertSee('450');
        $response->assertSee('650');
        $this->assertEquals(450.0, $product->fresh()->final_price);
        $this->assertEquals(650.0, $product->fresh()->original_price);

        // Clean up
        $product->variants()->delete();
        $product->delete();
        $guide->delete();
    }

    public function test_table_of_contents_is_automatically_generated_from_headings()
    {
        $htmlContent = '
            <h1>Special H1 Section</h1>
            <p>H1 content</p>
            <h2>1. Why Moisturizing Matters</h2>
            <p>Moisture is key to skin barrier.</p>
            <h3>1.1 Winter Protection</h3>
            <p>Winter dehydrates the skin.</p>
            <h2>2. Top Recommended Oils</h2>
            <p>Cocoa butter and Argan oil.</p>
        ';

        $guide = BuyingGuide::create([
            'title' => 'Test TOC Generation Article',
            'slug' => 'test-toc-generation-article',
            'content' => $htmlContent,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Model Table of Contents Accessor
        $toc = $guide->table_of_contents;
        $this->assertCount(4, $toc);
        $this->assertEquals('Special H1 Section', $toc[0]['title']);
        $this->assertEquals(1, $toc[0]['level']);
        $this->assertEquals('1. Why Moisturizing Matters', $toc[1]['title']);
        $this->assertEquals(2, $toc[1]['level']);
        $this->assertEquals('1.1 Winter Protection', $toc[2]['title']);
        $this->assertEquals(3, $toc[2]['level']);

        // Model Processed Content Accessor
        $processed = $guide->processed_content;
        $this->assertStringContainsString('id="special-h1-section"', $processed);
        $this->assertStringContainsString('id="1-why-moisturizing-matters"', $processed);
        $this->assertStringContainsString('id="11-winter-protection"', $processed);
        $this->assertStringContainsString('id="2-top-recommended-oils"', $processed);
        $this->assertStringContainsString('scroll-mt-24', $processed);

        // Frontend Page Render
        $response = $this->get(route('guides.show', $guide->slug));
        $response->assertStatus(200);
        $response->assertSee('In This Article');
        $response->assertSee('#special-h1-section');
        $response->assertSee('#1-why-moisturizing-matters');
        $response->assertSee('#11-winter-protection');
        $response->assertSee('#2-top-recommended-oils');
    }

    public function test_views_count_increments_for_guide()
    {
        $guide = BuyingGuide::create([
            'title' => 'Test Popular Guide Views',
            'slug' => 'test-popular-guide-views',
            'content' => '<h2>Introduction</h2><p>Welcome to our popular guide.</p>',
            'views_count' => 0,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('guides.show', $guide->slug));
        $this->assertEquals(1, $guide->fresh()->views_count);

        // Visit again: increments on every visit
        $this->get(route('guides.show', $guide->slug));
        $this->assertEquals(2, $guide->fresh()->views_count);

        $this->get(route('guides.show', $guide->slug));
        $this->assertEquals(3, $guide->fresh()->views_count);
    }

    public function test_footer_quick_links_includes_buying_guides()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('guides.index'));
        $response->assertSee('Buying Guides');
    }

    public function test_sitemap_xml_and_sitemap_guides_xml_contain_published_guides()
    {
        $guide = BuyingGuide::create([
            'title' => 'Test Sitemap Guide',
            'slug' => 'test-sitemap-guide',
            'content' => '<p>Content for sitemap.</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        // Flush sitemap cache
        \Illuminate\Support\Facades\Cache::forget('sitemap_index_xml');
        \Illuminate\Support\Facades\Cache::forget('sitemap_guides_xml');

        // Main sitemap index
        $indexResponse = $this->get('/sitemap.xml');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('/sitemap-guides.xml');

        // Sub sitemap for guides
        $guidesSitemapResponse = $this->get('/sitemap-guides.xml');
        $guidesSitemapResponse->assertStatus(200);
        $guidesSitemapResponse->assertSee(route('guides.index'));
        $guidesSitemapResponse->assertSee(route('guides.show', $guide->slug));
    }

    public function test_admin_super_admin_can_access_guides_index_create_edit()
    {
        $admin = $this->getSuperAdmin();

        $guide = BuyingGuide::create([
            'title' => 'Test Admin View Guide',
            'slug' => 'test-admin-view-guide',
            'content' => '<h2>Admin Content</h2><p>Body.</p>',
            'is_published' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.guides.index'))
            ->assertStatus(200)
            ->assertSee('Buying Guides');

        $this->actingAs($admin)
            ->get(route('admin.guides.create'))
            ->assertStatus(200)
            ->assertSee('Create New Buying Guide');

        $this->actingAs($admin)
            ->get(route('admin.guides.edit', $guide))
            ->assertStatus(200)
            ->assertSee('Edit: Test Admin View Guide');
    }

    public function test_admin_can_create_and_publish_guide()
    {
        $admin = $this->getSuperAdmin();

        $postData = [
            'title' => 'Test Admin Created Guide',
            'topic' => 'Beauty Tips',
            'excerpt' => 'Short summary created by admin.',
            'content' => '<h2>Step 1</h2><p>Cleanse skin.</p><h2>Step 2</h2><p>Tone skin.</p>',
            'is_published' => 1,
            'meta_title' => 'SEO Title',
            'meta_description' => 'SEO Description',
        ];

        $response = $this->actingAs($admin)
            ->post(route('admin.guides.store'), $postData);

        $response->assertRedirect(route('admin.guides.index'));

        $this->assertDatabaseHas('buying_guides', [
            'title' => 'Test Admin Created Guide',
            'topic' => 'Beauty Tips',
            'is_published' => 1,
        ]);

        $guide = BuyingGuide::where('title', 'Test Admin Created Guide')->first();
        $this->assertNotNull($guide);
        $this->assertEquals('test-admin-created-guide', $guide->slug);
        $this->assertGreaterThan(0, $guide->reading_time_minutes);
    }

    public function test_admin_can_update_guide()
    {
        $admin = $this->getSuperAdmin();

        $guide = BuyingGuide::create([
            'title' => 'Test Before Update',
            'slug' => 'test-before-update',
            'content' => '<h2>Heading</h2><p>Original body.</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)
            ->put(route('admin.guides.update', $guide), [
                'title' => 'Test After Update',
                'slug' => 'test-after-update',
                'topic' => 'Updated Topic',
                'content' => '<h2>New Heading</h2><p>Updated content.</p>',
                'is_published' => 1,
            ]);

        $response->assertRedirect(route('admin.guides.index'));
        $this->assertEquals('Test After Update', $guide->fresh()->title);
        $this->assertEquals('test-after-update', $guide->fresh()->slug);
        $this->assertEquals('Updated Topic', $guide->fresh()->topic);
    }

    public function test_admin_can_toggle_publish_status_via_ajax()
    {
        $admin = $this->getSuperAdmin();

        $guide = BuyingGuide::create([
            'title' => 'Test Toggle Status Guide',
            'slug' => 'test-toggle-status-guide',
            'content' => '<p>Content</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson(route('admin.guides.toggle-publish', $guide));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'is_published' => false,
        ]);

        $this->assertFalse($guide->fresh()->is_published);
    }

    public function test_admin_can_search_products_via_ajax()
    {
        $admin = $this->getSuperAdmin();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.guides.search-products', ['q' => 'shampoo']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['products']);
    }

    public function test_admin_can_delete_guide()
    {
        $admin = $this->getSuperAdmin();

        $guide = BuyingGuide::create([
            'title' => 'Test Deletable Guide',
            'slug' => 'test-deletable-guide',
            'content' => '<p>To be deleted</p>',
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.guides.destroy', $guide));

        $response->assertRedirect(route('admin.guides.index'));
        $this->assertSoftDeleted('buying_guides', ['id' => $guide->id]);
    }
}
