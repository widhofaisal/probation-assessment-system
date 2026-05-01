<?php $this->extend('layouts/main'); $this->section('content'); ?>

<?php
$deptOptions = ['Production', 'Quality Control', 'Warehouse', 'Maintenance', 'Administration', 'Finance', 'Human Resources'];
$bulanId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>

<!-- Filters, Search, and Actions -->
<div class="mb-6 bg-white rounded-xl shadow p-5">
    <div class="flex flex-col md:flex-row gap-3 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Cari Karyawan</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Cari NIK atau nama..."
                       class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
        </div>
        <div class="w-full md:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Departemen</label>
            <select id="departmentFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                <option value="">Semua Departemen</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['departemen']) ?>" <?= $selectedDepartment === $dept['departemen'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['departemen']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
            <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                <option value="">Semua Status</option>
                <option value="pending"     <?= $selectedStatus === 'pending'     ? 'selected' : '' ?>>Pending</option>
                <option value="lulus"       <?= $selectedStatus === 'lulus'       ? 'selected' : '' ?>>Lulus</option>
                <option value="tidak-lulus" <?= $selectedStatus === 'tidak-lulus' ? 'selected' : '' ?>>Tidak Lulus</option>
                <option value="warning"     <?= $selectedStatus === 'warning'     ? 'selected' : '' ?>>Warning</option>
            </select>
        </div>
        <button onclick="openAddModal()" class="flex-shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> Tambah Karyawan
        </button>
    </div>
</div>

<!-- Employees Table -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900">Daftar Karyawan Probation</h3>
        <span id="rowCount" class="text-sm text-gray-500"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full" id="employeeTable">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">NIK</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Departemen</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Posisi</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Mulai Probation</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody id="employeeBody">
                <?php if (!empty($employees)): ?>
                    <?php foreach ($employees as $emp):
                        $monthNum = (int) date('n', strtotime($emp['mulai_probation']));
                        $tglProbation = date('d', strtotime($emp['mulai_probation'])) . ' ' . $bulanId[$monthNum] . ' ' . date('Y', strtotime($emp['mulai_probation']));
                    ?>
                        <tr class="border-b hover:bg-gray-50 transition employee-row"
                            data-id="<?= $emp['id'] ?>"
                            data-nik="<?= strtolower($emp['nik']) ?>"
                            data-nama="<?= strtolower($emp['nama']) ?>"
                            data-dept="<?= htmlspecialchars($emp['departemen']) ?>"
                            data-status="<?= htmlspecialchars($emp['status']) ?>"
                            data-posisi="<?= htmlspecialchars($emp['posisi']) ?>"
                            data-email="<?= htmlspecialchars($emp['email'] ?? '') ?>"
                            data-tanggal_masuk="<?= htmlspecialchars($emp['tanggal_masuk'] ?? '') ?>"
                            data-mulai_probation="<?= htmlspecialchars($emp['mulai_probation'] ?? '') ?>"
                            data-team_leader_id="<?= htmlspecialchars($emp['team_leader_id'] ?? '') ?>">
                            <td class="px-6 py-4 font-medium text-gray-900 text-sm"><?= htmlspecialchars($emp['nik']) ?></td>
                            <td class="px-6 py-4 text-gray-800 text-sm"><?= htmlspecialchars($emp['nama']) ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($emp['departemen']) ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?= htmlspecialchars($emp['posisi']) ?></td>
                            <td class="px-6 py-4 text-gray-600 text-sm"><?= $tglProbation ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                    <?php
                                    if ($emp['status'] === 'pending') echo 'bg-yellow-100 text-yellow-700';
                                    elseif ($emp['status'] === 'lulus') echo 'bg-green-100 text-green-700';
                                    elseif ($emp['status'] === 'tidak-lulus') echo 'bg-red-100 text-red-700';
                                    else echo 'bg-orange-100 text-orange-700';
                                    ?>">
                                    <?= ucfirst(str_replace('-', ' ', $emp['status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-3">
                                    <button onclick="openEditModal(<?= $emp['id'] ?>)"
                                            class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center gap-1 transition">
                                        <i class="fas fa-edit text-xs"></i> Edit
                                    </button>
                                    <button onclick="confirmDelete(<?= $emp['id'] ?>, '<?= htmlspecialchars(addslashes($emp['nama'])) ?>')"
                                            class="text-red-500 hover:text-red-700 font-medium text-sm flex items-center gap-1 transition">
                                        <i class="fas fa-trash text-xs"></i> Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="emptyRow">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-users text-3xl mb-3 block"></i>
                            Tidak ada karyawan dengan filter yang dipilih
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="noSearchResult" class="hidden px-6 py-10 text-center text-gray-400">
            <i class="fas fa-search text-3xl mb-3 block"></i>
            Tidak ada karyawan yang cocok dengan pencarian
        </div>
    </div>
</div>

<!-- ========== MODAL TAMBAH KARYAWAN ========== -->
<div id="addModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto modal-enter">
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-user-plus text-blue-600"></i> Tambah Karyawan Baru
            </h3>
            <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="addEmployeeForm" action="/employees/store" method="POST" class="p-6 space-y-5" novalidate>
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">NIK <span class="text-red-500">*</span></label>
                    <input type="text" name="nik" id="a_nik" placeholder="Contoh: EMP010"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_nik">NIK wajib diisi (min. 3 karakter)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="a_nama" placeholder="Nama lengkap karyawan"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_nama">Nama wajib diisi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Departemen <span class="text-red-500">*</span></label>
                    <select name="departemen" id="a_departemen"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                        <option value="">-- Pilih Departemen --</option>
                        <?php foreach ($deptOptions as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_departemen">Departemen wajib dipilih</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Posisi <span class="text-red-500">*</span></label>
                    <input type="text" name="posisi" id="a_posisi" placeholder="Contoh: Operator Produksi"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_posisi">Posisi wajib diisi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="a_email" placeholder="email@perusahaan.com"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_email">Format email tidak valid</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" id="a_tanggal_masuk"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mulai Probation <span class="text-red-500">*</span></label>
                    <input type="date" name="mulai_probation" id="a_mulai_probation"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="aerr_probation">Tanggal mulai probation wajib diisi</p>
                    <p class="text-xs text-gray-400 mt-1">Akhir probation otomatis dihitung 90 hari sejak tanggal ini</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Team Leader</label>
                    <select name="team_leader_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                        <option value="">-- Pilih Team Leader (opsional) --</option>
                        <?php foreach ($teamLeaders as $leader): ?>
                            <option value="<?= $leader['id'] ?>">
                                <?= htmlspecialchars($leader['nama']) ?> (<?= htmlspecialchars($leader['departemen']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <i class="fas fa-info-circle text-amber-500 mt-0.5 flex-shrink-0"></i>
                <p class="text-sm text-amber-800">
                    <strong>Catatan:</strong> Masa probation biasanya berlangsung selama 3 bulan. Pastikan data yang diisi sudah benar sebelum menyimpan.
                </p>
            </div>
            <div class="flex gap-3 pt-2 border-t">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Karyawan
                </button>
                <button type="button" onclick="closeAddModal()" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========== MODAL EDIT KARYAWAN ========== -->
<div id="editModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeEditModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto modal-enter">
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-user-edit text-blue-600"></i> Edit Data Karyawan
            </h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editEmployeeForm" action="" method="POST" class="p-6 space-y-5" novalidate>
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">NIK</label>
                    <input type="text" id="e_nik" name="nik"
                           class="w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-gray-500 text-sm cursor-not-allowed" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="e_nama"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="eerr_nama">Nama wajib diisi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Departemen <span class="text-red-500">*</span></label>
                    <select name="departemen" id="e_departemen"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                        <option value="">-- Pilih Departemen --</option>
                        <?php foreach ($deptOptions as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hidden text-xs text-red-500 mt-1" id="eerr_departemen">Departemen wajib dipilih</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Posisi <span class="text-red-500">*</span></label>
                    <input type="text" name="posisi" id="e_posisi"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="eerr_posisi">Posisi wajib diisi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="e_email"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="eerr_email">Format email tidak valid</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" id="e_tanggal_masuk"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Mulai Probation <span class="text-red-500">*</span></label>
                    <input type="date" name="mulai_probation" id="e_mulai_probation"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                    <p class="hidden text-xs text-red-500 mt-1" id="eerr_probation">Tanggal mulai probation wajib diisi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Status Probation <span class="text-red-500">*</span></label>
                    <select name="status" id="e_status"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                        <option value="pending">Pending</option>
                        <option value="lulus">Lulus</option>
                        <option value="tidak-lulus">Tidak Lulus</option>
                        <option value="warning">Warning</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Team Leader</label>
                    <select name="team_leader_id" id="e_team_leader_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm transition">
                        <option value="">-- Pilih Team Leader (opsional) --</option>
                        <?php foreach ($teamLeaders as $leader): ?>
                            <option value="<?= $leader['id'] ?>">
                                <?= htmlspecialchars($leader['nama']) ?> (<?= htmlspecialchars($leader['departemen']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2 border-t">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ---- Add Modal ----
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('addEmployeeForm').reset();
    clearAddErrors();
}

// ---- Edit Modal ----
function openEditModal(id) {
    const row = document.querySelector('tr[data-id="' + id + '"]');
    if (!row) return;

    document.getElementById('editEmployeeForm').action = '/employees/' + id;
    document.getElementById('e_nik').value           = row.querySelector('td:nth-child(1)').textContent.trim();
    document.getElementById('e_nama').value          = row.dataset.nama ? row.querySelector('td:nth-child(2)').textContent.trim() : '';
    document.getElementById('e_posisi').value        = row.dataset.posisi || '';
    document.getElementById('e_email').value         = row.dataset.email || '';
    document.getElementById('e_tanggal_masuk').value = row.dataset.tanggal_masuk || '';
    document.getElementById('e_mulai_probation').value = row.dataset.mulai_probation || '';

    const dept = row.dataset.dept || '';
    const deptSel = document.getElementById('e_departemen');
    for (let i = 0; i < deptSel.options.length; i++) {
        deptSel.options[i].selected = deptSel.options[i].value === dept;
    }

    const status = row.dataset.status || 'pending';
    const statSel = document.getElementById('e_status');
    for (let i = 0; i < statSel.options.length; i++) {
        statSel.options[i].selected = statSel.options[i].value === status;
    }

    const tlId = row.dataset.team_leader_id || '';
    const tlSel = document.getElementById('e_team_leader_id');
    for (let i = 0; i < tlSel.options.length; i++) {
        tlSel.options[i].selected = tlSel.options[i].value === tlId;
    }

    clearEditErrors();
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
    clearEditErrors();
}

// ---- ESC closes any open modal ----
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeAddModal(); closeEditModal(); }
});

// ---- Add Form Validation ----
function setFieldError(inputId, errId, show) {
    const el = document.getElementById(inputId);
    const er = document.getElementById(errId);
    if (er) er.classList.toggle('hidden', !show);
    if (el) { el.classList.toggle('border-red-400', show); el.classList.toggle('border-gray-300', !show); }
}
function clearAddErrors() {
    [['a_nik','aerr_nik'],['a_nama','aerr_nama'],['a_departemen','aerr_departemen'],['a_posisi','aerr_posisi'],['a_email','aerr_email'],['a_mulai_probation','aerr_probation']]
        .forEach(function(p) { setFieldError(p[0], p[1], false); });
}
function clearEditErrors() {
    [['e_nama','eerr_nama'],['e_departemen','eerr_departemen'],['e_posisi','eerr_posisi'],['e_email','eerr_email'],['e_mulai_probation','eerr_probation']]
        .forEach(function(p) { setFieldError(p[0], p[1], false); });
}

document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    clearAddErrors();
    var ok = true;
    var nik = document.getElementById('a_nik').value.trim();
    if (nik.length < 3) { setFieldError('a_nik', 'aerr_nik', true); ok = false; }
    if (!document.getElementById('a_nama').value.trim()) { setFieldError('a_nama', 'aerr_nama', true); ok = false; }
    if (!document.getElementById('a_departemen').value) { setFieldError('a_departemen', 'aerr_departemen', true); ok = false; }
    if (!document.getElementById('a_posisi').value.trim()) { setFieldError('a_posisi', 'aerr_posisi', true); ok = false; }
    var email = document.getElementById('a_email').value.trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setFieldError('a_email', 'aerr_email', true); ok = false; }
    if (!document.getElementById('a_mulai_probation').value) { setFieldError('a_mulai_probation', 'aerr_probation', true); ok = false; }
    if (!ok) e.preventDefault();
});

document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
    clearEditErrors();
    var ok = true;
    if (!document.getElementById('e_nama').value.trim()) { setFieldError('e_nama', 'eerr_nama', true); ok = false; }
    if (!document.getElementById('e_departemen').value) { setFieldError('e_departemen', 'eerr_departemen', true); ok = false; }
    if (!document.getElementById('e_posisi').value.trim()) { setFieldError('e_posisi', 'eerr_posisi', true); ok = false; }
    var email = document.getElementById('e_email').value.trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setFieldError('e_email', 'eerr_email', true); ok = false; }
    if (!document.getElementById('e_mulai_probation').value) { setFieldError('e_mulai_probation', 'eerr_probation', true); ok = false; }
    if (!ok) e.preventDefault();
});

// ---- Search ----
document.getElementById('searchInput').addEventListener('input', filterTable);

// ---- Dept/Status server filters ----
document.getElementById('departmentFilter').addEventListener('change', function() {
    var url = new URL(window.location);
    this.value ? url.searchParams.set('department', this.value) : url.searchParams.delete('department');
    window.location = url.toString();
});
document.getElementById('statusFilter').addEventListener('change', function() {
    var url = new URL(window.location);
    this.value ? url.searchParams.set('status', this.value) : url.searchParams.delete('status');
    window.location = url.toString();
});

function filterTable() {
    var q = document.getElementById('searchInput').value.trim().toLowerCase();
    var rows = document.querySelectorAll('.employee-row');
    var visible = 0;
    rows.forEach(function(row) {
        var match = !q || row.dataset.nik.includes(q) || row.dataset.nama.includes(q);
        row.classList.toggle('hidden', !match);
        if (match) visible++;
    });
    document.getElementById('noSearchResult').classList.toggle('hidden', visible > 0 || rows.length === 0);
    document.getElementById('rowCount').textContent = q ? (visible + ' dari ' + rows.length + ' karyawan') : (rows.length + ' karyawan');
}

// ---- Delete ----
function confirmDelete(id, nama) {
    showConfirm('Hapus karyawan "' + nama + '"? Data yang sudah dihapus tidak bisa dikembalikan.', function() {
        fetch('/employees/' + id, { method: 'DELETE' })
            .then(function(r) {
                if (r.ok) {
                    showToast('Karyawan berhasil dihapus.', 'success');
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    showToast('Gagal menghapus karyawan.', 'error');
                }
            })
            .catch(function() { showToast('Terjadi kesalahan jaringan.', 'error'); });
    });
}

filterTable();
</script>

<?php $this->endSection(); ?>
