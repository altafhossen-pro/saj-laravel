<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class HeroSlideController extends Controller
{
    public function index()
    {
        $slides = HeroSlide::orderBy('sort_order')->get();
        return view('admin.homepage.hero.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.homepage.hero.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:image,html',
            'link' => 'nullable|url',
            'sort_order' => 'required|integer',
        ]);

        $content = '';
        $mobileContent = '';
        if ($request->type === 'image') {
            $request->validate([
                'image' => 'required|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
                'mobile_image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);
            $path = $request->file('image')->store('hero', 'public');
            $content = Storage::url($path);

            if ($request->hasFile('mobile_image')) {
                $mobilePath = $request->file('mobile_image')->store('hero', 'public');
                $mobileContent = Storage::url($mobilePath);
            }
        } else {
            $request->validate([
                'html_content' => 'required|string',
                'mobile_html_content' => 'nullable|string',
            ]);
            $content = $request->html_content;
            $mobileContent = $request->mobile_html_content;
        }

        HeroSlide::create([
            'type' => $request->type,
            'content' => $content,
            'mobile_content' => $mobileContent,
            'link' => $request->link,
            'sort_order' => $request->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        Cache::forget('homepage_hero_slides');
        return redirect()->route('admin.homepage.hero.index')->with('success', 'Slide created successfully.');
    }

    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.homepage.hero.edit', compact('heroSlide'));
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        $request->validate([
            'type' => 'required|in:image,html',
            'link' => 'nullable|url',
            'sort_order' => 'required|integer',
        ]);

        $content = $heroSlide->content;
        $mobileContent = $heroSlide->mobile_content;
        if ($request->type === 'image') {
            $request->validate([
                'image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
                'mobile_image' => 'nullable|file|mimes:jpeg,png,jpg,webp,gif|max:5120',
            ]);
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('hero', 'public');
                $content = Storage::url($path);
            }
            if ($request->hasFile('mobile_image')) {
                $mobilePath = $request->file('mobile_image')->store('hero', 'public');
                $mobileContent = Storage::url($mobilePath);
            }
        } else {
            $request->validate([
                'html_content' => 'required|string',
                'mobile_html_content' => 'nullable|string',
            ]);
            $content = $request->html_content;
            $mobileContent = $request->mobile_html_content;
        }

        $heroSlide->update([
            'type' => $request->type,
            'content' => $content,
            'mobile_content' => $mobileContent,
            'link' => $request->link,
            'sort_order' => $request->sort_order,
            'is_active' => $request->has('is_active'),
        ]);

        Cache::forget('homepage_hero_slides');
        return redirect()->route('admin.homepage.hero.index')->with('success', 'Slide updated successfully.');
    }

    public function toggle(HeroSlide $heroSlide)
    {
        $heroSlide->update(['is_active' => !$heroSlide->is_active]);
        Cache::forget('homepage_hero_slides');

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $heroSlide->is_active]);
        }
        return redirect()->back()->with('success', 'Slide visibility updated successfully.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();
        Cache::forget('homepage_hero_slides');

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('admin.homepage.hero.index')->with('success', 'Slide deleted successfully.');
    }
}
