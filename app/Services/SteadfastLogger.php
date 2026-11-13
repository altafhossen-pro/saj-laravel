<?php

namespace App\Services;

use Carbon\Carbon;
use Throwable;

class SteadfastLogger
{
    /**
     * Get the absolute path to steadfast.log file.
     */
    public static function getLogPath(): string
    {
        $dir = storage_path('logs');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/steadfast.log';
        if (!file_exists($file)) {
            @file_put_contents($file, "");
        }
        return $file;
    }

    /**
     * Log an event with a specific level and optional context.
     *
     * @param string $level (INFO, SUCCESS, WARNING, ERROR)
     * @param string $message
     * @param array $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        try {
            $path = self::getLogPath();
            $timestamp = Carbon::now('Asia/Dhaka')->format('Y-m-d H:i:s') . ' BDT';
            $level = strtoupper($level);

            // User requirement: Do NOT log successful events or routine info.
            // Only store actual errors, warnings, and failures in steadfast.log.
            if (in_array($level, ['INFO', 'SUCCESS'])) {
                return;
            }

            $entry = "[{$timestamp}] {$level}: {$message}";
            if (!empty($context)) {
                $entry .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $entry .= PHP_EOL;

            @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            // Fail safely without disrupting the application flow
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function success(string $message, array $context = []): void
    {
        self::log('SUCCESS', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Read the full content or tail of the log file.
     */
    public static function getContent(int $maxBytes = 2500000): string
    {
        $path = self::getLogPath();
        if (!file_exists($path)) {
            return '';
        }

        $size = @filesize($path) ?: 0;
        if ($size > $maxBytes) {
            $fp = @fopen($path, 'r');
            if ($fp) {
                @fseek($fp, -$maxBytes, SEEK_END);
                $content = @fread($fp, $maxBytes);
                @fclose($fp);
                return "[... TRUNCATED DUE TO SIZE: Showing last " . round($maxBytes / 1024) . " KB ...]\n" . $content;
            }
        }

        return @file_get_contents($path) ?: '';
    }

    /**
     * Save updated content to the log file.
     */
    public static function saveContent(string $content): bool
    {
        $path = self::getLogPath();
        $res = @file_put_contents($path, $content, LOCK_EX);
        return $res !== false;
    }

    /**
     * Clear all contents from steadfast.log.
     */
    public static function clear(): bool
    {
        $path = self::getLogPath();
        $res = @file_put_contents($path, '', LOCK_EX);
        return $res !== false;
    }

    /**
     * Get file metadata (size, lines, modified).
     */
    public static function getMetadata(): array
    {
        $path = self::getLogPath();
        $sizeBytes = file_exists($path) ? (@filesize($path) ?: 0) : 0;
        $mtime = file_exists($path) ? (@filemtime($path) ?: time()) : time();
        $content = self::getContent();
        $lines = substr_count($content, "\n") + (strlen($content) > 0 ? 1 : 0);

        return [
            'file_name' => 'steadfast.log',
            'path' => $path,
            'size' => self::formatBytes($sizeBytes),
            'size_bytes' => $sizeBytes,
            'lines' => $lines,
            'modified' => Carbon::createFromTimestamp($mtime, 'Asia/Dhaka')->format('d M Y, h:i:s A') . ' BDT',
            'timestamp' => $mtime,
        ];
    }

    /**
     * Format byte sizes into human readable units.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
