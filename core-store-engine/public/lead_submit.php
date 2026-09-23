<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../engine/JsonStorage.php';

use CoreStore\Engine\JsonStorage;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$guests = trim($input['guests'] ?? '2 Guests');
$date = trim($input['date'] ?? date('Y-m-d'));
$time = trim($input['time'] ?? 'Dinner (7:30 PM)');
$notes = trim($input['notes'] ?? '');
$formName = trim($input['form_name'] ?? 'Table Reservation');
$page = trim($input['page'] ?? 'menu.php');

if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name and contact number are required.']);
    exit;
}

$storage = new JsonStorage(__DIR__ . '/../data/leads.json');
$leads = $storage->read() ?: [];

$newLead = [
    'id' => 'lead_' . time() . '_' . bin2hex(random_bytes(3)),
    'created_at' => date('Y-m-d H:i'),
    'form_name' => $formName,
    'page' => $page,
    'name' => $name,
    'phone' => $phone,
    'guests' => $guests,
    'date' => $date,
    'time' => $time,
    'notes' => $notes,
    'status' => 'pending'
];

array_unshift($leads, $newLead);
$storage->write($leads);

echo json_encode([
    'success' => true,
    'message' => 'Your reservation request has been received. Our concierge will connect with you to confirm.',
    'lead' => $newLead
]);
