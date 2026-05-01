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
    protected $allowedFields = ['employee_id', 'team_leader_id', 'tanggal_penilaian', 'nilai_total',
                                'status', 'catatan_team_leader',
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
                             users.nama as team_leader_nama')
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
