<?php

/**
 * Penanda akses Team Member terhadap hasil penilaiannya.
 *
 * Satu sumber tampilan untuk semua halaman (HRD, Team Leader, Team Member)
 * supaya arti warnanya tidak pernah berbeda antar halaman:
 *
 *   abu   = belum dibuka sama sekali
 *   biru  = sudah dilihat nilainya
 *   hijau = sudah diunduh PDF-nya (otomatis berarti sudah dilihat)
 */

if (!function_exists('ack_state')) {
    /**
     * Tingkat akses tertinggi yang sudah dicapai: 'diunduh' | 'dilihat' | 'belum'.
     */
    function ack_state(?array $evaluation): string
    {
        if (!empty($evaluation['diunduh_at'])) {
            return 'diunduh';
        }

        if (!empty($evaluation['dilihat_at'])) {
            return 'dilihat';
        }

        return 'belum';
    }
}

if (!function_exists('ack_badge')) {
    /**
     * Chip status untuk HRD / Team Leader — ringkas, dengan tanggal lengkap
     * di tooltip supaya kolom tabel tidak melebar.
     */
    function ack_badge(?array $evaluation): string
    {
        $state = ack_state($evaluation);

        [$class, $icon, $label, $stamp] = match ($state) {
            'diunduh' => ['bg-green-50 text-green-700 border-green-200', 'fa-circle-check', 'Diunduh',       $evaluation['diunduh_at']],
            'dilihat' => ['bg-blue-50 text-blue-700 border-blue-200',    'fa-eye',          'Dilihat',       $evaluation['dilihat_at']],
            default   => ['bg-gray-50 text-gray-500 border-gray-200',    'fa-circle',       'Belum dilihat', null],
        };

        $teks    = $stamp ? $label . ' ' . date('d/m', strtotime($stamp)) : $label;
        $tooltip = $stamp
            ? $label . ' oleh Team Member pada ' . date('d/m/Y H:i', strtotime($stamp))
            : 'Team Member belum pernah membuka hasil penilaian ini';

        return '<span class="inline-flex items-center gap-1 px-2 py-0.5 border rounded-full text-xs font-medium whitespace-nowrap ' . $class . '"'
             . ' title="' . htmlspecialchars($tooltip, ENT_QUOTES) . '">'
             . '<i class="fas ' . $icon . ' text-[10px]"></i>' . htmlspecialchars($teks)
             . '</span>';
    }
}

if (!function_exists('ack_badge_member')) {
    /**
     * Chip yang sama untuk Team Member sendiri, tapi berbunyi seperti tanda
     * terima ("Sudah Anda unduh"), bukan seperti laporan pengawasan.
     *
     * Status "belum dilihat" tidak pernah muncul di sini: begitu halamannya
     * terbuka, penilaiannya sudah tercatat dilihat.
     */
    function ack_badge_member(?array $evaluation): string
    {
        if (!empty($evaluation['diunduh_at'])) {
            return '<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-xs font-medium">'
                 . '<i class="fas fa-circle-check"></i>Sudah Anda unduh · ' . date('d/m/Y H:i', strtotime($evaluation['diunduh_at']))
                 . '</span>';
        }

        return '<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-medium">'
             . '<i class="fas fa-download"></i>Belum Anda unduh'
             . '</span>';
    }
}

if (!function_exists('ack_unviewed_count')) {
    /**
     * Berapa penilaian milik Team Member yang sedang login yang belum pernah
     * dibuka — dipakai layout untuk titik merah di menu "Hasil Evaluasi".
     *
     * Dipanggil dari layout supaya setiap controller tidak perlu ikut
     * menyiapkan datanya; hanya berjalan untuk role Team Member.
     */
    function ack_unviewed_count(): int
    {
        if (session()->get('role') !== 'probationary-employee') {
            return 0;
        }

        $employee = model('EmployeeModel')->findByNik(session()->get('nik'));

        if (!$employee) {
            return 0;
        }

        return model('EvaluationModel')
            ->where('employee_id', $employee['id'])
            ->where('dilihat_at', null)
            ->countAllResults();
    }
}
