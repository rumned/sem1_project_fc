<?php
// index.php - Protected home page with role-specific dashboards
session_start();

// Check if user is logged in
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$email = $_SESSION['email'];
$role = $_SESSION['user_role'];

if ($role === 'student') {
    $name = $_SESSION['student_name'] ?? 'Student';
    $student_id = $_SESSION['student_id'];
} elseif ($role === 'lecturer') {
    $name = $_SESSION['lecturer_name'] ?? 'Lecturer';
    $lecturer_id = $_SESSION['lecturer_id'];
} elseif ($role === 'admin') {
    $name = 'Admin';
} else {
    $name = 'User';
}

$success = '';
$error = '';

// ========================================
// STUDENT ACTIONS
// ========================================

// Handle course registration (for students)
if ($role === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_courses'])) {
    $selected_courses = $_POST['courses'] ?? [];
    
    if (empty($selected_courses)) {
        $error = 'Please select at least one course';
    } else {
        try {
            $db->beginTransaction();
            
            // Get student's starting semester
            $student_semester_query = $db->prepare("SELECT Starting_Semester FROM Students WHERE Student_ID = ?");
            $student_semester_query->execute([$student_id]);
            $student_data = $student_semester_query->fetch(PDO::FETCH_ASSOC);
            $starting_semester = $student_data['Starting_Semester'];
            
            foreach ($selected_courses as $course_id) {
                // Check if already registered (any status)
                $check = $db->prepare("SELECT * FROM Registration WHERE Student_ID = ? AND Course_ID = ? AND Semester = ?");
                $check->execute([$student_id, $course_id, $starting_semester]);
                
                if ($check->rowCount() == 0) {
                    // New registration
                    $stmt = $db->prepare("INSERT INTO Registration (Student_ID, Course_ID, Registration_Date, Semester, Status) VALUES (?, ?, CURDATE(), ?, 'Enrolled')");
                    $stmt->execute([$student_id, $course_id, $starting_semester]);
                } else {
                    // Re-registering after drop - update status to Enrolled
                    $stmt = $db->prepare("UPDATE Registration SET Status = 'Enrolled', Registration_Date = CURDATE() WHERE Student_ID = ? AND Course_ID = ? AND Semester = ?");
                    $stmt->execute([$student_id, $course_id, $starting_semester]);
                }
            }
            
            $db->commit();
            
            // Redirect to refresh page
            header('Location: index.php?success=registered');
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Handle course drop (for students)
if ($role === 'student' && isset($_GET['drop']) && is_numeric($_GET['drop'])) {
    $registration_id = $_GET['drop'];
    
    try {
        $stmt = $db->prepare("UPDATE Registration SET Status = 'Dropped' WHERE Registration_ID = ? AND Student_ID = ?");
        $stmt->execute([$registration_id, $student_id]);
        
        // Redirect to refresh page
        header('Location: index.php?success=dropped');
        exit;
        
    } catch (PDOException $e) {
        $error = 'Error dropping course: ' . $e->getMessage();
    }
}

// ========================================
// LECTURER ACTIONS
// ========================================

// Handle course teaching assignment (for lecturers)
if ($role === 'lecturer' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_courses'])) {
    $selected_courses = $_POST['courses'] ?? [];
    $semester = $_POST['semester'] ?? '2025-1';
    
    if (empty($selected_courses)) {
        $error = 'Please select at least one course';
    } else {
        try {
            $db->beginTransaction();
            
            foreach ($selected_courses as $course_id) {
                // Check if already assigned
                $check = $db->prepare("SELECT * FROM Course_Lecturers WHERE Lecturer_ID = ? AND Course_ID = ? AND Semester = ?");
                $check->execute([$lecturer_id, $course_id, $semester]);
                
                if ($check->rowCount() == 0) {
                    $stmt = $db->prepare("INSERT INTO Course_Lecturers (Course_ID, Lecturer_ID, Semester) VALUES (?, ?, ?)");
                    $stmt->execute([$course_id, $lecturer_id, $semester]);
                }
            }
            
            $db->commit();
            
            // Redirect to refresh page
            header('Location: index.php?success=assigned');
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'Assignment failed: ' . $e->getMessage();
        }
    }
}

// Handle course unassignment (for lecturers)
if ($role === 'lecturer' && isset($_GET['unassign']) && is_numeric($_GET['unassign'])) {
    $assignment_id = $_GET['unassign'];
    
    try {
        $stmt = $db->prepare("DELETE FROM Course_Lecturers WHERE Course_Lecturer_ID = ? AND Lecturer_ID = ?");
        $stmt->execute([$assignment_id, $lecturer_id]);
        
        // Redirect to refresh page
        header('Location: index.php?success=unassigned');
        exit;
        
    } catch (PDOException $e) {
        $error = 'Error unassigning course: ' . $e->getMessage();
    }
}

// ========================================
// ADMIN ACTIONS
// ========================================

// Handle admin add student
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $email_new = trim($_POST['email']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $major = $_POST['major'];
    $semester = $_POST['semester'];
    
    try {
        $db->beginTransaction();
        
        // Check if email exists
        $check = $db->prepare("SELECT * FROM Users WHERE Email = ?");
        $check->execute([$email_new]);
        if ($check->rowCount() > 0) {
            throw new Exception('Email already exists');
        }
        
        // Insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO Users (Email, Password, Role) VALUES (?, ?, 'student')");
        $stmt->execute([$email_new, $hashed_password]);
        $user_id = $db->lastInsertId();
        
        // Insert student
        $stmt = $db->prepare("INSERT INTO Students (User_ID, Name, Starting_Semester, Major, Enrollment_Date, Status) VALUES (?, ?, ?, ?, CURDATE(), 'Active')");
        $stmt->execute([$user_id, $name, $semester, $major]);
        $student_id_new = $db->lastInsertId();
        
        // Generate Student_Code
        $year = date('Y');
        $student_code = 'STU' . $year . str_pad($student_id_new, 3, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("UPDATE Students SET Student_Code = ? WHERE Student_ID = ?");
        $stmt->execute([$student_code, $student_id_new]);
        
        $db->commit();
        header('Location: index.php?success=student_added');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error adding student: ' . $e->getMessage();
    }
}

// Handle admin add lecturer
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lecturer'])) {
    $email_new = trim($_POST['email']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $department = $_POST['department'];
    
    try {
        $db->beginTransaction();
        
        // Check if email exists
        $check = $db->prepare("SELECT * FROM Users WHERE Email = ?");
        $check->execute([$email_new]);
        if ($check->rowCount() > 0) {
            throw new Exception('Email already exists');
        }
        
        // Insert user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO Users (Email, Password, Role) VALUES (?, ?, 'lecturer')");
        $stmt->execute([$email_new, $hashed_password]);
        $user_id = $db->lastInsertId();
        
        // Insert lecturer
        $stmt = $db->prepare("INSERT INTO Lecturers (User_ID, Lecturer_Name, Lecturer_Department) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $name, $department]);
        $lecturer_id_new = $db->lastInsertId();
        
        // Generate Lecturer_Code
        $year = date('Y');
        $lecturer_code = 'LEC' . $year . str_pad($lecturer_id_new, 3, '0', STR_PAD_LEFT);
        
        $stmt = $db->prepare("UPDATE Lecturers SET Lecturer_Code = ? WHERE Lecturer_ID = ?");
        $stmt->execute([$lecturer_code, $lecturer_id_new]);
        
        $db->commit();
        header('Location: index.php?success=lecturer_added');
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error adding lecturer: ' . $e->getMessage();
    }
}

// Handle admin add course-lecturer assignment
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $lecturer_id_assign = $_POST['lecturer_id'];
    $course_id_assign = $_POST['course_id'];
    $semester_assign = $_POST['semester'];
    
    try {
        // Check if already assigned
        $check = $db->prepare("SELECT * FROM Course_Lecturers WHERE Lecturer_ID = ? AND Course_ID = ? AND Semester = ?");
        $check->execute([$lecturer_id_assign, $course_id_assign, $semester_assign]);
        
        if ($check->rowCount() == 0) {
            $stmt = $db->prepare("INSERT INTO Course_Lecturers (Course_ID, Lecturer_ID, Semester) VALUES (?, ?, ?)");
            $stmt->execute([$course_id_assign, $lecturer_id_assign, $semester_assign]);
            header('Location: index.php?success=assignment_added');
            exit;
        } else {
            $error = 'This assignment already exists';
        }
    } catch (PDOException $e) {
        $error = 'Error adding assignment: ' . $e->getMessage();
    }
}

// Handle admin delete student
if ($role === 'admin' && isset($_GET['delete_student'])) {
    $student_id_to_delete = $_GET['delete_student'];
    try {
        $stmt = $db->prepare("DELETE FROM Users WHERE User_ID = (SELECT User_ID FROM Students WHERE Student_ID = ?)");
        $stmt->execute([$student_id_to_delete]);
        header('Location: index.php?success=student_deleted');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting student: ' . $e->getMessage();
    }
}

// Handle admin delete lecturer
if ($role === 'admin' && isset($_GET['delete_lecturer'])) {
    $lecturer_id_to_delete = $_GET['delete_lecturer'];
    try {
        $stmt = $db->prepare("DELETE FROM Users WHERE User_ID = (SELECT User_ID FROM Lecturers WHERE Lecturer_ID = ?)");
        $stmt->execute([$lecturer_id_to_delete]);
        header('Location: index.php?success=lecturer_deleted');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting lecturer: ' . $e->getMessage();
    }
}

// Handle admin delete registration
if ($role === 'admin' && isset($_GET['delete_registration'])) {
    $reg_id = $_GET['delete_registration'];
    try {
        $stmt = $db->prepare("DELETE FROM Registration WHERE Registration_ID = ?");
        $stmt->execute([$reg_id]);
        header('Location: index.php?success=registration_deleted');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting registration: ' . $e->getMessage();
    }
}

// Handle admin delete course assignment
if ($role === 'admin' && isset($_GET['delete_assignment'])) {
    $assignment_id = $_GET['delete_assignment'];
    try {
        $stmt = $db->prepare("DELETE FROM Course_Lecturers WHERE Course_Lecturer_ID = ?");
        $stmt->execute([$assignment_id]);
        header('Location: index.php?success=assignment_deleted');
        exit;
    } catch (PDOException $e) {
        $error = 'Error deleting assignment: ' . $e->getMessage();
    }
}

// Handle admin edit student
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $student_id_edit = $_POST['student_id'];
    $user_id_edit = $_POST['user_id'];
    $email_edit = $_POST['email'];
    $name = $_POST['name'];
    $major = $_POST['major'];
    $semester = $_POST['semester'];
    $status = $_POST['status'];
    
    try {
        $db->beginTransaction();
        
        // Update email in Users table
        $stmt = $db->prepare("UPDATE Users SET Email = ? WHERE User_ID = ?");
        $stmt->execute([$email_edit, $user_id_edit]);
        
        // Update student info
        $stmt = $db->prepare("UPDATE Students SET Name = ?, Major = ?, Starting_Semester = ?, Status = ? WHERE Student_ID = ?");
        $stmt->execute([$name, $major, $semester, $status, $student_id_edit]);
        
        $db->commit();
        header('Location: index.php?success=student_updated');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Error updating student: ' . $e->getMessage();
    }
}

// Handle admin edit lecturer
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_lecturer'])) {
    $lecturer_id_edit = $_POST['lecturer_id'];
    $user_id_edit = $_POST['user_id'];
    $email_edit = $_POST['email'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    
    try {
        $db->beginTransaction();
        
        // Update email in Users table
        $stmt = $db->prepare("UPDATE Users SET Email = ? WHERE User_ID = ?");
        $stmt->execute([$email_edit, $user_id_edit]);
        
        // Update lecturer info
        $stmt = $db->prepare("UPDATE Lecturers SET Lecturer_Name = ?, Lecturer_Department = ? WHERE Lecturer_ID = ?");
        $stmt->execute([$name, $department, $lecturer_id_edit]);
        
        $db->commit();
        header('Location: index.php?success=lecturer_updated');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $error = 'Error updating lecturer: ' . $e->getMessage();
    }
}

// Handle admin edit registration
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_registration'])) {
    $reg_id = $_POST['registration_id'];
    $semester = $_POST['semester'];
    $status = $_POST['status'];
    $grade = $_POST['grade'];
    
    try {
        $stmt = $db->prepare("UPDATE Registration SET Semester = ?, Status = ?, Grade = ? WHERE Registration_ID = ?");
        $stmt->execute([$semester, $status, $grade, $reg_id]);
        header('Location: index.php?success=registration_updated');
        exit;
    } catch (PDOException $e) {
        $error = 'Error updating registration: ' . $e->getMessage();
    }
}

// Handle admin edit course-lecturer assignment
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $semester = $_POST['semester'];
    
    try {
        $stmt = $db->prepare("UPDATE Course_Lecturers SET Semester = ? WHERE Course_Lecturer_ID = ?");
        $stmt->execute([$semester, $assignment_id]);
        header('Location: index.php?success=assignment_updated');
        exit;
    } catch (PDOException $e) {
        $error = 'Error updating assignment: ' . $e->getMessage();
    }
}

// Display success messages from URL parameter
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'registered':
            $success = 'Successfully registered for selected courses!';
            break;
        case 'dropped':
            $success = 'Course dropped successfully!';
            break;
        case 'assigned':
            $success = 'Successfully assigned to courses!';
            break;
        case 'unassigned':
            $success = 'Successfully unassigned from course!';
            break;
        case 'student_added':
            $success = 'Student added successfully!';
            break;
        case 'lecturer_added':
            $success = 'Lecturer added successfully!';
            break;
        case 'assignment_added':
            $success = 'Course assignment added successfully!';
            break;
        case 'student_deleted':
            $success = 'Student deleted successfully!';
            break;
        case 'lecturer_deleted':
            $success = 'Lecturer deleted successfully!';
            break;
        case 'registration_deleted':
            $success = 'Registration deleted successfully!';
            break;
        case 'assignment_deleted':
            $success = 'Assignment deleted successfully!';
            break;
        case 'student_updated':
            $success = 'Student updated successfully!';
            break;
        case 'lecturer_updated':
            $success = 'Lecturer updated successfully!';
            break;
        case 'registration_updated':
            $success = 'Registration updated successfully!';
            break;
        case 'assignment_updated':
            $success = 'Assignment updated successfully!';
            break;
    }
}

// Helper function to display semester
function displaySemester($semester) {
    $semesters = [
        '2025-1' => 'March 2025',
        '2025-2' => 'July 2025',
        '2025-3' => 'November 2025',
        '2026-1' => 'March 2026',
        '2026-2' => 'July 2026',
        '2026-3' => 'November 2026',
    ];
    return $semesters[$semester] ?? $semester;
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
        <div class="dashboard-banner">
            <div class="dashboard-text">
                <p>Welcome, <strong><?php echo htmlspecialchars($name); ?></strong></p>
                <?php if ($role === 'student'): ?>
                    <?php
                    $student_info = $db->prepare("SELECT Starting_Semester FROM Students WHERE Student_ID = ?");
                    $student_info->execute([$student_id]);
                    $student_data = $student_info->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <p>Email: <strong><?php echo htmlspecialchars($email); ?> (<?php echo htmlspecialchars($role); ?>)</strong></p>
                    <p>Student ID: <strong><?php echo htmlspecialchars($_SESSION['student_code']); ?></strong> | Starting Semester: <strong><?php echo displaySemester($student_data['Starting_Semester']); ?></strong></p>
                <?php elseif ($role === 'lecturer'): ?>
                    <p>Email: <strong><?php echo htmlspecialchars($email); ?> (<?php echo htmlspecialchars($role); ?>)</strong></p>
                    <p>Lecturer ID: <strong><?php echo htmlspecialchars($_SESSION['lecturer_code']); ?></strong></p>
                <?php else: ?>
                    <p>Email: <strong><?php echo htmlspecialchars($email); ?> (<?php echo htmlspecialchars($role); ?>)</strong></p>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </header>
    
    <div class="dashboard-container">
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-box">
            
            <?php if ($role === 'student'): ?>
                <!-- ========================================
                     STUDENT DASHBOARD
                     ======================================== -->
                
                <!-- Section 1: Available Courses with Checkboxes -->
                <div class="dashboard-data">
                    <h2>Available Courses - Register Now</h2>
                    <form method="POST" action="">
                        <table>
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Department</th>
                                    <th>Credits</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get student's starting semester
                                $student_semester_query = $db->prepare("SELECT Starting_Semester FROM Students WHERE Student_ID = ?");
                                $student_semester_query->execute([$student_id]);
                                $student_data_current = $student_semester_query->fetch(PDO::FETCH_ASSOC);
                                $starting_semester = $student_data_current['Starting_Semester'];
                                
                                // Show courses not currently enrolled or completed
                                $available_courses = $db->prepare("
                                    SELECT c.*
                                    FROM Courses c
                                    WHERE c.Course_ID NOT IN (
                                        SELECT Course_ID 
                                        FROM Registration 
                                        WHERE Student_ID = ? 
                                        AND Semester = ?
                                        AND Status IN ('Enrolled', 'Completed')
                                    )
                                    ORDER BY c.Course_Code
                                ");
                                $available_courses->execute([$student_id, $starting_semester]);
                                
                                while ($course = $available_courses->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="courses[]" value="<?php echo $course['Course_ID']; ?>"></td>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Department']); ?></td>
                                    <td><?php echo $course['Credits']; ?></td>
                                    <td><?php echo htmlspecialchars($course['Description']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <button class="login-button" type="submit" name="register_courses">Register for Selected Courses</button>
                    </form>
                </div>

                <!-- Section 2: My Registrations -->
                <div class="dashboard-data">
                    <h2>My Course Registrations</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Show all registrations ordered by date (latest first)
                            $my_registrations = $db->prepare("
                                SELECT r.*, c.Course_Code, c.Course_Name, c.Credits
                                FROM Registration r
                                JOIN Courses c ON r.Course_ID = c.Course_ID
                                WHERE r.Student_ID = ?
                                ORDER BY r.Registration_Date DESC, c.Course_Code
                            ");
                            $my_registrations->execute([$student_id]);
                            
                            while ($reg = $my_registrations->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Course_Name']); ?></td>
                                <td><?php echo $reg['Credits']; ?></td>
                                <td><?php echo displaySemester($reg['Semester']); ?></td>
                                <td><strong><?php echo htmlspecialchars($reg['Status']); ?></strong></td>
                                <td><?php echo htmlspecialchars($reg['Grade'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($reg['Status'] === 'Enrolled'): ?>
                                        <button class = "logout-button">
                                            <a href="?drop=<?php echo $reg['Registration_ID']; ?>" onclick="return confirm('Drop this course?')">Drop</a>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Section 3: My Course Lecturers -->
                <div class="dashboard-data">
                    <h2>My Course Lecturers</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Lecturer Name</th>
                                <th>Department</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_lecturers = $db->prepare("
                                SELECT DISTINCT c.Course_Code, c.Course_Name, l.Lecturer_Name, l.Lecturer_Department, cl.Semester
                                FROM Registration r
                                JOIN Courses c ON r.Course_ID = c.Course_ID
                                JOIN Course_Lecturers cl ON c.Course_ID = cl.Course_ID
                                JOIN Lecturers l ON cl.Lecturer_ID = l.Lecturer_ID
                                WHERE r.Student_ID = ? AND r.Status = 'Enrolled'
                                ORDER BY c.Course_Code
                            ");
                            $my_lecturers->execute([$student_id]);
                            
                            while ($lec = $my_lecturers->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lec['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Lecturer_Name']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Lecturer_Department']); ?></td>
                                <td><?php echo displaySemester($lec['Semester']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($role === 'lecturer'): ?>
                <!-- ========================================
                     LECTURER DASHBOARD
                     ======================================== -->
                
                <!-- Section 0: Assign Yourself to Courses -->
                <div class="dashboard-data">
                    <h2>Available Courses - Assign Yourself to Teach</h2>
                    <form method="POST" action="">
                        <div style="display:flex; justify-content:center; align-items:center;gap:var(--small);">
                            <label style="color:var(--bodycolor); font-size:var(--medium);">Semester: </label>
                            <select style="max-width:fit-content;" name="semester" required>
                                <option value="2025-1">March 2025</option>
                                <option value="2025-2">July 2025</option>
                                <option value="2025-3">November 2025</option>
                                <option value="2026-1">March 2026</option>
                                <option value="2026-2">July 2026</option>
                                <option value="2026-3">November 2026</option>
                            </select>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Department</th>
                                    <th>Credits</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Show all courses
                                $all_courses_lec = $db->query("SELECT * FROM Courses ORDER BY Course_Code");
                                
                                while ($course = $all_courses_lec->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr>
                                    <td><input type="checkbox" name="courses[]" value="<?php echo $course['Course_ID']; ?>"></td>
                                    <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($course['Course_Department']); ?></td>
                                    <td><?php echo $course['Credits']; ?></td>
                                    <td><?php echo htmlspecialchars($course['Description']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <button class="login-button" type="submit" name="assign_courses">Assign to Selected Courses</button>
                    </form>
                </div>
                
                <!-- Section 1: My Teaching Assignments -->
                <div class="dashboard-data">
                    <h2>My Teaching Assignments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Semester</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_courses = $db->prepare("
                                SELECT c.*, cl.Semester, cl.Course_Lecturer_ID
                                FROM Course_Lecturers cl
                                JOIN Courses c ON cl.Course_ID = c.Course_ID
                                WHERE cl.Lecturer_ID = ?
                                ORDER BY cl.Semester DESC, c.Course_Code
                            ");
                            $my_courses->execute([$lecturer_id]);
                            
                            while ($course = $my_courses->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Department']); ?></td>
                                <td><?php echo $course['Credits']; ?></td>
                                <td><?php echo displaySemester($course['Semester']); ?></td>
                                <td><?php echo htmlspecialchars($course['Description']); ?></td>
                                <td>
                                    <button class="logout-button">
                                        <a href="?unassign=<?php echo $course['Course_Lecturer_ID']; ?>" onclick="return confirm('Unassign from this course?')">Unassign</a>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Section 2: Students in My Courses -->
                <div class="dashboard-data">
                    <h2>Students in My Courses</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Major</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_students = $db->prepare("
                                SELECT DISTINCT 
                                    c.Course_Code,
                                    s.Student_Code,
                                    s.Name as Student_Name,
                                    s.Major,
                                    r.Status,
                                    r.Grade,
                                    r.Semester
                                FROM Course_Lecturers cl
                                JOIN Registration r ON cl.Course_ID = r.Course_ID
                                JOIN Students s ON r.Student_ID = s.Student_ID
                                JOIN Courses c ON cl.Course_ID = c.Course_ID
                                WHERE cl.Lecturer_ID = ?
                                ORDER BY c.Course_Code, s.Name
                            ");
                            $my_students->execute([$lecturer_id]);
                            
                            while ($student = $my_students->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($student['Student_Code']); ?></td>
                                <td><?php echo htmlspecialchars($student['Student_Name']); ?></td>
                                <td><?php echo htmlspecialchars($student['Major']); ?></td>
                                <td><?php echo htmlspecialchars($student['Status']); ?></td>
                                <td><?php echo htmlspecialchars($student['Grade'] ?? 'N/A'); ?></td>
                                <td><?php echo displaySemester($student['Semester']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Section 3: All Course-Lecturer Assignments -->
                <div class="dashboard-data">
                    <h2>All Course-Lecturer Assignments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Lecturer ID</th>
                                <th>Lecturer Name</th>
                                <th>Department</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_lecturers = $db->query("
                                SELECT 
                                    c.Course_Code,
                                    c.Course_Name,
                                    l.Lecturer_Code,
                                    l.Lecturer_Name,
                                    l.Lecturer_Department,
                                    cl.Semester
                                FROM Course_Lecturers cl
                                JOIN Courses c ON cl.Course_ID = c.Course_ID
                                JOIN Lecturers l ON cl.Lecturer_ID = l.Lecturer_ID
                                ORDER BY c.Course_Code, l.Lecturer_Name
                            ");
                            
                            while ($lec = $all_lecturers->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lec['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Lecturer_Code']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Lecturer_Name']); ?></td>
                                <td><?php echo htmlspecialchars($lec['Lecturer_Department']); ?></td>
                                <td><?php echo displaySemester($lec['Semester']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($role === 'admin'): ?>
                <!-- ========================================
                     ADMIN DASHBOARD - Full CRUD Operations
                     ======================================== -->
                
                <!-- ADD NEW STUDENT FORM -->
                <div class="dashboard-data">
                    <h2>Add New Student</h2>
                    <form method="POST" action="" style="border: solid 4px var(--rm5color); padding: 20px; border-radius: 5px;">
                        <table style="width: auto;">
                            <tr>
                                <td><label>Email:</label></td>
                                <td><input type="email" name="email" required style="width: 250px;"></td>
                                <td><label>Password:</label></td>
                                <td><input type="password" name="password" required style="width: 150px;"></td>
                            </tr>
                            <tr style="background-color: whitesmoke";>
                                <td><label>Full Name:</label></td>
                                <td><input type="text" name="name" required style="width: 250px;"></td>
                                <td><label>Major:</label></td>
                                <td>
                                    <select name="major" required style="width: 150px;">
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Data Science">Data Science</option>
                                        <option value="Software Engineering">Software Engineering</option>
                                        <option value="Engineering">Engineering</option>
                                        <option value="Mathematics">Mathematics</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><label>Starting Semester:</label></td>
                                <td>
                                    <select name="semester" required style="width: 250px;">
                                        <option value="2025-1">March 2025</option>
                                        <option value="2025-2">July 2025</option>
                                        <option value="2025-3">November 2025</option>
                                        <option value="2026-1">March 2026</option>
                                        <option value="2026-2">July 2026</option>
                                        <option value="2026-3">November 2026</option>
                                    </select>
                                </td>
                                <td colspan="2">
                                    <button type="submit" name="add_student" class="login-button">Add Student</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- ADD NEW LECTURER FORM -->
                <div class="dashboard-data">
                    <h2>Add New Lecturer</h2>
                    <form method="POST" action="" style="border: solid 4px var(--rm5color); padding: 20px; border-radius: 5px;">
                        <table style="width: auto;">
                            <tr>
                                <td><label>Email:</label></td>
                                <td><input type="email" name="email" required style="width: 250px;"></td>
                                <td><label>Password:</label></td>
                                <td><input type="password" name="password" required style="width: 150px;"></td>
                            </tr>
                            <tr style="background-color: whitesmoke";>
                                <td><label>Full Name:</label></td>
                                <td><input type="text" name="name" required style="width: 250px;"></td>
                                <td><label>Department:</label></td>
                                <td>
                                    <select name="department" required style="width: 150px;">
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Mathematics">Mathematics</option>
                                        <option value="Arts and Literature">Arts and Literature</option>
                                        <option value="Literature">Literature</option>
                                        <option value="Engineering">Engineering</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="4">
                                    <button type="submit" name="add_lecturer" class="login-button">Add Lecturer</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- ADD COURSE-LECTURER ASSIGNMENT FORM -->
                <div class="dashboard-data">
                    <h2>Add Course-Lecturer Assignment</h2>
                    <form method="POST" action="" style="border: solid 4px var(--rm5color); padding: 20px; border-radius: 5px;">
                        <table style="width: auto;">
                            <tr>
                                <td><label>Lecturer:</label></td>
                                <td>
                                    <select name="lecturer_id" required style="width: 250px;">
                                        <option value="">Select Lecturer</option>
                                        <?php
                                        $lecturers_list = $db->query("SELECT Lecturer_ID, Lecturer_Code, Lecturer_Name FROM Lecturers ORDER BY Lecturer_Name");
                                        while ($lec = $lecturers_list->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <option value="<?php echo $lec['Lecturer_ID']; ?>">
                                            <?php echo htmlspecialchars($lec['Lecturer_Code'] . ' - ' . $lec['Lecturer_Name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><label>Course:</label></td>
                                <td>
                                    <select name="course_id" required style="width: 250px;">
                                        <option value="">Select Course</option>
                                        <?php
                                        $courses_list = $db->query("SELECT Course_ID, Course_Code, Course_Name FROM Courses ORDER BY Course_Code");
                                        while ($crs = $courses_list->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <option value="<?php echo $crs['Course_ID']; ?>">
                                            <?php echo htmlspecialchars($crs['Course_Code'] . ' - ' . $crs['Course_Name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><label>Semester:</label></td>
                                <td>
                                    <select name="semester" required style="width: 150px;">
                                        <option value="2025-1">March 2025</option>
                                        <option value="2025-2">July 2025</option>
                                        <option value="2025-3">November 2025</option>
                                        <option value="2026-1">March 2026</option>
                                        <option value="2026-2">July 2026</option>
                                        <option value="2026-3">November 2026</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" name="add_assignment" class="login-button">Add Assignment</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- All Students with Edit/Delete -->
                <div class="dashboard-data">
                    <h2>All Students (Edit/Delete)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Major</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_students = $db->query("
                                SELECT s.*, u.Email, u.User_ID
                                FROM Students s
                                JOIN Users u ON s.User_ID = u.User_ID
                                ORDER BY s.Student_Code
                            ");
                            
                            while ($student = $all_students->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['Student_Code']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="student_id" value="<?php echo $student['Student_ID']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $student['User_ID']; ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($student['Name']); ?>" style="width:150px;">
                                </td>
                                <td>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($student['Email']); ?>" style="width:200px;">
                                </td>
                                <td>
                                        <select name="major" style="width:150px;">
                                            <option value="Computer Science" <?php echo $student['Major'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                            <option value="Data Science" <?php echo $student['Major'] === 'Data Science' ? 'selected' : ''; ?>>Data Science</option>
                                            <option value="Software Engineering" <?php echo $student['Major'] === 'Software Engineering' ? 'selected' : ''; ?>>Software Engineering</option>
                                            <option value="Engineering" <?php echo $student['Major'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                            <option value="Mathematics" <?php echo $student['Major'] === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                        </select>
                                </td>
                                <td>
                                        <select name="semester" style="width:120px;">
                                            <option value="2025-1" <?php echo $student['Starting_Semester'] === '2025-1' ? 'selected' : ''; ?>>March 2025</option>
                                            <option value="2025-2" <?php echo $student['Starting_Semester'] === '2025-2' ? 'selected' : ''; ?>>July 2025</option>
                                            <option value="2025-3" <?php echo $student['Starting_Semester'] === '2025-3' ? 'selected' : ''; ?>>November 2025</option>
                                            <option value="2026-1" <?php echo $student['Starting_Semester'] === '2026-1' ? 'selected' : ''; ?>>March 2026</option>
                                            <option value="2026-2" <?php echo $student['Starting_Semester'] === '2026-2' ? 'selected' : ''; ?>>July 2026</option>
                                            <option value="2026-3" <?php echo $student['Starting_Semester'] === '2026-3' ? 'selected' : ''; ?>>November 2026</option>
                                        </select>
                                </td>
                                <td>
                                        <select name="status">
                                            <option value="Active" <?php echo $student['Status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="Inactive" <?php echo $student['Status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="Graduated" <?php echo $student['Status'] === 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                        </select>
                                </td>
                                <td>
                                    <button class="logout-button" style="background-color:var(--loginbtncolor);"type="submit" name="edit_student">Save</button>
                                    <button class="logout-button">
                                        <a href="?delete_student=<?php echo $student['Student_ID']; ?>" onclick="return confirm('Delete this student? This will delete all their data!')">Delete</a>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Lecturers with Edit/Delete -->
                <div class="dashboard-data">
                    <h2>All Lecturers (Edit/Delete)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Lecturer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_lecturers_admin = $db->query("
                                SELECT l.*, u.Email, u.User_ID
                                FROM Lecturers l
                                JOIN Users u ON l.User_ID = u.User_ID
                                ORDER BY l.Lecturer_Code
                            ");
                            
                            while ($lecturer = $all_lecturers_admin->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lecturer['Lecturer_Code']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="lecturer_id" value="<?php echo $lecturer['Lecturer_ID']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $lecturer['User_ID']; ?>">
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($lecturer['Lecturer_Name']); ?>" style="width:150px;">
                                </td>
                                <td>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($lecturer['Email']); ?>" style="width:200px;">
                                </td>
                                <td>
                                        <select name="department" style="width:180px;">
                                            <option value="Computer Science" <?php echo $lecturer['Lecturer_Department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                            <option value="Mathematics" <?php echo $lecturer['Lecturer_Department'] === 'Mathematics' ? 'selected' : ''; ?>>Mathematics</option>
                                            <option value="Arts and Literature" <?php echo $lecturer['Lecturer_Department'] === 'Arts and Literature' ? 'selected' : ''; ?>>Arts and Literature</option>
                                            <option value="Literature" <?php echo $lecturer['Lecturer_Department'] === 'Literature' ? 'selected' : ''; ?>>Literature</option>
                                            <option value="Engineering" <?php echo $lecturer['Lecturer_Department'] === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        </select>
                                </td>
                                <td>
                                    <button class="logout-button" style="background-color:var(--loginbtncolor);" type="submit" name="edit_lecturer">Save</button>
                                    <button class="logout-button">
                                        <a href="?delete_lecturer=<?php echo $lecturer['Lecturer_ID']; ?>" onclick="return confirm('Delete this lecturer? This will delete all their data!')">Delete</a>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Courses -->
                <div class="dashboard-data">
                    <h2>All Courses</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_courses = $db->query("SELECT * FROM Courses ORDER BY Course_Code");
                            
                            while ($course = $all_courses->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($course['Course_Department']); ?></td>
                                <td><?php echo $course['Credits']; ?></td>
                                <td><?php echo htmlspecialchars($course['Description']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Registrations with Edit/Delete -->
                <div class="dashboard-data">
                    <h2>All Registrations (Edit/Delete)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course Code</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_registrations = $db->query("
                                SELECT 
                                    r.Registration_ID,
                                    s.Student_Code,
                                    s.Name as Student_Name,
                                    c.Course_Code,
                                    r.Semester,
                                    r.Status,
                                    r.Grade
                                FROM Registration r
                                JOIN Students s ON r.Student_ID = s.Student_ID
                                JOIN Courses c ON r.Course_ID = c.Course_ID
                                ORDER BY r.Semester DESC, s.Student_Code
                            ");
                            
                            while ($reg = $all_registrations->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['Student_Code']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Student_Name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Course_Code']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $reg['Registration_ID']; ?>">
                                        <select name="semester" style="width:120px;">
                                            <option value="2025-1" <?php echo $reg['Semester'] === '2025-1' ? 'selected' : ''; ?>>March 2025</option>
                                            <option value="2025-2" <?php echo $reg['Semester'] === '2025-2' ? 'selected' : ''; ?>>July 2025</option>
                                            <option value="2025-3" <?php echo $reg['Semester'] === '2025-3' ? 'selected' : ''; ?>>November 2025</option>
                                            <option value="2026-1" <?php echo $reg['Semester'] === '2026-1' ? 'selected' : ''; ?>>March 2026</option>
                                            <option value="2026-2" <?php echo $reg['Semester'] === '2026-2' ? 'selected' : ''; ?>>July 2026</option>
                                            <option value="2026-3" <?php echo $reg['Semester'] === '2026-3' ? 'selected' : ''; ?>>November 2026</option>
                                        </select>
                                </td>
                                <td>
                                        <select name="status">
                                            <option value="Enrolled" <?php echo $reg['Status'] === 'Enrolled' ? 'selected' : ''; ?>>Enrolled</option>
                                            <option value="Completed" <?php echo $reg['Status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="Dropped" <?php echo $reg['Status'] === 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                                            <option value="Withdrawn" <?php echo $reg['Status'] === 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                                        </select>
                                </td>
                                <td>
                                        <input type="text" name="grade" value="<?php echo htmlspecialchars($reg['Grade'] ?? ''); ?>" style="width:50px;">
                                </td>
                                <td>
                                    <button class="logout-button" style="background-color:var(--loginbtncolor);" type="submit" name="edit_registration">Save</button>
                                    <button class="logout-button">
                                        <a href="?delete_registration=<?php echo $reg['Registration_ID']; ?>" onclick="return confirm('Delete this registration?')">Delete</a>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Course-Lecturer Assignments with Edit/Delete -->
                <div class="dashboard-data">
                    <h2>Course-Lecturer Assignments (Edit/Delete)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Lecturer Name</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $course_lecturers = $db->query("
                                SELECT 
                                    cl.Course_Lecturer_ID,
                                    c.Course_Code,
                                    c.Course_Name,
                                    l.Lecturer_Name,
                                    cl.Semester
                                FROM Course_Lecturers cl
                                JOIN Courses c ON cl.Course_ID = c.Course_ID
                                JOIN Lecturers l ON cl.Lecturer_ID = l.Lecturer_ID
                                ORDER BY c.Course_Code
                            ");
                            
                            while ($cl = $course_lecturers->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cl['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($cl['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($cl['Lecturer_Name']); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="assignment_id" value="<?php echo $cl['Course_Lecturer_ID']; ?>">
                                        <select name="semester" style="width:120px;">
                                            <option value="2025-1" <?php echo $cl['Semester'] === '2025-1' ? 'selected' : ''; ?>>March 2025</option>
                                            <option value="2025-2" <?php echo $cl['Semester'] === '2025-2' ? 'selected' : ''; ?>>July 2025</option>
                                            <option value="2025-3" <?php echo $cl['Semester'] === '2025-3' ? 'selected' : ''; ?>>November 2025</option>
                                            <option value="2026-1" <?php echo $cl['Semester'] === '2026-1' ? 'selected' : ''; ?>>March 2026</option>
                                            <option value="2026-2" <?php echo $cl['Semester'] === '2026-2' ? 'selected' : ''; ?>>July 2026</option>
                                            <option value="2026-3" <?php echo $cl['Semester'] === '2026-3' ? 'selected' : ''; ?>>November 2026</option>
                                        </select>
                                </td>
                                <td>
                                    <button class="logout-button" style="background-color:var(--loginbtncolor);" type="submit" name="edit_assignment">Save</button>
                                    <button class="logout-button">
                                        <a href="?delete_assignment=<?php echo $cl['Course_Lecturer_ID']; ?>" onclick="return confirm('Delete this assignment?')">Delete</a>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </div>
    </div>
    <footer>
      <p>&copy Ramdzuanny Musram</p>
      <p>All rights reserved.</p>
    </footer>
</body>
</html>