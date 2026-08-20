#!/bin/bash
set -e

# 1. Clean up
echo "Cleaning up old containers..."
docker stop ugreen-builder >/dev/null 2>&1 || true
docker rm ugreen-builder >/dev/null 2>&1 || true

# 2. Start the compiler container
echo "Starting compiler container..."
docker run -dit --name ugreen-builder -v /boot/config/ugreen_leds:/output ghcr.io/ich777/unraid_kernel:gcc_14.2.0 >/dev/null

echo "Waiting for kernel source to fully extract and container to be ready (this may take 5-10 minutes)..."
until docker logs ugreen-builder 2>&1 | grep -q "Container ready!"; do
  echo -n "."
  sleep 5
done
echo " Ready!"

# 3. Download PR 100 via tarball, prepare kernel, compile, and copy
docker exec ugreen-builder bash -c "
  cd /usr/src/linux-*-Unraid
  export KDIR=\$(pwd)
  
  echo 'Preparing kernel source tree...'
  make olddefconfig >/dev/null
  make modules_prepare >/dev/null
  
  echo 'Compiling UGREEN LED driver from PR 100 (fixes DXP4800 GT write issues)...'
  cd /tmp
  wget -qO repo.tar.gz \"https://api.github.com/repos/miskcoo/ugreen_leds_controller/tarball/pull/100/head\"
  mkdir repo
  tar -xzf repo.tar.gz -C repo --strip-components=1
  cd repo/kmod
  
  make -j\$(nproc) KDIR=\$KDIR
  
  echo 'Copying led-ugreen.ko to /boot/config/ugreen_leds...'
  cp led-ugreen.ko /output/
"

# 4. Clean up
docker stop ugreen-builder >/dev/null
docker rm ugreen-builder >/dev/null
echo "led-ugreen.ko successfully compiled to /boot/config/ugreen_leds!"
