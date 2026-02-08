<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Common fields
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? ''; // student or lecturer

    // Basic validation
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'Email, password, and role are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Role-specific validation
        if ($role === 'student') {
            $student_name = trim($_POST['student_name'] ?? '');
            $major = trim($_POST['major'] ?? '');
            $semester = trim($_POST['semester'] ?? '');
            if (empty($student_name) || empty($major) || empty($semester)) {
                $error = 'All student fields are required';
            }
        } elseif ($role === 'lecturer') {
            $lecturer_name = trim($_POST['lecturer_name'] ?? '');
            $lecturer_department = trim($_POST['lecturer_department'] ?? '');
            if (empty($lecturer_name) || empty($lecturer_department)) {
                $error = 'All lecturer fields are required';
            }
        } else {
            $error = 'Invalid role selected';
        }
    }

    // Proceed only if no validation errors
    if (!$error) {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT * FROM Users WHERE Email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email already exists';
            } else {
                // Start transaction
                $db->beginTransaction();

                // Insert into Users
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare(
                    "INSERT INTO Users (Email, Password, Role)
                     VALUES (:email, :password, :role)"
                );
                $stmt->execute([
                    'email' => $email,
                    'password' => $hashed_password,
                    'role' => $role
                ]);
                $user_id = $db->lastInsertId();

                // Role-specific inserts
                if ($role === 'student') {
                    // Insert into Students
                    $stmt = $db->prepare(
                        "INSERT INTO Students
                        (User_ID, Name, Starting_Semester, Major, Enrollment_Date, Status)
                        VALUES (:uid, :name, :semester, :major, CURDATE(), 'Active')"
                    );
                    $stmt->execute([
                        'uid' => $user_id,
                        'name' => $student_name,
                        'semester' => $semester,
                        'major' => $major
                    ]);
                    $student_id = $db->lastInsertId();

                    // Generate Student_Code
                    $student_code = 'STU' . date('Y') . str_pad($student_id, 3, '0', STR_PAD_LEFT);

                    $stmt = $db->prepare(
                        "UPDATE Students SET Student_Code = :code WHERE Student_ID = :id"
                    );
                    $stmt->execute([
                        'code' => $student_code,
                        'id' => $student_id
                    ]);

                    $success = "Successfully registered as Student! Your Student ID: $student_code";

                } elseif ($role === 'lecturer') {
                    // Insert into Lecturers
                    $stmt = $db->prepare(
                        "INSERT INTO Lecturers
                        (User_ID, Lecturer_Name, Lecturer_Department)
                        VALUES (:uid, :name, :dept)"
                    );
                    $stmt->execute([
                        'uid' => $user_id,
                        'name' => $lecturer_name,
                        'dept' => $lecturer_department
                    ]);
                    $lecturer_id = $db->lastInsertId();

                    // Generate Lecturer_Code
                    $lecturer_code = 'LEC' . date('Y') . str_pad($lecturer_id, 3, '0', STR_PAD_LEFT);

                    $stmt = $db->prepare(
                        "UPDATE Lecturers SET Lecturer_Code = :code WHERE Lecturer_ID = :id"
                    );
                    $stmt->execute([
                        'code' => $lecturer_code,
                        'id' => $lecturer_id
                    ]);

                    $success = "Successfully registered as Lecturer! Your Lecturer Code: $lecturer_code";
                }

                // Commit transaction
                $db->commit();
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
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
    <body>
            <div class="signup-container">
                <div class="spacer"></div>
                <div class="signup-form">
                    <h1>Sign Up</h1>
                    
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" autocomplete="off">

                        <div class="form-group">
                            <label>Register as:</label>
                            <select name="role" id="role" required>
                                <option value="" disabled selected>Select role</option>
                                <option value="student">Student</option>
                                <option value="lecturer">Lecturer</option>
                            </select>
                        </div>

                        <div id="student-fields" class="role-fields" hidden>

                            <div class="form-group">
                                <label>Full Name:</label>
                                <input type="text" name="student_name">
                            </div>

                            <div class="form-group">
                                <label>Major:</label>
                                <select name="major">
                                    <option value="" disabled selected>Select major</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Data Science">Data Science</option>
                                    <option value="Software Engineering">Software Engineering</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Starting Semester:</label>
                                <select name="semester">
                                    <option value="" disabled selected>Semester</option>
                                    <option value="2025-1">March 2025</option>
                                    <option value="2025-2">July 2025</option>
                                    <option value="2025-3">November 2025</option>
                                    <option value="2026-1">March 2026</option>
                                </select>
                            </div>

                        </div>

                        <div id="lecturer-fields" class="role-fields" hidden>

                            <div class="form-group">
                                <label>Lecturer Name:</label>
                                <input type="text" name="lecturer_name">
                            </div>

                            <div class="form-group">
                                <label>Department:</label>
                                <select name="lecturer_department">
                                    <option value="" disabled selected>Select department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Mathematics">Mathematics</option>
                                    <option value="Arts and Literature">Arts and Literature</option>
                                </select>
                            </div>

                        </div>

                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label>Password:</label>
                            <input type="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password:</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group signup-button-container">
                            <div class="spacer"></div> 
                            <button type="submit" class="login-button">Sign Up</button>
                            <div class="spacer"></div>
                        </div>
                    </form>
                    
                    <div class="link">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </div>
                <div class="spacer"></div>
            </div>  
        <script src="script/script_signup.js"></script>                
    </body>
</html>