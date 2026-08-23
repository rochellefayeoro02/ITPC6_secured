<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {

    $firstName = trim($_POST["firstName"] ?? '');
    $middleName = trim($_POST["middleName"] ?? '');
    $lastName = trim($_POST["lastName"] ?? '');
    $birthDate = $_POST["birthDate"] ?? '';
    $address = trim($_POST["address"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $mobile = trim($_POST["mobile"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    if (
        empty($firstName) ||
        empty($lastName) ||
        empty($birthDate) ||
        empty($address) ||
        empty($email) ||
        empty($mobile) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        die("All required fields are required.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    $check = $conn->prepare("SELECT id FROM form WHERE email = ?");

    $check->bind_param("s", $email);
    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows > 0) {
        die("Email already exists.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO form (firstName, middleName, lastName, birthDate, address, email, mobile, password)VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param( "ssssssss", $firstName, $middleName, $lastName, $birthDate, $address, $email, $mobile, $hashed_password);

    if ($stmt->execute()) {

        header("Location: welcome.php");
        exit();

    } else {

        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $check->close();
    $conn->close();
}
?>