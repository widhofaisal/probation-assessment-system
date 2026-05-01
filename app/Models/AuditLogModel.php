<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['user_id', 'action', 'table_name', 'record_id', 'old_values', 'new_values',
                                'description', 'ip_address', 'user_agent', 'created_at'];
    protected $useTimestamps = false;

    /**
     * Log an action
     */
    public function logAction(
        int $userId,
        string $action,
        string $tableName,
        int $recordId,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $description = ''
    ) {
        $request = \Config\Services::request();

        return $this->insert([
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'description' => $description,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => substr($request->getUserAgent(), 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get logs by user
     */
    public function getByUser(int $userId, int $limit = 100)
    {
        return $this->select('audit_logs.*, users.nama as user_nama')
            ->join('users', 'audit_logs.user_id = users.id', 'left')
            ->where('audit_logs.user_id', $userId)
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get logs by table
     */
    public function getByTable(string $tableName, int $limit = 100)
    {
        return $this->select('audit_logs.*, users.nama as user_nama')
            ->join('users', 'audit_logs.user_id = users.id', 'left')
            ->where('audit_logs.table_name', $tableName)
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get logs by record
     */
    public function getByRecord(string $tableName, int $recordId)
    {
        return $this->select('audit_logs.*, users.nama as user_nama')
            ->join('users', 'audit_logs.user_id = users.id', 'left')
            ->where('audit_logs.table_name', $tableName)
            ->where('audit_logs.record_id', $recordId)
            ->orderBy('audit_logs.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get logs by action
     */
    public function getByAction(string $action, int $limit = 100)
    {
        return $this->select('audit_logs.*, users.nama as user_nama')
            ->join('users', 'audit_logs.user_id = users.id', 'left')
            ->where('audit_logs.action', $action)
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get recent logs
     */
    public function getRecent(int $limit = 50)
    {
        return $this->select('audit_logs.*, users.nama as user_nama')
            ->join('users', 'audit_logs.user_id = users.id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get audit summary for a date range
     */
    public function getSummary(\DateTime $from, \DateTime $to)
    {
        $fromStr = $from->format('Y-m-d H:i:s');
        $toStr = $to->format('Y-m-d H:i:s');

        $db = \Config\Database::connect();
        return $db->table('audit_logs')
            ->select('action')
            ->selectCount('id', 'count')
            ->whereBetween('created_at', [$fromStr, $toStr])
            ->groupBy('action')
            ->get()
            ->getResultArray();
    }
}
