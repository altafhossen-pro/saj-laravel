<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $categories = Category::select('id', 'name', 'parent_id')
            ->with('parent:id,name')
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($category) {
                $name = $category->parent ? $category->parent->name . ' > ' . $category->name : $category->name;
                return [
                    'id' => $category->id,
                    'name' => $name,
                ];
            });
            
        return response()->json($categories);
    }

    public function index(Request $request)
    {
        $query = Category::with('parent');

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('is_active', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $categories = $query->orderBy('sort_order')->paginate($perPage);

        $treeCategories = Category::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with(['children' => function ($sub) {
                    $sub->withCount('products')->orderBy('sort_order', 'asc');
                }])
                ->withCount(['children', 'products'])
                ->orderBy('sort_order', 'asc');
            }])
            ->withCount(['children', 'products'])
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('admin.categories.index', compact('categories', 'treeCategories'));
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:categories,id',
        ]);

        foreach ($request->order as $index => $id) {
            Category::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        \Illuminate\Support\Facades\Cache::forget('sidebar_categories');
        \Illuminate\Support\Facades\Cache::forget('homepage_sections');

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully.'
        ]);
    }

    public function create()
    {
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();
        $autoConvertWebp = filter_var(\App\Models\Setting::get('auto_convert_webp', true), FILTER_VALIDATE_BOOLEAN);
        return view('admin.categories.create', compact('parentCategories', 'autoConvertWebp'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'image_url' => 'nullable|url',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->except(['image', 'image_url']);
        $data['slug'] = Str::slug($request->name);
        
        // Ensure unique slug
        $originalSlug = $data['slug'];
        $count = 1;
        while (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $count;
            $count++;
        }

        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        $data['show_on_header'] = $request->has('show_on_header');
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $autoConvert = $request->has('auto_convert_webp');
            
            if ($autoConvert && in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                $quality = (int) \App\Models\Setting::get('webp_quality', 85);
                $filename = uniqid() . '.webp';
                $directory = storage_path('app/public/categories');
                
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                $path = $directory . '/' . $filename;
                
                // Create image resource based on original extension
                $imageResource = null;
                if ($file->extension() == 'png') {
                    $imageResource = imagecreatefrompng($file->getRealPath());
                    imagepalettetotruecolor($imageResource);
                    imagealphablending($imageResource, true);
                    imagesavealpha($imageResource, true);
                } else {
                    $imageResource = imagecreatefromjpeg($file->getRealPath());
                }
                
                // Convert and save to webp
                if ($imageResource) {
                    imagewebp($imageResource, $path, $quality);
                    imagedestroy($imageResource);
                    $data['image'] = 'categories/' . $filename;
                } else {
                    // Fallback if GD fails
                    $data['image'] = $file->store('categories', 'public');
                }
            } else {
                // Save normally if not auto convert or already webp/svg
                $data['image'] = $file->store('categories', 'public');
            }
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->image_url;
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        $parentCategories = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id) // cannot be parent of itself
            ->orderBy('name')
            ->get();
        $autoConvertWebp = filter_var(\App\Models\Setting::get('auto_convert_webp', true), FILTER_VALIDATE_BOOLEAN);
        
        return view('admin.categories.edit', compact('category', 'parentCategories', 'autoConvertWebp'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'image_url' => 'nullable|url',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'nullable|integer',
        ]);

        $data = $request->except(['image', 'image_url']);
        
        // Update slug if name changed
        if ($request->name !== $category->name) {
            $data['slug'] = Str::slug($request->name);
            $originalSlug = $data['slug'];
            $count = 1;
            while (Category::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        $data['is_active'] = $request->has('is_active');
        $data['is_featured'] = $request->has('is_featured');
        $data['show_on_header'] = $request->has('show_on_header');
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $autoConvert = $request->has('auto_convert_webp');
            
            // Delete old image if exists
            if ($category->image && !Str::startsWith($category->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($category->image);
            }
            
            if ($autoConvert && in_array($file->extension(), ['jpg', 'jpeg', 'png'])) {
                $quality = (int) \App\Models\Setting::get('webp_quality', 85);
                $filename = uniqid() . '.webp';
                $directory = storage_path('app/public/categories');
                
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }
                
                $path = $directory . '/' . $filename;
                
                $imageResource = null;
                if ($file->extension() == 'png') {
                    $imageResource = imagecreatefrompng($file->getRealPath());
                    imagepalettetotruecolor($imageResource);
                    imagealphablending($imageResource, true);
                    imagesavealpha($imageResource, true);
                } else {
                    $imageResource = imagecreatefromjpeg($file->getRealPath());
                }
                
                if ($imageResource) {
                    imagewebp($imageResource, $path, $quality);
                    imagedestroy($imageResource);
                    $data['image'] = 'categories/' . $filename;
                } else {
                    $data['image'] = $file->store('categories', 'public');
                }
            } else {
                $data['image'] = $file->store('categories', 'public');
            }
        } elseif ($request->filled('image_url')) {
            // Delete old image if local
            if ($category->image && !Str::startsWith($category->image, ['http://', 'https://'])) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->image_url;
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->image && !Str::startsWith($category->image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();
        
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
