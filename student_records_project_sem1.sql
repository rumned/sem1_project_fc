-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 09, 2026 at 03:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_records_project_sem1`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `Course_ID` int(11) NOT NULL,
  `Course_Name` varchar(100) NOT NULL,
  `Course_Code` varchar(20) NOT NULL,
  `Course_Department` varchar(100) NOT NULL,
  `Credits` int(11) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`Course_ID`, `Course_Name`, `Course_Code`, `Course_Department`, `Credits`, `Description`) VALUES
(1, 'Introduction to Programming', 'CS101', 'Computer Science', 3, 'Basic programming concepts using Python'),
(2, 'Data Structures', 'CS203', 'Computer Science', 4, 'Advanced data structures and algorithms'),
(3, 'Introduction to Calculus', 'MATH101', 'Mathematics', 3, 'Differential and integral calculus'),
(4, 'Technical English I', 'ART101', 'Arts and Literature', 3, 'English for writing scientific and technical reports'),
(5, 'Database Systems', 'CS103', 'Computer Science', 4, 'Database design and SQL'),
(6, 'Front-End Web Development', 'CS102', 'Computer Science', 4, 'Front-end development using HTML/CSS and React framework'),
(7, 'Back-End Web Development', 'CS202', 'Computer Science', 4, 'Back-end development using Node.js framework'),
(8, 'Introduction to Statistics', 'MATH103', 'Mathematics', 3, 'Introduction to probabilty and statistics'),
(9, 'Statistics', 'MATH203', 'Mathematics', 4, 'Statistics for data analytics'),
(10, 'Introduction to Linear Algebra', 'MATH102', 'Mathematics', 3, 'Basic concepts in linear algebra'),
(11, 'Linear Algebra', 'MATH202', 'Mathematics', 4, 'Linear algebra with focus on computer science applications'),
(12, 'Discrete Mathematics', 'MATH201', 'Mathematics', 4, 'Discrete mathematics for computer science'),
(13, 'Technical English II', 'ART102', 'Arts and Literature', 3, 'Oral presentations and discussions in technical situations'),
(14, 'Data Analytics', 'CS201', 'Computer Science', 4, 'Data analysis and engineering using Jupyter notebooks'),
(15, 'Mobile Development I', 'CS204', 'Computer Science', 4, 'Front-end mobile development'),
(16, 'Mobile Development II', 'CS303', 'Computer Science', 4, 'Back-end mobile development'),
(17, 'Application Security', 'CS304', 'Computer Science', 4, 'Secure applications and basic networking concepts'),
(18, 'Network Systems', 'CS302', 'Computer Science', 4, 'Advanced networking concepts and labs'),
(19, 'Business and Entrepreneurship', 'ART301', 'Arts and Literature', 3, 'Practical class to build confidence in entrepreneurship'),
(20, 'Introduction to Machine Learning and Artificial Intelligence', 'CS301', 'Computer Science', 4, 'Introductory concepts to ML and AI'),
(21, 'Final Year Project', 'FYP101', 'Computer Science', 10, 'Capstone project which combines all modules learned to demonstrate understanding of the field'),
(22, 'Design Principles', 'ART201', 'Arts and Literature', 4, 'Design principles with emphasis on UI/UX'),
(23, 'Product and Organizational Management', 'ART202', 'Arts and Literature', 4, 'Management principles for both product and resources');

-- --------------------------------------------------------

--
-- Table structure for table `course_lecturers`
--

CREATE TABLE `course_lecturers` (
  `Course_Lecturer_ID` int(11) NOT NULL,
  `Course_ID` int(11) NOT NULL,
  `Lecturer_ID` int(11) NOT NULL,
  `Semester` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_lecturers`
--

INSERT INTO `course_lecturers` (`Course_Lecturer_ID`, `Course_ID`, `Lecturer_ID`, `Semester`) VALUES
(7, 4, 6, '2026-1'),
(15, 13, 6, '2025-2'),
(8, 13, 6, '2026-1'),
(11, 19, 6, '2026-3'),
(9, 22, 6, '2026-1'),
(10, 23, 6, '2026-2');

-- --------------------------------------------------------

--
-- Stand-in structure for view `course_lecturer_display`
-- (See below for the actual view)
--
CREATE TABLE `course_lecturer_display` (
`Course_Lecturer_ID` int(11)
,`Course_Code` varchar(20)
,`Course_Name` varchar(100)
,`Lecturer_Code` varchar(20)
,`Lecturer_Name` varchar(100)
,`Semester` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `lecturers`
--

CREATE TABLE `lecturers` (
  `Lecturer_ID` int(11) NOT NULL,
  `Lecturer_Code` varchar(20) DEFAULT NULL,
  `User_ID` int(11) NOT NULL,
  `Lecturer_Name` varchar(100) NOT NULL,
  `Lecturer_Department` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturers`
--

INSERT INTO `lecturers` (`Lecturer_ID`, `Lecturer_Code`, `User_ID`, `Lecturer_Name`, `Lecturer_Department`) VALUES
(5, 'LEC2026005', 31, 'Math Teacher', 'Mathematics'),
(6, 'LEC2026006', 38, 'Art Teacher', 'Arts and Literature'),
(7, 'LEC2026007', 39, 'CompSci Teacher', 'Computer Science'),
(8, 'LEC2026008', 40, 'CS Lewis', 'Computer Science'),
(9, 'LEC2026009', 41, 'Srivinasa Ramanujan', 'Mathematics');

-- --------------------------------------------------------

--
-- Stand-in structure for view `lecturer_display`
-- (See below for the actual view)
--
CREATE TABLE `lecturer_display` (
`Lecturer_ID` int(11)
,`Lecturer_Code` varchar(20)
,`Lecturer_Name` varchar(100)
,`Lecturer_Department` varchar(100)
,`Email` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `registration`
--

CREATE TABLE `registration` (
  `Registration_ID` int(11) NOT NULL,
  `Student_ID` int(11) NOT NULL,
  `Course_ID` int(11) NOT NULL,
  `Registration_Date` date NOT NULL,
  `Semester` varchar(20) NOT NULL,
  `Grade` varchar(5) DEFAULT NULL,
  `Status` enum('Enrolled','Completed','Dropped','Withdrawn') DEFAULT 'Enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration`
--

INSERT INTO `registration` (`Registration_ID`, `Student_ID`, `Course_ID`, `Registration_Date`, `Semester`, `Grade`, `Status`) VALUES
(9, 5, 4, '2026-02-09', '2026-1', NULL, 'Enrolled'),
(10, 5, 13, '2026-02-09', '2026-1', NULL, 'Enrolled'),
(11, 5, 1, '2026-02-08', '2026-1', NULL, 'Enrolled'),
(12, 5, 6, '2026-02-08', '2026-1', NULL, 'Enrolled'),
(13, 5, 5, '2026-02-08', '2026-1', NULL, 'Enrolled'),
(14, 5, 3, '2026-02-08', '2026-1', NULL, 'Enrolled'),
(15, 5, 10, '2026-02-08', '2026-1', NULL, 'Enrolled'),
(16, 6, 22, '2026-02-08', '2025-1', NULL, 'Enrolled'),
(17, 6, 23, '2026-02-08', '2025-1', NULL, 'Enrolled'),
(18, 6, 5, '2026-02-08', '2025-1', NULL, 'Enrolled'),
(19, 6, 2, '2026-02-08', '2025-1', NULL, 'Enrolled'),
(20, 6, 16, '2026-02-08', '2025-1', NULL, 'Enrolled'),
(21, 7, 7, '2026-02-08', '2025-2', NULL, 'Dropped'),
(22, 7, 15, '2026-02-08', '2025-2', NULL, 'Dropped'),
(23, 7, 20, '2026-02-08', '2025-2', NULL, 'Dropped'),
(24, 7, 17, '2026-02-08', '2025-2', NULL, 'Enrolled'),
(25, 7, 3, '2026-02-08', '2025-2', NULL, 'Enrolled'),
(26, 7, 10, '2026-02-08', '2025-2', NULL, 'Enrolled'),
(27, 8, 4, '2026-02-08', '2025-3', NULL, 'Dropped'),
(28, 8, 13, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(29, 8, 22, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(30, 8, 23, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(31, 8, 19, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(32, 8, 1, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(33, 8, 6, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(34, 8, 5, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(35, 8, 14, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(36, 8, 7, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(37, 8, 2, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(38, 8, 15, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(39, 8, 20, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(40, 8, 18, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(41, 8, 16, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(42, 8, 17, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(43, 8, 21, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(44, 8, 3, '2026-02-08', '2025-3', NULL, 'Enrolled'),
(45, 8, 10, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(46, 8, 8, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(47, 8, 12, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(48, 8, 11, '2026-02-08', 'Fall 2024', NULL, 'Enrolled'),
(49, 8, 9, '2026-02-08', 'Fall 2024', NULL, 'Dropped'),
(50, 7, 23, '2026-02-09', '2025-2', NULL, 'Enrolled'),
(51, 7, 1, '2026-02-09', '2025-2', NULL, 'Enrolled'),
(52, 7, 9, '2026-02-09', '2025-2', NULL, 'Enrolled'),
(53, 9, 4, '2026-02-09', '2025-1', NULL, 'Enrolled');

-- --------------------------------------------------------

--
-- Stand-in structure for view `registration_display`
-- (See below for the actual view)
--
CREATE TABLE `registration_display` (
`Registration_ID` int(11)
,`Student_Code` varchar(20)
,`Student_Name` varchar(100)
,`Course_Code` varchar(20)
,`Course_Name` varchar(100)
,`Semester` varchar(20)
,`Grade` varchar(5)
,`Status` enum('Enrolled','Completed','Dropped','Withdrawn')
,`Registration_Date` date
);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `Student_ID` int(11) NOT NULL,
  `Student_Code` varchar(20) DEFAULT NULL,
  `User_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Starting_Semester` varchar(20) NOT NULL,
  `Major` varchar(100) NOT NULL,
  `Enrollment_Date` date DEFAULT NULL,
  `Status` enum('Active','Inactive','Graduated') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`Student_ID`, `Student_Code`, `User_ID`, `Name`, `Starting_Semester`, `Major`, `Enrollment_Date`, `Status`) VALUES
(5, 'STU2026005', 28, 'Michael Carrick', '2026-1', 'Data Science', '2026-02-07', 'Active'),
(6, 'STU2026006', 30, 'Ali Bin Abu', '2025-1', 'Software Engineering', '2026-02-08', 'Active'),
(7, 'STU2026007', 35, 'Tan Jia Xing', '2025-2', 'Software Engineering', '2026-02-08', 'Active'),
(8, 'STU2026008', 36, 'Rajeshwari A/P Muthusamy', '2025-3', 'Computer Science', '2026-02-08', 'Active'),
(9, 'STU2026009', 37, 'Juan Silva', '2025-1', 'Data Science', '2026-02-08', 'Active'),
(10, 'STU2026010', 42, 'Alan Turing', '2026-1', 'Computer Science', '2026-02-09', 'Active'),
(11, 'STU2026011', 43, 'Charles Babbage', '2025-1', 'Mathematics', '2026-02-09', 'Active');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_display`
-- (See below for the actual view)
--
CREATE TABLE `student_display` (
`Student_ID` int(11)
,`Student_Code` varchar(20)
,`Name` varchar(100)
,`Major` varchar(100)
,`Starting_Semester` varchar(20)
,`Status` enum('Active','Inactive','Graduated')
,`Email` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('student','lecturer','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `Email`, `Password`, `Role`) VALUES
(1, 'admin@uni.edu', '$2y$10$ayjc27DPAIOQpn/XUNf6X.26dozQVt4uXBKyDpffdU2UVIptHBEkW', 'admin'),
(28, 'student0@uni.edu', '$2y$10$4KpaizVvh2cK/Rw.hgRJoeutdvuGTxXnguX4Dceau6/LyLkUOZ1cm', 'student'),
(30, 'student1@uni.edu', '$2y$10$sB3f9x5UD9EGaJn0DOprBOI5e7r9ljM1SG4jmTMLA0ZD6uWlSe3Du', 'student'),
(31, 'mathlec@uni.edu', '$2y$10$/4M..2GGUU0eqgRV0GkcR.orzGHIgeUPYfkW6roWju1Ti7No0ssAy', 'lecturer'),
(35, 'student2@uni.edu', '$2y$10$BYLFkO3F6lw.OG9Kx6lZ7u2yivYburSfPrHnNwhE2KuIHW.C4iWGq', 'student'),
(36, 'student3@uni.edu', '$2y$10$09VMcl6axONfTe3S1vKPoOMA5WdhrS1Qo8lEdo3/l2XZavnwbMom2', 'student'),
(37, 'student4@uni.edu', '$2y$10$7rSh.SUcTctmnirKOft/YeLhSIqnrgsrVufeRGiJXlx7V1ERkEffe', 'student'),
(38, 'artlec@uni.edu', '$2y$10$bqTfrvVkR1ggD2KxCoOxEOgI6Ztwr4r7gzsNwIVlkxpVIxWBYU2vO', 'lecturer'),
(39, 'compscilec@uni.edu', '$2y$10$Gt08FB5XOPvc.3YlDpczAuSLIrfExqv4LojAOeKWx4nNovg9F.j9K', 'lecturer'),
(40, 'compscilec2@uni.edu', '$2y$10$hDefDPoLS4GAlQVLEDsAB.YNuaoPOWCPq8CllplGDcFu/09NRNbZ2', 'lecturer'),
(41, 'mathlec2@uni.edu', '$2y$10$Q5UdUeXZbPoG4CyKVI/mWuLLR6Or99Dl7x8kvyjqRjE9K3/0Tpt3u', 'lecturer'),
(42, 'alanturing@uni.edu', '$2y$10$yVplh5mDpbtUBFq6Xuf6NO5TsN/wSlvJUbvd91hbbPrSeNlN5kMH2', 'student'),
(43, 'charlesbabbage@uni.edu', '$2y$10$yIqUIYa9znnwFFjd0XAqB.m9h9g5x0tJ4JMZYnzDrbB4XNTyVAhsm', 'student');

-- --------------------------------------------------------

--
-- Structure for view `course_lecturer_display`
--
DROP TABLE IF EXISTS `course_lecturer_display`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `course_lecturer_display`  AS SELECT `cl`.`Course_Lecturer_ID` AS `Course_Lecturer_ID`, `c`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `l`.`Lecturer_Code` AS `Lecturer_Code`, `l`.`Lecturer_Name` AS `Lecturer_Name`, `cl`.`Semester` AS `Semester` FROM ((`course_lecturers` `cl` join `courses` `c` on(`cl`.`Course_ID` = `c`.`Course_ID`)) join `lecturers` `l` on(`cl`.`Lecturer_ID` = `l`.`Lecturer_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `lecturer_display`
--
DROP TABLE IF EXISTS `lecturer_display`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `lecturer_display`  AS SELECT `l`.`Lecturer_ID` AS `Lecturer_ID`, `l`.`Lecturer_Code` AS `Lecturer_Code`, `l`.`Lecturer_Name` AS `Lecturer_Name`, `l`.`Lecturer_Department` AS `Lecturer_Department`, `u`.`Email` AS `Email` FROM (`lecturers` `l` join `users` `u` on(`l`.`User_ID` = `u`.`User_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `registration_display`
--
DROP TABLE IF EXISTS `registration_display`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `registration_display`  AS SELECT `r`.`Registration_ID` AS `Registration_ID`, `s`.`Student_Code` AS `Student_Code`, `s`.`Name` AS `Student_Name`, `c`.`Course_Code` AS `Course_Code`, `c`.`Course_Name` AS `Course_Name`, `r`.`Semester` AS `Semester`, `r`.`Grade` AS `Grade`, `r`.`Status` AS `Status`, `r`.`Registration_Date` AS `Registration_Date` FROM ((`registration` `r` join `students` `s` on(`r`.`Student_ID` = `s`.`Student_ID`)) join `courses` `c` on(`r`.`Course_ID` = `c`.`Course_ID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `student_display`
--
DROP TABLE IF EXISTS `student_display`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_display`  AS SELECT `s`.`Student_ID` AS `Student_ID`, `s`.`Student_Code` AS `Student_Code`, `s`.`Name` AS `Name`, `s`.`Major` AS `Major`, `s`.`Starting_Semester` AS `Starting_Semester`, `s`.`Status` AS `Status`, `u`.`Email` AS `Email` FROM (`students` `s` join `users` `u` on(`s`.`User_ID` = `u`.`User_ID`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`Course_ID`),
  ADD UNIQUE KEY `Course_Code` (`Course_Code`);

--
-- Indexes for table `course_lecturers`
--
ALTER TABLE `course_lecturers`
  ADD PRIMARY KEY (`Course_Lecturer_ID`),
  ADD UNIQUE KEY `unique_teaching_assignment` (`Course_ID`,`Lecturer_ID`,`Semester`),
  ADD KEY `Lecturer_ID` (`Lecturer_ID`);

--
-- Indexes for table `lecturers`
--
ALTER TABLE `lecturers`
  ADD PRIMARY KEY (`Lecturer_ID`),
  ADD UNIQUE KEY `User_ID` (`User_ID`),
  ADD UNIQUE KEY `Lecturer_Code` (`Lecturer_Code`);

--
-- Indexes for table `registration`
--
ALTER TABLE `registration`
  ADD PRIMARY KEY (`Registration_ID`),
  ADD UNIQUE KEY `unique_registration` (`Student_ID`,`Course_ID`,`Semester`),
  ADD KEY `Course_ID` (`Course_ID`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`Student_ID`),
  ADD UNIQUE KEY `User_ID` (`User_ID`),
  ADD UNIQUE KEY `Student_Code` (`Student_Code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `Course_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `course_lecturers`
--
ALTER TABLE `course_lecturers`
  MODIFY `Course_Lecturer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lecturers`
--
ALTER TABLE `lecturers`
  MODIFY `Lecturer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `registration`
--
ALTER TABLE `registration`
  MODIFY `Registration_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `Student_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `course_lecturers`
--
ALTER TABLE `course_lecturers`
  ADD CONSTRAINT `course_lecturers_ibfk_1` FOREIGN KEY (`Course_ID`) REFERENCES `courses` (`Course_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_lecturers_ibfk_2` FOREIGN KEY (`Lecturer_ID`) REFERENCES `lecturers` (`Lecturer_ID`) ON DELETE CASCADE;

--
-- Constraints for table `lecturers`
--
ALTER TABLE `lecturers`
  ADD CONSTRAINT `lecturers_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `registration`
--
ALTER TABLE `registration`
  ADD CONSTRAINT `registration_ibfk_1` FOREIGN KEY (`Student_ID`) REFERENCES `students` (`Student_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `registration_ibfk_2` FOREIGN KEY (`Course_ID`) REFERENCES `courses` (`Course_ID`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
