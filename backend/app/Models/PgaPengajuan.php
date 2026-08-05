<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nomor_pengajuan',
    'user_id',
    'jenjang_pendidikan_id',
    'gelar_akademik',
    'nama_prodi',
    'perguruan_tinggi',
    'lokasi_pt',
    'nomor_ijazah',
    'tanggal_ijazah',
    'tahun_lulus',
    'status',
    'catatan_tolak',
    'tanggal_approve_admin',
    'tanggal_selesai',
    // Dokumen 1: Surat Pengantar/Usulan dari Instansi
    'surat_pengantar_file',
    // Dokumen 2a: SK Pangkat Terakhir
    'sk_pangkat_file',
    // Dokumen 3: SK Jabatan Terbaru
    'sk_jabatan_file',
    // Dokumen 4: Surat Izin Belajar/Tugas Belajar/Surat Keterangan
    'surat_izin_file',
    // Dokumen 5a: Asli Ijazah
    'ijazah_file',
    // Dokumen 5b: Lampiran Forlap Dikti
    'ijazah_forlap_file',
    // Dokumen 6: Asli Transkrip Nilai
    'transkrip_file',
    // Dokumen 7: Akreditasi Program Studi
    'akreditasi_file',
    // Dokumen 8: Ijazah luar negeri yang disetarakan
    'ijazah_dikti_file',
    // Legacy (reusing ijazah_dikti_file for SK Kum)
    'sk_kum_file',
])]
class PgaPengajuan extends Model
{
    use SoftDeletes;

    protected $table = 'pga_pengajuan';

    protected function casts(): array
    {
        return [
            'tanggal_ijazah' => 'date',
            'tahun_lulus' => 'integer',
            'tanggal_approve_admin' => 'datetime',
            'tanggal_selesai' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jenjangPendidikan(): BelongsTo
    {
        return $this->belongsTo(JenjangPendidikan::class);
    }

    // Status helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApprovedAdmin(): bool
    {
        return $this->status === 'approved_admin';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'ditolak']);
    }

    public function getAllDocumentsUploaded(): bool
    {
        // Check all 8 required documents
        return ! empty($this->surat_pengantar_file)
            && ! empty($this->sk_pangkat_file)
            && ! empty($this->sk_jabatan_file)
            && ! empty($this->surat_izin_file)
            && ! empty($this->ijazah_file)
            && ! empty($this->ijazah_forlap_file)
            && ! empty($this->transkrip_file)
            && ! empty($this->akreditasi_file);
    }

    /**
     * Get uploaded documents count
     */
    public function getUploadedDocumentsCount(): int
    {
        $count = 0;
        $documentFields = [
            'surat_pengantar_file',
            'sk_pangkat_file',
            'sk_jabatan_file',
            'surat_izin_file',
            'ijazah_file',
            'ijazah_forlap_file',
            'transkrip_file',
            'akreditasi_file',
            'ijazah_dikti_file',
        ];

        foreach ($documentFields as $field) {
            if (! empty($this->{$field})) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get all document file paths as array
     */
    public function getDocumentPaths(): array
    {
        return [
            'surat_pengantar_file' => $this->surat_pengantar_file,
            'sk_pangkat_file' => $this->sk_pangkat_file,
            'sk_jabatan_file' => $this->sk_jabatan_file,
            'surat_izin_file' => $this->surat_izin_file,
            'ijazah_file' => $this->ijazah_file,
            'ijazah_forlap_file' => $this->ijazah_forlap_file,
            'transkrip_file' => $this->transkrip_file,
            'akreditasi_file' => $this->akreditasi_file,
            'ijazah_dikti_file' => $this->ijazah_dikti_file,
        ];
    }

    public function generateNomorPengajuan(): string
    {
        $year = date('Y');
        $month = date('m');
        $count = self::withTrashed()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->count() + 1;

        return 'PGA-'.$year.$month.sprintf('%04d', $count);
    }
}
