<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Fetch stats for reporting page
$total_logs = $pdo->query("SELECT COUNT(*) FROM logbook_entries")->fetchColumn();
$completed = $pdo->query("SELECT COUNT(*) FROM logbook_entries WHERE finish_date IS NOT NULL")->fetchColumn();
$in_progress = $total_logs - $completed;
?>
<div class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-200">
    <div class="bg-[#005294] p-4 font-bold text-center text-white text-xl border-b border-blue-400">
        Reporting
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Stats Cards -->
            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                <div class="flex items-center"><div class="p-2 bg-blue-500 rounded-lg"><i class="fas fa-clipboard-list text-white text-xl"></i></div><div class="ml-4"><p class="text-sm font-medium text-blue-600">Total Logbooks</p><p class="text-2xl font-bold text-blue-900"><?php echo (int)$total_logs; ?></p></div></div>
            </div>
            <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                <div class="flex items-center"><div class="p-2 bg-green-500 rounded-lg"><i class="fas fa-check-circle text-white text-xl"></i></div><div class="ml-4"><p class="text-sm font-medium text-green-600">Completed</p><p class="text-2xl font-bold text-green-900"><?php echo (int)$completed; ?></p></div></div>
            </div>
            <div class="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                <div class="flex items-center"><div class="p-2 bg-yellow-500 rounded-lg"><i class="fas fa-clock text-white text-xl"></i></div><div class="ml-4"><p class="text-sm font-medium text-yellow-600">In Progress</p><p class="text-2xl font-bold text-yellow-900"><?php echo (int)$in_progress; ?></p></div></div>
            </div>
        </div>
        
        <!-- Report Generation -->
        <div class="p-6 border border-gray-200 rounded-lg">
            <h3 class="text-lg font-semibold mb-4">Generate Report</h3>
            <div class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-600 mb-1">Report Type:</label><select class="w-full p-2 border border-gray-300 rounded-md bg-gray-50 text-sm"><option>Daily Report</option><option>Weekly Report</option><option>Monthly Report</option></select></div>
                <div><label class="block text-sm font-medium text-gray-600 mb-1">Date Range:</label><div class="grid grid-cols-2 gap-2"><input type="date" class="p-2 border border-gray-300 rounded-md bg-gray-50 text-sm"><input type="date" class="p-2 border border-gray-300 rounded-md bg-gray-50 text-sm"></div></div>
                <button class="w-full bg-[#005294] hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg"><i class="fas fa-download mr-2"></i>Generate Report</button>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>
