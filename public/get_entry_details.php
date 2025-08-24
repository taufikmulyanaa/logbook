<?php
// public/get_entry_details.php
require_once __DIR__ . '/../config/init.php';

// Atur header untuk merespon sebagai JSON
header('Content-Type: application/json');

// Keamanan: Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Ambil dan validasi ID entri dari request
$entry_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$entry_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid entry ID.']);
    exit();
}

try {
    // Ambil semua detail untuk entri yang dipilih, termasuk nama user dan instrumen
    $stmt = $pdo->prepare("
        SELECT 
            le.*, 
            u.name as user_name, 
            i.name as instrument_name,
            i.code as instrument_code
        FROM logbook_entries le
        JOIN users u ON le.user_id = u.id
        JOIN instruments i ON le.instrument_id = i.id
        WHERE le.id = :id
    ");
    $stmt->execute(['id' => $entry_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($entry) {
        echo json_encode(['success' => true, 'data' => $entry]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Entry not found.']);
    }

} catch (PDOException $e) {
    log_activity("Error fetching entry details: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}

exit();
?>