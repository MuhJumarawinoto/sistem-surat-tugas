#!/bin/bash
# Deploy Commands for be-surat-tugas.velotek.co.id
# Sesuaikan USERNAME dengan cPanel username

SERVER="be-surat-tugas.velotek.co.id"
USER="username"  # Ganti dengan cPanel username
REMOTE_PATH="public_html"

echo "=== Deploying to $SERVER ==="

# Backend files
echo "Uploading backend files..."
scp backend/app/Http/Controllers/SuratIzinBelajarController.php $USER@$SERVER:$REMOTE_PATH/api/app/Http/Controllers/
scp backend/app/Http/Controllers/SuratTugasMandiriController.php $USER@$SERVER:$REMOTE_PATH/api/app/Http/Controllers/
scp backend/app/Models/SuratTugasMandiri.php $USER@$SERVER:$REMOTE_PATH/api/app/Models/

# Frontend files
echo "Uploading frontend files..."
scp -r frontend/dist/* $USER@$SERVER:$REMOTE_PATH/

echo "=== Deploy Complete ==="
echo ""
echo "Run these commands on server via SSH or cPanel Terminal:"
echo "  cd ~/public_html/api"
echo "  php artisan migrate"
echo "  composer dump-autoload"
