<?php
$cfgFile = '/boot/config/plugins/dxp4800-gt-leds/settings.cfg';
$port = isset($argv[1]) ? (int)$argv[1] : 4800;

$socket = stream_socket_server("tcp://0.0.0.0:$port", $errno, $errstr);
if (!$socket) {
    die("Error: $errstr ($errno)\n");
}

while (true) {
    $conn = @stream_socket_accept($socket, -1);
    if ($conn) {
        $input = fread($conn, 4096);
        fclose($conn);
        
        if (empty(trim($input))) continue;
        
        // Ignore random HTTP scanners
        if (strpos($input, 'HTTP/') !== false) continue;
        
        $changed = false;
        $cfg = array();
        if (file_exists($cfgFile)) {
            $parsed = @parse_ini_file($cfgFile);
            if (is_array($parsed)) $cfg = $parsed;
        }

        $pairs = explode(',', $input);
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                if (preg_match('/^[A-Z0-9_]+$/', $key) && isset($cfg[$key])) {
                    $cfg[$key] = $value;
                    $changed = true;
                }
            }
        }
        
        if ($changed) {
            $out = "";
            foreach ($cfg as $k => $v) {
                $out .= "$k=\"$v\"\n";
            }
            file_put_contents($cfgFile, $out);
            touch('/var/run/dxp4800-gt-leds-reload');
        }
    }
}
