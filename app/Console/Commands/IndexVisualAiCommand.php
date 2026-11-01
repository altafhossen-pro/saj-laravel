<?php

namespace App\Console\Commands;

use App\Models\ProductVisualIndex;
use App\Services\CloudflareAiService;
use App\Services\VisualSearchService;
use Illuminate\Console\Command;

class IndexVisualAiCommand extends Command
{
    protected $signature = 'visual-search:ai-index {--force : Recompute existing embeddings}';
    protected $description = 'Generate and precompute 512-dim AI vector embeddings for all catalog product images using Cloudflare Workers AI';

    public function handle(): int
    {
        if (!CloudflareAiService::isConfigured()) {
            $this->error('Cloudflare Workers AI credentials are not configured.');
            $this->line('Set your Account ID and API Token in Admin Settings (/admin/settings/visual-search) or .env');
            return Command::FAILURE;
        }

        $this->info('Starting Cloudflare Workers AI embedding generation...');
        $force = $this->option('force');

        $query = ProductVisualIndex::query();
        if (!$force) {
            $query->whereNull('ai_embedding');
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('All images are already indexed with AI embeddings. Use --force to recompute.');
            return Command::SUCCESS;
        }

        $this->info("Found {$total} image(s) to process.");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $failedCount = 0;

        $indexes = $query->get();
        foreach ($indexes as $index) {
            $fullPath = VisualSearchService::resolveProductImagePath($index->image_path);
            if ($fullPath && file_exists($fullPath)) {
                $embedding = CloudflareAiService::generateImageEmbedding($fullPath);
                if ($embedding) {
                    $index->ai_embedding = $embedding;
                    $index->save();
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } else {
                $failedCount++;
            }

            $bar->advance();
            // Tiny 100ms pause to avoid bursting rate limits
            usleep(100000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Completed! Successfully embedded: {$successCount}, Failed/Missing: {$failedCount}");

        return Command::SUCCESS;
    }
}

