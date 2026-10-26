<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

class LogViewerController extends Controller
{
    /**
     * Get the absolute path to storage logs directory.
     */
    protected function getLogDir(): string
    {
        return storage_path('logs');
    }

    /**
     * Display the log viewer and editor dashboard.
     */
    public function index(Request $request)
    {
        $logDir = $this->getLogDir();

        if (!file_exists($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Ensure default laravel.log exists
        $defaultLog = $logDir . '/laravel.log';
        if (!file_exists($defaultLog)) {
            @file_put_contents($defaultLog, "");
        }

        // List all .log files
        $rawFiles = glob($logDir . '/*.log') ?: [];
        $logFiles = [];

        foreach ($rawFiles as $filePath) {
            $name = basename($filePath);
            $bytes = @filesize($filePath) ?: 0;
            $mtime = @filemtime($filePath) ?: time();

            $logFiles[] = [
                'name' => $name,
                'path' => $filePath,
                'size' => $this->formatBytes($bytes),
                'size_bytes' => $bytes,
                'modified' => date('d M Y, h:i A', $mtime),
                'timestamp' => $mtime,
            ];
        }

        // Sort by timestamp descending
        usort($logFiles, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Determine active selected file
        $selectedFile = basename($request->get('file', 'laravel.log'));
        $targetPath = $logDir . '/' . $selectedFile;

        if (!file_exists($targetPath)) {
            $selectedFile = !empty($logFiles) ? $logFiles[0]['name'] : 'laravel.log';
            $targetPath = $logDir . '/' . $selectedFile;
        }

        $fileSizeBytes = file_exists($targetPath) ? (@filesize($targetPath) ?: 0) : 0;
        $fileSize = $this->formatBytes($fileSizeBytes);
        $lastModified = file_exists($targetPath) ? date('d M Y, h:i:s A', @filemtime($targetPath) ?: time()) : 'N/A';

        // Read content safely (if file is larger than 2.5MB, read the trailing tail to avoid memory exhaustion)
        $isTruncated = false;
        $content = '';
        $maxSafeBytes = 2.5 * 1024 * 1024; // 2.5 MB

        if (file_exists($targetPath)) {
            if ($fileSizeBytes > $maxSafeBytes && !$request->boolean('full', false)) {
                $isTruncated = true;
                $content = $this->readTail($targetPath, 2500);
            } else {
                $content = @file_get_contents($targetPath) ?: '';
            }
        }

        $totalLines = substr_count($content, "\n") + (strlen($content) > 0 ? 1 : 0);

        return view('admin.settings.logs', compact(
            'logFiles',
            'selectedFile',
            'content',
            'fileSize',
            'fileSizeBytes',
            'lastModified',
            'totalLines',
            'isTruncated'
        ));
    }

    /**
     * Save updated content to the selected log file.
     */
    public function save(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
            'content' => 'nullable|string',
        ]);

        $filename = basename($request->file);
        $filePath = $this->getLogDir() . '/' . $filename;

        if (!str_ends_with($filename, '.log') || !file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Invalid or missing log file.'], 404);
        }

        try {
            $written = @file_put_contents($filePath, $request->input('content', ''), LOCK_EX);

            if ($written === false) {
                return response()->json(['success' => false, 'message' => 'Failed to write to file. Please check file permissions.'], 500);
            }

            return response()->json([
                'success' => true,
                'message' => "Log file '{$filename}' saved successfully (" . $this->formatBytes(strlen($request->input('content', ''))) . ").",
                'size' => $this->formatBytes(filesize($filePath)),
                'modified' => date('d M Y, h:i:s A', filemtime($filePath))
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear all contents from the selected log file.
     */
    public function clear(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
        ]);

        $filename = basename($request->file);
        $filePath = $this->getLogDir() . '/' . $filename;

        if (!str_ends_with($filename, '.log') || !file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Invalid or missing log file.'], 404);
        }

        @file_put_contents($filePath, '', LOCK_EX);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Log file '{$filename}' cleared successfully.",
                'size' => '0 B',
                'modified' => date('d M Y, h:i:s A')
            ]);
        }

        return redirect()->route('admin.settings.logs', ['file' => $filename])
            ->with('success', "Log file '{$filename}' has been cleared.");
    }

    /**
     * Delete a specific log file.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'file' => 'required|string',
        ]);

        $filename = basename($request->file);
        $filePath = $this->getLogDir() . '/' . $filename;

        if ($filename === 'laravel.log') {
            // Never completely delete laravel.log; clear it instead
            @file_put_contents($filePath, '', LOCK_EX);
            return redirect()->route('admin.settings.logs')
                ->with('success', "Main log file 'laravel.log' was emptied.");
        }

        if (file_exists($filePath) && str_ends_with($filename, '.log')) {
            @unlink($filePath);
            return redirect()->route('admin.settings.logs')
                ->with('success', "Log file '{$filename}' deleted successfully.");
        }

        return redirect()->route('admin.settings.logs')
            ->with('error', "Could not find log file '{$filename}'.");
    }

    /**
     * Download the log file.
     */
    public function download(Request $request)
    {
        $filename = basename($request->get('file', 'laravel.log'));
        $filePath = $this->getLogDir() . '/' . $filename;

        if (!file_exists($filePath) || !str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found.');
        }

        return response()->download($filePath, $filename);
    }

    /**
     * Read the last N lines of a large file safely.
     */
    protected function readTail(string $filepath, int $lines = 2000): string
    {
        $f = @fopen($filepath, "rb");
        if (!$f) return "";

        $buffer = "";
        $pos = -2;
        $lineCount = 0;

        fseek($f, 0, SEEK_END);
        $fileSize = ftell($f);

        if ($fileSize === 0) {
            fclose($f);
            return "";
        }

        while ($lineCount < $lines && abs($pos) <= $fileSize) {
            fseek($f, $pos, SEEK_END);
            $char = fgetc($f);
            if ($char === "\n") {
                $lineCount++;
            }
            $pos--;
        }

        $startPos = max(0, $fileSize + $pos + 2);
        fseek($f, $startPos);
        $content = fread($f, $fileSize - $startPos);
        fclose($f);

        return $content ?: "";
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
