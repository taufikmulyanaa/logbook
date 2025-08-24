<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

function read_log_file($file_path) {
    if (!file_exists($file_path) || !is_readable($file_path)) {
        return [];
    }
    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_entries = [];
    foreach ($lines as $line) {
        // Regex to parse log entry: [YYYY-MM-DD HH:MM:SS] - Message
        if (preg_match('/^\[(.*?)\]\s-\s(.*)$/', $line, $matches)) {
            $log_entries[] = [
                'timestamp' => $matches[1],
                'message' => $matches[2]
            ];
        }
    }
    return array_reverse($log_entries); // Show newest first
}

$log_file = __DIR__ . '/../logs/app.log';
$logs = read_log_file($log_file);
?>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.tailwindcss.min.css">

<div class="bg-card p-6 rounded-xl border border-border shadow-lg">
    <h1 class="text-xl font-bold text-foreground mb-2">Audit Trail</h1>
    <p class="text-sm text-muted-foreground mb-6">A chronological record of system activities.</p>
    
    <table id="auditTable" class="display table-auto w-full text-sm">
        <thead>
            <tr>
                <th class="p-3 w-48">Timestamp</th>
                <th class="p-3 text-left">Activity</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td class="p-2 whitespace-nowrap text-muted-foreground"><?php echo esc_html($log['timestamp']); ?></td>
                <td class="p-2"><?php echo esc_html($log['message']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.tailwindcss.min.js"></script>

<script>
$(document).ready(function() {
    $('#auditTable').DataTable({
        "pageLength": 50,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>