<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';


$user_id = $_SESSION["user_id"] ?? null;

if (!$user_id) {
  echo json_encode([
    "success" => false,
    "message" => "Not logged in"
  ]);
  exit;
}

$sql = "
SELECT 
  r.report_id,
  g.game_name,
  r.title,
  r.behavior_other AS description,
  r.status,
  r.date_reported
FROM reports r
JOIN games g ON r.game_id = g.game_id
WHERE r.user_id = ?
ORDER BY r.date_reported DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
  echo json_encode([
    "success" => false,
    "message" => "Prepare failed: " . $conn->error
  ]);
  exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
  $reports[] = $row;
}

echo json_encode([
  "success" => true,
  "reports" => $reports
]);

$stmt->close();
$conn->close();
?>