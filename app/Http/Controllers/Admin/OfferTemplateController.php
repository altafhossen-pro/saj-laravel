<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfferTemplate;
use Illuminate\Http\Request;

class OfferTemplateController extends Controller
{
    public function index()
    {
        $templates = OfferTemplate::latest()->paginate(15);
        return view('admin.offer-templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'button_text' => 'nullable|string|max:60',
            'button_url' => 'nullable|string|max:255',
            'delay_seconds' => 'nullable|integer|min:0|max:300',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['coupon_code'] = $validated['coupon_code'] ? strtoupper(trim($validated['coupon_code'])) : null;
        $validated['button_text'] = $validated['button_text'] ?: 'অফারটি গ্রহণ করুন';
        $validated['button_url'] = $validated['button_url'] ?: '/checkout';
        $validated['delay_seconds'] = isset($validated['delay_seconds']) ? (int)$validated['delay_seconds'] : 10;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        OfferTemplate::create($validated);

        return redirect()->back()->with('success', 'নতুন অফার টেমপ্লেট সফলভাবে যোগ করা হয়েছে।');
    }

    public function update(Request $request, OfferTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'button_text' => 'nullable|string|max:60',
            'button_url' => 'nullable|string|max:255',
            'delay_seconds' => 'nullable|integer|min:0|max:300',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['coupon_code'] = $validated['coupon_code'] ? strtoupper(trim($validated['coupon_code'])) : null;
        $validated['button_text'] = $validated['button_text'] ?: 'অফারটি গ্রহণ করুন';
        $validated['button_url'] = $validated['button_url'] ?: '/checkout';
        $validated['delay_seconds'] = isset($validated['delay_seconds']) ? (int)$validated['delay_seconds'] : 10;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $template->update($validated);

        return redirect()->back()->with('success', 'অফার টেমপ্লেট সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(OfferTemplate $template)
    {
        $template->delete();
        return redirect()->back()->with('success', 'অফার টেমপ্লেট ডিলিট করা হয়েছে।');
    }
}

