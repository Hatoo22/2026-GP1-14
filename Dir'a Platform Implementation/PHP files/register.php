<?php
session_start();
require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$firstName = trim($data['first_name'] ?? '');
$lastName  = trim($data['last_name'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';

if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters.'
    ]);
    exit;
}

if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must include both letters and numbers.'
    ]);
    exit;
}

$columns = get_columns($conn, 'users');
if (empty($columns)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Table users was not found in dira_db.']);
    exit;
}

$emailCol = pick_column($columns, ['email', 'user_email']);
$passCol  = pick_column($columns, ['password', 'password_hash', 'user_password']);
$firstCol = pick_column($columns, ['first_name', 'firstname', 'fname']);
$lastCol  = pick_column($columns, ['last_name', 'lastname', 'lname']);
$nameCol  = pick_column($columns, ['name', 'full_name', 'username']);
$roleCol  = pick_column($columns, ['role', 'user_role', 'type']);

if (!$emailCol || !$passCol) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'users table must contain email and password columns.']);
    exit;
}

$checkSql = "SELECT `$emailCol` FROM `users` WHERE `$emailCol` = ? LIMIT 1";
$check = $conn->prepare($checkSql);
$check->bind_param('s', $email);
$check->execute();
$exists = $check->get_result();
if ($exists && $exists->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    exit;
}

$insertColumns = [];
$values = [];
$types = '';

if ($firstCol) { $insertColumns[] = "`$firstCol`"; $values[] = $firstName; $types .= 's'; }
if ($lastCol)  { $insertColumns[] = "`$lastCol`";  $values[] = $lastName;  $types .= 's'; }
if (!$firstCol && !$lastCol && $nameCol) { $insertColumns[] = "`$nameCol`"; $values[] = $firstName . ' ' . $lastName; $types .= 's'; }

$insertColumns[] = "`$emailCol`"; $values[] = $email; $types .= 's';
$insertColumns[] = "`$passCol`";  $values[] = password_hash($password, PASSWORD_DEFAULT); $types .= 's';

if ($roleCol) { $insertColumns[] = "`$roleCol`"; $values[] = 'individual'; $types .= 's'; }

$placeholders = implode(',', array_fill(0, count($values), '?'));
$sql = "INSERT INTO `users` (" . implode(',', $insertColumns) . ") VALUES ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;

$_SESSION["user_id"] = $user_id;
$_SESSION["user_name"] = $firstName . " " . $lastName;
$_SESSION["user_email"] = $email;
$_SESSION["user_role"] = "individual";
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.',
        'user' => [
            'id' => $user_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sign up failed: ' . $stmt->error]);
}
?>
