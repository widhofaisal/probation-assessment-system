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
                                 'lain_lain', 'status_akhir', 'hrd_id', 'created_at', 'updated_at'];
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
