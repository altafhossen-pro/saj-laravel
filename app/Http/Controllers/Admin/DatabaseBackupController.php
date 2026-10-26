<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class DatabaseBackupController extends Controller
{
    protected DatabaseBackupService $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display the database backup & restore management dashboard.
     */
    public function index()
    {
        $backups = $this->backupService->listBackups();
        $mathCaptcha = $this->backupService->generateMathCaptcha();

        $connection = DB::connection();
        $dbName = $connection->getDatabaseName();
        $tableCount = count(DB::select('SHOW TABLES'));

        // Calculate approximate database size
        $sizeQuery = DB::select("
            SELECT SUM(data_length + index_length) as db_size
            FROM information_schema.TABLES
            WHERE table_schema = ?
        ", [$dbName]);
        $approxSizeBytes = (int) ($sizeQuery[0]->db_size ?? 0);
        $approxSizeFormatted = $this->backupService->formatBytes($approxSizeBytes);

        return view('admin.settings.backup', compact(
            'backups',
            'mathCaptcha',
            'dbName',
            'tableCount',
            'approxSizeFormatted'
        ));
    }

    /**
     * Generate a new math captcha via AJAX.
     */
    public function getCaptcha()
    {
        $mathCaptcha = $this->backupService->generateMathCaptcha();
        return response()->json([
            'success' => true,
            'question' => $mathCaptcha['question']
        ]);
    }

    /**
     * Create a database backup.
     */
    public function create(Request $request)
    {
        try {
            $storeInServer = $request->boolean('store_in_server', false);
            $result = $this->backupService->createBackup($storeInServer);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Database backup created successfully.',
                    'filename' => $result['filename'],
                    'size' => $result['size'],
                    'store_in_server' => $storeInServer,
                    'download_url' => route('admin.settings.backup.download', $result['filename'])
                ]);
            }

            if ($storeInServer) {
                return redirect()->route('admin.settings.backup')
                    ->with('success', "Database backup '{$result['filename']}' ({$result['size']}) created and securely stored on the server vault.");
            }

            // Direct download if not storing on server
            return response()->download($result['path'])->deleteFileAfterSend(true);

        } catch (Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->route('admin.settings.backup')->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a stored backup archive.
     */
    public function download(string $filename)
    {
        $clean = basename($filename);
        $path = DatabaseBackupService::getBackupDir() . '/' . $clean;

        if (!file_exists($path) || !str_ends_with($clean, '.zip')) {
            abort(404, 'Backup archive not found.');
        }

        return response()->download($path, $clean);
    }

    /**
     * Delete a stored backup archive from the server.
     */
    public function destroy(string $filename)
    {
        $clean = basename($filename);
        $deleted = $this->backupService->deleteBackup($clean);

        if ($deleted) {
            return redirect()->route('admin.settings.backup')
                ->with('success', "Backup file '{$clean}' was successfully deleted.");
        }

        return redirect()->route('admin.settings.backup')
            ->with('error', "Could not delete backup '{$clean}'. File may not exist.");
    }

    /**
     * Restore database from a server-stored backup.
     */
    public function restoreStored(Request $request, string $filename)
    {
        $request->validate([
            'math_answer' => 'required',
        ]);

        if (!$this->backupService->verifyMathCaptcha($request->math_answer)) {
            return redirect()->route('admin.settings.backup')
                ->with('error', 'Math Captcha calculation was incorrect! Restore aborted for security.');
        }

        $clean = basename($filename);
        $path = DatabaseBackupService::getBackupDir() . '/' . $clean;

        if (!file_exists($path)) {
            return redirect()->route('admin.settings.backup')
                ->with('error', "Backup archive '{$clean}' does not exist on server.");
        }

        try {
            $result = $this->backupService->restoreBackup($path, $clean);

            return redirect()->route('admin.settings.backup')
                ->with('success', $result['message'] . " (Safety fallback snapshot: {$result['safety_backup']})");
        } catch (Exception $e) {
            return redirect()->route('admin.settings.backup')
                ->with('error', 'Database restoration failed: ' . $e->getMessage());
        }
    }

    /**
     * Restore database from an uploaded ZIP or SQL file.
     */
    public function restoreUpload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:102400', // max 100MB
            'math_answer' => 'required',
        ]);

        if (!$this->backupService->verifyMathCaptcha($request->math_answer)) {
            return redirect()->route('admin.settings.backup')
                ->with('error', 'Math Captcha calculation was incorrect! Restore aborted for security.');
        }

        $file = $request->file('backup_file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, ['zip', 'sql'])) {
            return redirect()->route('admin.settings.backup')
                ->with('error', 'Invalid file type. Please upload a valid .zip or .sql database archive.');
        }

        try {
            $tempDir = DatabaseBackupService::getBackupDir() . '/temp_upload';
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0755, true);
            }
            $storedPath = $file->move($tempDir, 'upload_' . time() . '_' . $originalName);

            $result = $this->backupService->restoreBackup($storedPath->getPathname(), $originalName);

            @unlink($storedPath->getPathname());

            return redirect()->route('admin.settings.backup')
                ->with('success', $result['message'] . " (Safety fallback snapshot: {$result['safety_backup']})");
        } catch (Exception $e) {
            return redirect()->route('admin.settings.backup')
                ->with('error', 'Database restoration failed: ' . $e->getMessage());
        }
    }
}
