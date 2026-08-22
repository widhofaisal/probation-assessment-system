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
    protected $allowedFields = ['nik', 'nama', 'email', 'password_hash', 'role', 'departemen', 'posisi',
                                'jenis_kelamin', 'tanggal_lahir', 'alamat', 'created_at', 'updated_at'];
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

    /**
     * Buat password awal yang acak.
     *
     * Sebelumnya password awal setiap akun diisi dengan NIK orang tersebut,
     * padahal NIK juga dipakai sebagai username dan diketahui banyak orang -
     * artinya password akun baru bisa ditebak siapa saja. Sekarang dibuat acak
     * dan hanya ditampilkan sekali kepada HRD untuk diteruskan ke pemiliknya.
     *
     * Huruf dan angka yang mudah tertukar (0, O, 1, l, I) sengaja dibuang,
     * karena password ini akan disalin manual oleh manusia.
     */
    public static function generatePassword(int $panjang = 12): string
    {
        $alfabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $batas   = strlen($alfabet) - 1;

        $password = '';
        for ($i = 0; $i < $panjang; $i++) {
            $password .= $alfabet[random_int(0, $batas)];
        }

        return $password;
    }
}
