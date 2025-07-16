<!-- index.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Taxi Booking System - Home</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #e0f7fa, #ffffff);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            text-align: center;
            background-color: white;
            padding: 60px 40px;
            border-radius: 20px;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #007bff;
            font-size: 28px;
            margin-bottom: 40px;
        }

        .btn {
            display: inline-block;
            margin: 12px;
            padding: 14px 28px;
            font-size: 18px;
            font-weight: 500;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }

        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚖 Welcome to the Taxi Booking System</h1>
        <a href="booking.html" class="btn">🚗 Book a Taxi</a>
        <a href="admin.html" class="btn">🧾 Admin Panel</a>
        <a href="history.html" class="btn">📜 View Booking History</a> <!-- updated to match styling -->
        <div class="footer">
            &copy; <?php echo date("Y"); ?> Juchang Kim. All rights reserved.
        </div>
    </div>
</body>
</html>
