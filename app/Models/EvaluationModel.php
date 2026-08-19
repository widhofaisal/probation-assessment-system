<?php

namespace App\Models;

use CodeIgniter\Model;

class EvaluationModel extends Model
{
    protected $table = 'penilaian';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['employee_id', 'nomor_penilaian', 'team_leader_id', 'tanggal_penilaian',
                                'tanggal_mulai_penilaian', 'tanggal_selesai_penilaian',
                                'nilai_total', 'status', 'catatan_team_leader',
                                'dilihat_at', 'diunduh_at',
                                'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'employee_id' => 'required|integer',
        'team_leader_id' => 'required|integer',
        'nilai_total' => 'permit_empty|numeric',
        'status' => 'required|in_list[draft,submitted]',
        'catatan_team_leader' => 'permit_empty',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get evaluation by ID with details
     */
    public function getWithDetails(int $evaluationId)
    {
        return $this->select('penilaian.*, employees.nama, employees.nik, employees.posisi, employees.departemen,
                             employees.mulai_probation, users.nama as team_leader_nama')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->where('penilaian.id', $evaluationId)
            ->first();
    }

    /**
     * Get all evaluations for an employee
     */
    public function getByEmployee(int $employeeId)
    {
        return $this->select('penilaian.*, users.nama as team_leader_nama')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->where('penilaian.employee_id', $employeeId)
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();
    }

    /**
     * Get all evaluations by team leader
     */
    public function getByTeamLeader(int $teamLeaderId)
    {
        return $this->select('penilaian.*, employees.nama as employee_nama, employees.nik')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->where('penilaian.team_leader_id', $teamLeaderId)
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();
    }

    /**
     * Count evaluations by status
     */
    public function countByStatus(string $status = null): int
    {
        if ($status) {
            return $this->where('status', $status)->countAllResults();
        }
        return $this->countAllResults();
    }

    /**
     * Calculate average score from all details
     */
    public function calculateAverageScore(int $evaluationId): float
    {
        $db = \Config\Database::connect();
        $result = $db->table('penilaian_detail')
            ->selectAvg('nilai')
            ->where('penilaian_id', $evaluationId)
            ->get()
            ->getRow();

        return $result->nilai ?? 0;
    }

    /**
     * Tandai bahwa Team Member sudah melihat hasil penilaiannya.
     *
     * Stempel first-touch: hanya diisi kalau masih kosong, jadi yang tersimpan
     * adalah kapan hasil ini PERTAMA KALI diterima — itu yang berguna kalau
     * nanti masa probation dipersoalkan. Riwayat kunjungan berikutnya tetap
     * lengkap di audit_logs.
     *
     * $employeeId dipakai sebagai pagar: penilaian milik orang lain tidak
     * pernah ikut tertandai walaupun ID-nya salah oper.
     */
    public function markViewed(int $evaluationId, int $employeeId): bool
    {
        return $this->stampAccess($evaluationId, $employeeId, ['dilihat_at'], 'VIEW_RESULT',
                                  'Team Member melihat hasil penilaian');
    }

    /**
     * Tandai bahwa Team Member sudah mengunduh PDF penilaiannya.
     *
     * Mengunduh berarti melihat, jadi dilihat_at ikut terisi kalau member
     * langsung menekan tombol unduh tanpa membuka halaman laporan lebih dulu.
     */
    public function markDownloaded(int $evaluationId, int $employeeId): bool
    {
        return $this->stampAccess($evaluationId, $employeeId, ['diunduh_at', 'dilihat_at'], 'DOWNLOAD_PDF',
                                  'Team Member mengunduh PDF penilaian');
    }

    /**
     * Isi kolom-kolom stempel yang masih NULL, lalu catat ke audit trail
     * kalau memang ada yang berubah.
     *
     * Kolom yang sudah terisi tidak pernah ditimpa — kondisi "IS NULL" ada di
     * dalam UPDATE-nya sendiri supaya dua request berbarengan tidak saling
     * menimpa stempel pertama.
     */
    protected function stampAccess(int $evaluationId, int $employeeId, array $columns, string $action, string $description): bool
    {
        $db      = \Config\Database::connect();
        $changed = false;

        foreach ($columns as $column) {
            $db->table($this->table)
                ->where('id', $evaluationId)
                ->where('employee_id', $employeeId)
                ->where($column . ' IS NULL', null, false)
                ->set($column, 'NOW()', false)
                ->update();

            if ($db->affectedRows() > 0) {
                $changed = true;
            }
        }

        if ($changed && session()->has('user_id')) {
            model('AuditLogModel')->logAction(
                (int) session()->get('user_id'),
                $action,
                $this->table,
                $evaluationId,
                null,
                null,
                $description
            );
        }

        return $changed;
    }

    /**
     * Jumlah penilaian yang belum pernah dibuka Team Member-nya — dipakai
     * sebagai kartu statistik di dashboard HRD.
     */
    public function countUnviewed(): int
    {
        return $this->where('dilihat_at', null)->countAllResults();
    }

    /**
     * Get scores by category
     */
    public function getScoresByCategory(int $evaluationId)
    {
        $db = \Config\Database::connect();
        return $db->table('penilaian_detail')
            ->selectAvg('nilai', 'average_score')
            ->select('kategori')
            ->where('penilaian_id', $evaluationId)
            ->groupBy('kategori')
            ->get()
            ->getResultArray();
    }
}
