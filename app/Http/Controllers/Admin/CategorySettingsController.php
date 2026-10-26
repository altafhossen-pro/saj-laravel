<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategorySettingsController extends Controller
{
    public function updateHomepageSettings(Request $request, Category $category)
    {
        $request->validate([
            'show_on_homepage' => 'sometimes|boolean',
            'homepage_layout' => 'sometimes|in:grid,slider',
            'homepage_max_products' => 'sometimes|integer|min:1',
            'homepage_per_page' => 'sometimes|integer|min:1',
            'sort_order' => 'sometimes|nullable|integer|min:0',
        ]);

        if ($request->has('show_on_homepage')) {
            $category->show_on_homepage = $request->boolean('show_on_homepage');
        }

        if ($request->has('sort_order')) {
            $category->sort_order = $request->sort_order ?? 0;
        }

        if ($request->has('homepage_layout')) {
            $category->homepage_layout = $request->homepage_layout;
        }

        if ($request->has('homepage_max_products')) {
            $category->homepage_max_products = $request->homepage_max_products;
        }

        if ($request->has('homepage_per_page')) {
            $category->homepage_per_page = $request->homepage_per_page;
        }

        $category->save();

        // Clear the cached homepage sections
        \Illuminate\Support\Facades\Cache::forget('homepage_sections');

        return response()->json([
            'success' => true,
            'message' => 'Homepage settings updated successfully.',
            'category' => $category->fresh()
        ]);
    }
}
