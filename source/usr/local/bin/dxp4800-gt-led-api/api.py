#!/usr/bin/python3
import http.server
import socketserver
import json
import os
import sys

CONFIG_FILE = '/boot/config/plugins/dxp4800-gt-leds/settings.cfg'
PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 4800

ALLOWED_FIELDS = {
    'MASTER_LED_SWITCH', 'SCHEDULE_ENABLE', 'SCHEDULE_START_TIME', 'SCHEDULE_END_TIME',
    'NETWORK_INTERFACE', 'DISK_LED_INVERT', 'COLOR_DISK_HEALTH', 'BRIGHTNESS_DISK_LEDS',
    'POWER_LED_MODE', 'POWER_LED_COLOR', 'NETDEV_LED_MODE', 'NETDEV_LED_COLOR',
    'BAY_DISK1_MODE', 'BAY_DISK1_COLOR', 'BAY_DISK2_MODE', 'BAY_DISK2_COLOR',
    'BAY_DISK3_MODE', 'BAY_DISK3_COLOR', 'BAY_DISK4_MODE', 'BAY_DISK4_COLOR'
}

class APIHandler(http.server.BaseHTTPRequestHandler):
    def do_POST(self):
        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length)
        
        try:
            data = json.loads(post_data)
        except json.JSONDecodeError:
            self.send_response(400)
            self.end_headers()
            self.wfile.write(b'{"status":"error","message":"Invalid JSON"}')
            return

        cfg = {}
        if os.path.exists(CONFIG_FILE):
            with open(CONFIG_FILE, 'r') as f:
                for line in f:
                    if '=' in line:
                        k, v = line.strip().split('=', 1)
                        cfg[k.strip()] = v.strip().strip('"')

        changed = False
        for k, v in data.items():
            if k in ALLOWED_FIELDS:
                cfg[k] = str(v).strip()
                changed = True

        if changed:
            with open(CONFIG_FILE, 'w') as f:
                for k, v in cfg.items():
                    f.write(f'{k}="{v}"\n')
            
            with open('/var/run/dxp4800-gt-leds-reload', 'w') as f:
                f.write('')
                
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(b'{"status":"success"}')
        else:
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(b'{"status":"no changes"}')

    def log_message(self, format, *args):
        pass  # Suppress default stdout logging

socketserver.TCPServer.allow_reuse_address = True
with socketserver.TCPServer(("0.0.0.0", PORT), APIHandler) as httpd:
    httpd.serve_forever()
