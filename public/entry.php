<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Ambil daftar instrumen untuk dropdown, termasuk kode
$instruments = $pdo->query("SELECT id, name, code FROM instruments ORDER BY name ASC")->fetchAll();

// Buat Log Book Code otomatis (contoh sederhana)
$last_id_stmt = $pdo->query("SELECT MAX(id) FROM logbook_entries");
$next_id = ($last_id_stmt->fetchColumn() ?: 0) + 1;
$log_book_code = 'RD/ME/' . str_pad($next_id, 3, '0', STR_PAD_LEFT);

// Ambil matriks instrumen untuk JavaScript
$matrix_stmt = $pdo->query("SELECT * FROM instruments");
$instrument_matrix_data = [];
$parameter_columns = ['MobilePhase', 'Speed', 'ElectrodeType', 'Result', 'WavelengthScan', 'Diluent', 'Lamp', 'Column', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity'];
foreach ($matrix_stmt->fetchAll() as $instrument) {
    $active_fields = [];
    foreach ($parameter_columns as $param) { if ($instrument[$param]) $active_fields[] = $param; }
    $instrument_matrix_data[$instrument['id']] = ['fields' => $active_fields];
}
?>

<div class="bg-card p-6 rounded-xl border border-border shadow-lg">
    <h2 class="text-xl font-bold text-foreground mb-6 border-b border-border pb-4">Add Log Book</h2>
    
    <form action="add_entry_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-muted-foreground">Log Book Code</label>
                    <input type="text" name="log_book_code" value="<?php echo esc_html($log_book_code); ?>" class="mt-1 w-full bg-muted/50 border-border rounded-lg px-4 py-2 text-sm" readonly>
                </div>
                <div>
                    <label for="instrument" class="block text-sm font-medium text-muted-foreground">Instrument</label>
                    <select id="instrument" name="instrument_id" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" required>
                        <option value="">Pilih Instrument</option>
                        <?php foreach ($instruments as $instrument): ?>
                            <option value="<?php echo (int)$instrument['id']; ?>">
                                <?php echo esc_html($instrument['name'] . ' (' . $instrument['code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-muted-foreground">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm" required>
                    </div>
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-muted-foreground">Start Time</label>
                        <input type="time" id="start_time" name="start_time" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm" required>
                    </div>
                </div>
                <div>
                    <label for="sample_name" class="block text-sm font-medium text-muted-foreground">Sample Name</label>
                    <input type="text" id="sample_name" name="sample_name" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                </div>
                 <div>
                    <label for="trial_code" class="block text-sm font-medium text-muted-foreground">Trial Code</label>
                    <input type="text" id="trial_code" name="trial_code" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                </div>
                <!-- Dynamic Fields will appear here -->
                <?php foreach (['Column', 'MobilePhase', 'ElectrodeType', 'Result', 'WavelengthScan'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="block text-sm font-medium text-muted-foreground"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <!-- Dynamic Fields will appear here -->
                 <?php foreach (['Lamp', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity', 'Speed', 'Diluent'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="block text-sm font-medium text-muted-foreground"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Full Width Bottom Section -->
            <div class="md:col-span-2 space-y-4 border-t border-border pt-6">
                 <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="finish_date" class="block text-sm font-medium text-muted-foreground">Finish Date</label>
                        <input type="date" id="finish_date" name="finish_date" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                    </div>
                    <div>
                        <label for="finish_time" class="block text-sm font-medium text-muted-foreground">Finish Time</label>
                        <input type="time" id="finish_time" name="finish_time" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                    </div>
                     <div>
                        <label for="condition_after" class="block text-sm font-medium text-muted-foreground">Condition After</label>
                        <select id="condition_after" name="condition_after" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                            <option value="">Choose</option>
                            <option value="Good">Good</option>
                            <option value="Need Maintenance">Need Maintenance</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="remark" class="block text-sm font-medium text-muted-foreground">Remark</label>
                    <textarea id="remark" name="remark" rows="3" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm"></textarea>
                </div>
                 <div>
                    <label for="activity" class="block text-sm font-medium text-muted-foreground">Activity Summary</label>
                    <textarea id="activity" name="activity" rows="3" class="mt-1 w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm" required></textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex justify-end gap-4 border-t border-border pt-6">
            <a href="index.php" class="px-4 py-2 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors">Cancel</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium bg-green-600 text-white rounded-lg flex items-center gap-2 hover:bg-green-600/90 transition-colors">
                <i data-lucide="save" class="w-4 h-4"></i>
                Save
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const instrumentSelect = document.getElementById('instrument');
    const INSTRUMENT_MATRIX = <?php echo json_encode($instrument_matrix_data); ?>;
    const ALL_FIELDS = <?php echo json_encode($parameter_columns); ?>;
    
    function updateFormFields(instrumentId) {
        ALL_FIELDS.forEach(field => {
            const group = document.getElementById(`field-group-${field}`);
            if (group) group.classList.add('hidden');
        });

        if (instrumentId && INSTRUMENT_MATRIX[instrumentId]) {
            INSTRUMENT_MATRIX[instrumentId].fields.forEach(field => {
                const group = document.getElementById(`field-group-${field}`);
                if (group) group.classList.remove('hidden');
            });
        }
    }
    instrumentSelect.addEventListener('change', (e) => updateFormFields(e.target.value));
    updateFormFields(instrumentSelect.value);
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
