<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
  }

  $user_id = $_SESSION["user_id"] ?? null;

  if (!$user_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
  }

  $game_id = $_POST["game_id"] ?? null;
  $title = trim($_POST["title"] ?? "");

  $behavior_toxic = isset($_POST["behavior_toxic"]) ? 1 : 0;
  $behavior_bullying = isset($_POST["behavior_bullying"]) ? 1 : 0;
  $behavior_hate = isset($_POST["behavior_hate"]) ? 1 : 0;
  $behavior_sexual = isset($_POST["behavior_sexual"]) ? 1 : 0;
  $behavior_threat = isset($_POST["behavior_threat"]) ? 1 : 0;
  $behavior_other = trim($_POST["behavior_other"] ?? "");

  $severity = trim($_POST["severity"] ?? "");

  $location_chat = isset($_POST["location_chat"]) ? 1 : 0;
  $location_gameplay = isset($_POST["location_gameplay"]) ? 1 : 0;
  $location_community = isset($_POST["location_community"]) ? 1 : 0;

  if (!$game_id || $title === "" || $severity === "") {
    echo json_encode(["success" => false, "message" => "Please fill all required fields"]);
    exit;
  }

  if (
    $behavior_toxic === 0 &&
    $behavior_bullying === 0 &&
    $behavior_hate === 0 &&
    $behavior_sexual === 0 &&
    $behavior_threat === 0 &&
    $behavior_other === ""
  ) {
    echo json_encode(["success" => false, "message" => "Please select at least one behavior or write other details"]);
    exit;
  }

  if (
    $location_chat === 0 &&
    $location_gameplay === 0 &&
    $location_community === 0
  ) {
    echo json_encode(["success" => false, "message" => "Please select where the behavior happened"]);
    exit;
  }

  $sql = "
    INSERT INTO reports
    (
      user_id,
      game_id,
      title,
      behavior_toxic,
      behavior_bullying,
      behavior_hate,
      behavior_sexual,
      behavior_threat,
      behavior_other,
      severity,
      location_chat,
      location_gameplay,
      location_community,
      status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
  ";

  $stmt = $conn->prepare($sql);

  $stmt->bind_param(
    "iisiiiiissiii",
    $user_id,
    $game_id,
    $title,
    $behavior_toxic,
    $behavior_bullying,
    $behavior_hate,
    $behavior_sexual,
    $behavior_threat,
    $behavior_other,
    $severity,
    $location_chat,
    $location_gameplay,
    $location_community
  );

  $stmt->execute();

  echo json_encode([
    "success" => true,
    "message" => "Report submitted successfully"
  ]);

  $stmt->close();

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Server error: " . $e->getMessage()
  ]);
}
?>