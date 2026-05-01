<?php

namespace App\Controllers;

use App\Models\EvaluationModel;
use App\Models\EvaluationDetailModel;
use App\Models\EmployeeModel;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class ReportsController extends BaseController
{
    protected $evaluationModel;
    protected $evaluationDetailModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->evaluationModel = new EvaluationModel();
        $this->evaluationDetailModel = new EvaluationDetailModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * View evaluation report
     */
    public function view(int $id)
    {
        $evaluation = $this->evaluationModel->getWithDetails($id);

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Penilaian tidak ditemukan');
        }

        // Check authorization
        $role = session()->get('role');
        if ($role === 'team-leader' && $evaluation['team_leader_id'] != session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $details = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);

        $data = [
            'title' => 'Laporan Penilaian',
            'evaluation' => $evaluation,
            'details' => $details,
            'categoryScores' => $categoryScores,
        ];

        return view('Reports/view', $data);
    }

    /**
     * Generate PDF report
     */
    public function generatePdf(int $id)
    {
        $evaluation = $this->evaluationModel->getWithDetails($id);

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Penilaian tidak ditemukan');
        }

        // Check authorization
        $role = session()->get('role');
        if ($role === 'team-leader' && $evaluation['team_leader_id'] != session()->get('user_id')) {
            if ($role === 'probationary-employee') {
                $employee = $this->employeeModel->findByNik(session()->get('nik'));
                if (!$employee || $employee['id'] != $evaluation['employee_id']) {
                    return redirect()->back()->with('error', 'Akses ditolak');
                }
            }
        }

        $details = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);

        // Generate HTML
        $html = $this->generatePdfHtml($evaluation, $details, $categoryScores);

        // Create PDF
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output
        $filename = 'Penilaian_' . $evaluation['nik'] . '_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => false]);
    }

    /**
     * Export evaluations to CSV
     */
    public function exportCsv()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $evaluations = $this->evaluationModel->select('penilaian.*, employees.nama, employees.nik, users.nama as team_leader_nama')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();

        // Create CSV
        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evaluations_' . date('Y-m-d_H-i-s') . '.csv"');

        // BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, ['NIK', 'Nama Karyawan', 'Team Leader', 'Tanggal Penilaian', 'Nilai Total', 'Status'], ';');

        // Data
        foreach ($evaluations as $evaluation) {
            fputcsv($output, [
                $evaluation['nik'],
                $evaluation['nama'],
                $evaluation['team_leader_nama'],
                $evaluation['tanggal_penilaian'],
                $evaluation['nilai_total'],
                $evaluation['status'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Export employees to CSV
     */
    public function exportEmployeesCsv()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $employees = $this->employeeModel->select('employees.*, users.nama as team_leader_nama')
            ->join('users', 'employees.team_leader_id = users.id', 'left')
            ->findAll();

        // Create CSV
        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d_H-i-s') . '.csv"');

        // BOM for UTF-8
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, ['NIK', 'Nama', 'Departemen', 'Posisi', 'Email', 'Team Leader', 'Status Probation', 'Mulai Probation', 'Akhir Probation'], ';');

        // Data
        foreach ($employees as $employee) {
            fputcsv($output, [
                $employee['nik'],
                $employee['nama'],
                $employee['departemen'],
                $employee['posisi'],
                $employee['email'],
                $employee['team_leader_nama'] ?? '-',
                $employee['status'],
                $employee['mulai_probation'],
                $employee['akhir_probation'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * View audit trail
     */
    public function auditTrail()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $auditModel = model('AuditLogModel');
        $logs = $auditModel->getRecent(100);

        $data = [
            'title' => 'Audit Trail',
            'logs' => $logs,
        ];

        return view('Reports/audit_trail', $data);
    }

    /**
     * Generate PDF HTML
     */
    protected function generatePdfHtml(array $evaluation, array $details, array $categoryScores): string
    {
        $company = config('App')->companyName ?? 'PT Sumber Masanda Jaya';
        $date = date('d/m/Y', strtotime($evaluation['tanggal_penilaian']));

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #0066cc;
                    padding-bottom: 15px;
                }
                .header h1 {
                    margin: 0;
                    color: #0066cc;
                    font-size: 24px;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .section {
                    margin: 20px 0;
                    padding: 15px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                .section h2 {
                    color: #0066cc;
                    border-bottom: 2px solid #0066cc;
                    padding-bottom: 10px;
                    margin-top: 0;
                    font-size: 16px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 10px 0;
                }
                th {
                    background-color: #0066cc;
                    color: white;
                    padding: 10px;
                    text-align: left;
                }
                td {
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .score-high {
                    color: #28a745;
                    font-weight: bold;
                }
                .score-low {
                    color: #dc3545;
                    font-weight: bold;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: right;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Laporan Penilaian Probation</h1>
                <p>{$company}</p>
            </div>

            <div class="section">
                <h2>Informasi Karyawan</h2>
                <table>
                    <tr>
                        <td><strong>NIK</strong></td>
                        <td>{$evaluation['nik']}</td>
                        <td><strong>Departemen</strong></td>
                        <td>{$evaluation['departemen']}</td>
                    </tr>
                    <tr>
                        <td><strong>Nama</strong></td>
                        <td>{$evaluation['nama']}</td>
                        <td><strong>Posisi</strong></td>
                        <td>{$evaluation['posisi']}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>Hasil Penilaian</h2>
                <table>
                    <tr>
                        <th>Kategori</th>
                        <th>Skor Rata-rata</th>
                    </tr>
HTML;

        foreach ($categoryScores as $category) {
            $scoreClass = $category['average'] >= 6 ? 'score-high' : 'score-low';
            $html .= <<<HTML
                    <tr>
                        <td>{$category['kategori']}</td>
                        <td class="{$scoreClass}">{$category['average']}</td>
                    </tr>
HTML;
        }

        $html .= <<<HTML
                    <tr style="background-color: #e8f4f8;">
                        <td><strong>Skor Keseluruhan</strong></td>
                        <td><strong>{$evaluation['nilai_total']}</strong></td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h2>Detail Penilaian</h2>
                <table>
                    <tr>
                        <th>Kategori</th>
                        <th>Aspek</th>
                        <th>Nilai</th>
                        <th>Alasan (jika ada)</th>
                    </tr>
HTML;

        foreach ($details as $detail) {
            $scoreClass = $detail['nilai'] >= 6 ? 'score-high' : 'score-low';
            $alasan = !empty($detail['alasan']) ? $detail['alasan'] : '-';
            $html .= <<<HTML
                    <tr>
                        <td>{$detail['kategori']}</td>
                        <td>{$detail['aspek']}</td>
                        <td class="{$scoreClass}">{$detail['nilai']}</td>
                        <td>{$alasan}</td>
                    </tr>
HTML;
        }

        $catatanTL = $evaluation['catatan_team_leader'] ?? '-';
        $catatanHRD = $evaluation['catatan_hrd'] ?? '-';

        $html .= <<<HTML
                </table>
            </div>

            <div class="section">
                <h2>Catatan</h2>
                <p><strong>Catatan Team Leader:</strong></p>
                <p>{$catatanTL}</p>
                <p><strong>Catatan HRD:</strong></p>
                <p>{$catatanHRD}</p>
            </div>

            <div class="footer">
                <p>Laporan ini dicetak pada: {$date}</p>
                <p>© {$company}</p>
            </div>
        </body>
        </html>
HTML;

        return $html;
    }
}
