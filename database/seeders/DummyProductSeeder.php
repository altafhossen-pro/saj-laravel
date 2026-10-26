<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product\Product;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DummyProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $categories = Category::where('is_active', true)->get();

        if ($categories->isEmpty()) {
            $this->command->info('No categories found. Please create categories first.');
            return;
        }

        $this->command->info('Creating 25 products for each category...');

        foreach ($categories as $category) {
            for ($i = 1; $i <= 25; $i++) {
                $title = $faker->words(3, true) . ' ' . $category->name . ' ' . $i;
                $slug = Str::slug($title) . '-' . uniqid();
                
                // Use dummyimage.com for reliable dummy images
                // Use picsum.photos for random photographic images
                $imageUrl = "https://picsum.photos/seed/" . uniqid() . "/600/600";

                Product::create([
                    'title' => ucwords($title),
                    'slug' => $slug,
                    'short_description' => $faker->sentence(10),
                    'description' => $faker->paragraphs(3, true),
                    'base_price' => $faker->randomFloat(2, 50, 5000),
                    'total_stock' => $faker->numberBetween(0, 100),
                    'is_active' => true,
                    'status' => 'published',
                    'product_type' => 'simple',
                    'category_id' => $category->id,
                    'brand_id' => null,
                    'featured_image' => $imageUrl,
                    'average_rating' => $faker->randomFloat(1, 3, 5),
                    'total_sold' => $faker->numberBetween(5, 500),
                    'total_reviews' => $faker->numberBetween(1, 100),
                ]);
            }
        }

        $this->command->info('Dummy products created successfully!');
    }
}
