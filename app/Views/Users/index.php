<?php $this->extend('layouts/main'); $this->section('content'); ?>

<?php
$deptOptions = ['Human Resources', 'Produksi', 'Quality Control', 'Warehouse', 'Maintenance', 'Administrasi', 'Finance'];
?>

<!-- Header & Tambah -->
<div class="mb-6 bg-white rounded-xl shadow p-5">
    <div class="flex flex-col md:flex-row gap-3 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Cari User</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Cari NIK atau nama..."
                       class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
        </div>
        <button onclick="openAddModal()" class="flex-shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> Tambah User
        </button>
    </div>
</div>

<!-- Flash Messages -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl text-sm">
        <i class="fas fa-check-circle mr-2"></i><?= session()->getFlashdata('success') ?>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i><?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

<!-- Tabel -->
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h3 class="text-lg font-bold text-gray-900">Daftar HRD & Team Leader</h3>
        <span id="rowCount" class="text-sm text-gray-500"></span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full" id="usersTable">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">NIK</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Departemen</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="border-b hover:bg-gray-50 transition" data-search="<?= strtolower(htmlspecialchars($user['nik'] . ' ' . $user['nama'])) ?>">
                            <td class="px-6 py-4 text-sm font-mono text-gray-700"><?= htmlspecialchars($user['nik']) ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($user['nama']) ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold
                                    <?= $user['role'] === 'hrd' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                    <?= $user['role'] === 'hrd' ? 'HRD' : 'Team Leader' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($user['departemen'] ?? '-') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if ($user['id'] !== session()->get('user_id')): ?>
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)"
                                                class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold rounded-lg transition">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>
                                        <button onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nik'], ENT_QUOTES) ?>')"
                                                class="px-3 py-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-lg transition">
                                            <i class="fas fa-key mr-1"></i>Reset
                                        </button>
                                        <button onclick="hapusUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama'], ENT_QUOTES) ?>')"
                                                class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg transition">
                                            <i class="fas fa-trash mr-1"></i>Hapus
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Akun Anda</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-400">
                            <i class="fas fa-users text-3xl mb-3 block text-gray-300"></i>
                            Belum ada user
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Tambah User</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form action="/users/store" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
                    <input type="text" name="nik" required placeholder="Contoh: TL003"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    <p class="text-xs text-gray-400 mt-1">Password default = NIK</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="team-leader">Team Leader</option>
                        <option value="hrd">HRD</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama" required placeholder="Nama lengkap"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" placeholder="email@perusahaan.com"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                    <select name="departemen" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($deptOptions as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi</label>
                    <input type="text" name="posisi" placeholder="Contoh: Supervisor"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('addModal')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Batal</button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">Edit User</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIK <span class="text-red-500">*</span></label>
                    <input type="text" name="nik" id="e_nik" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select name="role" id="e_role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="team-leader">Team Leader</option>
                        <option value="hrd">HRD</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama" id="e_nama" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="e_email"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                    <select name="departemen" id="e_departemen" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                        <option value="">-- Pilih --</option>
                        <?php foreach ($deptOptions as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Posisi</label>
                    <input type="text" name="posisi" id="e_posisi"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('editModal')"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">Batal</button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let count = 0;
    document.querySelectorAll('#usersTable tbody tr[data-search]').forEach(function (row) {
        const match = row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) count++;
    });
    document.getElementById('rowCount').textContent = count + ' user';
});

// Init count
document.addEventListener('DOMContentLoaded', function () {
    const total = document.querySelectorAll('#usersTable tbody tr[data-search]').length;
    document.getElementById('rowCount').textContent = total + ' user';
});

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function openEditModal(user) {
    document.getElementById('editForm').action = '/users/' + user.id;
    document.getElementById('e_nik').value        = user.nik;
    document.getElementById('e_nama').value       = user.nama;
    document.getElementById('e_email').value      = user.email || '';
    document.getElementById('e_posisi').value     = user.posisi || '';

    const roleSel = document.getElementById('e_role');
    for (let i = 0; i < roleSel.options.length; i++)
        roleSel.options[i].selected = roleSel.options[i].value === user.role;

    const deptSel = document.getElementById('e_departemen');
    for (let i = 0; i < deptSel.options.length; i++)
        deptSel.options[i].selected = deptSel.options[i].value === (user.departemen || '');

    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}

function resetPassword(id, nik) {
    showConfirm('Reset password ' + nik + '? Password akan direset ke NIK: ' + nik, function () {
        fetch('/users/' + id + '/reset-password', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
            body: JSON.stringify({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) showToast(data.message, 'success');
            else showToast(data.error, 'error');
        });
    });
}

function hapusUser(id, nama) {
    showConfirm('Hapus user ' + nama + '?', function () {
        fetch('/users/' + id, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) location.reload();
            else showToast(data.error, 'error');
        });
    });
}

// Tutup modal klik di luar
['addModal', 'editModal'].forEach(function (id) {
    document.getElementById(id).addEventListener('click', function (e) {
        if (e.target === this) closeModal(id);
    });
});
</script>

<?php $this->endSection(); ?>
