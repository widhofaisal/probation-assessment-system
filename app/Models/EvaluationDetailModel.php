<?php

namespace App\Models;

use CodeIgniter\Model;

class EvaluationDetailModel extends Model
{
    protected $table = 'penilaian_detail';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['penilaian_id', 'kategori', 'aspek', 'nilai', 'alasan', 'created_at'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'penilaian_id' => 'required|integer',
        'kategori' => 'required|min_length[3]|max_length[100]',
        'aspek' => 'required|min_length[3]|max_length[255]',
        'nilai' => 'required|integer|greater_than_equal_to[1]|less_than_equal_to[10]',
        'alasan' => 'permit_empty',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Get all details for an evaluation
     */
    public function getByEvaluation(int $evaluationId)
    {
        return $this->where('penilaian_id', $evaluationId)->findAll();
    }

    /**
     * Get details grouped by category
     */
    public function getByEvaluationGrouped(int $evaluationId)
    {
        return $this->where('penilaian_id', $evaluationId)
            ->orderBy('kategori', 'ASC')
            ->orderBy('aspek', 'ASC')
            ->findAll();
    }

    /**
     * Get average score by category
     */
    public function getCategoryAverages(int $evaluationId)
    {
        $db = \Config\Database::connect();
        return $db->table('penilaian_detail')
            ->selectAvg('nilai', 'average')
            ->select('kategori')
            ->where('penilaian_id', $evaluationId)
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all details with low scores (< 6) for a specific evaluation
     */
    public function getLowScores(int $evaluationId)
    {
        return $this->where('penilaian_id', $evaluationId)
            ->where('nilai <', 6)
            ->findAll();
    }

    /**
     * Bulk insert details
     */
    public function insertDetails(int $evaluationId, array $details)
    {
        $data = [];
        foreach ($details as $detail) {
            $data[] = [
                'penilaian_id' => $evaluationId,
                'kategori' => $detail['kategori'],
                'aspek' => $detail['aspek'],
                'nilai' => $detail['nilai'],
                'alasan' => $detail['alasan'] ?? null,
            ];
        }
        return $this->insertBatch($data);
    }

    /**
     * Standard evaluation aspects/categories (sesuai Form Penilaian Probation)
     */
    public static function getStandardAspects()
    {
        return [
            'A. Pengetahuan Akan Tugas (Knowledge)' => [
                'Pengetahuan tentang penggunaan & pemeliharaan perangkat kerja (tools) e.g. mesin, komputer dll.',
                'Mengerti & memahami prosedur kerja standar (SOP) yang harus dijalankan.',
                'Mengerti & memahami standar kualitas kerja yang diterapkan perusahaan.',
                'Mengetahui proses pembuatan sepatu secara umum.',
            ],
            'B. Keahlian Kerja (Technical Skill)' => [
                'Keahlian dalam menjalankan fungsi kerja utama (e.g. cutting, sewing dll.).',
                'Mampu mengoperasikan perangkat kerja (tools) e.g. mesin, kuas, lem dll.',
                'Bekerja sesuai dengan prosedur kerja standar (SOP) dengan benar/secara keseluruhan.',
                'Bekerja secara cepat & teliti sesuai dengan target (kuantitas dan kualitas).',
                'Mampu memenuhi standar kualitas kerja yang diterapkan oleh perusahaan.',
                'Pengelolaan & pemeliharaan perangkat kerja (tools) e.g. mesin, kuas, lem dll.',
            ],
            'C. Sikap Kerja (Attitude)' => [
                'Mampu menjalankan disiplin kerja yang ada di departemen (e.g. jam kerja, seragam, APD dll.).',
                'Memiliki sikap dan perilaku kerja yang sesuai dengan NCOC.',
                'Menunjukkan sikap tidak mudah menyerah dalam menghadapi kesulitan saat bekerja sehari-hari.',
                'Jujur dalam menjalankan tugasnya.',
            ],
            'D. Kemampuan Diri (Interpersonal Skill)' => [
                'Mampu bersosialisasi & bekerja sama dengan rekan kerja yang lain.',
                'Berani mengungkapkan pendapat kepada orang lain, baik rekan kerja ataupun atasan.',
                'Bersedia menerima masukan dan pendapat dari orang lain, baik rekan kerja atau atasan.',
            ],
        ];
    }
}
