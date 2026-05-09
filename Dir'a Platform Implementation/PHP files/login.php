<?php
session_start();
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter email and password.']);
    exit;
}

$columns = get_columns($conn, 'users');

$emailCol = pick_column($columns, ['email', 'user_email']);
$passCol  = pick_column($columns, ['password', 'password_hash', 'user_password']);
$nameCol  = pick_column($columns, ['name', 'full_name', 'username']);
$idCol    = pick_column($columns, ['user_id', 'id']);
$roleCol  = pick_column($columns, ['role']);

$sql = "SELECT * FROM `users` WHERE `$emailCol` = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
    exit;
}

$user = $result->fetch_assoc();

$storedPassword = $user[$passCol] ?? '';
$passwordIsCorrect = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

if (!$passwordIsCorrect) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Incorrect email or password.']);
    exit;
}

/* مهم: هنا نحفظ اليوزر الحالي */
$_SESSION['user_id'] = $user[$idCol];
$_SESSION['user_email'] = $user[$emailCol];
$_SESSION['user_name'] = $nameCol ? $user[$nameCol] : '';
$_SESSION['user_role'] = $roleCol ? $user[$roleCol] : '';

echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'user' => [
        'id' => $user[$idCol],
        'name' => $nameCol ? $user[$nameCol] : '',
        'email' => $user[$emailCol],
        'role' => $roleCol ? $user[$roleCol] : ''
    ]
]);
?>