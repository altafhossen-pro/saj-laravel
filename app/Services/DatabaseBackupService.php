<?php

namespace App\Services;

use App\Mail\DatabaseRestoredMail;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use ZipArchive;
use PDO;
use Exception;

class DatabaseBackupService
{
    /**
     * Get the absolute path to the server backup directory.
     */
    public static function getBackupDir(): string
    {
        $dir = storage_path('app/backups');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }

        // Security safeguard: protect backup directory from direct HTTP access
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Order allow,deny\nDeny from all\n");
        }

        return $dir;
    }

    /**
     * List all stored backup zip files on the server.
     */
    public function listBackups(): array
    {
        $dir = self::getBackupDir();
        $files = glob($dir . '/*.zip');
        $backups = [];

        if (!$files) {
            return [];
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $sizeBytes = @filesize($file) ?: 0;
            $mtime = @filemtime($file) ?: time();

            $isSafety = str_starts_with($filename, 'pre_restore_safety');

            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'created_at' => date('d M Y, h:i A', $mtime),
                'timestamp' => $mtime,
                'is_safety' => $isSafety,
            ];
        }

        // Sort newest first
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Generate a new database backup zip using ultra-optimized PDO streaming.
     */
    public function createBackup(bool $storeInServer = false, string $prefix = 'backup'): array
    {
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        $backupDir = self::getBackupDir();
        $tempDir = $backupDir . '/temp';
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $connection = DB::connection();
        $dbName = $connection->getDatabaseName();
        $timestamp = date('Y-m-d_H-i-s');
        $sqlFilename = "db_{$dbName}_{$timestamp}.sql";
        $sqlPath = $tempDir . '/' . $sqlFilename;
        $zipFilename = "{$prefix}_{$dbName}_{$timestamp}.zip";
        $zipPath = $backupDir . '/' . $zipFilename;

        $pdo = $connection->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Open SQL file stream
        $sqlFile = fopen($sqlPath, 'w');
        if (!$sqlFile) {
            throw new Exception("Unable to open temporary SQL file for writing at {$sqlPath}");
        }

        // Write header
        fwrite($sqlFile, "-- ==========================================================\n");
        fwrite($sqlFile, "-- Ario Mart Super-Optimized Database Backup\n");
        fwrite($sqlFile, "-- Generation Date: " . date('Y-m-d H:i:s') . "\n");
        fwrite($sqlFile, "-- Database: `{$dbName}`\n");
        fwrite($sqlFile, "-- ==========================================================\n\n");
        fwrite($sqlFile, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($sqlFile, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($sqlFile, "SET time_zone = '+00:00';\n\n");

        // Fetch all tables
        $tables = [];
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Write DROP TABLE & CREATE TABLE
            fwrite($sqlFile, "-- Table structure for `{$table}`\n");
            fwrite($sqlFile, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            fwrite($sqlFile, $createRow[1] . ";\n\n");

            // Write Data in Chunks of 250 rows (keeps RAM negligible)
            fwrite($sqlFile, "-- Dumping data for `{$table}`\n");

            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $totalRows = (int) $countStmt->fetchColumn();

            if ($totalRows > 0) {
                $chunkSize = 250;
                for ($offset = 0; $offset < $totalRows; $offset += $chunkSize) {
                    $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
                    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        break;
                    }

                    $columns = array_keys($rows[0]);
                    $colList = implode('`, `', array_map('addslashes', $columns));

                    $valueLines = [];
                    foreach ($rows as $row) {
                        $escapedValues = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } else {
                                $escapedValues[] = $pdo->quote((string)$val);
                            }
                        }
                        $valueLines[] = '(' . implode(', ', $escapedValues) . ')';
                    }

                    fwrite($sqlFile, "INSERT INTO `{$table}` (`{$colList}`) VALUES \n" . implode(",\n", $valueLines) . ";\n");
                }
            }
            fwrite($sqlFile, "\n");
        }

        fwrite($sqlFile, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($sqlFile);

        // Package into ZIP Archive
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sqlPath);
            throw new Exception("Failed to create ZIP archive at {$zipPath}");
        }

        $zip->addFile($sqlPath, $sqlFilename);
        $zip->close();

        // Clean temporary SQL file
        @unlink($sqlPath);

        $sizeBytes = filesize($zipPath);
        $sizeFormatted = $this->formatBytes($sizeBytes);

        // If not safety backup, notify Telegram
        if ($prefix !== 'pre_restore_safety') {
            try {
                $telegram = new TelegramService();
                if ($telegram->isConfigured()) {
                    $adminName = Auth::user()?->name ?? 'System Admin';
                    $msg = "💾 <b>Database Backup Created</b>\n\n"
                         . "📦 <b>File:</b> <code>{$zipFilename}</code>\n"
                         . "📊 <b>Size:</b> {$sizeFormatted}\n"
                         . "📅 <b>Time:</b> " . date('d M Y, h:i A') . "\n"
                         . "👤 <b>Operator:</b> {$adminName}\n"
                         . "🌐 <b>IP:</b> " . request()->ip() . "\n"
                         . "💾 <b>Stored on Server:</b> " . ($storeInServer ? 'Yes' : 'Direct Download Only');
                    $telegram->sendMessage($msg);
                }
            } catch (\Throwable $e) {
                Log::warning('Telegram backup alert failed: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'path' => $zipPath,
            'filename' => $zipFilename,
            'size' => $sizeFormatted,
            'size_bytes' => $sizeBytes,
            'store_in_server' => $storeInServer,
        ];
    }

    /**
     * Restore database from a given zip or sql file path.
     */
    public function restoreBackup(string $filePath, ?string $originalName = null): array
    {
        @ini_set('max_execution_time', '600');
        @ini_set('memory_limit', '512M');

        if (!file_exists($filePath)) {
            throw new Exception("Restore source file not found at {$filePath}");
        }

        $displayFilename = $originalName ?: basename($filePath);

        // 1. Mandatory Pre-Restore Safety Snapshot (Automated Fail-safe)
        $safetyBackup = $this->createBackup(true, 'pre_restore_safety');

        // 2. Extract or locate SQL content
        $backupDir = self::getBackupDir();
        $tempExtractDir = $backupDir . '/temp_restore_' . time();
        @mkdir($tempExtractDir, 0755, true);

        $sqlPath = null;
        $isZip = str_ends_with(strtolower($filePath), '.zip');

        if ($isZip) {
            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new Exception("Unable to open the uploaded ZIP archive.");
            }

            $zip->extractTo($tempExtractDir);
            $zip->close();

            // Find extracted .sql file
            $sqlFiles = glob($tempExtractDir . '/*.sql');
            if (empty($sqlFiles)) {
                $this->recursiveDelete($tempExtractDir);
                throw new Exception("Invalid backup archive: No .sql file found inside the zip.");
            }
            $sqlPath = $sqlFiles[0];
        } else {
            $sqlPath = $filePath;
        }

        // 3. Execute SQL Statements through PDO safely
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $pdo->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");

        $handle = fopen($sqlPath, 'r');
        if (!$handle) {
            $this->recursiveDelete($tempExtractDir);
            throw new Exception("Could not read SQL restoration file.");
        }

        $query = '';
        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            // Skip comments and empty lines
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
                continue;
            }

            $query .= $line;
            if (str_ends_with($trimmed, ';')) {
                try {
                    $pdo->exec($query);
                } catch (\Throwable $e) {
                    // Log individual query errors but continue restoring remaining tables
                    Log::warning("Restore query failed: " . substr($query, 0, 150) . " | Error: " . $e->getMessage());
                }
                $query = '';
            }
        }
        fclose($handle);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");

        // Clean temp extracted folder
        $this->recursiveDelete($tempExtractDir);

        // Clear application caches
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
        } catch (\Throwable $e) {}

        $tableCount = count(DB::select('SHOW TABLES'));

        // 4. Send Telegram Critical Alert
        $adminUser = Auth::user();
        $adminName = $adminUser?->name ?? 'Admin';
        $adminEmail = $adminUser?->email ?? 'N/A';
        $ip = request()->ip();
        $timestampStr = date('d M Y, h:i:s A');

        try {
            $telegram = new TelegramService();
            if ($telegram->isConfigured()) {
                $teleMsg = "🚨🚨 <b>CRITICAL SYSTEM ALERT: Database Restored!</b> 🚨🚨\n\n"
                         . "⚠️ <b>The database was restored successfully from backup!</b>\n"
                         . "📦 <b>Restored Archive:</b> <code>{$displayFilename}</code>\n"
                         . "📅 <b>Timestamp:</b> {$timestampStr}\n"
                         . "👤 <b>Operator:</b> {$adminName} ({$adminEmail})\n"
                         . "🌐 <b>IP Address:</b> {$ip}\n"
                         . "📊 <b>Synchronized Tables:</b> {$tableCount} tables active\n"
                         . "🛡️ <b>Pre-Restore Safety Snapshot:</b> <code>" . ($safetyBackup['filename'] ?? 'Created') . "</code>";
                $telegram->sendMessage($teleMsg);
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram restore alert failed: ' . $e->getMessage());
        }

        // 5. Send Critical Email Alert to Admin
        try {
            if (\App\Services\MailConfigService::isMailEnabled()) {
                $recipientEmail = $adminEmail !== 'N/A' ? $adminEmail : (Setting::get('contact_email') ?: config('mail.from.address'));
                if (!empty($recipientEmail)) {
                    \App\Services\MailConfigService::applyRuntimeConfig();
                    $mailDetails = [
                        'site_name' => Setting::get('site_name', 'Ario Mart'),
                        'filename' => $displayFilename,
                        'timestamp' => $timestampStr,
                        'admin_name' => $adminName,
                        'admin_email' => $adminEmail,
                        'ip' => $ip,
                        'safety_backup' => $safetyBackup['filename'] ?? 'Created automatically',
                        'table_count' => $tableCount,
                    ];
                    Mail::to($recipientEmail)->send(new DatabaseRestoredMail($mailDetails));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Email restore alert failed: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => "Database successfully restored from {$displayFilename} ({$tableCount} tables synced).",
            'safety_backup' => $safetyBackup['filename'] ?? null,
            'table_count' => $tableCount,
        ];
    }

    /**
     * Delete a stored backup zip safely.
     */
    public function deleteBackup(string $filename): bool
    {
        $clean = basename($filename);
        $path = self::getBackupDir() . '/' . $clean;

        if (file_exists($path) && str_ends_with($clean, '.zip')) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * Generate a simple friendly arithmetic math captcha.
     * e.g. "14 + 8" or "25 − 9" or "6 × 7"
     */
    public function generateMathCaptcha(): array
    {
        $type = rand(1, 3);
        $question = '';
        $answer = 0;

        switch ($type) {
            case 1: // Addition
                $a = rand(5, 25);
                $b = rand(3, 20);
                $answer = $a + $b;
                $question = "{$a} + {$b}";
                break;

            case 2: // Subtraction
                $a = rand(15, 40);
                $b = rand(3, 14);
                $answer = $a - $b;
                $question = "{$a} − {$b}";
                break;

            case 3: // Small multiplication
            default:
                $a = rand(3, 9);
                $b = rand(3, 9);
                $answer = $a * $b;
                $question = "{$a} × {$b}";
                break;
        }

        session(['db_restore_math_answer' => $answer]);

        return [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    /**
     * Verify the user's submitted math captcha answer.
     */
    public function verifyMathCaptcha($userAnswer): bool
    {
        $expected = session('db_restore_math_answer');
        if ($expected === null) {
            return false;
        }

        $isValid = ((int)trim((string)$userAnswer) === (int)$expected);
        if ($isValid) {
            session()->forget('db_restore_math_answer');
        }

        return $isValid;
    }

    /**
     * Helper to format bytes to human readable string.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Recursively delete a directory.
     */
    protected function recursiveDelete(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $p = $dir . '/' . $file;
            is_dir($p) ? $this->recursiveDelete($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
