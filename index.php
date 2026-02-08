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

// Handle course registration (for students)
if ($role === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_courses'])) {
    $selected_courses = $_POST['courses'] ?? [];
    
    if (empty($selected_courses)) {
        $error = 'Please select at least one course';
    } else {
        try {
            $db->beginTransaction();
            
            // Get student's semester
            $student_semester_query = $db->prepare("SELECT Starting_Semester FROM Students WHERE Student_ID = ?");
            $student_semester_query->execute([$student_id]);
            $student_data = $student_semester_query->fetch(PDO::FETCH_ASSOC);
            $starting_semester = $student_data['Starting_Semester'];
            
            foreach ($selected_courses as $course_id) {
                $check = $db->prepare("SELECT * FROM Registration WHERE Student_ID = ? AND Course_ID = ? AND Semester = ?");
                $check->execute([$student_id, $course_id, $starting_semester]);
                
                if ($check->rowCount() == 0) {
                    $stmt = $db->prepare("INSERT INTO Registration (Student_ID, Course_ID, Registration_Date, Semester, Status) VALUES (?, ?, CURDATE(), ?, 'Enrolled')");
                    $stmt->execute([$student_id, $course_id, $starting_semester]);
                }
            }
            
            $db->commit();
            $success = 'Successfully registered for selected courses!';
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
        $success = 'Course dropped successfully';
    } catch (PDOException $e) {
        $error = 'Error dropping course: ' . $e->getMessage();
    }
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
                    <p>Student ID: <strong><?php echo htmlspecialchars($_SESSION['student_code']); ?></strong> | Starting Semester: <strong><?php echo htmlspecialchars($student_data['Starting_Semester']); ?></strong></p>
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
                <!-- STUDENT DASHBOARD -->
                
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
                                $available_courses = $db->prepare("
                                    SELECT c.*
                                    FROM Courses c
                                    WHERE c.Course_ID NOT IN (
                                        SELECT Course_ID 
                                        FROM Registration 
                                        WHERE Student_ID = ? 
                                        AND Status IN ('Enrolled', 'Completed')
                                    )
                                    ORDER BY c.Course_Code
                                ");
                                $available_courses->execute([$student_id]);
                                
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
                            $my_registrations = $db->prepare("
                                SELECT r.*, c.Course_Code, c.Course_Name, c.Credits
                                FROM Registration r
                                JOIN Courses c ON r.Course_ID = c.Course_ID
                                WHERE r.Student_ID = ?
                                ORDER BY r.Semester DESC, c.Course_Code
                            ");
                            $my_registrations->execute([$student_id]);
                            
                            while ($reg = $my_registrations->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reg['Course_Code']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Course_Name']); ?></td>
                                <td><?php echo $reg['Credits']; ?></td>
                                <td><?php echo htmlspecialchars($reg['Semester']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Status']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Grade'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($reg['Status'] === 'Enrolled'): ?>
                                        <a href="?drop=<?php echo $reg['Registration_ID']; ?>" onclick="return confirm('Drop this course?')">Drop</a>
                                    <?php else: ?>
                                        -
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
                                <td><?php echo htmlspecialchars($lec['Semester']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($role === 'lecturer'): ?>
                <!-- LECTURER DASHBOARD -->
                
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $my_courses = $db->prepare("
                                SELECT c.*, cl.Semester
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
                                <td><?php echo htmlspecialchars($course['Semester']); ?></td>
                                <td><?php echo htmlspecialchars($course['Description']); ?></td>
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
                                <td><?php echo htmlspecialchars($student['Semester']); ?></td>
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
                                <td><?php echo htmlspecialchars($lec['Semester']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($role === 'admin'): ?>
                <!-- ADMIN DASHBOARD - See Everything -->
                
                <!-- All Students -->
                <div class="dashboard-data">
                    <h2>All Students</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Major</th>
                                <th>Semester</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_students = $db->query("
                                SELECT s.*, u.Email
                                FROM Students s
                                JOIN Users u ON s.User_ID = u.User_ID
                                ORDER BY s.Student_Code
                            ");
                            
                            while ($student = $all_students->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['Student_Code']); ?></td>
                                <td><?php echo htmlspecialchars($student['Name']); ?></td>
                                <td><?php echo htmlspecialchars($student['Email']); ?></td>
                                <td><?php echo htmlspecialchars($student['Major']); ?></td>
                                <td><?php echo htmlspecialchars($student['Starting_Semester']); ?></td>
                                <td><?php echo htmlspecialchars($student['Status']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Lecturers -->
                <div class="dashboard-data">
                    <h2>All Lecturers</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Lecturer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_lecturers_admin = $db->query("
                                SELECT l.*, u.Email
                                FROM Lecturers l
                                JOIN Users u ON l.User_ID = u.User_ID
                                ORDER BY l.Lecturer_Code
                            ");
                            
                            while ($lecturer = $all_lecturers_admin->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lecturer['Lecturer_Code']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['Lecturer_Name']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['Email']); ?></td>
                                <td><?php echo htmlspecialchars($lecturer['Lecturer_Department']); ?></td>
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

                <!-- All Registrations -->
                <div class="dashboard-data">
                    <h2>All Registrations</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Student Name</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $all_registrations = $db->query("
                                SELECT 
                                    s.Student_Code,
                                    s.Name as Student_Name,
                                    c.Course_Code,
                                    c.Course_Name,
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
                                <td><?php echo htmlspecialchars($reg['Course_Name']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Semester']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Status']); ?></td>
                                <td><?php echo htmlspecialchars($reg['Grade'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- All Course-Lecturer Assignments -->
                <div class="dashboard-data">
                    <h2>Course-Lecturer Assignments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Lecturer Name</th>
                                <th>Semester</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $course_lecturers = $db->query("
                                SELECT 
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
                                <td><?php echo htmlspecialchars($cl['Semester']); ?></td>
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