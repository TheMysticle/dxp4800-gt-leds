#!/bin/bash
CONFIG_FILE="/boot/config/plugins/dxp4800-gt-leds/settings.cfg"
PORT=${1:-4800}

while true; do
    input=$(nc -l -p $PORT)
    if [ -n "$input" ]; then
        changed=0
        IFS=',' read -ra pairs <<< "$input"
        for pair in "${pairs[@]}"; do
            key="${pair%%=*}"
            value="${pair#*=}"
            
            # Trim whitespace
            key=$(echo $key | xargs)
            value=$(echo $value | xargs)
            
            # Ensure key is somewhat valid and exists in config
            if [[ "$key" =~ ^[A-Z0-9_]+$ ]] && grep -q "^$key=" "$CONFIG_FILE"; then
                sed -i "s|^$key=.*|$key=\"$value\"|g" "$CONFIG_FILE"
                changed=1
            fi
        done
        
        if [ "$changed" = "1" ]; then
            touch /var/run/dxp4800-gt-leds-reload
        fi
    fi
done
