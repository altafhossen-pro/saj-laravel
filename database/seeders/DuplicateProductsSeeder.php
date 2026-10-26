<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Product;
use Illuminate\Support\Str;

class DuplicateProductsSeeder extends Seeder
{
    /**
     * Run the database seeds to duplicate existing products up to 200+.
     */
    public function run(): void
    {
        $baseProducts = Product::with('variants')->get();

        if ($baseProducts->isEmpty()) {
            $this->command->error('No products found to duplicate.');
            return;
        }

        $modifiers = [
            'Premium',
            'Luxury',
            'Royal',
            'Vintage',
            'Bridal Edition',
            'Special Collection',
            'Handcrafted',
            'Exclusive',
        ];

        $validCategoryIds = \App\Models\Category::pluck('id')->toArray();
        $fallbackCategoryId = $validCategoryIds[0] ?? null;

        $created = 0;

        foreach ($modifiers as $modifier) {
            foreach ($baseProducts as $product) {
                // Duplicate product
                $newProduct = $product->replicate();
                $newProduct->title = $modifier . ' ' . $product->title;
                $newProduct->slug = Str::slug($newProduct->title) . '-' . Str::lower(Str::random(6));
                $newProduct->is_active = true;

                // Ensure category exists
                if (!$newProduct->category_id || !in_array($newProduct->category_id, $validCategoryIds)) {
                    $newProduct->category_id = $fallbackCategoryId;
                }

                $newProduct->brand_id = null;
                $newProduct->created_by = null;
                $newProduct->updated_by = null;
                $newProduct->created_at = now()->subMinutes(rand(10, 10000));
                $newProduct->updated_at = now();
                $newProduct->save();

                // Duplicate variants if any
                if ($product->variants && $product->variants->count() > 0) {
                    foreach ($product->variants as $variant) {
                        $newVariant = $variant->replicate();
                        $newVariant->product_id = $newProduct->id;
                        $newVariant->sku = 'VAR-' . strtoupper(Str::random(8));
                        $newVariant->barcode = null;
                        $newVariant->save();
                    }
                }

                $created++;
            }
        }

        $totalNow = Product::count();
        $this->command->info("Created {$created} duplicated products. Total products in DB: {$totalNow}");
    }
}
