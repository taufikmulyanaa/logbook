<?php
// public/update_entry_action.php
require_once __DIR__ . '/../config/init.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die('Method Not Allowed'); }
if (!isset($_SESSION['user_id'])) { die('Unauthorized'); }
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) { die('CSRF Token Error'); }

$entry_id = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);
if (!$entry_id) {
    die('Invalid entry ID.');
}

// Check if user can edit this entry
$stmt = $pdo->prepare("SELECT user_id FROM logbook_entries WHERE id = :id");
$stmt->execute(['id' => $entry_id]);
$entry = $stmt->fetch();

if (!$entry) {
    die('Entry not found.');
}

if ($entry['user_id'] != $_SESSION['user_id']) {
    die('You can only edit your own entries.');
}

// Get and validate input
$instrument_id = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
$activity = trim($_POST['activity'] ?? '');
$sample_name = trim($_POST['sample_name'] ?? null);
$trial_code = trim($_POST['trial_code'] ?? null);
$start_date = trim($_POST['start_date'] ?? '');
$start_time = trim($_POST['start_time'] ?? '');
$finish_date = !empty($_POST['finish_date']) ? trim($_POST['finish_date']) : null;
$finish_time = !empty($_POST['finish_time']) ? trim($_POST['finish_time']) : null;
$condition_after = trim($_POST['condition_after'] ?? null);
$remark = trim($_POST['remark'] ?? null);

// Validate required fields
if (empty($instrument_id) || empty($activity) || empty($start_date) || empty($start_time)) {
    die('Required fields cannot be empty.');
}

// Prepare data for update
$data = [
    'instrument_id' => $instrument_id,
    'activity' => $activity,
    'sample_name' => $sample_name,
    'trial_code' => $trial_code,
    'start_date' => $start_date,
    'start_time' => $start_time,
    'finish_date' => $finish_date,
    'finish_time' => $finish_time,
    'condition_after' => $condition_after,
    'remark' => $remark,
    'entry_id' => $entry_id
];

// Update query
$sql = "UPDATE logbook_entries SET 
        instrument_id = :instrument_id,
        activity = :activity,
        sample_name = :sample_name,
        trial_code = :trial_code,
        start_date = :start_date,
        start_time = :start_time,
        finish_date = :finish_date,
        finish_time = :finish_time,
        condition_after = :condition_after,
        remark = :remark,
        updated_at = CURRENT_TIMESTAMP
        WHERE id = :entry_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);
    log_activity("Entry updated (ID: {$entry_id}) by user ID: {$_SESSION['user_id']}");
} catch (PDOException $e) {
    log_activity("Error updating entry: " . $e->getMessage());
    die("Failed to update entry. Error: " . $e->getMessage());
}

// Redirect with success message
header('Location: logbook_list.php?message=Entry updated successfully');
exit();
?>

<?php
// public/delete_entry_action.php
require_once __DIR__ . '/../config/init.php';

// Security checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die('Method Not Allowed'); }
if (!isset($_SESSION['user_id'])) { die('Unauthorized'); }
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) { die('CSRF Token Error'); }

$entry_id = filter_input(INPUT_POST, 'entry_id', FILTER_VALIDATE_INT);
if (!$entry_id) {
    die('Invalid entry ID.');
}

// Check if user can delete this entry
$stmt = $pdo->prepare("SELECT user_id, log_book_code FROM logbook_entries WHERE id = :id");
$stmt->execute(['id' => $entry_id]);
$entry = $stmt->fetch();

if (!$entry) {
    die('Entry not found.');
}

if ($entry['user_id'] != $_SESSION['user_id']) {
    die('You can only delete your own entries.');
}

try {
    // Delete the entry
    $stmt = $pdo->prepare("DELETE FROM logbook_entries WHERE id = :id");
    $stmt->execute(['id' => $entry_id]);
    
    log_activity("Entry deleted (ID: {$entry_id}, Code: {$entry['log_book_code']}) by user ID: {$_SESSION['user_id']}");
} catch (PDOException $e) {
    log_activity("Error deleting entry: " . $e->getMessage());
    die("Failed to delete entry. Error: " . $e->getMessage());
}

// Redirect with success message
header('Location: logbook_list.php?message=Entry deleted successfully');
exit();
?>