<?php
// public/add_log_action.php
require_once __DIR__ . '/../config/init.php';

// Hanya izinkan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Metode tidak diizinkan.');
}

// 🔑 3. Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    die('Anda harus login untuk melakukan aksi ini.');
}

// 🌐 5. Validasi CSRF Token
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    http_response_code(403); // Forbidden
    log_activity("CSRF token validation failed for adding logbook.");
    die('Aksi tidak diizinkan (CSRF Token Error).');
}

// 🛡️ 2. Validasi & Sanitasi Input
$user_id = $_SESSION['user_id'];
$instrument_id = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
$activity = trim($_POST['activity'] ?? '');
$params = $_POST['params'] ?? [];

if (empty($instrument_id) || empty($activity)) {
    // Di aplikasi nyata, Anda akan redirect dengan pesan error
    die('Instrumen dan aktivitas wajib diisi.');
}

// 🛡️ 2. Gunakan Prepared Statement untuk mencegah SQL Injection
$sql = "INSERT INTO logbook_entries (
            user_id, instrument_id, activity,
            mobile_phase_val, speed_val, electrode_type_val, result_val, 
            wavelength_scan_val, diluent_val, lamp_val, column_val, 
            apparatus_val, medium_val, total_volume_val, vessel_quantity_val
        ) VALUES (
            :user_id, :instrument_id, :activity,
            :MobilePhase, :Speed, :ElectrodeType, :Result, 
            :WavelengthScan, :Diluent, :Lamp, :Column, 
            :Apparatus, :Medium, :TotalVolume, :VesselQuantity
        )";

$stmt = $pdo->prepare($sql);

$data = [
    'user_id' => $user_id,
    'instrument_id' => $instrument_id,
    'activity' => $activity
];

// Siapkan data parameter, hanya ambil yang ada di daftar (whitelist) untuk keamanan
$parameter_columns = [
    'MobilePhase', 'Speed', 'ElectrodeType', 'Result', 'WavelengthScan', 
    'Diluent', 'Lamp', 'Column', 'Apparatus', 'Medium', 'TotalVolume', 'VesselQuantity'
];

foreach ($parameter_columns as $param) {
    // Sanitasi setiap nilai parameter
    $data[$param] = isset($params[$param]) && is_string($params[$param]) ? trim($params[$param]) : null;
}

try {
    $stmt->execute($data);
    log_activity("New logbook entry created by user ID: {$user_id}");
} catch (PDOException $e) {
    log_activity("Error creating logbook entry: " . $e->getMessage());
    die("Gagal menyimpan data. Silakan coba lagi.");
}

// Redirect kembali ke halaman utama
header('Location: index.php');
exit();
