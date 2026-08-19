<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Kotak "(Diisi oleh Dept. HRD)" pada lembar penilaian ke-2.
 *
 * Satu baris per lembar penilaian ke-2 (penilaian_id unik). Baris inilah yang
 * menentukan status akhir karyawan: employees.status hanya boleh berubah lewat
 * sini, tidak lagi lewat form data karyawan — lihat EmployeesController::update().
 *
 * Ketiga baris keputusan (diangkat / diakhiri / lain-lain) disimpan apa adanya
 * seperti form kertasnya, sedangkan status_akhir adalah kesimpulan yang dipilih
 * HRD sendiri karena tidak bisa disimpulkan otomatis dari ketiga baris itu.
 */
class EvaluationDecisionModel extends Model
{
    protected $table          = 'penilaian_keputusan';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['penilaian_id', 'employee_id', 'tanggal_diangkat', 'tanggal_diakhiri',
                                 'lain_lain', 'nomor_sk', 'tanggal_sk', 'status_akhir', 'hrd_id',
                                 'created_at', 'updated_at'];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $dateFormat     = 'datetime';

    /** Panjang maksimal "Lain-lain" — dibatasi agar tetap muat satu baris di form cetak. */
    public const MAX_LAIN_LAIN = 60;

    /** Status akhir yang boleh dipilih HRD (sesuai enum employees.status). */
    public const STATUS_AKHIR = ['lulus', 'tidak-lulus', 'warning'];

    /**
     * Keputusan untuk satu lembar penilaian, atau null bila HRD belum mengisi.
     */
    public function getByEvaluation(int $evaluationId): ?array
    {
        return $this->where('penilaian_id', $evaluationId)->first();
    }

    /**
     * Peta penilaian_id => keputusan untuk sekumpulan lembar penilaian.
     * Dipakai daftar penilaian agar tidak query satu per satu per baris tabel.
     */
    public function mapByEvaluations(array $evaluationIds): array
    {
        if (empty($evaluationIds)) {
            return [];
        }

        $map = [];
        foreach ($this->whereIn('penilaian_id', $evaluationIds)->findAll() as $row) {
            $map[(int) $row['penilaian_id']] = $row;
        }

        return $map;
    }

    /**
     * Keputusan 'lulus' milik satu karyawan, atau null bila belum ada.
     *
     * Dipakai Surat Keputusan: satu karyawan hanya punya satu lembar penilaian
     * ke-2, jadi paling banyak satu keputusan yang berstatus lulus.
     */
    public function getLulusByEmployee(int $employeeId): ?array
    {
        return $this->where('employee_id', $employeeId)
                    ->where('status_akhir', 'lulus')
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    /**
     * Apakah Surat Keputusan sudah bisa dicetak dari keputusan ini?
     *
     * Ketiganya wajib: statusnya lulus, nomor agenda sudah diisi HRD, dan
     * tanggal pengangkatannya ada — surat ini berbunyi "Terhitung sejak
     * tanggal ..., mengangkat karyawan tersebut", jadi tanpa tanggal itu
     * suratnya tidak bisa dibuat.
     */
    public static function skSiap(?array $keputusan): bool
    {
        return $keputusan
            && ($keputusan['status_akhir'] ?? '') === 'lulus'
            && trim((string)($keputusan['nomor_sk'] ?? '')) !== ''
            && !empty($keputusan['tanggal_diangkat']);
    }

    /**
     * '33372' + tanggal SK → '33372/SMJ/RSC-HRD/VIII/2026'.
     *
     * Hanya nomor urutnya yang disimpan; ekornya disusun dari tanggal terbit SK
     * (tanggal_sk, yang dibekukan saat SK pertama kali terbit) supaya nomor pada
     * surat tidak ikut berubah kalau keputusannya disunting di bulan lain.
     */
    public static function nomorSkLengkap(array $keputusan): string
    {
        $urut = trim((string)($keputusan['nomor_sk'] ?? ''));
        if ($urut === '') {
            return '';
        }

        $ts    = strtotime((string)($keputusan['tanggal_sk'] ?? '')) ?: time();
        $bulan = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $urut . '/SMJ/RSC-HRD/' . $bulan[(int)date('n', $ts)] . '/' . date('Y', $ts);
    }

    /** 'tidak-lulus' → 'Tidak Lulus'. */
    public static function statusLabel(?string $statusAkhir): string
    {
        return match ($statusAkhir) {
            'lulus'       => 'Lulus',
            'tidak-lulus' => 'Tidak Lulus',
            'warning'     => 'Warning',
            default       => '-',
        };
    }
}
