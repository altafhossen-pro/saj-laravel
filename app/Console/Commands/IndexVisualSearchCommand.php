<?php

namespace App\Console\Commands;

use App\Models\Product\Product;
use App\Services\VisualSearchService;
use Illuminate\Console\Command;

class IndexVisualSearchCommand extends Command
{
    protected $signature = 'visual-search:index {--product= : Index a specific product by ID}';
    protected $description = 'Index products for reverse image search without external AI APIs';

    public function handle(): int
    {
        $productId = $this->option('product');

        if ($productId) {
            $product = Product::find($productId);
            if (!$product) {
                $this->error("Product with ID {$productId} not found.");
                return 1;
            }

            $count = VisualSearchService::indexProduct($product);
            $this->info("Successfully indexed {$count} image(s) for product: {$product->title}");
            return 0;
        }

        $this->info('Starting visual image indexing for all active products...');
        $total = VisualSearchService::indexAllProducts();
        $this->info("Completed! Total {$total} product image(s) indexed for visual search.");

        return 0;
    }
}
