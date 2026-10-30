<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VisitorCustomOffer;
use App\Models\VisitorSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorOfferAndLiveTrackingTest extends TestCase
{
    /**
     * Test pulse API logs activity and returns pending offer with custom delay and button
     */
    public function test_pulse_endpoint_returns_pending_offer_for_visitor(): void
    {
        $testToken = 'test_vid_' . uniqid();

        // 1. Send initial pulse from visitor
        $response = $this->postJson('/api/visitor/pulse', [
            'vid' => $testToken,
            'url' => '/product/argan-oil',
            'title' => 'Argan Oil Facial Serum',
            'device' => 'Desktop',
            'is_engaged' => 1,
            'scroll_percent' => 85,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'offer' => null,
        ]);

        // Verify session was recorded in database
        $this->assertDatabaseHas('visitor_sessions', [
            'visitor_token' => $testToken,
            'entry_url' => '/product/argan-oil',
        ]);

        // 2. Admin creates an instant custom offer for this visitor
        $offer = VisitorCustomOffer::create([
            'visitor_token' => $testToken,
            'title' => 'Exclusive 15% OFF for you!',
            'message' => 'Complete your order right now and get flat 15% off.',
            'coupon_code' => 'TEST15',
            'button_text' => 'Claim 15% Now',
            'button_url' => '/checkout',
            'delay_seconds' => 10,
            'status' => 'pending',
            'admin_name' => 'Super Admin',
        ]);

        // 3. Visitor sends heartbeat / next pulse
        $pulse2 = $this->postJson('/api/visitor/pulse', [
            'vid' => $testToken,
            'url' => '/product/argan-oil',
            'title' => 'Argan Oil Facial Serum',
            'device' => 'Desktop',
        ]);

        $pulse2->assertStatus(200);
        $pulse2->assertJsonStructure([
            'status',
            'offer' => [
                'id',
                'title',
                'message',
                'coupon_code',
                'button_text',
                'button_url',
                'delay_seconds',
            ]
        ]);

        $this->assertEquals($offer->id, $pulse2->json('offer.id'));
        $this->assertEquals('Exclusive 15% OFF for you!', $pulse2->json('offer.title'));
        $this->assertEquals('Claim 15% Now', $pulse2->json('offer.button_text'));
        $this->assertEquals(10, $pulse2->json('offer.delay_seconds'));

        // 4. Test visitor responds (Delivered -> Clicked)
        $respond = $this->postJson("/api/visitor/offer/{$offer->id}/respond", [
            'action' => 'clicked',
        ]);

        $respond->assertStatus(200);
        $this->assertEquals('clicked', $offer->fresh()->status);
    }

    /**
     * Test that visitor footprint admin page renders without JavaScript leaks
     */
    public function test_visitor_footprint_view_renders_cleanly(): void
    {
        $admin = User::first() ?? User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.visitors.footprint'));

        $response->assertStatus(200);
        // Ensure no leaked unescaped alpine attributes render as raw text in HTML
        $response->assertDontSee('item.id == this.selectedTemplate); if (t)');
        $response->assertSee('visitorFootprintManager()');
    }

    /**
     * Test that frontend layout includes cross-page persistent countdown timer logic
     */
    public function test_frontend_layout_has_cross_page_offer_scheduler(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('__scheduleVisitorOffer');
        $response->assertSee('fb_offer_target_');
        $response->assertSee('__triggerModalOpen');
    }
}

