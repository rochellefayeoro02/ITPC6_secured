<?php
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: #f7f7f7;
            font-family: Arial, sans-serif;
            color: #222;
        }

        .frame {
            width: 100%;
            max-width: 450px;
            background: #ffffff;
            padding: 50px 40px;
            text-align: center;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .welcome-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 25px;
            border-radius: 50%;
            background: #111;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        h2 {
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #111;
        }

        p {
            font-size: 15px;
            line-height: 1.6;
            color: #666;
        }

        .btn {
            display: block;
            width: 100%;
            margin-top: 30px;
            padding: 13px;
            background: #111;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s ease;
        }

        .btn:hover {
            background: #333;
        }

        @media (max-width: 480px) {
            .frame {
                padding: 40px 25px;
            }

            h2 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <div class="frame">

        <div class="welcome-icon">✓</div>

        <h2>
            Welcome!</h2>

        <p>You have successfully logged in to your account.</p>

        <a href="index.html" class="btn">Log out</a>

    </div>

</body>
</html>