<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['nik', 'nama', 'departemen', 'posisi', 'email', 'tanggal_masuk',
                                'mulai_probation', 'akhir_probation', 'status', 'team_leader_id',
                                'jenis_kelamin', 'tanggal_lahir', 'alamat',
                                'created_by', 'created_at', 'updated_at', 'deleted_at'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'nik' => 'required|is_unique[employees.nik]|min_length[3]|max_length[20]',
        'nama' => 'required|min_length[3]|max_length[100]',
        'departemen' => 'required|max_length[50]',
        'posisi' => 'required|max_length[50]',
        'email' => 'permit_empty|valid_email',
        'tanggal_masuk' => 'permit_empty|valid_date',
        'mulai_probation' => 'required|valid_date',
        'akhir_probation' => 'permit_empty|valid_date',
        'status' => 'required|in_list[pending,lulus,tidak-lulus,warning]',
        'team_leader_id' => 'permit_empty|integer',
        'created_by' => 'permit_empty|integer',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Find employee by NIK
     */
    public function findByNik(string $nik)
    {
        return $this->where('nik', $nik)->first();
    }

    /**
     * Get employees by status
     */
    public function getByStatus(string $status)
    {
        return $this->where('status', $status)->findAll();
    }

    /**
     * Get employees by team leader
     */
    public function getByTeamLeader(int $teamLeaderId)
    {
        return $this->where('team_leader_id', $teamLeaderId)->findAll();
    }

    /**
     * Get employees by department
     */
    public function getByDepartment(string $department)
    {
        return $this->where('departemen', $department)->findAll();
    }

    /**
     * Get all unique departments
     */
    public function getDepartments()
    {
        return $this->distinct()
            ->select('departemen')
            ->findAll();
    }

    /**
     * Get employees with team leader info
     */
    public function getWithTeamLeader()
    {
        return $this->select('employees.*, users.nama as team_leader_nama')
            ->join('users', 'employees.team_leader_id = users.id', 'left')
            ->findAll();
    }

    /**
     * Get employee with full details including evaluations
     */
    public function getWithDetails(int $employeeId)
    {
        return $this->select('employees.*, users.nama as team_leader_nama, users.email as team_leader_email')
            ->join('users', 'employees.team_leader_id = users.id', 'left')
            ->where('employees.id', $employeeId)
            ->first();
    }

    /**
     * Count employees by status
     */
    public function countByStatus(string $status = null): int
    {
        if ($status) {
            return $this->where('status', $status)->countAllResults();
        }
        return $this->countAllResults();
    }

    /**
     * Calculate probation end date (90 days from start)
     */
    public static function calculateProbationEnd(string $startDate): string
    {
        $date = new \DateTime($startDate);
        $date->modify('+90 days');
        return $date->format('Y-m-d');
    }
}
