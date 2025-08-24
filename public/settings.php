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
        width: 1.25rem;
        height: 1.25rem;
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
        font-size: 0.8rem;
        font-weight: bold;
    }
</style>

<div class="bg-card p-6 rounded-xl border border-border shadow-lg space-y-6">
    <h1 class="text-xl font-bold text-foreground">🔬 Matriks Data Instrumen Laboratorium</h1>
    
    <!-- Summary Section -->
    <div class="bg-muted/50 p-4 rounded-lg border border-border">
        <h3 class="font-semibold text-foreground mb-2">📊 Manajemen Matriks</h3>
        <p class="text-sm">Gunakan *checkbox* di bawah untuk mengaktifkan atau menonaktifkan parameter yang relevan untuk setiap instrumen. Perubahan akan disimpan secara otomatis.</p>
    </div>

    <!-- Notification Toast -->
    <div id="notification-toast" class="hidden fixed top-20 right-6 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transition-transform transform translate-x-full z-50">
        Perubahan berhasil disimpan!
    </div>

    <!-- Matrix Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm border-collapse border border-border">
            <thead class="bg-muted">
                <tr>
                    <th class="p-3 border border-border text-left sticky left-0 bg-muted z-10">Nama Instrumen</th>
                    <th class="p-3 border border-border sticky left-[250px] bg-muted z-10">Kode</th>
                    <?php foreach ($parameter_columns as $param): ?>
                        <th class="p-3 border border-border whitespace-nowrap"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param)); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instruments as $instrument): ?>
                <tr class="hover:bg-accent/50">
                    <td class="p-3 border border-border text-left font-medium sticky left-0 bg-card hover:bg-accent/50 z-10" style="min-width: 250px;"><?php echo esc_html($instrument['name']); ?></td>
                    <td class="p-3 border border-border sticky left-[250px] bg-card hover:bg-accent/50 z-10" style="min-width: 150px;"><?php echo esc_html($instrument['code']); ?></td>
                    <?php foreach ($parameter_columns as $param): ?>
                        <td class="p-3 border border-border text-center">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.matrix-checkbox');
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
