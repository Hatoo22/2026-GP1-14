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

/* User */
$userSql = "SELECT name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* Tracked count */
$trackedSql = "SELECT COUNT(*) AS total FROM tracked_games WHERE user_id = ?";
$stmt = $conn->prepare($trackedSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tracked = $stmt->get_result()->fetch_assoc();

/* Reports in review */
$reportsSql = "
SELECT COUNT(*) AS total 
FROM reports 
WHERE user_id = ? 
AND status = 'Reviewing'
";
$stmt = $conn->prepare($reportsSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports = $stmt->get_result()->fetch_assoc();

/* Submitted reports */
$submittedReportsSql = "
SELECT COUNT(*) AS total
FROM reports
WHERE user_id = ?
";
$stmt = $conn->prepare($submittedReportsSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$submittedReports = $stmt->get_result()->fetch_assoc();

/* Active alerts = high risk games */
$alertsSql = "
SELECT COUNT(*) AS total
FROM tracked_games tg
JOIN games g ON tg.game_id = g.game_id
WHERE tg.user_id = ?
AND g.overall_risk_level = 'High'
";
$stmt = $conn->prepare($alertsSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$alerts = $stmt->get_result()->fetch_assoc();

/* Recent games */
$gamesSql = "
SELECT 
  game_id,
  game_name,
  description,
  genre,
  platform,
  image_url,
  overall_risk_percent,
  overall_risk_level,
  comments_count,
  analysis_status
FROM games
ORDER BY created_at DESC
LIMIT 7
";

$result = $conn->query($gamesSql);

$games = [];

while ($row = $result->fetch_assoc()) {
  $games[] = $row;
}

echo json_encode([
  "success" => true,
  "user" => $user,
  "stats" => [
  "tracked_games" => $tracked["total"],
  "active_alerts" => $alerts["total"],
  "reports_in_review" => $reports["total"],
  "submitted_reports" => $submittedReports["total"]
],
  "recent_games" => $games
], JSON_UNESCAPED_UNICODE);

$conn->close();
?>