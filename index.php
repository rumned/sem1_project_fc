<?php
// index.php - Protected home page
session_start();

// Check if user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}


$email = $_SESSION['email'];
$role = $_SESSION['user_role'];

if ($role === 'student') {
    $name = $_SESSION['student_name'] ?? 'Student';
} elseif ($role === 'lecturer') {
    $name = $_SESSION['lecturer_name'] ?? 'Lecturer';
} elseif ($role === 'admin') {
    $name = 'Admin';
} else{
    $name ='User';
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>StudenTrack - Dashboard</title>
    <link href="assets/styles.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
</head>
<body>
    <header id="dashboard-header">
        <h1>Dashboard</h1>
        <a href="logout.php" class="logout-button">Logout</a>
    </header>
    <div class="dashboard-container">
        <div class="dashboard-banner">
            <div class="dashboard-text">
                <p>Welcome, <?php echo htmlspecialchars($name) ?>.</p>
                <p>You are logged in as: <strong><?php echo htmlspecialchars($email); ?> (<?php echo htmlspecialchars($role); ?>)</strong></p>
            </div>
        </div>
        <div class="dashboard-data">
            <?php
                $host = 'localhost';
                $dbname = 'student_records_project_sem1';
                $username = 'root';
                $password = '';

                try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                // set the PDO error mode to exception
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch(PDOException $e){
                die("Could not connect. " . $e->getMessage());
                }

                try {
                $sql = "SELECT * FROM courses";
                // Execute the SQL query
                $result = $conn->query($sql);
                // Process the result set
                if ($result->rowCount() > 0) {
                    echo "<table><tr><th>ID</th><th>Course Name</th><th>Course Code</th><th>Course Department</th><th>Credits</th><th>Description</th></tr>";
                    // Output data of each row
                    while($row = $result->fetch()) {
                    echo "<tr>";
                    echo "<td>" . $row['Course_ID'] . "</td>";
                    echo "<td>" . $row['Course_Name'] . "</td>";
                    echo "<td>" . $row['Course_Code']. "</td>";
                    echo "<td>" . $row['Course_Department'] . "</td>";
                    echo "<td>" . $row['Credits'] . "</td>";
                    echo "<td>" . $row['Description'] . "</td>";
                    echo "</tr>";
                    }
                    echo "</table>";
                    unset($result);
                }else {
                    echo "No records found.";
                }
                } catch(PDOException $e) {
                echo "Error: " . $e->getMessage();
                }

                $conn = null;
                ?>
        </div>
    </div>
</body>
</html>