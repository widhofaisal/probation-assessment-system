<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['nik', 'nama', 'email', 'password_hash', 'role', 'departemen', 'posisi', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';

    // Validation rules
    protected $validationRules = [
        'nik' => 'required|is_unique[users.nik]|min_length[3]|max_length[20]',
        'nama' => 'required|min_length[3]|max_length[100]',
        'email' => 'permit_empty|valid_email|is_unique[users.email]',
        'password_hash' => 'required|min_length[8]',
        'role' => 'required|in_list[hrd,team-leader,probationary-employee]',
        'departemen' => 'permit_empty|max_length[50]',
        'posisi' => 'permit_empty|max_length[50]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Find user by NIK or Email
     */
    public function findByNikOrEmail(string $identifier)
    {
        return $this->where('nik', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role)
    {
        return $this->where('role', $role)->findAll();
    }

    /**
     * Get team leaders (for assignment to employees)
     */
    public function getTeamLeaders()
    {
        return $this->where('role', 'team-leader')->findAll();
    }

    /**
     * Get HRD managers
     */
    public function getHrdManagers()
    {
        return $this->where('role', 'hrd')->findAll();
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
