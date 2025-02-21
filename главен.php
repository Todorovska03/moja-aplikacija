<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'donations_system');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$createUsersTable = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('organizer', 'donor') DEFAULT 'donor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);";

$createActionsTable = "CREATE TABLE IF NOT EXISTS actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(10, 2) NOT NULL,
    current_amount DECIMAL(10, 2) DEFAULT 0,
    end_date DATE NOT NULL,
    organizer_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id)
);";

$createDonationsTable = "CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    donation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (action_id) REFERENCES actions(id)
);";

if ($conn->query($createUsersTable) === TRUE) {
    echo "Users table created successfully.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

if ($conn->query($createActionsTable) === TRUE) {
    echo "Actions table created successfully.<br>";
} else {
    echo "Error creating actions table: " . $conn->error . "<br>";
}

if ($conn->query($createDonationsTable) === TRUE) {
    echo "Donations table created successfully.<br>";
} else {
    echo "Error creating donations table: " . $conn->error . "<br>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $conn->real_escape_string($_POST['role']);

    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";

    if ($conn->query($sql) === TRUE) {
        echo "Registration successful!<br>";
    } else {
        echo "Error: " . $conn->error . "<br>";
    }
}

session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            echo "Login successful! Welcome, " . $user['name'] . "<br>";
        } else {
            echo "Invalid password.<br>";
        }
    } else {
        echo "No user found with that email.<br>";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_action'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'organizer') {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $goal_amount = $conn->real_escape_string($_POST['goal_amount']);
        $end_date = $conn->real_escape_string($_POST['end_date']);
        $organizer_id = $_SESSION['user_id'];

        $sql = "INSERT INTO actions (title, description, goal_amount, end_date, organizer_id) VALUES ('$title', '$description', '$goal_amount', '$end_date', '$organizer_id')";

        if ($conn->query($sql) === TRUE) {
            echo "Action created successfully!<br>";
        } else {
            echo "Error: " . $conn->error . "<br>";
        }
    } else {
        echo "Only organizers can create actions.<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register & Login</title>
    <link rel="stylesheet" href="дизајн.css">
</head>
<body>
    <h1>User Registration</h1>
    <form method="POST">
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="role">Role:</label><br>
        <select id="role" name="role">
            <option value="donor">Donor</option>
            <option value="organizer">Organizer</option>
        </select><br><br>

        <button type="submit" name="register">Register</button>
    </form>

    <h1>User Login</h1>
    <form method="POST">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <button type="submit" name="login">Login</button>
    </form>

    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'organizer'): ?>
        <h1>Create Action</h1>
        <form method="POST">
            <label for="title">Title:</label><br>
            <input type="text" id="title" name="title" required><br><br>

            <label for="description">Description:</label><br>
            <textarea id="description" name="description" required></textarea><br><br>

            <label for="goal_amount">Goal Amount:</label><br>
            <input type="number" step="0.01" id="goal_amount" name="goal_amount" required><br><br>

            <label for="end_date">End Date:</label><br>
            <input type="date" id="end_date" name="end_date" required><br><br>

            <button type="submit" name="create_action">Create Action</button>
        </form>
    <?php endif; ?>

    <h1>Available Actions</h1>
    <?php
    $actions = $conn->query("SELECT * FROM actions");
    if ($actions->num_rows > 0):
        while ($action = $actions->fetch_assoc()):
    ?>
            <div>
                <h2><?php echo $action['title']; ?></h2>
                <p><?php echo $action['description']; ?></p>
                <p>Goal: $<?php echo $action['goal_amount']; ?>, Current: $<?php echo $action['current_amount']; ?></p>
                <p>Ends on: <?php echo $action['end_date']; ?></p>
            </div>
    <?php
        endwhile;
    else:
        echo "No actions available.";
    endif;
    ?>

</body>
</html>
