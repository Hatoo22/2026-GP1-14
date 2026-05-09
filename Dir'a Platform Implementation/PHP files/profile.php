<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "root", "root", "dira_db", 8889);

require_once __DIR__ . '/config.php';


$user_id = $_SESSION["user_id"] ?? null;

if (!$user_id) {
  echo json_encode([
    "success" => false,
    "message" => "Not logged in"
  ]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
  $sql = "SELECT user_id, name, email, created_at, profile_avatar FROM users WHERE user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();

  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  if (!$user) {
    echo json_encode([
      "success" => false,
      "message" => "User not found"
    ]);
    exit;
  }

  echo json_encode([
    "success" => true,
    "user" => $user
  ]);
  exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $first_name = trim($_POST["first_name"] ?? "");
$last_name = trim($_POST["last_name"] ?? "");
$profile_avatar = trim($_POST["profile_avatar"] ?? "default");

if ($first_name === "" || $last_name === "") {
  echo json_encode([
    "success" => false,
    "message" => "First name and last name are required."
  ]);
  exit;
}

$name = $first_name . " " . $last_name;

  $allowedAvatars = ["default", "cat", "lion", "rabbit", "owl", "duck", "turtle", "bear"];

  if (!in_array($profile_avatar, $allowedAvatars)) {
    $profile_avatar = "default";
  }

  $sql = "UPDATE users SET name = ?, profile_avatar = ? WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $name, $profile_avatar, $user_id);

  if ($stmt->execute()) {
    echo json_encode([
      "success" => true,
      "message" => "Profile updated successfully"
    ]);
  } else {
    echo json_encode([
      "success" => false,
      "message" => "Update failed: " . $stmt->error
    ]);
  }

  $stmt->close();
  exit;
}

$conn->close();
?>