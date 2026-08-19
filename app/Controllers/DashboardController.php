<?php

namespace App\Controllers;

use App\Models\EmployeeModel;
use App\Models\EvaluationDecisionModel;
use App\Models\EvaluationModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    protected $employeeModel;
    protected $evaluationModel;
    protected $evaluationDecisionModel;
    protected $userModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->evaluationModel = new EvaluationModel();
        $this->evaluationDecisionModel = new EvaluationDecisionModel();
        $this->userModel = new UserModel();
    }

    /**
     * HRD Dashboard
     */
    public function hrd()
    {
        if (!$this->isAuthorized('hrd')) {
            return redirect()->to('/auth/login');
        }

        $stats = [
            'total_employees' => $this->employeeModel->countAllResults(),
            'pending' => $this->employeeModel->countByStatus('pending'),
            'lulus' => $this->employeeModel->countByStatus('lulus'),
            'tidak_lulus' => $this->employeeModel->countByStatus('tidak-lulus'),
            'warning' => $this->employeeModel->countByStatus('warning'),
            'total_evaluations' => $this->evaluationModel->countAllResults(),
            'belum_dilihat' => $this->evaluationModel->countUnviewed(),
        ];

        $recentEvalsRaw = $this->evaluationModel
            ->select('penilaian.*, employees.nama, employees.nik, users.nama as team_leader_nama')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->limit(20)
            ->findAll();

        $evalByEmployee = [];
        foreach ($recentEvalsRaw as $eval) {
            $eid   = $eval['employee_id'];
            $nomor = (int)($eval['nomor_penilaian'] ?? 1);
            if (!isset($evalByEmployee[$eid])) {
                $evalByEmployee[$eid] = [
                    'employee_id'      => $eid,
                    'nik'              => $eval['nik'],
                    'nama'             => $eval['nama'],
                    'team_leader_nama' => $eval['team_leader_nama'],
                    'eval1'            => null,
                    'eval2'            => null,
                ];
            }
            if ($nomor === 1 && !$evalByEmployee[$eid]['eval1']) $evalByEmployee[$eid]['eval1'] = $eval;
            if ($nomor === 2 && !$evalByEmployee[$eid]['eval2']) $evalByEmployee[$eid]['eval2'] = $eval;
        }

        $data = [
            'title'             => 'Dashboard HRD',
            'greeting'          => $this->getGreeting(),
            'user'              => session()->get(),
            'stats'             => $stats,
            'recentEvaluations' => array_slice($recentEvalsRaw, 0, 10),
            'evalByEmployee'    => array_slice(array_values($evalByEmployee), 0, 10),
        ];

        return view('Dashboard/hrd', $data);
    }

    /**
     * Team Leader Dashboard
     */
    public function teamLeader()
    {
        if (!$this->isAuthorized('team-leader')) {
            return redirect()->to('/auth/login');
        }

        $teamLeaderId = session()->get('user_id');
        $employees    = $this->employeeModel->getByTeamLeader($teamLeaderId);
        $evaluations  = $this->evaluationModel->getByTeamLeader($teamLeaderId);

        $stats = [
            'total_team_members' => count($employees),
            'pending_evaluation' => count(array_filter($employees, fn($e) => $e['status'] === 'pending')),
            'total_evaluations'  => count($evaluations),
            'submitted'          => count(array_filter($evaluations, fn($e) => $e['status'] === 'submitted')),
        ];

        // Group evaluations by employee for the "Penilaian Terbaru" section
        $evalByEmployee = [];
        foreach ($evaluations as $eval) {
            $eid   = $eval['employee_id'];
            $nomor = (int)($eval['nomor_penilaian'] ?? 1);
            if (!isset($evalByEmployee[$eid])) {
                $evalByEmployee[$eid] = [
                    'employee_id'   => $eid,
                    'employee_nama' => $eval['employee_nama'],
                    'nik'           => $eval['nik'],
                    'eval1'         => null,
                    'eval2'         => null,
                ];
            }
            if ($nomor === 1 && !$evalByEmployee[$eid]['eval1']) $evalByEmployee[$eid]['eval1'] = $eval;
            if ($nomor === 2 && !$evalByEmployee[$eid]['eval2']) $evalByEmployee[$eid]['eval2'] = $eval;
        }

        $data = [
            'title'             => 'Dashboard Team Leader',
            'greeting'          => $this->getGreeting(),
            'user'              => session()->get(),
            'stats'             => $stats,
            'employees'         => $employees,
            'recentEvaluations' => array_slice($evaluations, 0, 5),
            'evalByEmployee'    => array_slice(array_values($evalByEmployee), 0, 5),
        ];

        return view('Dashboard/teamleader', $data);
    }

    /**
     * Team Leader — Tim Saya (daftar anggota + modal penilaian)
     */
    public function myTeam()
    {
        if (!$this->isAuthorized('team-leader')) {
            return redirect()->to('/auth/login');
        }

        $teamLeaderId = session()->get('user_id');
        $employees    = $this->employeeModel->getByTeamLeader($teamLeaderId);
        $db           = \Config\Database::connect();

        // Ambil semua evaluasi TL ini, diurutkan terbaru dulu
        $allEvals = $db->table('penilaian')
            ->select('employee_id, id, tanggal_penilaian, nomor_penilaian, dilihat_at, diunduh_at')
            ->where('team_leader_id', $teamLeaderId)
            ->orderBy('tanggal_penilaian', 'DESC')
            ->get()->getResultArray();

        // Bangun map: employee_id → {last_eval_date, last_eval_id, total_penilaian, eval1_id, eval2_id}
        $lastEvalMap = [];
        foreach ($allEvals as $ev) {
            $eid   = $ev['employee_id'];
            $nomor = (int)($ev['nomor_penilaian'] ?? 1);
            if (!isset($lastEvalMap[$eid])) {
                $lastEvalMap[$eid] = [
                    'last_eval_date'  => $ev['tanggal_penilaian'],
                    'last_eval_id'    => $ev['id'],
                    'total_penilaian' => 0,
                    'eval1_id'        => null,
                    'eval2_id'        => null,
                    'eval1_ack'       => null,
                    'eval2_ack'       => null,
                ];
            }
            $lastEvalMap[$eid]['total_penilaian']++;
            if ($nomor === 1 && !$lastEvalMap[$eid]['eval1_id']) {
                $lastEvalMap[$eid]['eval1_id']  = $ev['id'];
                $lastEvalMap[$eid]['eval1_ack'] = $ev;
            }
            if ($nomor === 2 && !$lastEvalMap[$eid]['eval2_id']) {
                $lastEvalMap[$eid]['eval2_id']  = $ev['id'];
                $lastEvalMap[$eid]['eval2_ack'] = $ev;
            }
        }

        $today          = new \DateTime('today');
        // Keputusan Lulus per karyawan, sekali query — dipakai tombol SK di daftar.
        $skMap = [];
        foreach ($this->evaluationDecisionModel->where('status_akhir', 'lulus')->findAll() as $kep) {
            $skMap[(int) $kep['employee_id']] = $kep;
        }

        $intervalHarian = 45; // Siklus penilaian: ~1,5 bulan
        $maxPenilaian   = 2;  // Maks 2x penilaian selama probation 3 bulan

        foreach ($employees as &$emp) {
            $lastEval       = $lastEvalMap[$emp['id']] ?? null;
            $totalPenilaian = (int)($lastEval['total_penilaian'] ?? 0);
            $emp['last_eval_id'] = $lastEval['last_eval_id'] ?? null;
            $emp['eval1_id']     = $lastEval['eval1_id'] ?? null;
            $emp['eval2_id']     = $lastEval['eval2_id'] ?? null;
            $emp['eval1_ack']    = $lastEval['eval1_ack'] ?? null;
            $emp['eval2_ack']    = $lastEval['eval2_ack'] ?? null;
            $mulai          = $emp['mulai_probation'] ? new \DateTime($emp['mulai_probation']) : null;

            // Surat Keputusan hanya ada setelah HRD memutuskan Lulus dan mengisi
            // nomor SK-nya. Disetel di sini, sebelum cabang-cabang di bawah:
            // karyawan yang statusnya sudah final justru keluar lewat continue,
            // dan merekalah satu-satunya yang punya SK.
            $emp['sk_siap'] = EvaluationDecisionModel::skSiap($skMap[(int) $emp['id']] ?? null);

            // Status final dari HRD → tidak perlu logika siklus
            if ($emp['status'] !== 'pending') {
                $emp['eval_status']     = $emp['status'];
                $emp['last_eval_date']  = $lastEval['last_eval_date'] ?? null;
                $emp['total_penilaian'] = $totalPenilaian;
                $emp['hari_sejak_eval'] = null;
                $emp['perlu_dinilai']   = false;
                continue;
            }

            // Sudah dinilai 2x → menunggu keputusan HRD
            if ($totalPenilaian >= $maxPenilaian) {
                $emp['eval_status']     = 'selesai_dinilai';
                $emp['last_eval_date']  = $lastEval['last_eval_date'];
                $emp['total_penilaian'] = $totalPenilaian;
                $emp['hari_sejak_eval'] = null;
                $emp['perlu_dinilai']   = false;
                continue;
            }

            if ($totalPenilaian === 0) {
                // Belum pernah dinilai — cek apakah sudah 45 hari sejak mulai probation
                $hariMasuk    = $mulai ? (int)$today->diff($mulai)->days : $intervalHarian;
                $perluDinilai = $hariMasuk >= $intervalHarian;
                $emp['eval_status']     = $perluDinilai ? 'perlu_dinilai' : 'belum_waktunya';
                $emp['hari_sejak_eval'] = null;
                $emp['sisa_hari']       = $perluDinilai ? 0 : $intervalHarian - $hariMasuk;
            } else {
                // Sudah dinilai 1x — cek apakah sudah 45 hari sejak penilaian terakhir
                $lastDate     = new \DateTime($lastEval['last_eval_date']);
                $hariSejak    = (int)$today->diff($lastDate)->days;
                $perluDinilai = $hariSejak >= $intervalHarian;
                $emp['eval_status']     = $perluDinilai ? 'perlu_dinilai' : 'sudah_dinilai';
                $emp['hari_sejak_eval'] = $hariSejak;
                $emp['sisa_hari']       = $perluDinilai ? 0 : $intervalHarian - $hariSejak;
            }

            $emp['last_eval_date']  = $lastEval['last_eval_date'] ?? null;
            $emp['total_penilaian'] = $totalPenilaian;
            $emp['perlu_dinilai']   = $emp['eval_status'] === 'perlu_dinilai';
        }
        unset($emp);

        $stats = [
            'total_team_members' => count($employees),
            'pending_evaluation' => count(array_filter($employees, fn($e) => $e['perlu_dinilai'] ?? false)),
        ];

        $data = [
            'title'     => 'Tim Saya',
            'user'      => session()->get(),
            'employees' => $employees,
            'stats'     => $stats,
        ];

        return view('Dashboard/myteam', $data);
    }

    /**
     * Probationary Employee Dashboard
     */
    public function probationary()
    {
        if (!$this->isAuthorized('probationary-employee')) {
            return redirect()->to('/auth/login');
        }

        $userId = session()->get('user_id');

        // Find the employee record for this user (by NIK)
        $employee = $this->employeeModel->findByNik(session()->get('nik'));

        if (!$employee) {
            return view('Dashboard/member_no_data', ['title' => 'Dashboard', 'user' => session()->get()]);
        }

        $evaluations = $this->evaluationModel->getByEmployee($employee['id']);

        // Surat Keputusan pengangkatan muncul sendiri begitu HRD memutuskan Lulus
        // dan melengkapi nomor SK-nya; null selama itu belum terjadi.
        $keputusan = $this->evaluationDecisionModel->getLulusByEmployee((int) $employee['id']);
        $sk        = EvaluationDecisionModel::skSiap($keputusan) ? $keputusan : null;

        $data = [
            'title' => 'Dashboard',
            'greeting' => $this->getGreeting(),
            'user' => session()->get(),
            'employee' => $employee,
            'evaluations' => $evaluations,
            'sk' => $sk,
        ];

        return view('Dashboard/probationary', $data);
    }

    /**
     * Get statistics (API)
     */
    public function stats()
    {
        $role = session()->get('role');

        $stats = match ($role) {
            'hrd' => $this->getHrdStats(),
            'team-leader' => $this->getTeamLeaderStats(),
            'probationary-employee' => $this->getMemberStats(),
            default => [],
        };

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $stats,
        ]);
    }

    /**
     * Get HRD statistics
     */
    protected function getHrdStats(): array
    {
        return [
            'total_employees' => $this->employeeModel->countAllResults(),
            'pending' => $this->employeeModel->countByStatus('pending'),
            'lulus' => $this->employeeModel->countByStatus('lulus'),
            'tidak_lulus' => $this->employeeModel->countByStatus('tidak-lulus'),
            'warning' => $this->employeeModel->countByStatus('warning'),
            'total_evaluations' => $this->evaluationModel->countAllResults(),
            'submitted_evaluations' => $this->evaluationModel->where('status', 'submitted')->countAllResults(),
            'belum_dilihat' => $this->evaluationModel->countUnviewed(),
        ];
    }

    /**
     * Get Team Leader statistics
     */
    protected function getTeamLeaderStats(): array
    {
        $teamLeaderId = session()->get('user_id');
        $employees = $this->employeeModel->getByTeamLeader($teamLeaderId);
        $evaluations = $this->evaluationModel->getByTeamLeader($teamLeaderId);

        return [
            'total_team_members' => count($employees),
            'pending_evaluation' => count(array_filter($employees, fn($e) => $e['status'] === 'pending')),
            'total_evaluations' => count($evaluations),
            'submitted' => count(array_filter($evaluations, fn($e) => $e['status'] === 'submitted')),
            'draft' => count(array_filter($evaluations, fn($e) => $e['status'] === 'draft')),
        ];
    }

    /**
     * Get Team Member statistics
     */
    protected function getMemberStats(): array
    {
        $employee = $this->employeeModel->findByNik(session()->get('nik'));

        if (!$employee) {
            return ['message' => 'Employee record not found'];
        }

        $evaluations = $this->evaluationModel->getByEmployee($employee['id']);

        return [
            'employee_id' => $employee['id'],
            'status' => $employee['status'],
            'total_evaluations' => count($evaluations),
            'probation_start' => $employee['mulai_probation'],
            'probation_end' => $employee['akhir_probation'],
        ];
    }

    /**
     * Helper: Check authorization
     */
    protected function isAuthorized(string $requiredRole): bool
    {
        if (!session()->has('user_id')) {
            return false;
        }

        $role = session()->get('role');
        return $role === $requiredRole;
    }

    /**
     * Helper: Get greeting based on time of day
     */
    protected function getGreeting(): string
    {
        $dt   = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $hour = (int) $dt->format('H');

        if ($hour < 12) {
            return 'Selamat Pagi';
        } elseif ($hour < 17) {
            return 'Selamat Siang';
        } else {
            return 'Selamat Malam';
        }
    }
}
