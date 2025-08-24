<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Ambil semua data instrumen dari database
$stmt = $pdo->query("SELECT * FROM instruments ORDER BY name ASC");
$instruments = $stmt->fetchAll();

// Definisikan kolom parameter untuk header tabel
$parameter_columns = [
    'MobilePhase', 'Speed', 'ElectrodeType', 'Result', 'WavelengthScan', 'Diluent', 
    'Lamp', 'Column', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity'
];
?>
<style>
    /* Custom styling for the checkbox to make it look modern */
    .matrix-checkbox {
        appearance: none;
        background-color: #f1f5f9; /* slate-100 */
        border: 1px solid #cbd5e1; /* slate-300 */
        border-radius: 0.25rem;
        width: 1rem; /* Smaller checkbox */
        height: 1rem; /* Smaller checkbox */
        cursor: pointer;
        position: relative;
        transition: background-color 0.2s, border-color 0.2s;
    }
    .matrix-checkbox:checked {
        background-color: hsl(221.2, 83.2%, 53.3%); /* primary */
        border-color: hsl(221.2, 83.2%, 53.3%);
    }
    .matrix-checkbox:checked::after {
        content: '✓';
        color: white;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 0.6rem; /* Smaller checkmark */
        font-weight: bold;
    }
    
    /* Small font styling */
    .matrix-table {
        font-size: 0.75rem; /* text-xs */
    }
    
    .matrix-table th,
    .matrix-table td {
        padding: 0.375rem; /* py-1.5 px-1.5 equivalent */
        font-size: 0.75rem; /* text-xs */
        line-height: 1.2;
    }
    
    .matrix-table th {
        font-weight: 500;
        white-space: nowrap;
    }
    
    .instrument-name {
        min-width: 200px; /* Smaller than before */
        max-width: 200px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .instrument-code {
        min-width: 120px; /* Smaller than before */
        max-width: 120px;
        font-size: 0.75rem;
    }
</style>

<div class="bg-card p-6 rounded-xl border border-border shadow-lg space-y-4">
    <h1 class="text-lg font-bold text-foreground">🔬 Matriks Data Instrumen Laboratorium</h1>
    
    <!-- Summary Section -->
    <div class="bg-muted/50 p-3 rounded-lg border border-border">
        <h3 class="font-semibold text-foreground mb-2 text-sm">📊 Manajemen Matriks</h3>
        <p class="text-xs">Gunakan *checkbox* di bawah untuk mengaktifkan atau menonaktifkan parameter yang relevan untuk setiap instrumen. Perubahan akan disimpan secara otomatis.</p>
    </div>

    <!-- Notification Toast -->
    <div id="notification-toast" class="hidden fixed top-20 right-6 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg transition-transform transform translate-x-full z-50 text-sm">
        Perubahan berhasil disimpan!
    </div>

    <!-- Matrix Table -->
    <div class="overflow-x-auto">
        <table class="matrix-table min-w-full border-collapse border border-border">
            <thead class="bg-muted">
                <tr>
                    <th class="border border-border text-left sticky left-0 bg-muted z-10 instrument-name">Nama Instrumen</th>
                    <th class="border border-border sticky left-[200px] bg-muted z-10 instrument-code">Kode</th>
                    <?php foreach ($parameter_columns as $param): ?>
                        <th class="border border-border whitespace-nowrap text-center"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instruments as $instrument): ?>
                <tr class="hover:bg-accent/50">
                    <td class="border border-border text-left font-medium sticky left-0 bg-card hover:bg-accent/50 z-10 instrument-name">
                        <div class="truncate" title="<?php echo esc_html($instrument['name']); ?>">
                            <?php echo esc_html($instrument['name']); ?>
                        </div>
                    </td>
                    <td class="border border-border sticky left-[200px] bg-card hover:bg-accent/50 z-10 instrument-code">
                        <div class="truncate" title="<?php echo esc_html($instrument['code']); ?>">
                            <?php echo esc_html($instrument['code']); ?>
                        </div>
                    </td>
                    <?php foreach ($parameter_columns as $param): ?>
                        <td class="border border-border text-center">
                            <input type="checkbox" 
                                   class="matrix-checkbox"
                                   data-instrument-id="<?php echo (int)$instrument['id']; ?>"
                                   data-parameter="<?php echo esc_html($param); ?>"
                                   <?php echo $instrument[$param] ? 'checked' : ''; ?>>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Table Legend -->
    <div class="flex flex-wrap gap-4 text-xs text-muted-foreground border-t border-border pt-3">
        <div class="flex items-center gap-2">
            <input type="checkbox" class="matrix-checkbox" checked disabled>
            <span>Parameter aktif</span>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" class="matrix-checkbox" disabled>
            <span>Parameter tidak aktif</span>
        </div>
        <div class="flex items-center gap-1 ml-auto">
            <i class="fas fa-info-circle text-xs"></i>
            <span>Klik checkbox untuk mengubah status parameter</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.matrix-checkbox:not([disabled])');
    const notificationToast = document.getElementById('notification-toast');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const instrumentId = this.dataset.instrumentId;
            const parameter = this.dataset.parameter;
            const isChecked = this.checked;

            const formData = new FormData();
            formData.append('instrument_id', instrumentId);
            formData.append('parameter', parameter);
            formData.append('is_checked', isChecked ? 'true' : 'false');
            // Add CSRF token for security
            formData.append('csrf_token', '<?php echo esc_html($_SESSION['csrf_token']); ?>');

            try {
                const response = await fetch('update_matrix_action.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    showNotification('Perubahan berhasil disimpan!', 'success');
                } else {
                    // Revert checkbox on failure
                    this.checked = !isChecked;
                    showNotification(result.message || 'Gagal menyimpan perubahan.', 'error');
                }
            } catch (error) {
                // Revert checkbox on network error
                this.checked = !isChecked;
                showNotification('Terjadi kesalahan jaringan.', 'error');
                console.error('Error:', error);
            }
        });
    });

    function showNotification(message, type = 'success') {
        notificationToast.textContent = message;
        if (type === 'error') {
            notificationToast.classList.remove('bg-green-500');
            notificationToast.classList.add('bg-red-500');
        } else {
            notificationToast.classList.remove('bg-red-500');
            notificationToast.classList.add('bg-green-500');
        }

        notificationToast.classList.remove('hidden', 'translate-x-full');
        notificationToast.classList.add('translate-x-0');

        setTimeout(() => {
            notificationToast.classList.remove('translate-x-0');
            notificationToast.classList.add('translate-x-full');
            setTimeout(() => {
                notificationToast.classList.add('hidden');
            }, 300); // Wait for transition to finish
        }, 3000); // Hide after 3 seconds
    }
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>