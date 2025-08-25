<?php
// Malware Finder + Cleaner + Restore by SukaJanda01
set_time_limit(0);
error_reporting(0);

// Konfigurasi
$scan_dir = __DIR__;
$quarantine_dir = __DIR__ . '/karantina';
$suspicious_patterns = [
    'eval\s*\(',
    'base64_decode\s*\(',
    'gzinflate\s*\(',
    'str_rot13\s*\(',
    'shell_exec\s*\(',
    'system\s*\(',
    'exec\s*\(',
    'popen\s*\(',
    'proc_open\s*\(',
    'preg_replace\s*\(.*\/e.*\)',
    'php:\/\/input',
    'php:\/\/filter',
    'ob_start\s*\(',
    'create_function\s*\(',
    '\$_(GET|POST|REQUEST|COOKIE)\s*\[.*\]',
    'assert\s*\(',
];

// Buat folder karantina
if (!is_dir($quarantine_dir)) {
    mkdir($quarantine_dir, 0755, true);
}

// Fungsi scan recursive
function scan_files($dir, $patterns) {
    $found = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === basename(__FILE__)) continue;
        if (strpos($dir . DIRECTORY_SEPARATOR . $item, '/karantina') !== false) continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            $subfound = scan_files($path, $patterns);
            $found = array_merge($found, $subfound);
        } elseif (is_file($path)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'inc', 'phtml'])) {
                $content = @file_get_contents($path);
                if ($content === false) continue;

                foreach ($patterns as $pattern) {
                    if (preg_match('/' . $pattern . '/i', $content)) {
                        $found[$path][] = $pattern;
                    }
                }
            }
        }
    }

    return $found;
}

// Quarantine action
if (isset($_POST['action']) && $_POST['action'] === 'clean') {
    $results = scan_files($scan_dir, $suspicious_patterns);
    foreach ($results as $file => $patterns) {
        $dest = $quarantine_dir . '/' . basename($file) . '.' . time() . '.quarantine';
        if (rename($file, $dest)) {
            echo "<p style='color:red;'>[QUARANTINED] $file => $dest</p>";
        } else {
            echo "<p style='color:orange;'>[FAILED] $file could not be quarantined</p>";
        }
    }
    echo "<p style='color:green;'>Cleaning complete. ".count($results)." files quarantined.</p>";
    echo "<p><a href='?'>Back</a></p>";
    exit;
}

// Restore action
if (isset($_POST['restore'])) {
    $restore_file = basename($_POST['restore']);
    $source = $quarantine_dir . '/' . $restore_file;

    if (preg_match('/^(.*)\.\d+\.quarantine$/', $restore_file, $matches)) {
        $original_name = $matches[1];
        $restore_path = $scan_dir . '/' . $original_name;

        if (file_exists($restore_path)) {
            $restore_path .= '.restored';
        }

        if (rename($source, $restore_path)) {
            echo "<p style='color:green;'>[RESTORED] $restore_file => $restore_path</p>";
        } else {
            echo "<p style='color:red;'>[FAILED] Could not restore $restore_file</p>";
        }
    }
    echo "<p><a href='?restore_panel=1'>Back to Restore Panel</a></p>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Malware Finder + Cleaner + Restore by SukaJanda01</title>
    <style>
        body { font-family: sans-serif; background: #f7f7f7; padding: 20px; }
        h2 { background: #333; color: #fff; padding: 10px; }
        table { border-collapse: collapse; width: 100%; background: #fff; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        th { background: #eee; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; margin-right: 10px; }
        .btn-red { background: red; }
        .btn-green { background: green; }
    </style>
</head>
<body>

<h2>Malware Finder + Cleaner + Restore by SukaJanda01</h2>

<form method="post">
    <button class="btn" type="submit" name="scan" value="1">Scan Only</button>
    <button class="btn btn-red" type="submit" name="action" value="clean" onclick="return confirm('Yakin ingin karantina file terdeteksi?')">Scan & Quarantine</button>
    <a class="btn btn-green" href="?restore_panel=1">Restore from Quarantine</a>
</form>

<?php
// Scan Only
if (isset($_POST['scan'])):
    $results = scan_files($scan_dir, $suspicious_patterns);
    if (count($results) > 0):
?>
    <table>
        <tr>
            <th>No</th>
            <th>File</th>
            <th>Detected Patterns</th>
        </tr>
        <?php $no = 1; foreach ($results as $file => $patterns): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($file) ?></td>
            <td><?= implode(", ", $patterns) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="color:green;">No malware found.</p>
<?php endif; endif; ?>

<?php
// Restore Panel
if (isset($_GET['restore_panel'])):
    $files = scandir($quarantine_dir);
    $quarantined_files = array_filter($files, function($f) {
        return strpos($f, '.quarantine') !== false;
    });
?>
    <h3>Restore Panel - Files in Quarantine</h3>
    <?php if (count($quarantined_files) > 0): ?>
    <table>
        <tr>
            <th>No</th>
            <th>Quarantined File</th>
            <th>Action</th>
        </tr>
        <?php $no = 1; foreach ($quarantined_files as $file): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= htmlspecialchars($file) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="restore" value="<?= htmlspecialchars($file) ?>">
                    <button type="submit" class="btn btn-green">Restore</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php else: ?>
        <p style="color:orange;">No files in quarantine.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
