-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 26, 2025 at 05:53 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fyp`
--

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_number` int NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `student_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `student_faculty` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `student_program` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisor_number` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_number`, `user_id`, `student_name`, `student_faculty`, `student_program`, `supervisor_number`) VALUES
(11, '1220531', 'MUHAMMAD IZZUDDIN BIN MUHAMMAD ZAIDI', 'Faculty of Science and Technology', 'Bachelor of Computer Science with Honours (Information Security and Assurance)', 8),
(12, '1220533', 'MUHAMMAD AIMAN BIN SAOD', 'Faculty of Dentistry', 'Bachelor of Dental Surgery (BDS)', 8);

-- --------------------------------------------------------

--
-- Table structure for table `supervisor`
--

CREATE TABLE `supervisor` (
  `supervisor_number` int NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisor_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisor_faculty` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `supervisor_department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor`
--

INSERT INTO `supervisor` (`supervisor_number`, `user_id`, `supervisor_name`, `supervisor_faculty`, `supervisor_department`) VALUES
(8, '1220532', 'DR NORHIDAYAH AZMAN', 'Faculty of Science and Technology', 'Computer Science Department');

-- --------------------------------------------------------

--
-- Table structure for table `thesis`
--

CREATE TABLE `thesis` (
  `thesis_id` int NOT NULL,
  `thesis_title` varchar(255) NOT NULL,
  `thesis_path` varchar(255) NOT NULL,
  `student_number` int NOT NULL,
  `supervisor_number` int NOT NULL,
  `thesis_remark` varchar(255) NOT NULL,
  `submission_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `review_status` enum('pending','approved','failed','revision_required') DEFAULT 'pending',
  `review_date` timestamp NULL DEFAULT NULL,
  `reviewer_remarks` text,
  `publication_consent` enum('pending','approved','denied') DEFAULT 'pending',
  `consent_date` timestamp NULL DEFAULT NULL,
  `allow_publication` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `thesis`
--

INSERT INTO `thesis` (`thesis_id`, `thesis_title`, `thesis_path`, `student_number`, `supervisor_number`, `thesis_remark`, `submission_date`, `review_status`, `review_date`, `reviewer_remarks`, `publication_consent`, `consent_date`, `allow_publication`) VALUES
(37, 'Pdf1', 'uploads/thesis/1220531_1764135337.pdf', 11, 8, '', '2025-11-26 05:35:37', 'approved', '2025-11-26 05:37:28', 'Good', 'pending', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_number` int NOT NULL,
  `user_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_role` enum('student','supervisor','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_logged_in` tinyint(1) DEFAULT '0',
  `ic_number` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_number`, `user_id`, `user_email`, `user_password`, `user_role`, `is_logged_in`, `ic_number`) VALUES
(22, '1220531', 'Student1@mail.com', '44a34d475e43395047ae67c20a1024f2', 'student', 1, 10825140411),
(23, '1220532', 'Supervisor1@mail.com', 'bc76894ae6dbff386bb25c04ae9712d2', 'supervisor', 0, 10825140412),
(24, '1220533', 'Aiman@mail.com', 'c1001660f63e2827927a334a9d9593a6', 'student', 0, 10825140413);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `supervisor_number` (`supervisor_number`);

--
-- Indexes for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD PRIMARY KEY (`supervisor_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `thesis`
--
ALTER TABLE `thesis`
  ADD PRIMARY KEY (`thesis_id`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_supervisor_number` (`supervisor_number`),
  ADD KEY `idx_review_status` (`review_status`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_number`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `user_email_unique` (`user_email`) USING BTREE,
  ADD KEY `idx_ic_number` (`ic_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_number` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `supervisor`
--
ALTER TABLE `supervisor`
  MODIFY `supervisor_number` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `thesis`
--
ALTER TABLE `thesis`
  MODIFY `thesis_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_number` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`supervisor_number`) REFERENCES `supervisor` (`supervisor_number`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD CONSTRAINT `supervisor_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `thesis`
--
ALTER TABLE `thesis`
  ADD CONSTRAINT `thesis_student_fk` FOREIGN KEY (`student_number`) REFERENCES `student` (`student_number`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `thesis_supervisor_fk` FOREIGN KEY (`supervisor_number`) REFERENCES `supervisor` (`supervisor_number`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
