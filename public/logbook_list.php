<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Ambil semua data logbook dari database
$stmt = $pdo->query("
    SELECT 
        le.*, -- Ambil semua kolom dari logbook_entries
        u.name as user_name, 
        i.name as instrument_name,
        i.code as instrument_code
    FROM logbook_entries le
    JOIN users u ON le.user_id = u.id
    JOIN instruments i ON le.instrument_id = i.id
    ORDER BY le.start_date DESC, le.start_time DESC
");
$logs = $stmt->fetchAll();

// Ambil daftar instrumen unik untuk filter
$instruments_for_filter = $pdo->query("SELECT id, name FROM instruments ORDER BY name ASC")->fetchAll();

// Definisikan kolom parameter untuk kemudahan
$parameter_columns = [
    'MobilePhase' => 'mobile_phase_val', 'Speed' => 'speed_val', 'ElectrodeType' => 'electrode_type_val', 
    'Result' => 'result_val', 'WavelengthScan' => 'wavelength_scan_val', 'Diluent' => 'diluent_val', 
    'Lamp' => 'lamp_val', 'Column' => 'column_val', 'Apparatus' => 'apparatus_val', 
    'Medium' => 'medium_val', 'TotalVolume' => 'total_volume_val', 'VesselQuantity' => 'vessel_quantity_val'
];
?>

<!-- DataTables CSS & Buttons Extension -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<style>
    /* Kustomisasi tampilan DataTables agar sesuai dengan tema */
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        background-color: hsl(210, 40%, 96.1%); border-color: hsl(214.3, 31.8%, 91.4%); border-radius: 0.5rem; padding: 0.5rem;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 0.5rem; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: hsl(221.2, 83.2%, 53.3%) !important; color: white !important; border-color: hsl(221.2, 83.2%, 53.3%) !important; }
    #logbookTable th { white-space: nowrap; }
    .dt-button { background-color: #4f46e5 !important; color: white !important; border-radius: 0.5rem !important; padding: 0.5rem 1rem !important; }
    .dt-button:hover { background-color: #4338ca !important; }
</style>

<div class="bg-card p-6 rounded-xl border border-border shadow-lg space-y-6">
    <h2 class="text-xl font-bold text-foreground border-b border-border pb-4">Logbook List</h2>
    
    <!-- Filter Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1">Search:</label>
            <input type="text" id="generalSearch" placeholder="Search anything..." class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1">Date From:</label>
            <input type="date" id="dateFromFilter" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1">Date To:</label>
            <input type="date" id="dateToFilter" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1">Instrument:</label>
            <select id="instrumentFilter" class="w-full bg-muted border border-border rounded-lg px-4 py-2 text-sm">
                <option value="">All Instruments</option>
                <?php foreach ($instruments_for_filter as $instrument): ?>
                    <option value="<?php echo esc_html($instrument['name']); ?>"><?php echo esc_html($instrument['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table id="logbookTable" class="display table-auto w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th class="p-3">Log Code</th>
                    <th class="p-3">Instrument</th>
                    <th class="p-3">User</th>
                    <th class="p-3">Sample Name</th>
                    <th class="p-3">Trial Code</th>
                    <th class="p-3">Start Time</th>
                    <th class="p-3">Finish Time</th>
                    <?php foreach (array_keys($parameter_columns) as $param_name): ?>
                        <th class="p-3"><?php echo esc_html(preg_replace('/(?<!^)([A-Z])/', ' $1', $param_name)); ?></th>
                    <?php endforeach; ?>
                    <th class="p-3">Condition After</th>
                    <th class="p-3">Activity</th>
                    <th class="p-3">Remark</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['log_book_code']); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['instrument_name']); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['user_name']); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['sample_name']); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['trial_code']); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html(date('Y-m-d H:i', strtotime($log['start_date'] . ' ' . $log['start_time']))); ?></td>
                    <td class="p-3 whitespace-nowrap"><?php echo $log['finish_date'] ? esc_html(date('Y-m-d H:i', strtotime($log['finish_date'] . ' ' . $log['finish_time']))) : 'N/A'; ?></td>
                    <?php foreach ($parameter_columns as $db_col): ?>
                        <td class="p-3 whitespace-nowrap"><?php echo esc_html($log[$db_col]); ?></td>
                    <?php endforeach; ?>
                    <td class="p-3 whitespace-nowrap"><?php echo esc_html($log['condition_after']); ?></td>
                    <td class="p-3"><?php echo esc_html($log['activity']); ?></td>
                    <td class="p-3"><?php echo esc_html($log['remark']); ?></td>
                    <td class="p-3 whitespace-nowrap">
                        <a href="#" class="text-primary hover:underline mr-2">Edit</a>
                        <a href="#" class="text-red-600 hover:underline">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- jQuery and DataTables JS with Buttons extension -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.tailwindcss.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // Custom date range filter
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            let min = $('#dateFromFilter').val();
            let max = $('#dateToFilter').val();
            let date = new Date(data[5].split(' ')[0]); // Get date from 'Start Time' column

            if (
                (min === "" || min === null) && (max === "" || max === null) ||
                (min === "" || min === null) && date <= new Date(max) ||
                (max === "" || max === null) && date >= new Date(min) ||
                (date >= new Date(min) && date <= new Date(max))
            ) {
                return true;
            }
            return false;
        }
    );

    let table = $('#logbookTable').DataTable({
        "pageLength": 10,
        "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
        "dom": 'Bfrtip', // Add Buttons to the DOM
        "buttons": [
            'copy', 'csv', 'excel', 'print'
        ]
    });

    // Event listener for custom filters
    $('#generalSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    $('#instrumentFilter').on('change', function() {
        table.column(1).search(this.value).draw(); // Column 1 is 'Instrument'
    });

    $('#dateFromFilter, #dateToFilter').on('change', function() {
        table.draw();
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
