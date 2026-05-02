@echo off
REM Run Laravel API so it's reachable from Android emulator AND physical device
REM - 127.0.0.1 (default): Only same-machine. Android emulator uses 10.0.2.2 -> host loopback.
REM - 0.0.0.0: Listens on all interfaces. REQUIRED for physical device (use your PC's LAN IP in app).
echo Starting Laravel API server (0.0.0.0 = reachable from emulator + physical device)...
php artisan serve --host=0.0.0.0
