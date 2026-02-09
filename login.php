<?php
// login.php - User login page
session_start();

// If user is already logged in, redirect to index
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

$error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Start logging
    $log = "=== " . date('Y-m-d H:i:s') . " ===\n";
    
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $log .= "Email: $email\n";
    $log .= "Password length: " . strlen($password) . "\n";
    
    // Validate the data
    if (empty($email) || empty($password)) {
        $error = 'All fields are required';
        $log .= "ERROR: Empty fields\n";
    } else {
        // Authenticate using Users table only
        $statement = $db->prepare("
            SELECT User_ID, Email, Password, Role
            FROM Users
            WHERE Email = :email
        ");
        $statement->execute(['email' => $email]);
        $user = $statement->fetch(PDO::FETCH_OBJ);
        
        $log .= "User found: " . ($user ? "YES (ID: {$user->User_ID})" : "NO") . "\n";
        
        // Verify password
        if ($user && password_verify($password, $user->Password)) {
            $log .= "Password verified: YES\n";
            
            // Password is correct, create session
            $_SESSION['authenticated'] = true;
            $_SESSION['user_id'] = $user->User_ID;
            $_SESSION['email'] = $user->Email;
            $_SESSION['user_role'] = $user->Role;

            // Load role-specific data
            if ($user->Role === 'student') {
                $stmtStudent = $db->prepare("
                    SELECT *
                    FROM Student_Display
                    WHERE Email = :email
                ");
                $stmtStudent->execute(['email' => $user->Email]);
                $student = $stmtStudent->fetch(PDO::FETCH_OBJ);

                if ($student) {
                    $_SESSION['student_id'] = $student->Student_ID;
                    $_SESSION['student_code'] = $student->Student_Code;
                    $_SESSION['student_name'] = $student->Name;
                    $log .= "Student data loaded: {$student->Student_Code}\n";
                }
            } elseif ($user->Role === 'lecturer') {
                $stmtLecturer = $db->prepare("
                    SELECT Lecturer_ID, Lecturer_Code, Lecturer_Name
                    FROM Lecturers
                    WHERE User_ID = :user_id
                ");
                $stmtLecturer->execute(['user_id' => $user->User_ID]);
                $lecturer = $stmtLecturer->fetch(PDO::FETCH_OBJ);

                if ($lecturer) {
                    $_SESSION['lecturer_id'] = $lecturer->Lecturer_ID;
                    $_SESSION['lecturer_code'] = $lecturer->Lecturer_Code;
                    $_SESSION['lecturer_name'] = $lecturer->Lecturer_Name;
                    $log .= "Lecturer data loaded: {$lecturer->Lecturer_Code}\n";
                }
            }
            
            $log .= "About to redirect...\n";
            file_put_contents('login_debug.txt', $log . "\n", FILE_APPEND);
            
            // Redirect to index page
            header('Location: index.php');
            exit;
            
        } else {
            $log .= "Password verified: NO\n";
            $log .= "password_verify result: " . (password_verify($password, $user->Password ?? '') ? 'true' : 'false') . "\n";
            $error = 'Invalid credentials';
        }
    }
    
    file_put_contents('login_debug.txt', $log . "\n", FILE_APPEND);
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StudenTrack - One Stop Portal</title>
    <link href="assets/styles.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
  </head>
  <body class="login-page">
    <header><h1>StudenTrack</h1></header>
    <section id="main-page">
      <div class="main-page-container">
        <div class="main-page-container-top">
          <div class="main-page-hero">
            <h2 class="main-page-subtitle">Your student life, in one portal</h2>
          </div>
          <div class="main-page-login">
            <div class="spacer"></div>
            <div>
              <h2 style="text-align:center;">Login</h2>
    
              <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
              <?php endif; ?>
    
              <form method="POST" action="">
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" size="35" required >
                    </div>
                    
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button class="login-button" type="submit">Login</button>
              </form>
              <div class="link">
                  Don't have an account? <a href="signup.php">Sign up here</a>
              </div>
            </div>
            <div class="spacer"></div>
            <div class="spacer"></div>
          </div>
        </div>
      </div>
    </section>
    <footer>
      <div>&copy Ramdzuanny Musram</div>
      <div>All rights reserved.</div>
    </footer>
  </body>
</html>