<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class PackageChangesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build:changes 
                            {--base=origin/main : Git reference/branch to compare changes against}
                            {--no-build : Skip running npm run build before packaging}
                            {--output= : Custom zip file name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs frontend build, gathers all changed files since last git push, and exports a clean cPanel-ready ZIP file maintaining project structure.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("🚀 Starting cPanel Deployment Packager...");

        // 1. Optional Frontend Build
        if (!$this->option('no-build')) {
            $this->info("📦 Compiling production frontend assets (npm run build)...");
            
            // Cross-platform command execution
            $buildCommand = (PHP_OS_FAMILY === 'Windows') ? 'npm.cmd run build' : 'npm run build';
            exec($buildCommand, $buildOutput, $buildReturnCode);

            if ($buildReturnCode !== 0) {
                $this->error("❌ 'npm run build' failed! Check frontend compile errors.");
                $this->line(implode("\n", $buildOutput));
                return Command::FAILURE;
            }
            $this->info("✅ Frontend assets compiled successfully (public/build ready).");
        } else {
            $this->comment("⏭️ Skipping frontend build (--no-build).");
        }

        // 2. Identify Changed Files via Git
        $base = $this->option('base');
        $this->info("🔍 Detecting changes compared to '{$base}'...");

        // Check tracked modified/added files against origin/main
        exec("git diff --name-only {$base} HEAD", $committedDiff, $code1);
        
        // Also check uncommitted working tree changes (staged or unstaged)
        exec("git diff --name-only {$base}", $uncommittedDiff, $code2);

        // Check untracked newly created files
        exec("git ls-files --others --exclude-standard", $untrackedFiles, $code3);

        $allCandidates = array_unique(array_filter(array_merge($committedDiff, $uncommittedDiff, $untrackedFiles)));

        // Always check if public/build exists and includes compiled manifest & assets
        $publicBuildFiles = [];
        $buildDir = public_path('build');
        if (is_dir($buildDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($buildDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    // Convert to relative path from project root
                    $relPath = str_replace('\\', '/', substr($item->getPathname(), strlen(base_path()) + 1));
                    $publicBuildFiles[] = $relPath;
                }
            }
        }

        // Merge changed files with compiled public/build
        $filesToZip = array_unique(array_merge($allCandidates, $publicBuildFiles));

        // Filter out excluded/unnecessary files (e.g. node_modules, .env, .git, storage logs)
        $ignoredPrefixes = [
            'node_modules/',
            '.git/',
            'storage/logs/',
            'storage/framework/cache/',
            'storage/framework/sessions/',
            'storage/framework/views/',
            '.env',
            '.gitignore',
            '.editorconfig',
            'packageChanges.zip'
        ];

        $validFiles = [];
        foreach ($filesToZip as $relPath) {
            $normalized = str_replace('\\', '/', trim($relPath));
            
            // Check exclusions
            $isIgnored = false;
            foreach ($ignoredPrefixes as $prefix) {
                if (str_starts_with($normalized, $prefix) || $normalized === trim($prefix, '/')) {
                    $isIgnored = true;
                    break;
                }
            }

            if ($isIgnored) continue;

            $fullPath = base_path($normalized);
            if (file_exists($fullPath) && is_file($fullPath)) {
                $validFiles[] = $normalized;
            }
        }

        if (empty($validFiles)) {
            $this->warn("⚠️ No changed files found to package.");
            return Command::SUCCESS;
        }

        // 3. Create the ZIP Archive maintaining directory structure
        $zipName = $this->option('output') ?: 'update-pack-' . date('Ymd-His') . '.zip';
        $zipPath = base_path($zipName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("❌ Cannot create ZIP file at {$zipPath}");
            return Command::FAILURE;
        }

        $this->info("📁 Adding files to archive maintainig folder structure...");
        $bar = $this->output->createProgressBar(count($validFiles));
        $bar->start();

        foreach ($validFiles as $file) {
            $fullPath = base_path($file);
            $zip->addFile($fullPath, $file);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $zip->close();

        // 4. Output Summary
        $zipSize = round(filesize($zipPath) / (1024 * 1024), 2);
        if ($zipSize < 0.01) {
            $zipSize = round(filesize($zipPath) / 1024, 2) . ' KB';
        } else {
            $zipSize .= ' MB';
        }

        $this->newLine();
        $this->info("=========================================================");
        $this->info("🎉 cPanel Update ZIP created successfully!");
        $this->info("📦 File: {$zipName} ({$zipSize})");
        $this->info("📍 Location: " . $zipPath);
        $this->info("📄 Total Files Included: " . count($validFiles));
        $this->info("=========================================================");
        $this->line("👉 cPanel এ গিয়ে রুট ফোল্ডারে এই ZIP টি Upload করে Extract করলেই");
        $this->line("   সব ফাইল অটোমেটিক যার যার জায়গায় (resources, app, public/build) রিপ্লেস হয়ে যাবে!");
        $this->newLine();

        return Command::SUCCESS;
    }
}
