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
  g.game_id,
  g.game_name,
  g.description,
  g.genre,
  g.image_url,
  g.platform,

  g.overall_risk_percent,
  g.overall_risk_level,
  g.comments_count,
  g.analysis_status

FROM tracked_games tg

JOIN games g 
ON tg.game_id = g.game_id

WHERE tg.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$games = [];

while ($row = $result->fetch_assoc()) {
  $games[] = $row;
}

echo json_encode([
  "success" => true,
  "games" => $games
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>