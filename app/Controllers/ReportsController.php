<?php

namespace App\Controllers;

use App\Models\EvaluationModel;
use App\Models\EvaluationDetailModel;
use App\Models\EmployeeModel;

class ReportsController extends BaseController
{
    protected $evaluationModel;
    protected $evaluationDetailModel;
    protected $employeeModel;

    public function __construct()
    {
        $this->evaluationModel      = new EvaluationModel();
        $this->evaluationDetailModel = new EvaluationDetailModel();
        $this->employeeModel        = new EmployeeModel();
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

        $role = session()->get('role');
        if ($role === 'team-leader' && $evaluation['team_leader_id'] != session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $details        = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);

        $data = [
            'title'          => 'Laporan Penilaian',
            'evaluation'     => $evaluation,
            'details'        => $details,
            'categoryScores' => $categoryScores,
        ];

        return view('Reports/view', $data);
    }

    /**
     * Generate PDF — fill Word template via PowerShell/Word COM, fallback to DomPDF
     */
    public function generatePdf(int $id)
    {
        $evaluation = $this->evaluationModel->getWithDetails($id);

        if (!$evaluation) {
            return redirect()->back()->with('error', 'Penilaian tidak ditemukan');
        }

        $role = session()->get('role');
        if ($role === 'team-leader' && (int)$evaluation['team_leader_id'] !== (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $nomor    = (int)($evaluation['nomor_penilaian'] ?? 1);
        $filename = 'Penilaian_' . ($evaluation['nik'] ?? $id) . '_ke' . $nomor . '.pdf';

        // --- Serve from cache if available ---
        $pdfDir     = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'pdfs';
        $cachedPath = $pdfDir . DIRECTORY_SEPARATOR . 'eval_' . $id . '.pdf';

        if (file_exists($cachedPath)) {
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->setBody(file_get_contents($cachedPath));
        }

        // Attach flat details array for the Word COM script
        $details = $this->evaluationDetailModel->where('penilaian_id', $id)->findAll();
        $evaluation['details'] = $details;

        // --- Primary: Word COM via PowerShell ---
        $pdfPath = $this->generatePdfViaWordCom($evaluation);
        if ($pdfPath && file_exists($pdfPath)) {
            // Save to permanent cache
            if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
            rename($pdfPath, $cachedPath);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->setBody(file_get_contents($cachedPath));
        }

        // --- Fallback: DomPDF ---
        $groupedDetails = $this->evaluationDetailModel->getByEvaluationGrouped($id);
        $categoryScores = $this->evaluationDetailModel->getCategoryAverages($id);
        $html           = $this->generatePdfHtml($evaluation, $groupedDetails, $categoryScores);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'Penilaian_' . ($evaluation['nik'] ?? $id) . '_' . date('Y-m-d') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => false]);
    }

    // ---------------------------------------------------------------
    // WORD COM PDF GENERATION
    // ---------------------------------------------------------------

    /**
     * Fill the Word template with evaluation data and export to PDF.
     * Returns the local PDF path on success, null on failure.
     */
    private function generatePdfViaWordCom(array $eval): ?string
    {
        $templatePath = ROOTPATH . '27. FORM PENILAIAN PROBATION TEAM MEMBER.doc';
        if (!file_exists($templatePath)) {
            log_message('error', 'PDF: Word template not found at ' . $templatePath);
            return null;
        }

        $tmpDir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $uid     = 'eval_' . $eval['id'] . '_' . uniqid();
        $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . $uid . '.pdf';
        $psPath  = $tmpDir . DIRECTORY_SEPARATOR . $uid . '.ps1';

        $script = $this->buildWordComScript($eval, $templatePath, $pdfPath);
        // Write with UTF-8 BOM so PowerShell reads it correctly
        file_put_contents($psPath, "\xEF\xBB\xBF" . $script);

        $cmd = 'powershell -NonInteractive -ExecutionPolicy Bypass -File "' . $psPath . '" 2>&1';
        exec($cmd, $output, $exitCode);

        @unlink($psPath);

        if ($exitCode !== 0 || !file_exists($pdfPath)) {
            log_message('error', 'PDF Word COM failed (exit=' . $exitCode . '): ' . implode(' | ', $output ?? []));
            return null;
        }

        return $pdfPath;
    }

    /**
     * Build the PowerShell script that opens the Word template,
     * fills in all cells, and exports to PDF.
     */
    private function buildWordComScript(array $eval, string $templatePath, string $pdfPath): string
    {
        $nomor   = (int)($eval['nomor_penilaian'] ?? 1);
        $off     = ($nomor === 2) ? 8 : 0; // table index offset: sheet 2 uses tables 9-16

        // Escape value for use inside a PowerShell single-quoted string
        $ps = fn($v) => str_replace("'", "''", (string)$v);

        $divisi       = $ps($eval['departemen'] ?? '');
        $jabatan      = $ps($eval['posisi'] ?? '');
        $nik          = $ps($eval['nik'] ?? '');
        $nama         = $ps($eval['nama'] ?? '');
        $tglMasuk     = $ps(!empty($eval['mulai_probation'])
                            ? date('d/m/Y', strtotime($eval['mulai_probation'])) : '');
        $tglPenilaian = $ps(date('d/m/Y', strtotime($eval['tanggal_penilaian'])));

        // Map details by category (A→B→C→D)
        $catMap = [
            'A' => 'A. Pengetahuan Akan Tugas (Knowledge)',
            'B' => 'B. Keahlian Kerja (Technical Skill)',
            'C' => 'C. Sikap Kerja (Attitude)',
            'D' => 'D. Kemampuan Diri (Interpersonal Skill)',
        ];
        $scores = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        foreach ($eval['details'] ?? [] as $d) {
            foreach ($catMap as $key => $catName) {
                if ($d['kategori'] === $catName) {
                    $scores[$key][] = (int)$d['nilai'];
                    break;
                }
            }
        }

        $allScores    = array_merge($scores['A'], $scores['B'], $scores['C'], $scores['D']);
        $totalScore   = array_sum($allScores);
        $countAspects = count($allScores);
        $avgScore     = $countAspects > 0
                        ? number_format($totalScore / $countAspects, 2, '.', '')
                        : '0.00';

        $tpl = $ps(str_replace('/', '\\', $templatePath));
        $pdf = $ps(str_replace('/', '\\', $pdfPath));

        $L = [];
        $L[] = '$ErrorActionPreference = "Continue"';
        $L[] = '$word = $null; $doc = $null';
        $L[] = 'try {';
        $L[] = '    $word = New-Object -ComObject Word.Application';
        $L[] = '    $word.Visible = $false';
        $L[] = "    \$doc = \$word.Documents.Open('{$tpl}', \$false, \$false)";
        $L[] = '';
        $L[] = '    function SetCell($tbl, $row, $col, $value) {';
        $L[] = '        try {';
        $L[] = '            $r = $tbl.Cell($row, $col).Range';
        $L[] = '            $r2 = $script:doc.Range($r.Start, [Math]::Max($r.Start, $r.End - 1))';
        $L[] = '            $r2.Text = $value';
        $L[] = '        } catch {}';
        $L[] = '    }';
        $L[] = '    # SetScoreCell: same as SetCell but normalizes number formatting (center, not bold)';
        $L[] = '    function SetScoreCell($tbl, $row, $col, $value) {';
        $L[] = '        try {';
        $L[] = '            $r = $tbl.Cell($row, $col).Range';
        $L[] = '            $r2 = $script:doc.Range($r.Start, [Math]::Max($r.Start, $r.End - 1))';
        $L[] = '            $r2.Text = $value';
        $L[] = '            $r2.ParagraphFormat.Alignment = 1';
        $L[] = '            $r2.Font.Bold = $false';
        $L[] = '        } catch {}';
        $L[] = '    }';
        $L[] = '';
        $L[] = "    \$off = {$off}";
        $L[] = '';
        $L[] = '    # DATA KARYAWAN (Table 1 for ke-1, Table 9 for ke-2)';
        $L[] = '    $t1 = $doc.Tables.Item(1 + $off)';
        $L[] = "    SetCell \$t1 2 3 '{$divisi}'";
        $L[] = "    SetCell \$t1 2 6 '{$jabatan}'";
        $L[] = "    SetCell \$t1 3 3 '{$nik}'";
        $L[] = "    SetCell \$t1 3 6 '{$tglMasuk}'";
        $L[] = "    SetCell \$t1 4 3 '{$nama}'";
        $L[] = "    SetCell \$t1 4 6 '{$tglPenilaian}'";
        $L[] = "    SetCell \$t1 5 3 '{$divisi}'";
        $L[] = "    SetCell \$t1 5 6 'PERCOBAAN'";
        $L[] = '';
        $L[] = '    # A. PENGETAHUAN AKAN TUGAS (Table 2 / 10)';
        $L[] = '    $t2 = $doc.Tables.Item(2 + $off)';
        foreach ($scores['A'] as $i => $s) {
            $L[] = "    SetScoreCell \$t2 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = '';
        $L[] = '    # B. KEAHLIAN KERJA (Table 3 / 11)';
        $L[] = '    $t3 = $doc.Tables.Item(3 + $off)';
        foreach ($scores['B'] as $i => $s) {
            $L[] = "    SetScoreCell \$t3 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = '';
        $L[] = '    # C. SIKAP KERJA (Table 4 / 12)';
        $L[] = '    $t4 = $doc.Tables.Item(4 + $off)';
        foreach ($scores['C'] as $i => $s) {
            $L[] = "    SetScoreCell \$t4 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = '';
        $L[] = '    # D. KEMAMPUAN DIRI (Table 5 / 13)';
        $L[] = '    $t5 = $doc.Tables.Item(5 + $off)';
        foreach ($scores['D'] as $i => $s) {
            $L[] = "    SetScoreCell \$t5 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = '';
        $L[] = '    # TOTAL (Table 6 / 14)';
        $L[] = '    $t6 = $doc.Tables.Item(6 + $off)';
        $L[] = "    SetScoreCell \$t6 1 2 '{$totalScore}'";
        $L[] = "    SetScoreCell \$t6 2 2 '{$avgScore}'";
        $L[] = '';
        // Export only the relevant page (ke-1 → page 1, ke-2 → page 2)
        $L[] = "    \$doc.ExportAsFixedFormat('{$pdf}', 17, \$false, 0, 3, {$nomor}, {$nomor})";
        $L[] = '    $doc.Close($false)';
        $L[] = '    $word.Quit()';
        $L[] = '    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null';
        $L[] = '    Write-Host "PDF_OK"';
        $L[] = '} catch {';
        $L[] = '    Write-Error $_.Exception.Message';
        $L[] = '    try { if ($doc)  { $doc.Close($false) }  } catch {}';
        $L[] = '    try { if ($word) { $word.Quit() }        } catch {}';
        $L[] = '    exit 1';
        $L[] = '}';

        return implode("\r\n", $L);
    }

    // ---------------------------------------------------------------
    // PDF ALL EVALUATIONS (both sheets in one file)
    // ---------------------------------------------------------------

    /**
     * Generate a combined PDF for all evaluations of an employee (both ke-1 and ke-2 sheets).
     */
    public function pdfAll(int $employeeId)
    {
        $role = session()->get('role');
        if (!in_array($role, ['hrd', 'team-leader'])) {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->find($employeeId);
        if (!$employee) {
            return redirect()->back()->with('error', 'Team Member tidak ditemukan');
        }

        if ($role === 'team-leader' && (int)$employee['team_leader_id'] !== (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        $raw1 = $this->evaluationModel->where('employee_id', $employeeId)->where('nomor_penilaian', 1)->first();
        if (!$raw1) {
            return redirect()->back()->with('error', 'Belum ada penilaian untuk Team Member ini');
        }

        $eval1 = $this->evaluationModel->getWithDetails($raw1['id']);
        $eval1['details'] = $this->evaluationDetailModel->where('penilaian_id', $raw1['id'])->findAll();

        $eval2 = null;
        $raw2  = $this->evaluationModel->where('employee_id', $employeeId)->where('nomor_penilaian', 2)->first();
        if ($raw2) {
            $eval2 = $this->evaluationModel->getWithDetails($raw2['id']);
            $eval2['details'] = $this->evaluationDetailModel->where('penilaian_id', $raw2['id'])->findAll();
        }

        $filename   = 'Penilaian_' . ($employee['nik'] ?? $employeeId) . '_semua.pdf';
        $pdfDir     = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'pdfs';
        $cachedPath = $pdfDir . DIRECTORY_SEPARATOR . 'eval_all_' . $employeeId . '.pdf';

        // Serve from cache if available
        if (file_exists($cachedPath)) {
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->setBody(file_get_contents($cachedPath));
        }

        $pdfPath = $this->generatePdfAllViaWordCom($eval1, $eval2);
        if ($pdfPath && file_exists($pdfPath)) {
            if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
            rename($pdfPath, $cachedPath);

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->setBody(file_get_contents($cachedPath));
        }

        return redirect()->back()->with('error', 'Gagal menghasilkan PDF');
    }

    private function generatePdfAllViaWordCom(array $eval1, ?array $eval2): ?string
    {
        $templatePath = ROOTPATH . '27. FORM PENILAIAN PROBATION TEAM MEMBER.doc';
        if (!file_exists($templatePath)) {
            log_message('error', 'PDF-All: Word template not found at ' . $templatePath);
            return null;
        }

        $tmpDir = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $uid     = 'eval_all_' . $eval1['id'] . '_' . uniqid();
        $pdfPath = $tmpDir . DIRECTORY_SEPARATOR . $uid . '.pdf';
        $psPath  = $tmpDir . DIRECTORY_SEPARATOR . $uid . '.ps1';

        $script = $this->buildWordComScriptAll($eval1, $eval2, $templatePath, $pdfPath);
        file_put_contents($psPath, "\xEF\xBB\xBF" . $script);

        $cmd = 'powershell -NonInteractive -ExecutionPolicy Bypass -File "' . $psPath . '" 2>&1';
        exec($cmd, $output, $exitCode);
        @unlink($psPath);

        if ($exitCode !== 0 || !file_exists($pdfPath)) {
            log_message('error', 'PDF-All Word COM failed (exit=' . $exitCode . '): ' . implode(' | ', $output ?? []));
            return null;
        }

        return $pdfPath;
    }

    private function buildWordComScriptAll(array $eval1, ?array $eval2, string $templatePath, string $pdfPath): string
    {
        $ps  = fn($v) => str_replace("'", "''", (string)$v);
        $tpl = $ps(str_replace('/', '\\', $templatePath));
        $pdf = $ps(str_replace('/', '\\', $pdfPath));

        $L   = [];
        $L[] = '$ErrorActionPreference = "Continue"';
        $L[] = '$word = $null; $doc = $null';
        $L[] = 'try {';
        $L[] = '    $word = New-Object -ComObject Word.Application';
        $L[] = '    $word.Visible = $false';
        $L[] = "    \$doc = \$word.Documents.Open('{$tpl}', \$false, \$false)";
        $L[] = '';
        $L[] = '    function SetCell($tbl, $row, $col, $value) {';
        $L[] = '        try {';
        $L[] = '            $r = $tbl.Cell($row, $col).Range';
        $L[] = '            $r2 = $script:doc.Range($r.Start, [Math]::Max($r.Start, $r.End - 1))';
        $L[] = '            $r2.Text = $value';
        $L[] = '        } catch {}';
        $L[] = '    }';
        $L[] = '    function SetScoreCell($tbl, $row, $col, $value) {';
        $L[] = '        try {';
        $L[] = '            $r = $tbl.Cell($row, $col).Range';
        $L[] = '            $r2 = $script:doc.Range($r.Start, [Math]::Max($r.Start, $r.End - 1))';
        $L[] = '            $r2.Text = $value';
        $L[] = '            $r2.ParagraphFormat.Alignment = 1';
        $L[] = '            $r2.Font.Bold = $false';
        $L[] = '        } catch {}';
        $L[] = '    }';

        foreach ([[$eval1, 0], [$eval2, 8]] as [$eval, $off]) {
            if (!$eval) continue;
            $L = array_merge($L, $this->buildSheetLines($eval, $off, $ps));
        }

        $L[] = '';
        $L[] = "    \$doc.ExportAsFixedFormat('{$pdf}', 17)";
        $L[] = '    $doc.Close($false)';
        $L[] = '    $word.Quit()';
        $L[] = '    [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null';
        $L[] = '    Write-Host "PDF_OK"';
        $L[] = '} catch {';
        $L[] = '    Write-Error $_.Exception.Message';
        $L[] = '    try { if ($doc)  { $doc.Close($false) }  } catch {}';
        $L[] = '    try { if ($word) { $word.Quit() }        } catch {}';
        $L[] = '    exit 1';
        $L[] = '}';

        return implode("\r\n", $L);
    }

    private function buildSheetLines(array $eval, int $off, callable $ps): array
    {
        $catMap = [
            'A' => 'A. Pengetahuan Akan Tugas (Knowledge)',
            'B' => 'B. Keahlian Kerja (Technical Skill)',
            'C' => 'C. Sikap Kerja (Attitude)',
            'D' => 'D. Kemampuan Diri (Interpersonal Skill)',
        ];

        $divisi       = $ps($eval['departemen'] ?? '');
        $jabatan      = $ps($eval['posisi'] ?? '');
        $nik          = $ps($eval['nik'] ?? '');
        $nama         = $ps($eval['nama'] ?? '');
        $tglMasuk     = $ps(!empty($eval['mulai_probation']) ? date('d/m/Y', strtotime($eval['mulai_probation'])) : '');
        $tglPenilaian = $ps(date('d/m/Y', strtotime($eval['tanggal_penilaian'])));

        $scores = ['A' => [], 'B' => [], 'C' => [], 'D' => []];
        foreach ($eval['details'] ?? [] as $d) {
            foreach ($catMap as $key => $catName) {
                if ($d['kategori'] === $catName) {
                    $scores[$key][] = (int)$d['nilai'];
                    break;
                }
            }
        }

        $allScores    = array_merge($scores['A'], $scores['B'], $scores['C'], $scores['D']);
        $totalScore   = array_sum($allScores);
        $countAspects = count($allScores);
        $avgScore     = $countAspects > 0 ? number_format($totalScore / $countAspects, 2, '.', '') : '0.00';

        $L   = [];
        $L[] = '';
        $L[] = "    \$t1 = \$doc.Tables.Item(1 + {$off})";
        $L[] = "    SetCell \$t1 2 3 '{$divisi}'";
        $L[] = "    SetCell \$t1 2 6 '{$jabatan}'";
        $L[] = "    SetCell \$t1 3 3 '{$nik}'";
        $L[] = "    SetCell \$t1 3 6 '{$tglMasuk}'";
        $L[] = "    SetCell \$t1 4 3 '{$nama}'";
        $L[] = "    SetCell \$t1 4 6 '{$tglPenilaian}'";
        $L[] = "    SetCell \$t1 5 3 '{$divisi}'";
        $L[] = "    SetCell \$t1 5 6 'PERCOBAAN'";
        $L[] = "    \$t2 = \$doc.Tables.Item(2 + {$off})";
        foreach ($scores['A'] as $i => $s) {
            $L[] = "    SetScoreCell \$t2 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = "    \$t3 = \$doc.Tables.Item(3 + {$off})";
        foreach ($scores['B'] as $i => $s) {
            $L[] = "    SetScoreCell \$t3 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = "    \$t4 = \$doc.Tables.Item(4 + {$off})";
        foreach ($scores['C'] as $i => $s) {
            $L[] = "    SetScoreCell \$t4 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = "    \$t5 = \$doc.Tables.Item(5 + {$off})";
        foreach ($scores['D'] as $i => $s) {
            $L[] = "    SetScoreCell \$t5 " . ($i + 2) . " 3 '{$s}'";
        }
        $L[] = "    \$t6 = \$doc.Tables.Item(6 + {$off})";
        $L[] = "    SetScoreCell \$t6 1 2 '{$totalScore}'";
        $L[] = "    SetScoreCell \$t6 2 2 '{$avgScore}'";

        return $L;
    }

    // ---------------------------------------------------------------
    // DOMPDF FALLBACK
    // ---------------------------------------------------------------

    /**
     * Generate PDF HTML — fallback when Word COM is unavailable
     */
    protected function generatePdfHtml(array $evaluation, array $details, array $categoryScores): string
    {
        $nomorPenilaian = $evaluation['nomor_penilaian'] ?? 1;
        $tanggalPenilaian = !empty($evaluation['tanggal_penilaian'])
            ? date('d/m/Y', strtotime($evaluation['tanggal_penilaian'])) : '-';
        $tanggalMasuk = !empty($evaluation['mulai_probation'])
            ? date('d/m/Y', strtotime($evaluation['mulai_probation'])) : '-';

        $grouped = [];
        foreach ($details as $d) {
            $grouped[$d['kategori']][] = $d;
        }

        $totalNilai = 0;
        $totalAspek = 0;
        foreach ($details as $d) {
            $totalNilai += $d['nilai'];
            $totalAspek++;
        }
        $rataRata = $totalAspek > 0 ? round($totalNilai / $totalAspek, 2) : 0;
        $lulus    = $rataRata >= 6;

        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; margin: 15mm; }
  .page-title { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 4px; }
  .badge { text-align: center; margin-bottom: 8px; }
  .badge span { background: #1a56db; color: #fff; padding: 3px 12px; border-radius: 4px; font-size: 9pt; font-weight: bold; }
  table.dk { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  table.dk td { padding: 4px 6px; border: 1px solid #bbb; font-size: 9.5pt; }
  table.dk td.lbl { font-weight: bold; width: 22%; background: #f0f4ff; }
  .sec { font-size: 10pt; font-weight: bold; background: #1a56db; color: #fff; padding: 4px 8px; margin-top: 10px; }
  table.sc { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
  table.sc th { background: #dce8ff; font-size: 9pt; padding: 4px 6px; border: 1px solid #aaa; text-align: center; }
  table.sc td { padding: 4px 6px; border: 1px solid #ccc; font-size: 9pt; vertical-align: top; }
  .no { text-align: center; width: 5%; }
  .val { text-align: center; width: 10%; font-weight: bold; }
  .ok { color: #0b6e0b; } .lo { color: #c0000a; }
  .tot td { font-weight: bold; background: #f0f4ff; }
  .hasil { text-align: center; font-size: 11pt; font-weight: bold; margin: 8px 0; padding: 6px; border: 2px solid; }
  .hasil.l { border-color: #0b6e0b; color: #0b6e0b; }
  .hasil.t { border-color: #c0000a; color: #c0000a; }
  table.norma { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 9pt; }
  table.norma th { background: #1a56db; color: #fff; padding: 4px 8px; border: 1px solid #aaa; }
  table.norma td { padding: 4px 8px; border: 1px solid #ccc; }
  .ttd { width: 100%; margin-top: 18px; border-collapse: collapse; }
  .ttd td { text-align: center; width: 33%; vertical-align: top; padding: 0 4px; font-size: 9pt; }
  .ttd-line { border-bottom: 1px solid #000; margin: 60px 10px 4px; }
  .pb { page-break-before: always; }
  .hrd-box { border: 1px solid #bbb; padding: 10px; margin-top: 10px; font-size: 9.5pt; }
  .hrd-line { border-bottom: 1px solid #000; display: inline-block; width: 200px; }
</style>
</head>
<body>
<?php
$renderPage = function(int $num, bool $isSheet2) use (
    $evaluation, $grouped, $totalNilai, $totalAspek, $rataRata, $lulus, $tanggalPenilaian, $tanggalMasuk
) {
    $tglMulai   = !empty($evaluation['tanggal_mulai_penilaian'])
                  ? date('d/m/Y', strtotime($evaluation['tanggal_mulai_penilaian'])) : '-';
    $tglSelesai = !empty($evaluation['tanggal_selesai_penilaian'])
                  ? date('d/m/Y', strtotime($evaluation['tanggal_selesai_penilaian'])) : '-';
    ?>
<div class="page-title">FORM PENILAIAN PROBATION TEAM MEMBER</div>
<div class="badge"><span>PENILAIAN KE-<?= $num ?></span></div>
<table class="dk">
  <tr>
    <td class="lbl">DIVISI / DEPT.</td><td><?= htmlspecialchars($evaluation['departemen'] ?? '-') ?></td>
    <td class="lbl">JABATAN</td><td><?= htmlspecialchars($evaluation['posisi'] ?? '-') ?></td>
  </tr>
  <tr>
    <td class="lbl">NIK</td><td><?= htmlspecialchars($evaluation['nik'] ?? '-') ?></td>
    <td class="lbl">TANGGAL MASUK</td><td><?= $tanggalMasuk ?></td>
  </tr>
  <tr>
    <td class="lbl">NAMA</td><td><?= htmlspecialchars($evaluation['nama'] ?? '-') ?></td>
    <td class="lbl">TANGGAL PENILAIAN</td><td><?= $tanggalPenilaian ?></td>
  </tr>
  <tr>
    <td class="lbl">BAGIAN</td><td><?= htmlspecialchars($evaluation['departemen'] ?? '-') ?></td>
    <td class="lbl">STATUS KARYAWAN</td><td>PERCOBAAN</td>
  </tr>
</table>
<p style="font-size:8.5pt;font-style:italic;margin:4px 0 8px;">* DIISI DENGAN NILAI YANG ADA PADA PANDUAN NORMA PENILAIAN (TABEL DIBAWAH)</p>
<?php
$catLabels = [
    'A. Pengetahuan Akan Tugas (Knowledge)' => 'A. PENGETAHUAN AKAN TUGAS (KNOWLEDGE)',
    'B. Keahlian Kerja (Technical Skill)'   => 'B. KEAHLIAN KERJA (TECHNICAL SKILL)',
    'C. Sikap Kerja (Attitude)'              => 'C. SIKAP KERJA (ATTITUDE)',
    'D. Kemampuan Diri (Interpersonal Skill)'=> 'D. KEMAMPUAN DIRI (INTERPERSONAL SKILL)',
];
foreach ($catLabels as $catKey => $catHeader):
    $items = $grouped[$catKey] ?? [];
    ?>
<div class="sec"><?= $catHeader ?></div>
<table class="sc">
  <tr><th class="no">NO.</th><th>DEFINISI</th><th style="width:10%">NILAI</th></tr>
  <?php foreach ($items as $i => $item): $sc = $item['nilai']; ?>
  <tr>
    <td class="no"><?= $i+1 ?></td>
    <td><?= htmlspecialchars($item['aspek']) ?></td>
    <td class="val <?= $sc >= 6 ? 'ok' : 'lo' ?>"><?= $sc ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endforeach; ?>
<table class="sc" style="margin-top:8px;">
  <tr class="tot">
    <td style="text-align:right">TOTAL</td>
    <td class="val"><?= $totalNilai ?></td>
  </tr>
  <tr class="tot">
    <td style="text-align:right">NILAI RATA-RATA = TOTAL NILAI : JUMLAH KRITERIA PENILAIAN</td>
    <td class="val <?= $lulus ? 'ok' : 'lo' ?>"><?= $rataRata ?></td>
  </tr>
</table>
<p style="font-size:8.5pt;font-style:italic;">*) Standar Kelulusan : Nilai Rata-Rata ≥ 6</p>
<table class="norma">
  <tr><th colspan="2">PANDUAN NORMA PENILAIAN</th></tr>
  <tr><td>Performance selalu melebihi harapan dan persyaratan kerja</td><td style="text-align:center;font-weight:bold;width:15%">9 - 10</td></tr>
  <tr><td>Performance memenuhi harapan dan persyaratan kerja</td><td style="text-align:center;font-weight:bold;">7 - 8</td></tr>
  <tr><td>Performance sebagian besar memenuhi harapan dan persyaratan kerja</td><td style="text-align:center;font-weight:bold;">6</td></tr>
  <tr><td>Performance hampir sebagian besar tidak memenuhi harapan dan persyaratan kerja</td><td style="text-align:center;font-weight:bold;">3 - 5</td></tr>
  <tr><td>Performance tidak memenuhi harapan dan persyaratan kerja</td><td style="text-align:center;font-weight:bold;">0 - 2</td></tr>
</table>
<?php if ($isSheet2): ?>
<div class="hrd-box">
  <strong>(Diisi oleh Dept. HRD)</strong><br>
  Memperhatikan penilaian tersebut di atas, maka karyawan tersebut dipertimbangkan dan atau diputuskan untuk :<br><br>
  Diangkat sebagai karyawan tetap per tanggal &nbsp;: <span class="hrd-line"></span><br><br>
  Diakhiri masa kerjanya per tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="hrd-line"></span><br><br>
  Lain-lain &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="hrd-line"></span>
</div>
<?php endif; ?>
<table class="ttd">
  <tr>
    <td><div style="font-weight:bold;">PENILAI / ATASAN LANGSUNG</div><div class="ttd-line"></div><div>Date : (………………………………….……………)</div></td>
    <td><div style="font-weight:bold;">MENGETAHUI / MENYETUJUI</div><div class="ttd-line"></div><div>Date : (………………………………….……………)</div></td>
    <td><div style="font-weight:bold;">YANG DINILAI</div><div class="ttd-line"></div><div>Date : (………………………………….……………)</div></td>
  </tr>
</table>
<?php
};

$renderPage($nomorPenilaian, $nomorPenilaian >= 2);
if ($nomorPenilaian == 1) {
    echo '<div class="pb"></div>';
    $renderPage(2, true);
}
?>
</body>
</html>
<?php
        return ob_get_clean();
    }

    // ---------------------------------------------------------------
    // CSV EXPORTS
    // ---------------------------------------------------------------

    public function exportCsv()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $evaluations = $this->evaluationModel
            ->select('penilaian.*, employees.nama, employees.nik, users.nama as team_leader_nama')
            ->join('employees', 'penilaian.employee_id = employees.id')
            ->join('users', 'penilaian.team_leader_id = users.id')
            ->orderBy('penilaian.tanggal_penilaian', 'DESC')
            ->findAll();

        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evaluations_' . date('Y-m-d_H-i-s') . '.csv"');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['NIK', 'Nama Team Member', 'Team Leader', 'Tanggal Penilaian', 'Nilai Total', 'Status'], ';');
        foreach ($evaluations as $e) {
            fputcsv($output, [$e['nik'], $e['nama'], $e['team_leader_nama'], $e['tanggal_penilaian'], $e['nilai_total'], $e['status']], ';');
        }
        fclose($output);
        exit;
    }

    public function exportEmployeesCsv()
    {
        if (session()->get('role') !== 'hrd') {
            return redirect()->to('/auth/login');
        }

        $employees = $this->employeeModel
            ->select('employees.*, users.nama as team_leader_nama')
            ->join('users', 'employees.team_leader_id = users.id', 'left')
            ->findAll();

        $output = fopen('php://output', 'w');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d_H-i-s') . '.csv"');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['NIK', 'Nama', 'Departemen', 'Posisi', 'Email', 'Team Leader', 'Status Probation', 'Mulai Probation', 'Akhir Probation'], ';');
        foreach ($employees as $emp) {
            fputcsv($output, [
                $emp['nik'], $emp['nama'], $emp['departemen'], $emp['posisi'],
                $emp['email'], $emp['team_leader_nama'] ?? '-', $emp['status'],
                $emp['mulai_probation'], $emp['akhir_probation'],
            ], ';');
        }
        fclose($output);
        exit;
    }
}
