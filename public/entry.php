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

<?php if (isset($_GET['success'])): ?>
    <div class="notification-toast notification-success">
        <i class="fas fa-check-circle mr-2"></i>
        Logbook entry berhasil disimpan!
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="notification-toast notification-error">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo esc_html($_GET['error']); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header text-base">
        Add Log Book Entry
    </div>
    
    <form action="add_entry_action.php" method="POST" class="modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-3">
                <div>
                    <label class="form-label text-xs">Log Book Code</label>
                    <input type="text" name="log_book_code" value="<?php echo esc_html($log_book_code); ?>" class="form-input text-xs bg-gray-100" readonly>
                </div>
                <div>
                    <label for="instrument" class="form-label text-xs">Instrument *</label>
                    <select id="instrument" name="instrument_id" class="form-select text-xs" required>
                        <option value="">Pilih Instrument</option>
                        <?php foreach ($instruments as $instrument): ?>
                            <option value="<?php echo (int)$instrument['id']; ?>">
                                <?php echo esc_html($instrument['name'] . ' (' . $instrument['code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="start_date" class="form-label text-xs">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-input text-xs" required>
                    </div>
                    <div>
                        <label for="start_time" class="form-label text-xs">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" class="form-input text-xs" required>
                    </div>
                </div>
                <div>
                    <label for="sample_name" class="form-label text-xs">Sample Name</label>
                    <input type="text" id="sample_name" name="sample_name" class="form-input text-xs" placeholder="Enter sample name">
                </div>
                <div>
                    <label for="trial_code" class="form-label text-xs">Trial Code</label>
                    <input type="text" id="trial_code" name="trial_code" class="form-input text-xs" placeholder="Enter trial code">
                </div>
                
                <?php foreach (['Column', 'MobilePhase', 'ElectrodeType', 'Result', 'WavelengthScan'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="form-label text-xs"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="form-input text-xs" placeholder="Enter <?php echo strtolower(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-3">
                <?php foreach (['Lamp', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity', 'Speed', 'Diluent'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="form-label text-xs"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="form-input text-xs" placeholder="Enter <?php echo strtolower(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <div class="md:col-span-2 space-y-3 pt-4 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div>
                        <label for="finish_date" class="form-label text-xs">Finish Date</label>
                        <input type="date" id="finish_date" name="finish_date" class="form-input text-xs">
                    </div>
                    <div>
                        <label for="finish_time" class="form-label text-xs">Finish Time</label>
                        <input type="time" id="finish_time" name="finish_time" class="form-input text-xs">
                    </div>
                    <div>
                        <label for="condition_after" class="form-label text-xs">Condition After</label>
                        <select id="condition_after" name="condition_after" class="form-select text-xs">
                            <option value="">Choose condition</option>
                            <option value="Good">Good</option>
                            <option value="Need Maintenance">Need Maintenance</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="form-label text-xs">Status</label>
                        <select id="status" name="status" class="form-select text-xs">
                            <option value="Not Complete">Not Complete</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="remark" class="form-label text-xs">Remark</label>
                    <textarea id="remark" name="remark" rows="2" class="form-textarea text-xs" placeholder="Add any remarks or notes"></textarea>
                </div>
                <div>
                    <label for="activity" class="form-label text-xs">Activity Summary *</label>
                    <textarea id="activity" name="activity" rows="3" class="form-textarea text-xs" required placeholder="Describe the activity performed"></textarea>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <a href="index.php" class="btn btn-secondary text-xs">
                <i class="fas fa-times mr-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-success text-xs">
                <i class="fas fa-save mr-1"></i>Save Entry
            </button>
        </div>
    </form>
</div>

<div id="field-info" class="card mt-4 hidden">
    <div class="p-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900 flex items-center">
            <i class="fas fa-info-circle text-primary mr-2"></i>
            Active Fields for Selected Instrument
        </h3>
    </div>
    <div id="field-info-content" class="p-3 text-xs">
        </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const instrumentSelect = document.getElementById('instrument');
    const INSTRUMENT_MATRIX = <?php echo json_encode($instrument_matrix_data); ?>;
    const ALL_FIELDS = <?php echo json_encode($parameter_columns); ?>;
    const fieldInfo = document.getElementById('field-info');
    const fieldInfoContent = document.getElementById('field-info-content');
    
    function updateFormFields(instrumentId) {
        ALL_FIELDS.forEach(field => {
            const group = document.getElementById(`field-group-${field}`);
            if (group) {
                group.classList.add('hidden');
                const input = group.querySelector('input, select, textarea');
                if (input) input.value = '';
            }
        });

        if (instrumentId && INSTRUMENT_MATRIX[instrumentId]) {
            const activeFields = INSTRUMENT_MATRIX[instrumentId].fields;
            activeFields.forEach(field => {
                const group = document.getElementById(`field-group-${field}`);
                if (group) group.classList.remove('hidden');
            });
            showFieldInformation(instrumentSelect.options[instrumentSelect.selectedIndex].text, activeFields);
        } else {
            fieldInfo.classList.add('hidden');
        }
    }
    
    function showFieldInformation(instrumentName, activeFields) {
        if (activeFields.length > 0) {
            const fieldNames = {
                'MobilePhase': 'Mobile Phase', 'Speed': 'Speed (RPM)', 'ElectrodeType': 'Electrode Type',
                'Result': 'Result/Analysis', 'WavelengthScan': 'Wavelength Scan', 'Diluent': 'Diluent',
                'Lamp': 'Lamp Type', 'Column': 'Column Specification', 'Apparatus': 'Apparatus Type',
                'Medium': 'Medium/Buffer', 'TotalVolume': 'Total Volume', 'VesselQuantity': 'Vessel Quantity'
            };
            const fieldsHtml = activeFields.map(field => `<span class="badge badge-info text-xs">${fieldNames[field] || field}</span>`).join(' ');
            fieldInfoContent.innerHTML = `
                <div class="mb-1"><strong class="text-primary text-xs">${instrumentName}</strong></div>
                <div class="mb-1 text-xs text-gray-600">The following fields are required/relevant for this instrument:</div>
                <div class="flex flex-wrap gap-1">${fieldsHtml}</div>
            `;
            fieldInfo.classList.remove('hidden');
        }
    }
    
    function setCurrentDateTime() {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
        document.getElementById('start_date').value = dateStr;
        document.getElementById('start_time').value = timeStr;
    }
    
    instrumentSelect.addEventListener('change', (e) => updateFormFields(e.target.value));
    
    setCurrentDateTime();
    updateFormFields(instrumentSelect.value);
    
    const notifications = document.querySelectorAll('.notification-toast');
    notifications.forEach(notification => {
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>