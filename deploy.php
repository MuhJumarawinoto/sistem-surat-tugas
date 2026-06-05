<?php
/**
 * Script Deployment Otomatis untuk Fitur Sync SIMPEG
 *
 * Cara penggunaan:
 * 1. Upload file ini ke server (public_html/ atau root)
 * 2. Akses via browser: https://domainanda.com/deploy.php
 * 3. Klik tombol "Deploy"
 * 4. Hapus file ini setelah selesai
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base directories
$baseDir = __DIR__;
$backendDir = $baseDir . '/api'; // Sesuaikan jika backend di subfolder berbeda
$frontendDistDir = $baseDir . '/assets'; // Sesuaikan jika frontend build di folder berbeda

// Files to deploy
$filesToDeploy = [
    'Backend' => [
        'SimpegService.php' => [
            'source' => __DIR__ . '/backend/app/Services/SimpegService.php',
            'dest' => $backendDir . '/app/Services/SimpegService.php'
        ],
        'PegawaiSyncController.php' => [
            'source' => __DIR__ . '/backend/app/Http/Controllers/PegawaiSyncController.php',
            'dest' => $backendDir . '/app/Http/Controllers/PegawaiSyncController.php'
        ],
        'services.php' => [
            'source' => __DIR__ . '/backend/config/services.php',
            'dest' => $backendDir . '/config/services.php'
        ],
    ],
];

// Messages
$messages = [];
$errors = [];

function addMessage($msg, $type = 'info') {
    global $messages;
    $messages[] = ['msg' => $msg, 'type' => $type];
}

function addError($msg) {
    global $errors;
    $errors[] = $msg;
    addMessage($msg, 'error');
}

// Check if source files exist
foreach ($filesToDeploy['Backend'] as $name => $file) {
    if (!file_exists($file['source'])) {
        addError("Source file tidak ditemukan: {$file['source']}");
    }
}

// Deploy function
function deployFile($source, $dest) {
    // Create directory if not exists
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            addError("Gagal membuat directory: {$dir}");
            return false;
        }
    }

    // Backup existing file
    if (file_exists($dest)) {
        $backup = $dest . '.backup.' . date('YmdHis');
        if (!copy($dest, $backup)) {
            addError("Gagal backup file: {$dest}");
            return false;
        }
        addMessage("Backup created: " . basename($backup), 'success');
    }

    // Copy file
    if (copy($source, $dest)) {
        addMessage("File deployed: " . basename($dest), 'success');
        return true;
    } else {
        addError("Gagal copy file ke: {$dest}");
        return false;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'deploy') {
        addMessage("Memulai deployment...", 'info');

        // Deploy files
        foreach ($filesToDeploy['Backend'] as $name => $file) {
            if (file_exists($file['source'])) {
                deployFile($file['source'], $file['dest']);
            }
        }

        // Clear config cache
        if (file_exists($backendDir . '/artisan')) {
            $cacheCmd = 'cd ' . escapeshellarg($backendDir) . ' && php artisan config:clear 2>&1';
            $cacheOutput = shell_exec($cacheCmd);
            addMessage("Config cache cleared", 'success');
        }

        addMessage("Deployment selesai!", 'success');
    }

    if ($action === 'rollback') {
        addMessage("Melakukan rollback...", 'info');

        foreach ($filesToDeploy['Backend'] as $name => $file) {
            $backupFiles = glob($file['dest'] . '.backup.*');
            if (!empty($backupFiles)) {
                // Get latest backup
                $latestBackup = end($backupFiles);
                if (copy($latestBackup, $file['dest'])) {
                    addMessage("Rolled back: " . basename($file['dest']), 'success');
                }
            }
        }

        addMessage("Rollback selesai!", 'success');
    }

    if ($action === 'check') {
        addMessage("Checking deployment status...", 'info');

        foreach ($filesToDeploy['Backend'] as $name => $file) {
            if (file_exists($file['dest'])) {
                $size = filesize($file['dest']);
                $modified = date('Y-m-d H:i:s', filemtime($file['dest']));
                addMessage("✓ {$name}: Exists ({$size} bytes, modified: {$modified})", 'success');
            } else {
                addError("✗ {$name}: Missing");
            }
        }

        // Check Guzzle installation
        $vendorAutoload = $backendDir . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
            if (class_exists('GuzzleHttp\Client')) {
                addMessage("✓ Guzzle HTTP Client installed", 'success');
            } else {
                addError("✗ Guzzle HTTP Client NOT installed");
            }
        } else {
            addMessage("⚠ vendor/autoload.php not found", 'warning');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment - Sync SIMPEG</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { padding: 20px; border-bottom: 1px solid #eee; }
        .header h1 { font-size: 24px; color: #333; }
        .header p { color: #666; margin-top: 5px; }
        .content { padding: 20px; }
        .message { padding: 10px 15px; margin-bottom: 10px; border-radius: 4px; font-size: 14px; }
        .message.info { background: #e3f2fd; color: #1976d2; }
        .message.success { background: #e8f5e9; color: #388e3c; }
        .message.error { background: #ffebee; color: #d32f2f; }
        .message.warning { background: #fff3e0; color: #f57c00; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; transition: all 0.2s; }
        .btn-primary { background: #2196f3; color: white; }
        .btn-primary:hover { background: #1976d2; }
        .btn-secondary { background: #757575; color: white; }
        .btn-secondary:hover { background: #616161; }
        .btn-success { background: #4caf50; color: white; }
        .btn-success:hover { background: #388e3c; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-warning:hover { background: #f57c00; }
        .file-list { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 15px; }
        .file-list h3 { margin-bottom: 10px; font-size: 16px; }
        .file-list ul { list-style: none; padding-left: 0; }
        .file-list li { padding: 8px 0; border-bottom: 1px solid #eee; font-size: 13px; }
        .file-list li:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge.new { background: #4caf50; color: white; }
        .badge.update { background: #2196f3; color: white; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Deployment Sync SIMPEG</h1>
            <p>Deploy fitur sync pegawai dari SIMPEG ke server</p>
        </div>
        <div class="content">
            <?php if (!empty($messages)): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['type'] ?>"><?= htmlspecialchars($msg['msg']) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST">
                <div class="actions">
                    <button type="submit" name="action" value="check" class="btn btn-warning">🔍 Check Status</button>
                    <button type="submit" name="action" value="deploy" class="btn btn-primary" onclick="return confirm('Mulai deployment? File yang ada akan di-backup otomatis.')">🚀 Deploy</button>
                    <button type="submit" name="action" value="rollback" class="btn btn-secondary" onclick="return confirm('Rollback ke versi sebelumnya?')">↩️ Rollback</button>
                </div>
            </form>

            <div class="file-list">
                <h3>Files yang akan di-deploy:</h3>
                <ul>
                    <li><span class="badge new">NEW</span> <strong>SimpegService.php</strong> → app/Services/</li>
                    <li><span class="badge update">UPDATE</span> <strong>PegawaiSyncController.php</strong> → app/Http/Controllers/</li>
                    <li><span class="badge update">UPDATE</span> <strong>services.php</strong> → config/</li>
                </ul>
            </div>

            <div class="file-list" style="margin-top: 20px;">
                <h3>Path di server:</h3>
                <ul>
                    <li>Backend: <code><?= $backendDir ?></code></li>
                    <li>Base: <code><?= $baseDir ?></code></li>
                </ul>
            </div>

            <div class="file-list" style="margin-top: 20px;">
                <h3>Catatan:</h3>
                <ul>
                    <li>✓ File yang ada akan di-backup otomatis</li>
                    <li>✓ Jika folder Services belum ada, akan dibuat otomatis</li>
                    <li>✓ Config cache akan di-clear otomatis</li>
                    <li>⚠️ Hapus file ini setelah deployment selesai</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
