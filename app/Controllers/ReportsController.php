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

    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Generate PDF — always produces the full 2-page employee form (ke-1 + ke-2).
     * Delegates to pdfAll() so the download is always the complete probation document.
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

        if ($role === 'probationary-employee') {
            $self = $this->employeeModel->findByNik(session()->get('nik'));
            if (!$self || (int)$evaluation['employee_id'] !== (int)$self['id']) {
                return redirect()->back()->with('error', 'Akses ditolak');
            }
        }

        return $this->pdfAll((int)$evaluation['employee_id']);
    }

    // ---------------------------------------------------------------
    // WORD COM PDF GENERATION
    // ---------------------------------------------------------------

    /**
     * Fill the Word template with evaluation data and export to PDF.
     * Returns the local PDF path on success, null on failure.
     * Only called on Windows — caller must guard with isWindows().
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
        if (!in_array($role, ['hrd', 'team-leader', 'probationary-employee'])) {
            return redirect()->to('/auth/login');
        }

        $employee = $this->employeeModel->find($employeeId);
        if (!$employee) {
            return redirect()->back()->with('error', 'Team Member tidak ditemukan');
        }

        if ($role === 'team-leader' && (int)$employee['team_leader_id'] !== (int)session()->get('user_id')) {
            return redirect()->back()->with('error', 'Akses ditolak');
        }

        if ($role === 'probationary-employee') {
            $self = $this->employeeModel->findByNik(session()->get('nik'));
            if (!$self || (int)$self['id'] !== $employeeId) {
                return redirect()->back()->with('error', 'Akses ditolak');
            }
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

        // --- Primary: Word COM via PowerShell (Windows only) ---
        if ($this->isWindows()) {
            $pdfPath = $this->generatePdfAllViaWordCom($eval1, $eval2);
            if ($pdfPath && file_exists($pdfPath)) {
                if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
                rename($pdfPath, $cachedPath);

                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->setBody(file_get_contents($cachedPath));
            }
        }

        // --- Fallback: DomPDF ---
        $grouped1 = $this->evaluationDetailModel->getByEvaluationGrouped($raw1['id']);
        $grouped2 = $raw2 ? $this->evaluationDetailModel->getByEvaluationGrouped($raw2['id']) : null;
        $html     = $this->generatePdfAllHtml($eval1, $eval2, $grouped1, $grouped2);

        $opts = new \Dompdf\Options();
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($opts);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
        file_put_contents($cachedPath, $pdfContent);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setBody($pdfContent);
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
    // DOMPDF HTML GENERATION
    // ---------------------------------------------------------------

    private function pdfCss(): string
    {
        return '<style>
@page { margin:10mm 12mm 10mm 12mm; }
* { box-sizing:border-box; }
body { font-family:Arial,Helvetica,sans-serif; font-size:8pt; color:#000; margin:0; padding:0; }

/* FORM HEADER */
table.fh { width:100%; border-collapse:collapse; margin-bottom:4px; }
table.fh td { border:1px solid #000; padding:2px 5px; vertical-align:middle; font-size:7.5pt; }
.fh-logo { background:#D94F00; color:#fff; font-size:12pt; font-weight:bold; font-style:italic;
           text-align:center; width:55px; letter-spacing:1pt; }
.fh-form { text-align:center; font-weight:bold; font-size:8.5pt; width:52px; }
.fh-key  { font-weight:bold; font-size:7pt; white-space:nowrap; }
.fh-val  { font-size:7pt; }
.fh-title { text-align:center; font-weight:bold; font-size:9pt; background:#ddd; padding:3px 0; }

/* DATA KARYAWAN */
.dk-title { text-align:center; font-weight:bold; font-size:8.5pt; margin:5px 0 3px; }
table.dk  { width:100%; border-collapse:collapse; margin-bottom:3px; }
table.dk td { padding:1px 3px; font-size:8pt; border:none; }
.dk-lbl { width:17%; white-space:nowrap; }
.dk-sep { width:2%; text-align:center; }
.dk-val { width:31%; }

/* NOTE */
p.note { font-size:7pt; font-style:italic; margin:2px 0 3px; }

/* TWO-COLUMN WRAPPER */
table.tc { width:100%; border-collapse:collapse; }
table.tc td { vertical-align:top; padding:0; }

/* CATEGORY HEADER */
p.cat { font-weight:bold; font-size:7.5pt; margin:4px 0 1px; text-transform:uppercase; }

/* SCORE TABLE */
table.sc { width:100%; border-collapse:collapse; margin-bottom:2px; }
table.sc th { border:1px solid #000; background:#f0f0f0; padding:1px 3px; font-size:7pt; text-align:center; font-weight:bold; }
table.sc td { border:1px solid #000; padding:1px 3px; font-size:7pt; vertical-align:top; }
.sno  { text-align:center; width:16px; }
.snl  { text-align:center; width:28px; }

/* TOTAL */
table.tot { width:100%; border-collapse:collapse; margin-top:3px; }
table.tot td { border:1px solid #000; padding:2px 5px; font-weight:bold; font-size:8pt; }
.tlb { text-align:center; }
.tva { text-align:center; width:36px; }

/* PANDUAN NORMA */
table.nm { width:100%; border-collapse:collapse; margin-top:3px; }
table.nm td { border:1px solid #000; padding:2px 4px; font-size:7.5pt; }
.nhd  { font-weight:bold; background:#ddd; text-align:center; }
.nhd2 { font-weight:bold; background:#f0f0f0; }
.nva  { text-align:center; width:44px; white-space:nowrap; font-weight:bold; }

/* HRD BOX */
.hrd-box { border:1px solid #000; padding:6px 8px; margin-top:5px; font-size:8pt; line-height:1.8; }
.hrd-line { display:inline-block; width:148px; border-bottom:1px solid #000; }

/* SIGNATURES */
table.sg { width:100%; border-collapse:collapse; margin-top:8px; }
table.sg td { text-align:center; font-size:7.5pt; width:33%; padding:0 3px; vertical-align:top; border:none; }

/* PEDOMAN */
.pd-box  { border:1px solid #000; padding:5px 6px; margin-left:3px; }
.pd-ttl  { font-weight:bold; font-size:7.5pt; text-align:center; margin-bottom:4px; }
.pd-item { font-size:6.8pt; margin-bottom:4px; line-height:1.4; text-align:justify; }

/* PAGE BREAK */
.page-break { page-break-before:always; }
</style>';
    }

    /**
     * Render one sheet (ke-1 or ke-2) of the evaluation form as HTML.
     */
    protected function generatePdfHtmlSheet(array $evaluation, array $grouped, int $nomorSheet): string
    {
        $isSheet2 = $nomorSheet >= 2;

        $tglPenilaian = !empty($evaluation['tanggal_penilaian'])
            ? date('d/m/Y', strtotime($evaluation['tanggal_penilaian'])) : '-';
        $tglMasuk = !empty($evaluation['mulai_probation'])
            ? date('d/m/Y', strtotime($evaluation['mulai_probation'])) : '-';

        // $grouped is a flat array of rows from getByEvaluationGrouped()
        $totalNilai = 0;
        $totalAspek = 0;
        foreach ($grouped as $row) {
            $totalNilai += (int)($row['nilai'] ?? 0);
            $totalAspek++;
        }
        $rataRata = $totalAspek > 0 ? number_format($totalNilai / $totalAspek, 2) : '0.00';

        // Group flat rows by kategori for renderPdfPage
        $groupedByKat = [];
        foreach ($grouped as $row) {
            $groupedByKat[$row['kategori'] ?? 'Lainnya'][] = $row;
        }

        $catLabels = [
            'A. Pengetahuan Akan Tugas (Knowledge)'  => 'A. Pengetahuan Akan Tugas (Knowledge)',
            'B. Keahlian Kerja (Technical Skill)'    => 'B. Keahlian Kerja (Technical Skill)',
            'C. Sikap Kerja (Attitude)'               => 'C. Sikap Kerja (Attitude)',
            'D. Kemampuan Diri (Interpersonal Skill)' => 'D. Kemampuan Diri (Interpersonal Skill)',
        ];

        $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        ob_start(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><?= $this->pdfCss() ?></head><body>
<?= $this->renderPdfPage($evaluation, $groupedByKat, $nomorSheet, $isSheet2, $tglPenilaian, $tglMasuk, $totalNilai, $rataRata, $catLabels) ?>
</body></html>
<?php
        return ob_get_clean();
    }

    /**
     * Render combined PDF for all evaluations (both sheets).
     */
    protected function generatePdfAllHtml(array $eval1, ?array $eval2, array $grouped1, ?array $grouped2): string
    {
        $tglPenilaian1 = !empty($eval1['tanggal_penilaian'])
            ? date('d/m/Y', strtotime($eval1['tanggal_penilaian'])) : '-';
        $tglMasuk1 = !empty($eval1['mulai_probation'])
            ? date('d/m/Y', strtotime($eval1['mulai_probation'])) : '-';

        $total1 = 0; $count1 = 0;
        foreach ($grouped1 as $row) { $total1 += (int)($row['nilai'] ?? 0); $count1++; }
        $rata1 = $count1 > 0 ? number_format($total1 / $count1, 2) : '0.00';

        $groupedByKat1 = [];
        foreach ($grouped1 as $row) { $groupedByKat1[$row['kategori'] ?? 'Lainnya'][] = $row; }

        $catLabels = [
            'A. Pengetahuan Akan Tugas (Knowledge)'  => 'A. Pengetahuan Akan Tugas (Knowledge)',
            'B. Keahlian Kerja (Technical Skill)'    => 'B. Keahlian Kerja (Technical Skill)',
            'C. Sikap Kerja (Attitude)'               => 'C. Sikap Kerja (Attitude)',
            'D. Kemampuan Diri (Interpersonal Skill)' => 'D. Kemampuan Diri (Interpersonal Skill)',
        ];

        ob_start(); ?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><?= $this->pdfCss() ?></head><body>
<?= $this->renderPdfPage($eval1, $groupedByKat1, 1, false, $tglPenilaian1, $tglMasuk1, $total1, $rata1, $catLabels) ?>
<?php
        if ($eval2 && $grouped2) {
            $tglPenilaian2 = !empty($eval2['tanggal_penilaian'])
                ? date('d/m/Y', strtotime($eval2['tanggal_penilaian'])) : '-';
            $tglMasuk2 = !empty($eval2['mulai_probation'])
                ? date('d/m/Y', strtotime($eval2['mulai_probation'])) : '-';
            $total2 = 0; $count2 = 0;
            foreach ($grouped2 as $row) { $total2 += (int)($row['nilai'] ?? 0); $count2++; }
            $rata2 = $count2 > 0 ? number_format($total2 / $count2, 2) : '0.00';
            $groupedByKat2 = [];
            foreach ($grouped2 as $row) { $groupedByKat2[$row['kategori'] ?? 'Lainnya'][] = $row; }
            echo '<div class="page-break"></div>';
            echo $this->renderPdfPage($eval2, $groupedByKat2, 2, true, $tglPenilaian2, $tglMasuk2, $total2, $rata2, $catLabels);
        }
?>
</body></html>
<?php
        return ob_get_clean();
    }

    /**
     * Render one page of the PDF form (used by both single and combined PDF).
     */
    private function renderPdfPage(
        array $evaluation, array $grouped, int $nomorSheet, bool $isSheet2,
        string $tglPenilaian, string $tglMasuk, int $totalNilai, string $rataRata,
        array $catLabels
    ): string {
        $h      = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $halaman = $isSheet2 ? '02/03' : '01/03';
        $title   = 'PENILAIAN ' . $nomorSheet . ' MASA PERCOBAAN TEAM MEMBER';
        $dots    = '&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;&#x2026;';

        ob_start(); ?>
<!-- FORM HEADER -->
<table class="fh">
  <tr>
    <td class="fh-logo" rowspan="2">SUMBER</td>
    <td class="fh-form" rowspan="2">FORMULIR</td>
    <td class="fh-key">No. Dokumen :</td>
    <td class="fh-key">Halaman</td>
    <td class="fh-val">: <?= $halaman ?></td>
  </tr>
  <tr>
    <td class="fh-val">SMJ-F-RSC-HRD-027</td>
    <td class="fh-key">No./Tgl Efektif :</td>
    <td class="fh-val">05/05 Maret 2025</td>
  </tr>
  <tr>
    <td colspan="5" class="fh-title"><?= $title ?></td>
  </tr>
</table>

<!-- DATA KARYAWAN -->
<p class="dk-title">DATA KARYAWAN</p>
<table class="dk">
  <tr>
    <td class="dk-lbl">DIVISI / DEPT.</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $h($evaluation['departemen'] ?? '') ?></td>
    <td class="dk-lbl">JABATAN</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $h($evaluation['posisi'] ?? '') ?></td>
  </tr>
  <tr>
    <td class="dk-lbl">NIK</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $h($evaluation['nik'] ?? '') ?></td>
    <td class="dk-lbl">TANGGAL MASUK</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $tglMasuk ?></td>
  </tr>
  <tr>
    <td class="dk-lbl">NAMA</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $h($evaluation['nama'] ?? '') ?></td>
    <td class="dk-lbl">TANGGAL PENILAIAN</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $tglPenilaian ?></td>
  </tr>
  <tr>
    <td class="dk-lbl">BAGIAN</td><td class="dk-sep">:</td>
    <td class="dk-val"><?= $h($evaluation['departemen'] ?? '') ?></td>
    <td class="dk-lbl">STATUS KARYAWAN</td><td class="dk-sep">:</td>
    <td class="dk-val">PERCOBAAN</td>
  </tr>
</table>
<p class="note">* DIISI DENGAN NILAI YANG ADA PADA PANDUAN NORMA PENILAIAN (TABEL DIBAWAH)</p>

<!-- TWO-COLUMN: score tables (left) + pedoman (right) -->
<table class="tc">
  <tr>
    <td style="width:72%;padding-right:2px;vertical-align:top;">
<?php foreach ($catLabels as $catKey => $catHeader):
    $items = $grouped[$catKey] ?? []; ?>
      <p class="cat"><?= $h($catHeader) ?></p>
      <table class="sc">
        <tr>
          <th class="sno">NO.</th>
          <th style="text-align:left;padding-left:3px;">DEFINISI</th>
          <th style="width:28px;">NILAI</th>
        </tr>
<?php foreach ($items as $i => $item): ?>
        <tr>
          <td class="sno"><?= $i + 1 ?></td>
          <td><?= $h($item['aspek']) ?></td>
          <td class="snl"><?= (int)$item['nilai'] ?></td>
        </tr>
<?php endforeach; ?>
      </table>
<?php endforeach; ?>
    </td>
    <td style="width:28%;vertical-align:top;">
      <div class="pd-box">
        <p class="pd-ttl">PEDOMAN PEMBERIAN PENILAIAN</p>
        <p class="pd-item">1. Pelajari dahulu <u>Panduan Aspek dan Kategori Penilaian Masa Percobaan Karyawan Baru</u> yang telah dibagikan.</p>
        <p class="pd-item">2. Isi <i>form</i> sesuai data-data kinerja &amp; data-data pendukung lainya sesuai kondisi yang sesungguhnya.</p>
        <p class="pd-item">3. Tulis dengan <u>jelas</u> angka nilai pada kolom nilai sesuai kategori penilaian yang tercantum di form ini.</p>
        <p class="pd-item">4. Hubungi HRD Dept. jika Anda mengalami kesulitan dalam mengisi formulir ini</p>
      </div>
    </td>
  </tr>
</table>

<!-- TOTAL -->
<table class="tot">
  <tr>
    <td class="tlb">TOTAL</td>
    <td class="tva"><?= $totalNilai ?></td>
  </tr>
  <tr>
    <td class="tlb">NILAI RATA-RATA = TOTAL NILAI : JUMLAH KRITERIA PENILAIAN</td>
    <td class="tva"><?= $rataRata ?></td>
  </tr>
</table>
<p class="note">*) Standar Kelulusan : Nilai Rata-Rata &ge; 6</p>

<!-- PANDUAN NORMA PENILAIAN -->
<table class="nm">
  <tr><td colspan="2" class="nhd">PANDUAN NORMA PENILAIAN</td></tr>
  <tr>
    <td class="nhd2" style="text-align:left;">KETERANGAN</td>
    <td class="nhd2 nva">NILAI</td>
  </tr>
  <tr>
    <td><i>Performance</i> <b>selalu melebihi</b> harapan dan persyaratan kerja</td>
    <td class="nva">9 - 10</td>
  </tr>
  <tr>
    <td><i>Performance</i> <b>memenuhi</b> harapan dan persyaratan kerja</td>
    <td class="nva">7 - 8</td>
  </tr>
  <tr>
    <td><i>Performance</i> <b>sebagian besar</b> memenuhi harapan dan persyaratan kerja</td>
    <td class="nva">6</td>
  </tr>
  <tr>
    <td><i>Performance</i> <b>hampir sebagian besar</b> tidak memenuhi harapan dan persyaratan kerja</td>
    <td class="nva">3 - 5</td>
  </tr>
  <tr>
    <td><i>Performance</i> <b>tidak memenuhi</b> harapan dan persyaratan kerja</td>
    <td class="nva">0 - 2</td>
  </tr>
</table>

<?php if ($isSheet2): ?>
<div class="hrd-box">
  <strong>(Diisi oleh Dept. HRD)</strong><br>
  Memperhatikan penilaian tersebut di atas, maka karyawan tersebut dipertimbangkan dan atau diputuskan untuk :<br>
  Diangkat sebagai karyawan tetap per tanggal &nbsp;: <span class="hrd-line"></span><br>
  Diakhiri masa kerjanya per tanggal &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="hrd-line"></span><br>
  Lain-lain &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="hrd-line"></span>
</div>
<?php endif; ?>

<!-- SIGNATURES -->
<table class="sg">
  <tr>
    <td><strong>PENILAI / ATASAN LANGSUNG</strong></td>
    <td><strong>MENGETAHUI / MENYETUJUI</strong></td>
    <td><strong>YANG DINILAI</strong></td>
  </tr>
  <tr>
    <td>
      Date :<br><br><br><br><br>
      (<?= $dots ?>)
    </td>
    <td>
      Date :<br><br><br><br><br>
      (<?= $dots ?>)
    </td>
    <td>
      Date :<br><br><br><br><br>
      (<?= $dots ?>)
    </td>
  </tr>
</table>
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
