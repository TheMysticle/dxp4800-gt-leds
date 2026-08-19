<?php
header('Content-Type: application/json');

$cfgFile = '/boot/config/plugins/dxp4800-gt-leds/settings.cfg';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON."]);
    exit;
}

$cfg = array();
if (file_exists($cfgFile)) {
    $parsed = @parse_ini_file($cfgFile);
    if (is_array($parsed)) $cfg = $parsed;
}

$allowed_fields = array('MASTER_LED_SWITCH', 'SCHEDULE_ENABLE', 'SCHEDULE_START_TIME', 'SCHEDULE_END_TIME',
                        'NETWORK_INTERFACE', 'DISK_LED_INVERT', 'COLOR_DISK_HEALTH', 'BRIGHTNESS_DISK_LEDS',
                        'POWER_LED_MODE', 'POWER_LED_COLOR', 'NETDEV_LED_MODE', 'NETDEV_LED_COLOR',
                        'BAY_DISK1_MODE', 'BAY_DISK1_COLOR', 'BAY_DISK2_MODE', 'BAY_DISK2_COLOR',
                        'BAY_DISK3_MODE', 'BAY_DISK3_COLOR', 'BAY_DISK4_MODE', 'BAY_DISK4_COLOR');

$changed = false;
foreach ($data as $key => $value) {
    if (in_array($key, $allowed_fields)) {
        $cfg[$key] = trim($value);
        $changed = true;
    }
}

if ($changed) {
    $out = "";
    foreach ($cfg as $k => $v) {
        $out .= "$k=\"$v\"\n";
    }
    file_put_contents($cfgFile, $out);
    touch('/var/run/dxp4800-gt-leds-reload');
    echo json_encode(["status" => "success", "message" => "Settings updated."]);
} else {
    echo json_encode(["status" => "success", "message" => "No valid fields provided."]);
}
