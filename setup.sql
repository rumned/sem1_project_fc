-- Student Records Database System with Authentication
-- Uses Hybrid Approach: Numeric AUTO_INCREMENT IDs + Alphanumeric Display Codes
-- Creates 6 tables with 5 linkages (foreign keys) between them

CREATE DATABASE IF NOT EXISTS student_records_project_sem1;
USE student_records_project_sem1;

-- ========================================
-- AUTHENTICATION TABLE
-- ========================================

-- Table 1: Users (for authentication)
CREATE TABLE IF NOT EXISTS Users (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,  -- Hashed password (use bcrypt/password_hash)
    Role ENUM('student', 'lecturer', 'admin') NOT NULL
);

-- ========================================
-- CORE TABLES
-- ========================================

-- Table 2: Students
CREATE TABLE IF NOT EXISTS Students (
    Student_ID INT AUTO_INCREMENT PRIMARY KEY,        -- Internal numeric ID for performance
    Student_Code VARCHAR(20) UNIQUE,                  -- Display ID like 'STU2024001'
    User_ID INT UNIQUE NOT NULL,                      -- LINKAGE 1: One-to-one with Users
    Name VARCHAR(100) NOT NULL,
    Starting_Semester VARCHAR(20) NOT NULL,
    Major VARCHAR(100) NOT NULL,
    Enrollment_Date DATE,
    Status ENUM('Active', 'Inactive', 'Graduated') DEFAULT 'Active',
    
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE
);

-- Table 3: Courses
CREATE TABLE IF NOT EXISTS Courses (
    Course_ID INT AUTO_INCREMENT PRIMARY KEY,
    Course_Name VARCHAR(100) NOT NULL,
    Course_Code VARCHAR(20) UNIQUE NOT NULL,          -- Already alphanumeric like 'CS101'
    Course_Department VARCHAR(100) NOT NULL,
    Credits INT NOT NULL,
    Description TEXT
);

-- Table 4: Lecturers
CREATE TABLE IF NOT EXISTS Lecturers (
    Lecturer_ID INT AUTO_INCREMENT PRIMARY KEY,       -- Internal numeric ID for performance
    Lecturer_Code VARCHAR(20) UNIQUE,                 -- Display ID like 'LEC2024001'
    User_ID INT UNIQUE NOT NULL,                      -- LINKAGE 2: One-to-one with Users
    Lecturer_Name VARCHAR(100) NOT NULL,
    Lecturer_Department VARCHAR(100) NOT NULL,
    
    FOREIGN KEY (User_ID) REFERENCES Users(User_ID) ON DELETE CASCADE
);

-- Table 5: Registration
-- This links Students to Courses (many-to-many relationship through registration)
CREATE TABLE IF NOT EXISTS Registration (
    Registration_ID INT AUTO_INCREMENT PRIMARY KEY,
    Student_ID INT NOT NULL,                          -- LINKAGE 3: Foreign key to Students (numeric for performance)
    Course_ID INT NOT NULL,                           -- LINKAGE 4: Foreign key to Courses (numeric for performance)
    Registration_Date DATE NOT NULL,
    Semester VARCHAR(20) NOT NULL,
    Grade VARCHAR(5),
    Status ENUM('Enrolled', 'Completed', 'Dropped', 'Withdrawn') DEFAULT 'Enrolled',
    
    -- Foreign key constraints
    FOREIGN KEY (Student_ID) REFERENCES Students(Student_ID) ON DELETE CASCADE,
    FOREIGN KEY (Course_ID) REFERENCES Courses(Course_ID) ON DELETE RESTRICT,
    
    -- Prevent duplicate registrations
    UNIQUE KEY unique_registration (Student_ID, Course_ID, Semester)
);

-- Table 6: Course_Lecturers (Junction table)
-- This table handles the many-to-many relationship between Courses and Lecturers
CREATE TABLE IF NOT EXISTS Course_Lecturers (
    Course_Lecturer_ID INT AUTO_INCREMENT PRIMARY KEY,
    Course_ID INT NOT NULL,                           -- LINKAGE 5: Foreign key to Courses (numeric for performance)
    Lecturer_ID INT NOT NULL,                         -- Links to Lecturers (numeric for performance)
    Semester VARCHAR(20) NOT NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (Course_ID) REFERENCES Courses(Course_ID) ON DELETE CASCADE,
    FOREIGN KEY (Lecturer_ID) REFERENCES Lecturers(Lecturer_ID) ON DELETE CASCADE,
    
    -- Prevent duplicate assignments
    UNIQUE KEY unique_teaching_assignment (Course_ID, Lecturer_ID, Semester)
);

-- ==========================================
-- SAMPLE DATA
-- ==========================================

-- Insert users (passwords are hashed version of 'password123')
-- In real application, use: password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO Users (Email, Password, Role) VALUES
('john.smith@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('mary.johnson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('david.lee@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('sarah.williams@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('r.brown@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('l.davis@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('m.chen@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('e.wilson@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lecturer'),
('admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert students (linked to users 1-4)
-- Student_Code will be generated: 'STU' + Year + Padded ID
INSERT INTO Students (User_ID, Student_Code, Name, Current_Semester, Major, Enrollment_Date, Status) VALUES
(1, 'STU2024001', 'John Smith', 'Fall 2024', 'Computer Science', '2022-09-01', 'Active'),
(2, 'STU2024002', 'Mary Johnson', 'Fall 2024', 'Engineering', '2022-09-01', 'Active'),
(3, 'STU2024003', 'David Lee', 'Spring 2024', 'Mathematics','2023-01-15', 'Active'),
(4, 'STU2024004', 'Sarah Williams', 'Fall 2024', 'Computer Science','2021-09-01', 'Active');

-- Insert courses
INSERT INTO Courses (Course_Name, Course_Code, Course_Department, Credits, Description) VALUES
('Introduction to Programming', 'CS101', 'Computer Science', 3, 'Basic programming concepts using Python'),
('Data Structures', 'CS102', 'Computer Science', 4, 'Advanced data structures and algorithms'),
('Calculus I', 'MATH201', 'Mathematics', 4, 'Differential and integral calculus'),
('Technical English I', 'TENG101', 'Literature', 4, 'English for writing scientific and technical reports'),
('Database Systems', 'CS103', 'Computer Science', 3, 'Database design and SQL');

INSERT INTO Courses (Course_Name, Course_Code, Course_Department, Credits, Description) VALUES
('Design Principles', 'ART201', 'Arts and Literature', 4, 'Design principles with emphasis on UI/UX'),
('Product and Organizational Management', 'ART202', 'Arts and Literature', 4, 'Management principles for both product and resources');

-- Insert lecturers (linked to users 5-8)
-- Lecturer_Code will be generated: 'LEC' + Year + Padded ID
INSERT INTO Lecturers (User_ID, Lecturer_Code, Lecturer_Name, Lecturer_Department) VALUES
(5, 'LEC2024001', 'Dr. Robert Brown', 'Computer Science'),
(6, 'LEC2024002', 'Prof. Lisa Davis', 'Computer Science'),
(7, 'LEC2024003', 'Dr. Michael Chen', 'Mathematics'),
(8, 'LEC2024004', 'Prof. Emma Wilson', 'Literature');

-- Insert course-lecturer assignments
INSERT INTO Course_Lecturers (Course_ID, Lecturer_ID, Semester) VALUES
(1, 1, 'Fall 2024'),  
(2, 1, 'Fall 2024'),  
(2, 2, 'Fall 2024'),  
(3, 3, 'Fall 2024'),  
(4, 4, 'Fall 2024'),  
(5, 2, 'Fall 2024'); 

-- Insert registrations
INSERT INTO Registration (Student_ID, Course_ID, Registration_Date, Semester, Grade, Status) VALUES
(1, 1, '2024-08-15', 'Fall 2024', NULL, 'Enrolled'),
(1, 2, '2024-08-15', 'Fall 2024', NULL, 'Enrolled'),
(2, 1, '2024-08-16', 'Fall 2024', NULL, 'Enrolled'),
(2, 4, '2024-08-16', 'Fall 2024', NULL, 'Enrolled'),
(3, 3, '2024-01-10', 'Spring 2024', 'A', 'Completed'),
(4, 1, '2023-08-20', 'Fall 2023', 'A-', 'Completed'),
(4, 2, '2024-08-15', 'Fall 2024', NULL, 'Enrolled'),
(4, 5, '2024-08-15', 'Fall 2024', NULL, 'Enrolled');

-- ==========================================
-- USEFUL VIEWS FOR DISPLAYING DATA
-- ==========================================

-- View for displaying student information with their display codes
CREATE OR REPLACE VIEW Student_Display AS
SELECT 
    s.Student_ID,
    s.Student_Code,
    s.Name,
    s.Major,
    s.Starting_Semester,  
    s.Status,
    u.Email
FROM Students s
JOIN Users u ON s.User_ID = u.User_ID;

Describe Students;


-- View for displaying lecturer information with their display codes
CREATE VIEW Lecturer_Display AS
SELECT 
    l.Lecturer_ID,
    l.Lecturer_Code,             -- Display this to users instead of Lecturer_ID
    l.Lecturer_Name,
    l.Lecturer_Department,
    u.Email
FROM Lecturers l
JOIN Users u ON l.User_ID = u.User_ID;

-- View for displaying registrations with student and course codes
CREATE VIEW Registration_Display AS
SELECT 
    r.Registration_ID,
    s.Student_Code,              -- Show STU2024001 instead of 1
    s.Name as Student_Name,
    c.Course_Code,               -- Show CS101 instead of 1
    c.Course_Name,
    r.Semester,
    r.Grade,
    r.Status,
    r.Registration_Date
FROM Registration r
JOIN Students s ON r.Student_ID = s.Student_ID
JOIN Courses c ON r.Course_ID = c.Course_ID;

-- View for displaying course-lecturer assignments with codes
CREATE VIEW Course_Lecturer_Display AS
SELECT 
    cl.Course_Lecturer_ID,
    c.Course_Code,               -- Show CS101 instead of 1
    c.Course_Name,
    l.Lecturer_Code,             -- Show LEC2024001 instead of 1
    l.Lecturer_Name,
    cl.Semester
FROM Course_Lecturers cl
JOIN Courses c ON cl.Course_ID = c.Course_ID
JOIN Lecturers l ON cl.Lecturer_ID = l.Lecturer_ID;


-- SELECT * FROM Users;
-- -- Update all existing users to have password 'abc'
-- UPDATE Users
-- SET Password = 'abc'
-- WHERE User_ID > 0;
-- DELETE FROM Users WHERE User_ID = 1;
-- DELETE FROM Users WHERE User_ID = 2;
-- DELETE FROM Users WHERE User_ID = 3;
-- DELETE FROM Users WHERE User_ID = 4;
-- DELETE FROM Users WHERE User_ID = 5;
-- DELETE FROM Users WHERE User_ID = 6;
-- DELETE FROM Users WHERE User_ID = 7;
-- DELETE FROM Users WHERE User_ID = 8;
-- DELETE FROM Users WHERE User_ID = 9;
-- ==========================================
-- EXAMPLE QUERIES FOR PHP APPLICATION
-- ==========================================

-- Display student profile (show Student_Code to user):
-- SELECT * FROM Student_Display WHERE Student_ID = ?;

-- Display all registrations for a student:
-- SELECT * FROM Registration_Display WHERE Student_Code = 'STU2024001';

-- Display lecturer's courses:
-- SELECT * FROM Course_Lecturer_Display WHERE Lecturer_Code = 'LEC2024001';

-- When inserting new student, generate Student_Code in PHP:
-- $year = date('Y');
-- $student_code = 'STU' . $year . str_pad($student_id, 3, '0', STR_PAD_LEFT);
-- Result: STU2024001, STU2024002, etc.

-- When inserting new lecturer, generate Lecturer_Code in PHP:
-- $year = date('Y');
-- $lecturer_code = 'LEC' . $year . str_pad($lecturer_id, 3, '0', STR_PAD_LEFT);
-- Result: LEC2024001, LEC2024002, etc.



