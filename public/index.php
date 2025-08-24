<?php
// public/index.php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Data untuk statistik
$total_logs = $pdo->query("SELECT COUNT(*) FROM logbook_entries")->fetchColumn();
$total_instruments = $pdo->query("SELECT COUNT(*) FROM instruments")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$last_log_date = $pdo->query("SELECT MAX(entry_date) FROM logbook_entries")->fetchColumn();

// Data untuk tabel log terbaru (diperbanyak menjadi 10)
$stmt = $pdo->query("SELECT le.*, u.name as user_name, i.name as instrument_name FROM logbook_entries le JOIN users u ON le.user_id = u.id JOIN instruments i ON le.instrument_id = i.id ORDER BY le.entry_date DESC LIMIT 10");
$logbook_entries = $stmt->fetchAll();

// Data untuk form
$instruments = $pdo->query("SELECT id, name FROM instruments ORDER BY name ASC")->fetchAll();
$matrix_stmt = $pdo->query("SELECT * FROM instruments");
$instrument_matrix_data = [];
$parameter_columns = ['MobilePhase', 'Speed', 'ElectrodeType', 'Result', 'WavelengthScan', 'Diluent', 'Lamp', 'Column', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity'];
foreach ($matrix_stmt->fetchAll() as $instrument) {
    $active_fields = [];
    foreach ($parameter_columns as $param) { if ($instrument[$param]) $active_fields[] = $param; }
    $instrument_matrix_data[$instrument['id']] = ['fields' => $active_fields];
}
?>

<!-- KPI Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-card p-6 rounded-xl border border-border"><div class="flex justify-between items-start"><div><p class="text-sm text-muted-foreground">Total Log Book</p><p class="text-2xl font-bold mt-2"><?php echo (int)$total_logs; ?></p></div><div class="bg-primary/10 p-2 rounded-md"><i data-lucide="book-marked" class="w-5 h-5 text-primary"></i></div></div></div>
    <div class="bg-card p-6 rounded-xl border border-border"><div class="flex justify-between items-start"><div><p class="text-sm text-muted-foreground">Total Instrument</p><p class="text-2xl font-bold mt-2"><?php echo (int)$total_instruments; ?></p></div><div class="bg-primary/10 p-2 rounded-md"><i data-lucide="beaker" class="w-5 h-5 text-primary"></i></div></div></div>
    <div class="bg-card p-6 rounded-xl border border-border"><div class="flex justify-between items-start"><div><p class="text-sm text-muted-foreground">Total Pengguna</p><p class="text-2xl font-bold mt-2"><?php echo (int)$total_users; ?></p></div><div class="bg-primary/10 p-2 rounded-md"><i data-lucide="users" class="w-5 h-5 text-primary"></i></div></div></div>
    <div class="bg-card p-6 rounded-xl border border-border"><div class="flex justify-between items-start"><div><p class="text-sm text-muted-foreground">Log Terakhir</p><p class="text-2xl font-bold mt-2"><?php echo $last_log_date ? date('d M Y', strtotime($last_log_date)) : 'N/A'; ?></p></div><div class="bg-primary/10 p-2 rounded-md"><i data-lucide="calendar-clock" class="w-5 h-5 text-primary"></i></div></div></div>
</div>

<!-- Log Terbaru Table -->
<div class="bg-card p-6 rounded-xl border border-border">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-foreground">Log Book Terbaru</h3>
        <button id="newLogbookBtn" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg flex items-center gap-2 hover:bg-blue-600/90 transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i><span>Baru</span>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="border-b border-border">
                <tr>
                    <th class="text-left text-sm font-medium text-muted-foreground p-3">Tanggal</th>
                    <th class="text-left text-sm font-medium text-muted-foreground p-3">User</th>
                    <th class="text-left text-sm font-medium text-muted-foreground p-3">Instrumen</th>
                    <th class="text-left text-sm font-medium text-muted-foreground p-3">Aktivitas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logbook_entries)): ?>
                    <tr><td colspan="4" class="p-4 text-center text-muted-foreground">Belum ada data.</td></tr>
                <?php else: ?>
                    <?php foreach ($logbook_entries as $entry): ?>
                    <tr class="border-b border-border last:border-b-0">
                        <td class="p-3 text-sm text-foreground whitespace-nowrap"><?php echo esc_html(date('d M Y, H:i', strtotime($entry['entry_date']))); ?></td>
                        <td class="p-3 text-sm text-foreground whitespace-nowrap"><?php echo esc_html($entry['user_name']); ?></td>
                        <td class="p-3 text-sm text-foreground whitespace-nowrap"><?php echo esc_html($entry['instrument_name']); ?></td>
                        <td class="p-3 text-sm text-foreground"><?php echo esc_html($entry['activity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Modal Form Logbook Baru -->
<div id="logbookModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4">
    <div class="bg-card rounded-xl border border-border shadow-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6"><h3 class="text-lg font-semibold text-foreground">Buat Log Book Baru</h3><button id="closeModalBtn" class="text-muted-foreground hover:text-foreground"><i data-lucide="x" class="w-5 h-5"></i></button></div>
        <form id="logbookForm" action="add_log_action.php" method="POST"><input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>"><div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div class="col-span-1 md:col-span-2"><label for="instrument" class="block text-sm font-medium text-muted-foreground mb-1">Pilih Instrumen</label><select id="instrument" name="instrument_id" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" required><option value="">-- Pilih Instrumen --</option><?php foreach ($instruments as $instrument): ?><option value="<?php echo (int)$instrument['id']; ?>"><?php echo esc_html($instrument['name']); ?></option><?php endforeach; ?></select></div><?php foreach ($parameter_columns as $param): ?><div id="field-group-<?php echo esc_html($param); ?>" class="hidden"><label for="param-<?php echo esc_html($param); ?>" class="block text-sm font-medium text-muted-foreground mb-1"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label><input type="text" id="param-<?php echo esc_html($param); ?>" name="params[<?php echo esc_html($param); ?>]" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"></div><?php endforeach; ?><div class="col-span-1 md:col-span-2"><label for="activity" class="block text-sm font-medium text-muted-foreground mb-1">Aktivitas</label><textarea id="activity" name="activity" rows="4" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" required></textarea></div></div><div class="mt-6 flex justify-end space-x-3"><button type="button" id="cancelBtn" class="px-4 py-2 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors">Batal</button><button type="submit" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-600/90 transition-colors">Simpan</button></div></form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('logbookModal'), newLogbookBtn = document.getElementById('newLogbookBtn'), closeModalBtn = document.getElementById('closeModalBtn'), cancelBtn = document.getElementById('cancelBtn'), instrumentSelect = document.getElementById('instrument');
    function showModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
    function hideModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    newLogbookBtn.addEventListener('click', showModal); closeModalBtn.addEventListener('click', hideModal); cancelBtn.addEventListener('click', hideModal);
    const INSTRUMENT_MATRIX = <?php echo json_encode($instrument_matrix_data); ?>, ALL_FIELDS = <?php echo json_encode($parameter_columns); ?>;
    function updateFormFields(instrumentId) { ALL_FIELDS.forEach(field => { const group = document.getElementById(`field-group-${field}`); if (group) group.classList.add('hidden'); }); if (instrumentId && INSTRUMENT_MATRIX[instrumentId]) { INSTRUMENT_MATRIX[instrumentId].fields.forEach(field => { const group = document.getElementById(`field-group-${field}`); if (group) group.classList.remove('hidden'); }); } }
    instrumentSelect.addEventListener('change', (e) => updateFormFields(e.target.value)); updateFormFields(instrumentSelect.value);
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
