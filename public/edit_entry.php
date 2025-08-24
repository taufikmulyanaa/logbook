<?php
// public/edit_entry.php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../templates/header.php';

// Get entry ID
$entry_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$entry_id) {
    die('Invalid entry ID.');
}

// Fetch entry data
$stmt = $pdo->prepare("
    SELECT le.*, i.name as instrument_name, u.name as user_name
    FROM logbook_entries le 
    JOIN instruments i ON le.instrument_id = i.id 
    JOIN users u ON le.user_id = u.id 
    WHERE le.id = :id
");
$stmt->execute(['id' => $entry_id]);
$entry = $stmt->fetch();

if (!$entry) {
    die('Entry not found.');
}

// Check permission (basic)
if ($entry['user_id'] != $_SESSION['user_id'] && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
    die('You can only edit your own entries.');
}

// Get instruments
$instruments = $pdo->query("SELECT id, name, code FROM instruments ORDER BY name ASC")->fetchAll();

// NEW: Check if the entry is completed to set read-only mode
$is_completed = ($entry && $entry['status'] === 'Completed');
?>

<div class="bg-white p-6 rounded-xl shadow-lg">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-xl font-bold">Edit Log Book Entry</h2>
        <div class="flex gap-2">
            <a href="logbook_list.php" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>Back to List
            </a>
            <?php if (!$is_completed): ?>
            <button onclick="confirmDelete(<?php echo $entry_id; ?>)" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-trash mr-2"></i>Delete
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_completed): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 mb-6 rounded-r-lg" role="alert">
            <p class="font-bold">Entry Locked</p>
            <p class="text-sm">This logbook entry has been marked as 'Completed' and is now in read-only mode.</p>
        </div>
    <?php endif; ?>

    <div class="bg-gray-50 p-4 rounded-lg mb-6 text-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><strong>Entry ID:</strong> #<?php echo $entry_id; ?></div>
            <div><strong>Created by:</strong> <?php echo esc_html($entry['user_name']); ?></div>
            <div><strong>Created:</strong> <?php echo date('d M Y, H:i', strtotime($entry['entry_date'])); ?></div>
        </div>
    </div>
    
    <form action="update_entry_action.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Log Book Code</label>
                    <input type="text" name="log_book_code" value="<?php echo esc_html($entry['log_book_code']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm bg-gray-100" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instrument</label>
                    <select name="instrument_id" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required <?php echo $is_completed ? 'disabled' : ''; ?>>
                        <?php foreach ($instruments as $instrument): ?>
                            <option value="<?php echo (int)$instrument['id']; ?>" <?php echo ($entry['instrument_id'] == $instrument['id']) ? 'selected' : ''; ?>>
                                <?php echo esc_html($instrument['name'] . ' (' . $instrument['code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo esc_html($entry['start_date']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required <?php echo $is_completed ? 'readonly' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time</label>
                        <input type="time" name="start_time" value="<?php echo esc_html($entry['start_time']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required <?php echo $is_completed ? 'readonly' : ''; ?>>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sample Name</label>
                    <input type="text" name="sample_name" value="<?php echo esc_html($entry['sample_name']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'readonly' : ''; ?>>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Trial Code</label>
                    <input type="text" name="trial_code" value="<?php echo esc_html($entry['trial_code']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'readonly' : ''; ?>>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Finish Date</label>
                        <input type="date" name="finish_date" value="<?php echo esc_html($entry['finish_date']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'readonly' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Finish Time</label>
                        <input type="time" name="finish_time" value="<?php echo esc_html($entry['finish_time']); ?>" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'readonly' : ''; ?>>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Condition After</label>
                    <select name="condition_after" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'disabled' : ''; ?>>
                        <option value="">Choose</option>
                        <option value="Good" <?php echo ($entry['condition_after'] == 'Good') ? 'selected' : ''; ?>>Good</option>
                        <option value="Need Maintenance" <?php echo ($entry['condition_after'] == 'Need Maintenance') ? 'selected' : ''; ?>>Need Maintenance</option>
                        <option value="Broken" <?php echo ($entry['condition_after'] == 'Broken') ? 'selected' : ''; ?>>Broken</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'disabled' : ''; ?>>
                        <option value="Not Complete" <?php echo ($entry['status'] == 'Not Complete') ? 'selected' : ''; ?>>Not Complete</option>
                        <option value="Completed" <?php echo ($entry['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Remark</label>
                    <textarea name="remark" rows="3" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" <?php echo $is_completed ? 'readonly' : ''; ?>><?php echo esc_html($entry['remark']); ?></textarea>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Activity Summary</label>
                <textarea name="activity" rows="4" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm" required <?php echo $is_completed ? 'readonly' : ''; ?>><?php echo esc_html($entry['activity']); ?></textarea>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-4 border-t pt-6">
            <a href="logbook_list.php" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50">Cancel</a>
            <?php if (!$is_completed): ?>
            <button type="submit" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Update Entry
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete this entry? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
            <button onclick="hideDeleteModal()" class="px-4 py-2 text-sm border rounded-lg">Cancel</button>
            <form id="deleteForm" method="POST" action="delete_entry_action.php" class="inline">
                <input type="hidden" name="csrf_token" value="<?php echo esc_html($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="entry_id" value="">
                <button type="submit" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg">Delete</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(entryId) {
    document.getElementById('deleteForm').querySelector('input[name="entry_id"]').value = entryId;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>