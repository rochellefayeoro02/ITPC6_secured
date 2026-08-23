<?php
session_start();

require "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") { $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare("SELECT id, firstName, email, password FROM form WHERE email = ?");

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["email"] = $user["email"];

                header("Location: welcome.php");
                exit();

            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Email not found.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #f7f7f7;
            color: #111;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .frame {
            width: 100%;
            max-width: 400px;
            background: #fff;
            padding: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        }

        h2 {
            text-align: center;
            margin: 0 0 30px;
            font-size: 22px;
            font-weight: 600;
        }

        label {
            display: block;
            margin: 16px 0 6px;
            font-size: 13px;
            color: #444;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #000;
        }

        input[type="submit"] {
            width: 100%;
            margin-top: 25px;
            padding: 12px;
            background: #111;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #333;
        }

        .link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }

        .link a {
            color: #111;
            font-weight: bold;
            text-decoration: none;
        }

        .link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="frame">
    <h2>Login</h2>

   <p style='text-align:center; color:green;'>Registration successful! Please log in.</p>   

    <form method="POST" action="login.php">

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Log In">

    </form>

    <div class="link">
        No account?
        <a href="index.html">Register</a>
    </div>
</div>

</body>
</html>