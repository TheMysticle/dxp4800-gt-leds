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
        
        $allowed_fields = array('MASTER_LED_SWITCH', 'SCHEDULE_ENABLE', 'SCHEDULE_START_TIME', 'SCHEDULE_END_TIME',
                                'NETWORK_INTERFACE', 'DISK_LED_INVERT', 'COLOR_DISK_HEALTH', 'BRIGHTNESS_DISK_LEDS',
                                'POWER_LED_MODE', 'POWER_LED_COLOR', 'NETDEV_LED_MODE', 'NETDEV_LED_COLOR',
                                'BAY_DISK1_MODE', 'BAY_DISK1_COLOR', 'BAY_DISK2_MODE', 'BAY_DISK2_COLOR',
                                'BAY_DISK3_MODE', 'BAY_DISK3_COLOR', 'BAY_DISK4_MODE', 'BAY_DISK4_COLOR');

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
                if (in_array($key, $allowed_fields)) {
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
