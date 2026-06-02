<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Cek User Kepala ===\n";
$user = \App\Models\User::where('email', 'kepala@bkpsdm.go.id')->first();

if ($user) {
    echo 'User: ' . $user->name . "\n";
    echo 'Email: ' . $user->email . "\n";
    if ($user->role) {
        echo 'Role: ' . $user->role->name . "\n";
    } else {
        echo "Role: NULL\n";
    }
    echo 'isKepalaBkpsdm: ' . ($user->isKepalaBkpsdm() ? 'YES' : 'NO') . "\n";
    echo 'is_admin_bkpsdm: ' . ($user->isAdminBkpsdm() ? 'YES' : 'NO') . "\n";
} else {
    echo "User tidak ditemukan\n";
}

echo "\n=== Cek Data Surat ===\n";
$surat = \App\Models\SuratIzinBelajar::with('pengajuan.user')->first();
if ($surat) {
    echo 'Surat ID: ' . $surat->id . "\n";
    echo 'Status: ' . $surat->status . "\n";
    echo 'Pemohon: ' . $surat->pengajuan->user->name . "\n";
} else {
    echo "Tidak ada surat\n";
}
