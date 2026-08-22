<?php

namespace App\Controllers;

use App\Libraries\RingkasanDashboard;
use App\Libraries\SiklusProbation;
use App\Models\EmployeeModel;
use App\Models\EvaluationDecisionModel;
use App\Models\EvaluationModel;
use App\Models\UserModel;

/**
 * Dashboard tiap peran.
 *
 * Dashboard di sini sengaja TIDAK mengulang isi menu lain. "Daftar Penilaian",
 * "Tim Saya", dan "Hasil Evaluasi" sudah menampilkan daftar rinci beserta
 * tombol aksinya; dashboard hanya menjawab tiga hal: sudah sampai mana
 * datanya, apa yang menunggu tindakan saya, dan ke mana arahnya. Perhitungannya
 * ada di RingkasanDashboard, aturan siklusnya di SiklusProbation.
 */
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
     * Dashboard HRD — keadaan seluruh proses probation perusahaan.
     *
     * Tiga query, berapa pun banyaknya karyawan: seluruh karyawan, seluruh
     * penilaian, seluruh keputusan. Sisanya dihitung di memori oleh
     * RingkasanDashboard, jadi tidak ada query di dalam perulangan.
     */
    public function hrd()
    {
        if (!$this->isAuthorized('hrd')) {
            return redirect()->to('/auth/login');
        }

        $employees = $this->employeeModel->getWithTeamLeader();
        $penilaian = $this->evaluationModel
            ->select('employee_id, nomor_penilaian, nilai_total, tanggal_penilaian, dilihat_at')
            ->findAll();
        $keputusan = $this->evaluationDecisionModel->findAll();
        $user      = session()->get();

        return view('Dashboard/hrd', [
            'title'     => 'Dashboard HRD',
            'greeting'  => $this->getGreeting(),
            'user'      => $user,
            'ringkasan' => RingkasanDashboard::hrd($employees, $penilaian, $keputusan),
            // Kepala halaman dirakit di sini, bukan di view: partial hero
            // dirender View::include() yang hanya melihat data dari controller,
            // bukan variabel lokal milik view pemanggilnya.
            'heroChips' => [
                ['fa-id-card', 'NIK: ' . ($user['nik'] ?? '')],
                ['fa-user-shield', 'HRD'],
                ['fa-users', count($employees) . ' Team Member terdaftar'],
            ],
            'heroDesc'  => 'Ringkasan proses probation seluruh perusahaan. Daftar rinci dan tombol aksinya ada di menu Data Team Member dan Daftar Penilaian.',
        ]);
    }

    /**
     * Dashboard Team Leader — beban penilaian dan perkembangan tim sendiri.
     *
     * Penilaian diambil menurut team_leader_id, sama seperti halaman "Tim Saya",
     * supaya kedua halaman tidak pernah berbeda kesimpulan tentang orang yang
     * sama.
     */
    public function teamLeader()
    {
        if (!$this->isAuthorized('team-leader')) {
            return redirect()->to('/auth/login');
        }

        $teamLeaderId = (int) session()->get('user_id');
        $employees    = $this->employeeModel->getByTeamLeader($teamLeaderId);
        $penilaian    = $this->evaluationModel->getByTeamLeader($teamLeaderId);
        $user         = session()->get();

        return view('Dashboard/teamleader', [
            'title'     => 'Dashboard Team Leader',
            'greeting'  => $this->getGreeting(),
            'user'      => $user,
            'ringkasan' => RingkasanDashboard::teamLeader($employees, $penilaian),
            'heroChips' => [
                ['fa-id-card', 'NIK: ' . ($user['nik'] ?? '')],
                ['fa-user-tie', 'Team Leader · ' . ($user['departemen'] ?? '-')],
                ['fa-users', count($employees) . ' anggota tim'],
            ],
            'heroDesc'  => 'Ringkasan perkembangan tim Anda. Untuk menilai atau membuka lembar penilaian, gunakan menu Tim Saya.',
        ]);
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

        // Keputusan Lulus per karyawan, sekali query — dipakai tombol SK di daftar.
        $skMap = [];
        foreach ($this->evaluationDecisionModel->where('status_akhir', 'lulus')->findAll() as $kep) {
            $skMap[(int) $kep['employee_id']] = $kep;
        }

        foreach ($employees as &$emp) {
            $lastEval       = $lastEvalMap[$emp['id']] ?? null;
            $totalPenilaian = (int)($lastEval['total_penilaian'] ?? 0);
            $emp['last_eval_id'] = $lastEval['last_eval_id'] ?? null;
            $emp['eval1_id']     = $lastEval['eval1_id'] ?? null;
            $emp['eval2_id']     = $lastEval['eval2_id'] ?? null;
            $emp['eval1_ack']    = $lastEval['eval1_ack'] ?? null;
            $emp['eval2_ack']    = $lastEval['eval2_ack'] ?? null;

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

            // Kapan dia boleh dinilai lagi — aturannya ada di SiklusProbation,
            // dipakai juga oleh dashboard supaya keduanya tidak bisa berbeda.
            $jadwal = SiklusProbation::jadwal(
                $emp['mulai_probation'] ?? null,
                $totalPenilaian,
                $lastEval['last_eval_date'] ?? null
            );

            $emp['eval_status']     = $jadwal['status'];
            $emp['hari_sejak_eval'] = $jadwal['hari_sejak_eval'];
            $emp['sisa_hari']       = $jadwal['sisa_hari'];
            $emp['last_eval_date']  = $lastEval['last_eval_date'] ?? null;
            $emp['total_penilaian'] = $totalPenilaian;
            $emp['perlu_dinilai']   = $jadwal['status'] === SiklusProbation::PERLU_DINILAI;
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
     * Dashboard Team Member — posisi dirinya sendiri dalam masa probation.
     *
     * Rincian tiap lembar penilaian tetap di menu "Hasil Evaluasi"; di sini
     * hanya sejauh mana probationnya berjalan, langkah apa yang sedang
     * ditunggu, dan nilai yang sudah keluar.
     */
    public function probationary()
    {
        if (!$this->isAuthorized('probationary-employee')) {
            return redirect()->to('/auth/login');
        }

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

        return view('Dashboard/probationary', [
            'title'     => 'Dashboard',
            'greeting'  => $this->getGreeting(),
            'user'      => session()->get(),
            'employee'  => $employee,
            'ringkasan' => RingkasanDashboard::member($employee, $evaluations, $sk),
            'heroChips' => [
                ['fa-id-card', 'NIK: ' . ($employee['nik'] ?? '')],
                ['fa-briefcase', ($employee['posisi'] ?? '-') . ' · ' . ($employee['departemen'] ?? '-')],
            ],
            'heroDesc'  => 'Perkembangan masa probasi Anda. Rincian tiap penilaian ada di menu Hasil Evaluasi.',
        ]);
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
