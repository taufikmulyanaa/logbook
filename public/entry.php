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

<!-- Success/Error Messages -->
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
    <div class="card-header">
        Add Log Book Entry
    </div>
    
    <form action="add_entry_action.php" method="POST" class="modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div class="space-y-4">
                <div>
                    <label class="form-label">Log Book Code</label>
                    <input type="text" name="log_book_code" value="<?php echo esc_html($log_book_code); ?>" class="form-input bg-gray-100" readonly>
                </div>
                <div>
                    <label for="instrument" class="form-label">Instrument *</label>
                    <select id="instrument" name="instrument_id" class="form-select" required>
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
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" class="form-input" required>
                    </div>
                    <div>
                        <label for="start_time" class="form-label">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" class="form-input" required>
                    </div>
                </div>
                <div>
                    <label for="sample_name" class="form-label">Sample Name</label>
                    <input type="text" id="sample_name" name="sample_name" class="form-input" placeholder="Enter sample name">
                </div>
                <div>
                    <label for="trial_code" class="form-label">Trial Code</label>
                    <input type="text" id="trial_code" name="trial_code" class="form-input" placeholder="Enter trial code">
                </div>
                
                <!-- Dynamic Parameter Fields - Left Column -->
                <?php foreach (['Column', 'MobilePhase', 'ElectrodeType', 'Result', 'WavelengthScan'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="form-label"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="form-input" placeholder="Enter <?php echo strtolower(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Column -->
            <div class="space-y-4">
                <!-- Dynamic Parameter Fields - Right Column -->
                <?php foreach (['Lamp', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity', 'Speed', 'Diluent'] as $param): ?>
                <div id="field-group-<?php echo esc_html($param); ?>" class="hidden">
                    <label class="form-label"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></label>
                    <input type="text" name="params[<?php echo esc_html($param); ?>]" class="form-input" placeholder="Enter <?php echo strtolower(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Full Width Bottom Section -->
            <div class="md:col-span-2 space-y-4 pt-6 border-t border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="finish_date" class="form-label">Finish Date</label>
                        <input type="date" id="finish_date" name="finish_date" class="form-input">
                    </div>
                    <div>
                        <label for="finish_time" class="form-label">Finish Time</label>
                        <input type="time" id="finish_time" name="finish_time" class="form-input">
                    </div>
                    <div>
                        <label for="condition_after" class="form-label">Condition After</label>
                        <select id="condition_after" name="condition_after" class="form-select">
                            <option value="">Choose condition</option>
                            <option value="Good">Good</option>
                            <option value="Need Maintenance">Need Maintenance</option>
                            <option value="Broken">Broken</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="remark" class="form-label">Remark</label>
                    <textarea id="remark" name="remark" rows="3" class="form-textarea" placeholder="Add any remarks or notes"></textarea>
                </div>
                <div>
                    <label for="activity" class="form-label">Activity Summary *</label>
                    <textarea id="activity" name="activity" rows="4" class="form-textarea" required placeholder="Describe the activity performed"></textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="modal-footer">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save mr-2"></i>Save Entry
            </button>
        </div>
    </form>
</div>

<!-- Field Information Panel -->
<div id="field-info" class="card mt-6 hidden">
    <div class="p-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-info-circle text-primary mr-2"></i>
            Active Fields for Selected Instrument
        </h3>
    </div>
    <div id="field-info-content" class="p-4">
        <!-- Dynamic content will be inserted here -->
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
        // Hide all dynamic fields first
        ALL_FIELDS.forEach(field => {
            const group = document.getElementById(`field-group-${field}`);
            if (group) {
                group.classList.add('hidden');
                // Clear the input value when hiding
                const input = group.querySelector('input, select, textarea');
                if (input) input.value = '';
            }
        });

        // Show relevant fields for selected instrument
        if (instrumentId && INSTRUMENT_MATRIX[instrumentId]) {
            const activeFields = INSTRUMENT_MATRIX[instrumentId].fields;
            
            activeFields.forEach(field => {
                const group = document.getElementById(`field-group-${field}`);
                if (group) {
                    group.classList.remove('hidden');
                }
            });
            
            // Show field information
            showFieldInformation(instrumentSelect.options[instrumentSelect.selectedIndex].text, activeFields);
        } else {
            // Hide field information
            fieldInfo.classList.add('hidden');
        }
    }
    
    function showFieldInformation(instrumentName, activeFields) {
        if (activeFields.length > 0) {
            const fieldNames = {
                'MobilePhase': 'Mobile Phase',
                'Speed': 'Speed (RPM)',
                'ElectrodeType': 'Electrode Type',
                'Result': 'Result/Analysis',
                'WavelengthScan': 'Wavelength Scan',
                'Diluent': 'Diluent',
                'Lamp': 'Lamp Type',
                'Column': 'Column Specification',
                'Apparatus': 'Apparatus Type',
                'Medium': 'Medium/Buffer',
                'TotalVolume': 'Total Volume',
                'VesselQuantity': 'Vessel Quantity'
            };
            
            const fieldsHtml = activeFields.map(field => 
                `<span class="badge badge-info">${fieldNames[field] || field}</span>`
            ).join(' ');
            
            fieldInfoContent.innerHTML = `
                <div class="mb-2">
                    <strong class="text-primary">${instrumentName}</strong>
                </div>
                <div class="mb-2 text-sm text-gray-600">
                    The following fields are required/relevant for this instrument:
                </div>
                <div class="flex flex-wrap gap-2">
                    ${fieldsHtml}
                </div>
            `;
            
            fieldInfo.classList.remove('hidden');
        }
    }
    
    // Set current date and time as defaults
    function setCurrentDateTime() {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].substring(0, 5);
        
        document.getElementById('start_date').value = dateStr;
        document.getElementById('start_time').value = timeStr;
    }
    
    // Event listeners
    instrumentSelect.addEventListener('change', (e) => {
        updateFormFields(e.target.value);
    });
    
    // Initialize
    setCurrentDateTime();
    updateFormFields(instrumentSelect.value);
    
    // Auto-hide notifications after 5 seconds
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