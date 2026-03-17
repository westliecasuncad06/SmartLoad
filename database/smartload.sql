-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 01:23 AM
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
-- Database: `smartload`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `status` enum('Pending','Approved','Rejected','Manual') NOT NULL DEFAULT 'Pending',
  `rationale` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `subject_id`, `teacher_id`, `status`, `rationale`, `created_at`) VALUES
(57, 156, 124, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(58, 157, 141, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(59, 158, 130, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(60, 159, 127, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(61, 160, 136, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(62, 161, 138, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(63, 162, 137, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(64, 163, 123, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(65, 164, 122, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(66, 165, 132, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(67, 166, 135, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(68, 167, 140, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(69, 168, 125, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(70, 169, 139, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(71, 170, 128, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(72, 171, 134, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(73, 172, 131, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(74, 173, 133, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(75, 174, 126, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:12'),
(76, 175, 129, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13'),
(77, 176, 141, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13'),
(78, 177, 138, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13'),
(79, 178, 137, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13'),
(80, 179, 123, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13'),
(81, 180, 122, 'Pending', 'No prerequisite keywords matched expertise tags.', '2026-03-17 07:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `user` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `action_type`, `description`, `user`, `created_at`) VALUES
(1, 'File Upload', 'Teacher CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:27:49'),
(2, 'File Upload', 'Teacher CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:28:12'),
(3, 'File Upload', 'Subject CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:28:28'),
(4, 'File Upload', 'Schedule CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:28:34'),
(5, 'File Upload', 'Teacher CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:38:37'),
(6, 'File Upload', 'Subject CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:38:45'),
(7, 'File Upload', 'Schedule CSV uploaded and imported (historical AY 2024-2025, 1st)', 'Program Chair', '2026-03-17 05:38:53'),
(8, 'Analytics Update', 'Predictive analytics refreshed: 36 teachers, 28 subjects, 30 schedules from historical data', 'Program Chair', '2026-03-17 05:38:56'),
(9, 'File Upload', 'Teacher CSV uploaded and imported (historical AY 2024-2026, 2nd)', 'Program Chair', '2026-03-17 05:40:28'),
(10, 'File Upload', 'Subject CSV uploaded and imported (historical AY 2024-2026, 2nd)', 'Program Chair', '2026-03-17 05:40:38'),
(11, 'File Upload', 'Schedule CSV uploaded and imported (historical AY 2024-2026, 2nd)', 'Program Chair', '2026-03-17 05:40:44'),
(12, 'Analytics Update', 'Predictive analytics refreshed: 48 teachers, 42 subjects, 45 schedules from historical data', 'Program Chair', '2026-03-17 05:40:46'),
(13, 'File Upload', 'Teacher CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 05:41:29'),
(14, 'File Upload', 'Subject CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 05:41:35'),
(15, 'File Upload', 'Schedule CSV uploaded, 0 inserted', 'Program Chair', '2026-03-17 05:41:58'),
(16, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 05:46:06'),
(17, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 05:46:08'),
(18, 'File Upload', 'Subject CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 05:46:16'),
(19, 'File Upload', 'Subject CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 05:46:19'),
(20, 'File Upload', 'Schedule CSV uploaded, 0 inserted', 'Program Chair', '2026-03-17 05:46:25'),
(21, 'Schedule Generation', 'Auto-assigned \"IT207 - Cloud Computing\" to \"Alexander Foster\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(22, 'Schedule Generation', 'Auto-assigned \"IT208 - Web Systems and Technologies\" to \"Amelia Cox\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(23, 'Schedule Generation', 'Auto-assigned \"IT209 - Network Security\" to \"Andrew Thompson\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(24, 'Schedule Generation', 'Auto-assigned \"IT210 - Database Administration\" to \"Benjamin Harris\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(25, 'Schedule Generation', 'Auto-assigned \"IT211 - IT Service Management\" to \"Charlotte Evans\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(26, 'Schedule Generation', 'Auto-assigned \"MATH201 - Linear Algebra\" to \"Christopher Martin\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(27, 'Schedule Generation', 'Auto-assigned \"MATH202 - Differential Equations\" to \"Eleanor Davis\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(28, 'Schedule Generation', 'Auto-assigned \"MATH203 - Probability and Statistics\" to \"Gregory Shaw\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(29, 'Schedule Generation', 'Auto-assigned \"MATH204 - Numerical Methods\" to \"Matthew Jackson\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(30, 'Schedule Generation', 'Auto-assigned \"MATH205 - Discrete Structures\" to \"Natalie Brooks\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(31, 'Schedule Generation', 'Auto-assigned \"MATH206 - Mathematical Modeling\" to \"Olivia Anderson\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(32, 'Schedule Generation', 'Auto-assigned \"GEN104 - Research Methods\" to \"Victoria White\" (score: 30/100).', 'System', '2026-03-17 05:49:09'),
(33, 'Analytics Update', 'Predictive analytics refreshed: 48 teachers, 42 subjects, 45 schedules from historical data', 'Program Chair', '2026-03-17 05:49:51'),
(34, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 05:50:40'),
(35, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 05:50:42'),
(36, 'File Upload', 'Subject CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 05:50:48'),
(37, 'File Upload', 'Subject CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 05:50:51'),
(38, 'File Upload', 'Schedule CSV uploaded, 0 inserted', 'Program Chair', '2026-03-17 05:50:58'),
(39, 'File Upload', 'Schedule CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 05:53:51'),
(40, 'File Upload', 'Subject CSV uploaded, 15 inserted', 'Program Chair', '2026-03-17 05:54:10'),
(41, 'File Upload', 'Teacher CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 05:56:00'),
(42, 'File Upload', 'Subject CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 05:56:13'),
(43, 'File Upload', 'Schedule CSV uploaded, 0 inserted', 'Program Chair', '2026-03-17 05:56:18'),
(44, 'Schedule Generation', 'Auto-assigned \"14 - Thursday\" to \"Alexander Foster\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(45, 'Schedule Generation', 'Auto-assigned \"15 - Friday\" to \"Amelia Cox\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(46, 'Schedule Generation', 'Auto-assigned \"IT207 - Cloud Computing\" to \"Andrew Thompson\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(47, 'Schedule Generation', 'Auto-assigned \"IT208 - Web Systems and Technologies\" to \"Benjamin Harris\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(48, 'Schedule Generation', 'Auto-assigned \"IT209 - Network Security\" to \"Charlotte Evans\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(49, 'Schedule Generation', 'Auto-assigned \"IT210 - Database Administration\" to \"Christopher Martin\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(50, 'Schedule Generation', 'Auto-assigned \"IT211 - IT Service Management\" to \"Eleanor Davis\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(51, 'Schedule Generation', 'Auto-assigned \"MATH201 - Linear Algebra\" to \"Gregory Shaw\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(52, 'Schedule Generation', 'Auto-assigned \"MATH202 - Differential Equations\" to \"Matthew Jackson\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(53, 'Schedule Generation', 'Auto-assigned \"MATH203 - Probability and Statistics\" to \"Natalie Brooks\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(54, 'Schedule Generation', 'Auto-assigned \"MATH204 - Numerical Methods\" to \"Olivia Anderson\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(55, 'Schedule Generation', 'Auto-assigned \"MATH205 - Discrete Structures\" to \"Victoria White\" (score: 30/100).', 'System', '2026-03-17 05:56:21'),
(56, 'Schedule Generation', 'Auto-assigned \"MATH206 - Mathematical Modeling\" to \"Benjamin Harris\" (score: 25/100).', 'System', '2026-03-17 05:56:21'),
(57, 'Schedule Generation', 'Auto-assigned \"GEN104 - Research Methods\" to \"Charlotte Evans\" (score: 25/100).', 'System', '2026-03-17 05:56:21'),
(58, 'File Upload', 'Subject CSV uploaded, 18 inserted, 7 updated', 'Program Chair', '2026-03-17 06:06:14'),
(59, 'File Upload', 'Schedule CSV uploaded, 0 inserted', 'Program Chair', '2026-03-17 06:06:16'),
(60, 'Analytics Update', 'Predictive analytics refreshed: 48 teachers, 42 subjects, 45 schedules from historical data (32 schedule codes backfilled)', 'Program Chair', '2026-03-17 06:16:05'),
(61, 'File Upload', 'Schedule CSV uploaded, 4 inserted', 'Program Chair', '2026-03-17 06:19:38'),
(62, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 06:20:46'),
(63, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 06:20:47'),
(64, 'File Upload', 'Subject CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 06:21:04'),
(65, 'File Upload', 'Teacher CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 06:22:07'),
(66, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 duplicates detected', 'Program Chair', '2026-03-17 06:22:21'),
(67, 'File Upload', 'Teacher CSV uploaded, 0 inserted, 12 updated', 'Program Chair', '2026-03-17 06:22:22'),
(68, 'File Upload', 'Subject CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 06:22:29'),
(69, 'File Upload', 'Teacher CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 06:23:29'),
(70, 'File Upload', 'Subject CSV uploaded, 12 inserted', 'Program Chair', '2026-03-17 06:23:34'),
(71, 'File Upload', 'Teacher CSV uploaded, 25 inserted', 'Program Chair', '2026-03-17 06:30:26'),
(72, 'File Upload', 'Subject CSV uploaded, 18 inserted, 7 duplicates detected', 'Program Chair', '2026-03-17 06:30:32'),
(73, 'File Upload', 'Subject CSV uploaded, 0 inserted, 25 updated', 'Program Chair', '2026-03-17 06:30:33'),
(74, 'File Upload', 'Schedule CSV uploaded, 4 inserted', 'Program Chair', '2026-03-17 06:30:40'),
(75, 'Schedule Generation', 'Auto-assigned \"IT207 - Cloud Computing\" to \"IT211\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(76, 'Schedule Generation', 'Auto-assigned \"IT208 - Web Systems and Technologies\" to \"CS102\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(77, 'Schedule Generation', 'Auto-assigned \"IT209 - Network Security\" to \"IT209\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(78, 'Schedule Generation', 'Auto-assigned \"IT210 - Database Administration\" to \"IT210\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(79, 'Schedule Generation', 'Auto-assigned \"IT211 - IT Service Management\" to \"Alexander Foster\" (score: 30/100).', 'System', '2026-03-17 06:30:44'),
(80, 'Schedule Generation', 'Auto-assigned \"MATH201 - Linear Algebra\" to \"MATH201\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(81, 'Schedule Generation', 'Auto-assigned \"MATH202 - Differential Equations\" to \"MATH202\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(82, 'Schedule Generation', 'Auto-assigned \"MATH203 - Probability and Statistics\" to \"MATH102\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(83, 'Schedule Generation', 'Auto-assigned \"MATH204 - Numerical Methods\" to \"Amelia Cox\" (score: 30/100).', 'System', '2026-03-17 06:30:44'),
(84, 'Schedule Generation', 'Auto-assigned \"MATH205 - Discrete Structures\" to \"MATH203\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(85, 'Schedule Generation', 'Auto-assigned \"MATH206 - Mathematical Modeling\" to \"Andrew Thompson\" (score: 30/100).', 'System', '2026-03-17 06:30:44'),
(86, 'Schedule Generation', 'Auto-assigned \"GEN104 - Research Methods\" to \"Benjamin Harris\" (score: 30/100).', 'System', '2026-03-17 06:30:44'),
(87, 'Schedule Generation', 'Auto-assigned \"IT101 - Introduction to IT\" to \"CS101\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(88, 'Schedule Generation', 'Auto-assigned \"IT102 - Data Communications\" to \"IT102\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(89, 'Schedule Generation', 'Auto-assigned \"CS101 - Programming Fundamentals\" to \"GEN101\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(90, 'Schedule Generation', 'Auto-assigned \"CS102 - Object-Oriented Programming\" to \"IT206\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(91, 'Schedule Generation', 'Auto-assigned \"CS201 - Data Structures\" to \"CS201\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(92, 'Schedule Generation', 'Auto-assigned \"CS210 - Operating Systems\" to \"CS210\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(93, 'Schedule Generation', 'Auto-assigned \"CS220 - Computer Architecture\" to \"CS220\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(94, 'Schedule Generation', 'Auto-assigned \"CS301 - Software Engineering\" to \"CS301\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(95, 'Schedule Generation', 'Auto-assigned \"IT202 - Database Systems\" to \"IT202\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(96, 'Schedule Generation', 'Auto-assigned \"IT204 - Computer Networking\" to \"IT204\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(97, 'Schedule Generation', 'Auto-assigned \"IT205 - Systems Analysis and Design\" to \"IT205\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(98, 'Schedule Generation', 'Auto-assigned \"IT206 - Web Systems and Technologies\" to \"Charlotte Evans\" (score: 30/100).', 'System', '2026-03-17 06:30:44'),
(99, 'Schedule Generation', 'Auto-assigned \"IT240 - Cloud Computing\" to \"IT240\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(100, 'Schedule Generation', 'Auto-assigned \"MATH101 - Calculus I\" to \"GEN102\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(101, 'Schedule Generation', 'Auto-assigned \"MATH102 - Calculus II\" to \"MATH205\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(102, 'Schedule Generation', 'Auto-assigned \"GEN101 - Communication Skills\" to \"IT101\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(103, 'Schedule Generation', 'Auto-assigned \"GEN102 - Ethics and Society\" to \"MATH101\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(104, 'Schedule Generation', 'Auto-assigned \"GEN103 - Technical Writing\" to \"GEN103\" (score: 100/100).', 'System', '2026-03-17 06:30:44'),
(105, 'File Upload', 'Teacher CSV uploaded and imported (historical AY 2025-2026, 1st)', 'Program Chair', '2026-03-17 07:21:48'),
(106, 'File Upload', 'Subject CSV uploaded and imported (historical AY 2025-2026, 1st)', 'Program Chair', '2026-03-17 07:22:00'),
(107, 'File Upload', 'Schedule CSV uploaded and imported (historical AY 2025-2026, 1st)', 'Program Chair', '2026-03-17 07:22:25'),
(108, 'Analytics Update', 'Predictive analytics refreshed: 60 teachers, 54 subjects, 60 schedules from historical data (3 schedule codes backfilled)', 'Program Chair', '2026-03-17 07:22:30'),
(109, 'Analytics Update', 'Predictive analytics refreshed: 60 teachers, 54 subjects, 60 schedules from historical data (0 schedule codes backfilled)', 'Program Chair', '2026-03-17 07:24:25'),
(110, 'File Upload', 'Teacher CSV uploaded, 20 inserted', 'Program Chair', '2026-03-17 07:24:50'),
(111, 'File Upload', 'Subject CSV uploaded, 25 inserted', 'Program Chair', '2026-03-17 07:24:57'),
(112, 'File Upload', 'Schedule CSV uploaded, 4 inserted', 'Program Chair', '2026-03-17 07:25:05'),
(113, 'Schedule Generation', 'Auto-assigned \"IT101 - Introduction to IT\" to \"Alan Turing\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(114, 'Schedule Generation', 'Auto-assigned \"IT102 - Data Communications\" to \"Chloe Kim\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(115, 'Schedule Generation', 'Auto-assigned \"CS101 - Programming Fundamentals\" to \"Daniel Lee\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(116, 'Schedule Generation', 'Auto-assigned \"CS102 - Object-Oriented Programming\" to \"Emily Davis\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(117, 'Schedule Generation', 'Auto-assigned \"CS201 - Data Structures\" to \"Emma Thompson\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(118, 'Schedule Generation', 'Auto-assigned \"CS210 - Operating Systems\" to \"Ethan Nguyen\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(119, 'Schedule Generation', 'Auto-assigned \"CS220 - Computer Architecture\" to \"Isabella Cruz\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(120, 'Schedule Generation', 'Auto-assigned \"CS301 - Software Engineering\" to \"Jane Smith\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(121, 'Schedule Generation', 'Auto-assigned \"IT202 - Database Systems\" to \"John Doe\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(122, 'Schedule Generation', 'Auto-assigned \"IT204 - Computer Networking\" to \"Kevin Chen\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(123, 'Schedule Generation', 'Auto-assigned \"IT205 - Systems Analysis and Design\" to \"Liam Anderson\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(124, 'Schedule Generation', 'Auto-assigned \"IT206 - Web Systems and Technologies\" to \"Lucas Perez\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(125, 'Schedule Generation', 'Auto-assigned \"IT209 - Network Security\" to \"Maria Garcia\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(126, 'Schedule Generation', 'Auto-assigned \"IT210 - Database Administration\" to \"Mia Robinson\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(127, 'Schedule Generation', 'Auto-assigned \"IT211 - IT Service Management\" to \"Michael Johnson\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(128, 'Schedule Generation', 'Auto-assigned \"IT240 - Cloud Computing\" to \"Noah Williams\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(129, 'Schedule Generation', 'Auto-assigned \"MATH101 - Calculus I\" to \"Olivia Wilson\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(130, 'Schedule Generation', 'Auto-assigned \"MATH102 - Calculus II\" to \"Priya Patel\" (score: 30/100).', 'System', '2026-03-17 07:25:12'),
(131, 'Schedule Generation', 'Auto-assigned \"MATH201 - Linear Algebra\" to \"Robert Brown\" (score: 30/100).', 'System', '2026-03-17 07:25:13'),
(132, 'Schedule Generation', 'Auto-assigned \"MATH202 - Differential Equations\" to \"Sophia Martinez\" (score: 30/100).', 'System', '2026-03-17 07:25:13'),
(133, 'Schedule Generation', 'Auto-assigned \"MATH203 - Probability and Statistics\" to \"Chloe Kim\" (score: 25/100).', 'System', '2026-03-17 07:25:13'),
(134, 'Schedule Generation', 'Auto-assigned \"MATH205 - Discrete Structures\" to \"Ethan Nguyen\" (score: 25/100).', 'System', '2026-03-17 07:25:13'),
(135, 'Schedule Generation', 'Auto-assigned \"GEN101 - Communication Skills\" to \"Isabella Cruz\" (score: 25/100).', 'System', '2026-03-17 07:25:13'),
(136, 'Schedule Generation', 'Auto-assigned \"GEN102 - Ethics and Society\" to \"Jane Smith\" (score: 25/100).', 'System', '2026-03-17 07:25:13'),
(137, 'Schedule Generation', 'Auto-assigned \"GEN103 - Technical Writing\" to \"John Doe\" (score: 25/100).', 'System', '2026-03-17 07:25:13');

-- --------------------------------------------------------

--
-- Table structure for table `historical_analytics_metadata`
--

CREATE TABLE `historical_analytics_metadata` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `import_date` datetime NOT NULL DEFAULT current_timestamp(),
  `total_teachers` int(10) UNSIGNED DEFAULT 0,
  `total_subjects` int(10) UNSIGNED DEFAULT 0,
  `total_assignments` int(10) UNSIGNED DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historical_analytics_metadata`
--

INSERT INTO `historical_analytics_metadata` (`id`, `academic_year`, `semester`, `import_date`, `total_teachers`, `total_subjects`, `total_assignments`, `notes`) VALUES
(1, '2022-2023', '1st Semester', '2026-03-17 08:19:18', 6, 6, 5, 'Seeded on 2026-03-17 08:19:18'),
(2, '2022-2023', '2nd Semester', '2026-03-17 08:19:18', 6, 6, 0, 'Seeded on 2026-03-17 08:19:18'),
(3, '2023-2024', '1st Semester', '2026-03-17 08:19:18', 6, 6, 0, 'Seeded on 2026-03-17 08:19:18'),
(4, '2023-2024', '2nd Semester', '2026-03-17 08:19:18', 6, 6, 4, 'Seeded on 2026-03-17 08:19:18');

-- --------------------------------------------------------

--
-- Table structure for table `historical_assignments`
--

CREATE TABLE `historical_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `teacher_email` varchar(150) DEFAULT NULL,
  `status` enum('Assigned','Unassigned','Substituted') NOT NULL DEFAULT 'Assigned',
  `rationale` text DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historical_assignments`
--

INSERT INTO `historical_assignments` (`id`, `academic_year`, `semester`, `subject_id`, `subject_code`, `teacher_id`, `teacher_name`, `teacher_email`, `status`, `rationale`, `recorded_at`) VALUES
(1, '2022-2023', '1st Semester', 55, 'CS101', 61, 'John Doe', 'john.doe@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:17'),
(2, '2022-2023', '1st Semester', 57, 'IT202', 64, 'Robert Brown', 'robert.brown@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:17'),
(3, '2022-2023', '1st Semester', 58, 'IT204', 62, 'Jane Smith', 'jane.smith@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:17'),
(4, '2022-2023', '1st Semester', 59, 'MATH101', 63, 'Alan Turing', 'alan.turing@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:17'),
(5, '2022-2023', '1st Semester', 60, 'CS301', 65, 'Michael Johnson', 'michael.johnson@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:17'),
(8, '2023-2024', '2nd Semester', 73, 'CS201', 81, 'Alan Turing', 'alan.turing@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:18'),
(9, '2023-2024', '2nd Semester', 74, 'CS202', 83, 'Michael Johnson', 'michael.johnson@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:18'),
(10, '2023-2024', '2nd Semester', 78, 'DB301', 82, 'Robert Brown', 'robert.brown@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:18'),
(11, '2023-2024', '2nd Semester', 77, 'AI201', 84, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Assigned', 'Seeded historical assignment', '2026-03-17 08:19:18');

-- --------------------------------------------------------

--
-- Table structure for table `historical_schedules`
--

CREATE TABLE `historical_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `section` varchar(50) DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historical_schedules`
--

INSERT INTO `historical_schedules` (`id`, `academic_year`, `semester`, `subject_id`, `subject_code`, `day_of_week`, `start_time`, `end_time`, `room`, `section`, `recorded_at`) VALUES
(1, '2024-2025', '1st Semester', 1, 'CS101', 'Monday', '08:00:00', '11:00:00', 'Room 101', '', '2026-03-17 05:28:34'),
(2, '2024-2025', '1st Semester', 2, 'IT202', 'Wednesday', '10:00:00', '13:00:00', 'Lab B', '', '2026-03-17 05:28:34'),
(3, '2024-2025', '1st Semester', 3, 'IT204', 'Tuesday', '09:00:00', '12:00:00', 'Lab B', '', '2026-03-17 05:28:34'),
(4, '2024-2025', '1st Semester', 4, 'MATH101', 'Thursday', '14:00:00', '17:00:00', 'Lab A', '', '2026-03-17 05:28:34'),
(5, '2024-2025', '1st Semester', 5, 'CS102', 'Friday', '08:00:00', '10:00:00', 'Room 305', '', '2026-03-17 05:28:34'),
(6, '2024-2025', '1st Semester', 6, 'CS201', 'Monday', '13:00:00', '16:00:00', 'Room 201', '', '2026-03-17 05:28:34'),
(7, '2024-2025', '1st Semester', 7, 'IT102', 'Tuesday', '10:00:00', '12:00:00', 'Lab C', '', '2026-03-17 05:28:34'),
(8, '2024-2025', '1st Semester', 8, 'IT205', 'Wednesday', '14:00:00', '17:00:00', 'Room 102', '', '2026-03-17 05:28:34'),
(9, '2024-2025', '1st Semester', 9, 'MATH102', 'Thursday', '09:00:00', '11:00:00', 'Lab D', '', '2026-03-17 05:28:34'),
(10, '2024-2025', '1st Semester', 10, 'CS301', 'Friday', '13:00:00', '16:00:00', 'Room 303', '', '2026-03-17 05:28:34'),
(11, '2024-2025', '1st Semester', 11, 'CS202', 'Monday', '10:00:00', '12:00:00', 'Room 104', '', '2026-03-17 05:28:34'),
(12, '2024-2025', '1st Semester', 12, 'CS203', 'Tuesday', '13:00:00', '16:00:00', 'Lab C', '', '2026-03-17 05:28:34'),
(13, '2024-2025', '1st Semester', 13, 'GEN101', 'Wednesday', '08:00:00', '10:00:00', 'Room 106', '', '2026-03-17 05:28:34'),
(14, '2024-2025', '1st Semester', 14, 'GEN102', 'Thursday', '11:00:00', '13:00:00', 'Room 202', '', '2026-03-17 05:28:34'),
(15, '2024-2025', '1st Semester', 15, 'CS101', 'Friday', '10:00:00', '12:00:00', 'Room 107', '', '2026-03-17 05:28:34'),
(16, '2024-2025', '1st Semester', 1, 'CS101', 'Monday', '08:00:00', '11:00:00', 'Room 101', '', '2026-03-17 05:38:53'),
(17, '2024-2025', '1st Semester', 2, 'IT202', 'Wednesday', '10:00:00', '13:00:00', 'Lab B', '', '2026-03-17 05:38:53'),
(18, '2024-2025', '1st Semester', 3, 'IT204', 'Tuesday', '09:00:00', '12:00:00', 'Lab B', '', '2026-03-17 05:38:53'),
(19, '2024-2025', '1st Semester', 4, 'MATH101', 'Thursday', '14:00:00', '17:00:00', 'Lab A', '', '2026-03-17 05:38:53'),
(20, '2024-2025', '1st Semester', 5, 'CS102', 'Friday', '08:00:00', '10:00:00', 'Room 305', '', '2026-03-17 05:38:53'),
(21, '2024-2025', '1st Semester', 6, 'CS201', 'Monday', '13:00:00', '16:00:00', 'Room 201', '', '2026-03-17 05:38:53'),
(22, '2024-2025', '1st Semester', 7, 'IT102', 'Tuesday', '10:00:00', '12:00:00', 'Lab C', '', '2026-03-17 05:38:53'),
(23, '2024-2025', '1st Semester', 8, 'IT205', 'Wednesday', '14:00:00', '17:00:00', 'Room 102', '', '2026-03-17 05:38:53'),
(24, '2024-2025', '1st Semester', 9, 'MATH102', 'Thursday', '09:00:00', '11:00:00', 'Lab D', '', '2026-03-17 05:38:53'),
(25, '2024-2025', '1st Semester', 10, 'CS301', 'Friday', '13:00:00', '16:00:00', 'Room 303', '', '2026-03-17 05:38:53'),
(26, '2024-2025', '1st Semester', 11, 'CS202', 'Monday', '10:00:00', '12:00:00', 'Room 104', '', '2026-03-17 05:38:53'),
(27, '2024-2025', '1st Semester', 12, 'CS203', 'Tuesday', '13:00:00', '16:00:00', 'Lab C', '', '2026-03-17 05:38:53'),
(28, '2024-2025', '1st Semester', 13, 'GEN101', 'Wednesday', '08:00:00', '10:00:00', 'Room 106', '', '2026-03-17 05:38:53'),
(29, '2024-2025', '1st Semester', 14, 'GEN102', 'Thursday', '11:00:00', '13:00:00', 'Room 202', '', '2026-03-17 05:38:53'),
(30, '2024-2025', '1st Semester', 15, 'CS101', 'Friday', '10:00:00', '12:00:00', 'Room 107', '', '2026-03-17 05:38:53'),
(31, '2024-2026', '2nd Semester', 16, '', 'Monday', '16:00:00', '18:00:00', 'Room 108', '', '2026-03-17 05:40:44'),
(32, '2024-2026', '2nd Semester', 17, '', 'Tuesday', '08:00:00', '10:00:00', 'Room 109', '', '2026-03-17 05:40:44'),
(33, '2024-2026', '2nd Semester', 18, '', 'Wednesday', '13:00:00', '16:00:00', 'Lab A', '', '2026-03-17 05:40:44'),
(34, '2024-2026', '2nd Semester', 19, '', 'Thursday', '08:00:00', '10:00:00', 'Room 110', '', '2026-03-17 05:40:44'),
(35, '2024-2026', '2nd Semester', 20, '', 'Friday', '16:00:00', '18:00:00', 'Lab D', '', '2026-03-17 05:40:44'),
(36, '2024-2026', '2nd Semester', 21, '', 'Monday', '11:00:00', '13:00:00', 'Room 203', '', '2026-03-17 05:40:44'),
(37, '2024-2026', '2nd Semester', 22, '', 'Tuesday', '16:00:00', '18:00:00', 'Room 204', '', '2026-03-17 05:40:44'),
(38, '2024-2026', '2nd Semester', 23, '', 'Wednesday', '11:00:00', '13:00:00', 'Room 205', '', '2026-03-17 05:40:44'),
(39, '2024-2026', '2nd Semester', 24, '', 'Thursday', '13:00:00', '16:00:00', 'Lab B', '', '2026-03-17 05:40:44'),
(40, '2024-2026', '2nd Semester', 25, '', 'Friday', '11:00:00', '13:00:00', 'Room 206', '', '2026-03-17 05:40:44'),
(41, '2024-2026', '2nd Semester', 26, '', 'Monday', '09:00:00', '11:00:00', 'Room 207', '', '2026-03-17 05:40:44'),
(42, '2024-2026', '2nd Semester', 27, '', 'Tuesday', '11:00:00', '13:00:00', 'Room 208', '', '2026-03-17 05:40:44'),
(43, '2024-2026', '2nd Semester', 28, '', 'Wednesday', '16:00:00', '18:00:00', 'Room 209', '', '2026-03-17 05:40:44'),
(44, '2024-2026', '2nd Semester', 29, 'CS204', 'Thursday', '10:00:00', '12:00:00', 'Room 210', '', '2026-03-17 05:40:44'),
(45, '2024-2026', '2nd Semester', 30, 'CS205', 'Friday', '14:00:00', '17:00:00', 'Room 211', '', '2026-03-17 05:40:44'),
(46, '2025-2026', '1st Semester', 31, '', 'Monday', '08:00:00', '10:00:00', 'COMP-LAB-A', '', '2026-03-17 07:22:24'),
(47, '2025-2026', '1st Semester', 32, '', 'Wednesday', '09:00:00', '12:00:00', 'RM301', '', '2026-03-17 07:22:24'),
(48, '2025-2026', '1st Semester', 33, '', 'Tuesday', '10:00:00', '13:00:00', 'COMP-LAB-B', '', '2026-03-17 07:22:24'),
(49, '2025-2026', '1st Semester', 34, '', 'Thursday', '15:00:00', '18:00:00', 'RM202', '', '2026-03-17 07:22:24'),
(50, '2025-2026', '1st Semester', 35, '', 'Friday', '08:00:00', '11:00:00', 'LAB1', '', '2026-03-17 07:22:24'),
(51, '2025-2026', '1st Semester', 36, '', 'Monday', '13:00:00', '15:00:00', 'RM401', '', '2026-03-17 07:22:24'),
(52, '2025-2026', '1st Semester', 37, '', 'Tuesday', '11:00:00', '13:00:00', 'COMP-LAB-C', '', '2026-03-17 07:22:24'),
(53, '2025-2026', '1st Semester', 38, '', 'Wednesday', '14:00:00', '17:00:00', 'RM501', '', '2026-03-17 07:22:24'),
(54, '2025-2026', '1st Semester', 39, '', 'Thursday', '10:00:00', '12:00:00', 'LAB2', '', '2026-03-17 07:22:24'),
(55, '2025-2026', '1st Semester', 40, '', 'Friday', '14:00:00', '17:00:00', 'RM302', '', '2026-03-17 07:22:24'),
(56, '2025-2026', '1st Semester', 41, '', 'Monday', '10:00:00', '12:00:00', 'RM201', '', '2026-03-17 07:22:24'),
(57, '2025-2026', '1st Semester', 42, '', 'Tuesday', '14:00:00', '17:00:00', 'LAB3', '', '2026-03-17 07:22:24'),
(58, '2025-2026', '1st Semester', 43, 'IT207', 'Wednesday', '08:00:00', '10:00:00', 'RM101', '', '2026-03-17 07:22:24'),
(59, '2025-2026', '1st Semester', 44, 'IT208', 'Thursday', '12:00:00', '14:00:00', 'RM502', '', '2026-03-17 07:22:25'),
(60, '2025-2026', '1st Semester', 45, 'IT209', 'Friday', '10:00:00', '12:00:00', 'RM303', '', '2026-03-17 07:22:25'),
(61, '2022-2023', '1st Semester', 55, 'CS101', 'Monday', '08:00:00', '10:00:00', 'Room 101', 'A', '2026-03-17 08:19:17'),
(62, '2022-2023', '1st Semester', 56, 'CS102', 'Tuesday', '10:00:00', '12:00:00', 'Room 102', 'A', '2026-03-17 08:19:17'),
(63, '2022-2023', '1st Semester', 57, 'IT202', 'Wednesday', '08:00:00', '11:00:00', 'Lab B', 'A', '2026-03-17 08:19:17'),
(64, '2022-2023', '1st Semester', 58, 'IT204', 'Thursday', '13:00:00', '15:00:00', 'Lab C', 'A', '2026-03-17 08:19:17'),
(65, '2022-2023', '1st Semester', 59, 'MATH101', 'Friday', '09:00:00', '11:00:00', 'Room 201', 'A', '2026-03-17 08:19:17'),
(66, '2022-2023', '1st Semester', 60, 'CS301', 'Friday', '13:00:00', '16:00:00', 'Room 303', 'A', '2026-03-17 08:19:17'),
(68, '2023-2024', '2nd Semester', 73, 'CS201', 'Monday', '08:00:00', '11:00:00', 'Room 104', 'B', '2026-03-17 08:19:17'),
(69, '2023-2024', '2nd Semester', 74, 'CS202', 'Tuesday', '13:00:00', '15:00:00', 'Room 105', 'B', '2026-03-17 08:19:17'),
(70, '2023-2024', '2nd Semester', 75, 'IT205', 'Wednesday', '10:00:00', '12:00:00', 'Lab A', 'B', '2026-03-17 08:19:17'),
(71, '2023-2024', '2nd Semester', 76, 'MATH102', 'Thursday', '09:00:00', '11:00:00', 'Room 202', 'B', '2026-03-17 08:19:17'),
(72, '2023-2024', '2nd Semester', 77, 'AI201', 'Friday', '08:00:00', '10:00:00', 'Lab ML', 'B', '2026-03-17 08:19:17'),
(73, '2023-2024', '2nd Semester', 78, 'DB301', 'Friday', '10:00:00', '12:00:00', 'Lab SQL', 'B', '2026-03-17 08:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `historical_subjects`
--

CREATE TABLE `historical_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `original_id` int(10) UNSIGNED DEFAULT NULL,
  `course_code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `program` varchar(100) NOT NULL,
  `units` tinyint(3) UNSIGNED NOT NULL,
  `prerequisites` varchar(255) DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historical_subjects`
--

INSERT INTO `historical_subjects` (`id`, `academic_year`, `semester`, `original_id`, `course_code`, `name`, `program`, `units`, `prerequisites`, `recorded_at`) VALUES
(1, '2024-2025', '1st Semester', NULL, 'CS101', 'Web Development', 'BS Computer Science', 3, 'None', '2026-03-17 05:28:27'),
(2, '2024-2025', '1st Semester', NULL, 'IT202', 'Database Systems', 'BS Information Technology', 3, 'CS101', '2026-03-17 05:28:28'),
(3, '2024-2025', '1st Semester', NULL, 'IT204', 'Computer Networking', 'BS Information Technology', 3, 'IT102', '2026-03-17 05:28:28'),
(4, '2024-2025', '1st Semester', NULL, 'MATH101', 'Calculus I', 'BS Mathematics', 3, 'None', '2026-03-17 05:28:28'),
(5, '2024-2025', '1st Semester', NULL, 'CS102', 'Programming Fundamentals', 'BS Computer Science', 3, 'None', '2026-03-17 05:28:28'),
(6, '2024-2025', '1st Semester', NULL, 'CS201', 'Data Structures', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:28:28'),
(7, '2024-2025', '1st Semester', NULL, 'IT102', 'Introduction to IT', 'BS Information Technology', 3, 'None', '2026-03-17 05:28:28'),
(8, '2024-2025', '1st Semester', NULL, 'IT205', 'Systems Analysis and Design', 'BS Information Technology', 3, 'IT202', '2026-03-17 05:28:28'),
(9, '2024-2025', '1st Semester', NULL, 'MATH102', 'Calculus II', 'BS Mathematics', 3, 'MATH101', '2026-03-17 05:28:28'),
(10, '2024-2025', '1st Semester', NULL, 'CS301', 'Software Engineering', 'BS Computer Science', 3, 'CS201', '2026-03-17 05:28:28'),
(11, '2024-2025', '1st Semester', NULL, 'CS202', 'Object-Oriented Programming', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:28:28'),
(12, '2024-2025', '1st Semester', NULL, 'CS203', 'Discrete Mathematics', 'BS Computer Science', 3, 'MATH101', '2026-03-17 05:28:28'),
(13, '2024-2025', '1st Semester', NULL, 'GEN101', 'Communication Skills', 'General Education', 3, 'None', '2026-03-17 05:28:28'),
(14, '2024-2025', '1st Semester', NULL, 'GEN102', 'Ethics and Society', 'General Education', 3, 'None', '2026-03-17 05:28:28'),
(15, '2024-2025', '1st Semester', NULL, 'CS101', 'Web Development', 'BS Computer Science', 3, 'None', '2026-03-17 05:38:45'),
(16, '2024-2025', '1st Semester', NULL, 'IT202', 'Database Systems', 'BS Information Technology', 3, 'CS101', '2026-03-17 05:38:45'),
(17, '2024-2025', '1st Semester', NULL, 'IT204', 'Computer Networking', 'BS Information Technology', 3, 'IT102', '2026-03-17 05:38:45'),
(18, '2024-2025', '1st Semester', NULL, 'MATH101', 'Calculus I', 'BS Mathematics', 3, 'None', '2026-03-17 05:38:45'),
(19, '2024-2025', '1st Semester', NULL, 'CS102', 'Programming Fundamentals', 'BS Computer Science', 3, 'None', '2026-03-17 05:38:45'),
(20, '2024-2025', '1st Semester', NULL, 'CS201', 'Data Structures', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:38:45'),
(21, '2024-2025', '1st Semester', NULL, 'IT102', 'Introduction to IT', 'BS Information Technology', 3, 'None', '2026-03-17 05:38:45'),
(22, '2024-2025', '1st Semester', NULL, 'IT205', 'Systems Analysis and Design', 'BS Information Technology', 3, 'IT202', '2026-03-17 05:38:45'),
(23, '2024-2025', '1st Semester', NULL, 'MATH102', 'Calculus II', 'BS Mathematics', 3, 'MATH101', '2026-03-17 05:38:45'),
(24, '2024-2025', '1st Semester', NULL, 'CS301', 'Software Engineering', 'BS Computer Science', 3, 'CS201', '2026-03-17 05:38:45'),
(25, '2024-2025', '1st Semester', NULL, 'CS202', 'Object-Oriented Programming', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:38:45'),
(26, '2024-2025', '1st Semester', NULL, 'CS203', 'Discrete Mathematics', 'BS Computer Science', 3, 'MATH101', '2026-03-17 05:38:45'),
(27, '2024-2025', '1st Semester', NULL, 'GEN101', 'Communication Skills', 'General Education', 3, 'None', '2026-03-17 05:38:45'),
(28, '2024-2025', '1st Semester', NULL, 'GEN102', 'Ethics and Society', 'General Education', 3, 'None', '2026-03-17 05:38:45'),
(29, '2024-2026', '2nd Semester', NULL, 'CS204', 'Computer Architecture', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:40:38'),
(30, '2024-2026', '2nd Semester', NULL, 'CS205', 'Operating Systems', 'BS Computer Science', 3, 'CS201', '2026-03-17 05:40:38'),
(31, '2024-2026', '2nd Semester', NULL, 'CS206', 'Human-Computer Interaction', 'BS Computer Science', 3, 'CS101', '2026-03-17 05:40:38'),
(32, '2024-2026', '2nd Semester', NULL, 'CS207', 'Web Programming II', 'BS Computer Science', 3, 'CS101', '2026-03-17 05:40:38'),
(33, '2024-2026', '2nd Semester', NULL, 'CS208', 'Mobile App Development', 'BS Computer Science', 3, 'CS102', '2026-03-17 05:40:38'),
(34, '2024-2026', '2nd Semester', NULL, 'CS209', 'Introduction to AI', 'BS Computer Science', 3, 'CS201', '2026-03-17 05:40:38'),
(35, '2024-2026', '2nd Semester', NULL, 'CS210', 'Machine Learning Fundamentals', 'BS Computer Science', 3, 'CS209', '2026-03-17 05:40:38'),
(36, '2024-2026', '2nd Semester', NULL, 'CS211', 'Data Analytics', 'BS Computer Science', 3, 'IT202', '2026-03-17 05:40:38'),
(37, '2024-2026', '2nd Semester', NULL, 'CS212', 'Information Security', 'BS Computer Science', 3, 'IT204', '2026-03-17 05:40:38'),
(38, '2024-2026', '2nd Semester', NULL, 'CS213', 'Capstone Project I', 'BS Computer Science', 3, 'CS301', '2026-03-17 05:40:38'),
(39, '2024-2026', '2nd Semester', NULL, 'IT201', 'IT Fundamentals II', 'BS Information Technology', 3, 'IT102', '2026-03-17 05:40:38'),
(40, '2024-2026', '2nd Semester', NULL, 'IT203', 'Data Communications', 'BS Information Technology', 3, 'IT204', '2026-03-17 05:40:38'),
(41, '2024-2026', '2nd Semester', NULL, 'IT206', 'IT Project Management', 'BS Information Technology', 3, 'IT205', '2026-03-17 05:40:38'),
(42, '2024-2026', '2nd Semester', NULL, 'GEN103', 'Technical Writing', 'General Education', 3, 'GEN101', '2026-03-17 05:40:38'),
(43, '2025-2026', '1st Semester', NULL, 'IT207', 'Cloud Computing', 'BS Information Technology', 3, 'IT205', '2026-03-17 07:22:00'),
(44, '2025-2026', '1st Semester', NULL, 'IT208', 'Web Systems and Technologies', 'BS Information Technology', 3, 'CS101', '2026-03-17 07:22:00'),
(45, '2025-2026', '1st Semester', NULL, 'IT209', 'Network Security', 'BS Information Technology', 3, 'IT204', '2026-03-17 07:22:00'),
(46, '2025-2026', '1st Semester', NULL, 'IT210', 'Database Administration', 'BS Information Technology', 3, 'IT202', '2026-03-17 07:22:00'),
(47, '2025-2026', '1st Semester', NULL, 'IT211', 'IT Service Management', 'BS Information Technology', 3, 'IT206', '2026-03-17 07:22:00'),
(48, '2025-2026', '1st Semester', NULL, 'MATH201', 'Linear Algebra', 'BS Mathematics', 3, 'MATH102', '2026-03-17 07:22:00'),
(49, '2025-2026', '1st Semester', NULL, 'MATH202', 'Differential Equations', 'BS Mathematics', 3, 'MATH102', '2026-03-17 07:22:00'),
(50, '2025-2026', '1st Semester', NULL, 'MATH203', 'Probability and Statistics', 'BS Mathematics', 3, 'MATH101', '2026-03-17 07:22:00'),
(51, '2025-2026', '1st Semester', NULL, 'MATH204', 'Numerical Methods', 'BS Mathematics', 3, 'MATH201', '2026-03-17 07:22:00'),
(52, '2025-2026', '1st Semester', NULL, 'MATH205', 'Discrete Structures', 'BS Mathematics', 3, 'MATH101', '2026-03-17 07:22:00'),
(53, '2025-2026', '1st Semester', NULL, 'MATH206', 'Mathematical Modeling', 'BS Mathematics', 3, 'MATH202', '2026-03-17 07:22:00'),
(54, '2025-2026', '1st Semester', NULL, 'GEN104', 'Research Methods', 'General Education', 3, 'GEN103', '2026-03-17 07:22:00'),
(55, '2022-2023', '1st Semester', NULL, 'CS101', 'Web Development', 'BS Computer Science', 3, 'None', '2026-03-17 08:19:17'),
(56, '2022-2023', '1st Semester', NULL, 'CS102', 'Programming Fundamentals', 'BS Computer Science', 3, 'None', '2026-03-17 08:19:17'),
(57, '2022-2023', '1st Semester', NULL, 'IT202', 'Database Systems', 'BS Information Technology', 3, 'CS101', '2026-03-17 08:19:17'),
(58, '2022-2023', '1st Semester', NULL, 'IT204', 'Computer Networking', 'BS Information Technology', 3, 'None', '2026-03-17 08:19:17'),
(59, '2022-2023', '1st Semester', NULL, 'MATH101', 'Calculus I', 'BS Mathematics', 3, 'None', '2026-03-17 08:19:17'),
(60, '2022-2023', '1st Semester', NULL, 'CS301', 'Software Engineering', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(61, '2022-2023', '2nd Semester', NULL, 'CS201', 'Data Structures', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(62, '2022-2023', '2nd Semester', NULL, 'CS202', 'Object-Oriented Programming', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(63, '2022-2023', '2nd Semester', NULL, 'IT205', 'Systems Analysis and Design', 'BS Information Technology', 3, 'IT202', '2026-03-17 08:19:17'),
(64, '2022-2023', '2nd Semester', NULL, 'MATH102', 'Calculus II', 'BS Mathematics', 3, 'MATH101', '2026-03-17 08:19:17'),
(65, '2022-2023', '2nd Semester', NULL, 'AI201', 'Intro to Machine Learning', 'BS Computer Science', 3, 'CS201', '2026-03-17 08:19:17'),
(66, '2022-2023', '2nd Semester', NULL, 'DB301', 'Advanced SQL', 'BS Information Technology', 3, 'IT202', '2026-03-17 08:19:17'),
(67, '2023-2024', '1st Semester', NULL, 'CS101', 'Web Development', 'BS Computer Science', 3, 'None', '2026-03-17 08:19:17'),
(68, '2023-2024', '1st Semester', NULL, 'CS102', 'Programming Fundamentals', 'BS Computer Science', 3, 'None', '2026-03-17 08:19:17'),
(69, '2023-2024', '1st Semester', NULL, 'IT202', 'Database Systems', 'BS Information Technology', 3, 'CS101', '2026-03-17 08:19:17'),
(70, '2023-2024', '1st Semester', NULL, 'IT204', 'Computer Networking', 'BS Information Technology', 3, 'None', '2026-03-17 08:19:17'),
(71, '2023-2024', '1st Semester', NULL, 'MATH101', 'Calculus I', 'BS Mathematics', 3, 'None', '2026-03-17 08:19:17'),
(72, '2023-2024', '1st Semester', NULL, 'CS301', 'Software Engineering', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(73, '2023-2024', '2nd Semester', NULL, 'CS201', 'Data Structures', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(74, '2023-2024', '2nd Semester', NULL, 'CS202', 'Object-Oriented Programming', 'BS Computer Science', 3, 'CS102', '2026-03-17 08:19:17'),
(75, '2023-2024', '2nd Semester', NULL, 'IT205', 'Systems Analysis and Design', 'BS Information Technology', 3, 'IT202', '2026-03-17 08:19:17'),
(76, '2023-2024', '2nd Semester', NULL, 'MATH102', 'Calculus II', 'BS Mathematics', 3, 'MATH101', '2026-03-17 08:19:17'),
(77, '2023-2024', '2nd Semester', NULL, 'AI201', 'Intro to Machine Learning', 'BS Computer Science', 3, 'CS201', '2026-03-17 08:19:17'),
(78, '2023-2024', '2nd Semester', NULL, 'DB301', 'Advanced SQL', 'BS Information Technology', 3, 'IT202', '2026-03-17 08:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `historical_teachers`
--

CREATE TABLE `historical_teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `original_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `type` enum('Full-time','Part-time') NOT NULL,
  `max_units` tinyint(3) UNSIGNED NOT NULL,
  `units_assigned` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expertise_tags` varchar(255) DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historical_teachers`
--

INSERT INTO `historical_teachers` (`id`, `academic_year`, `semester`, `original_id`, `name`, `email`, `type`, `max_units`, `units_assigned`, `expertise_tags`, `recorded_at`) VALUES
(1, '2024-2025', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Dev', '2026-03-17 05:27:49'),
(2, '2024-2025', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 05:27:49'),
(3, '2024-2025', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 05:27:49'),
(4, '2024-2025', '1st Semester', NULL, 'Maria Garcia', 'maria.garcia@university.edu', 'Full-time', 18, 0, 'Literature, Writing', '2026-03-17 05:27:49'),
(5, '2024-2025', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 05:27:49'),
(6, '2024-2025', '1st Semester', NULL, 'Emily Davis', 'emily.davis@university.edu', 'Part-time', 12, 0, 'UI/UX Design, Figma', '2026-03-17 05:27:49'),
(7, '2024-2025', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 05:27:49'),
(8, '2024-2025', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 05:27:49'),
(9, '2024-2025', '1st Semester', NULL, 'Daniel Lee', 'daniel.lee@university.edu', 'Part-time', 12, 0, 'Cloud Computing, AWS', '2026-03-17 05:27:49'),
(10, '2024-2025', '1st Semester', NULL, 'Olivia Wilson', 'olivia.wilson@university.edu', 'Full-time', 18, 0, 'Cybersecurity, Ethical Hacking', '2026-03-17 05:27:49'),
(11, '2024-2025', '1st Semester', NULL, 'Kevin Chen', 'kevin.chen@university.edu', 'Full-time', 18, 0, 'Data Structures, Algorithms, Discrete Math', '2026-03-17 05:27:49'),
(12, '2024-2025', '1st Semester', NULL, 'Priya Patel', 'priya.patel@university.edu', 'Full-time', 18, 0, 'Operating Systems, Computer Architecture', '2026-03-17 05:27:49'),
(13, '2024-2025', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Dev', '2026-03-17 05:28:12'),
(14, '2024-2025', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 05:28:12'),
(15, '2024-2025', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 05:28:12'),
(16, '2024-2025', '1st Semester', NULL, 'Maria Garcia', 'maria.garcia@university.edu', 'Full-time', 18, 0, 'Literature, Writing', '2026-03-17 05:28:12'),
(17, '2024-2025', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 05:28:12'),
(18, '2024-2025', '1st Semester', NULL, 'Emily Davis', 'emily.davis@university.edu', 'Part-time', 12, 0, 'UI/UX Design, Figma', '2026-03-17 05:28:12'),
(19, '2024-2025', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 05:28:12'),
(20, '2024-2025', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 05:28:12'),
(21, '2024-2025', '1st Semester', NULL, 'Daniel Lee', 'daniel.lee@university.edu', 'Part-time', 12, 0, 'Cloud Computing, AWS', '2026-03-17 05:28:12'),
(22, '2024-2025', '1st Semester', NULL, 'Olivia Wilson', 'olivia.wilson@university.edu', 'Full-time', 18, 0, 'Cybersecurity, Ethical Hacking', '2026-03-17 05:28:12'),
(23, '2024-2025', '1st Semester', NULL, 'Kevin Chen', 'kevin.chen@university.edu', 'Full-time', 18, 0, 'Data Structures, Algorithms, Discrete Math', '2026-03-17 05:28:12'),
(24, '2024-2025', '1st Semester', NULL, 'Priya Patel', 'priya.patel@university.edu', 'Full-time', 18, 0, 'Operating Systems, Computer Architecture', '2026-03-17 05:28:12'),
(25, '2024-2025', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Dev', '2026-03-17 05:38:37'),
(26, '2024-2025', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 05:38:37'),
(27, '2024-2025', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 05:38:37'),
(28, '2024-2025', '1st Semester', NULL, 'Maria Garcia', 'maria.garcia@university.edu', 'Full-time', 18, 0, 'Literature, Writing', '2026-03-17 05:38:37'),
(29, '2024-2025', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 05:38:37'),
(30, '2024-2025', '1st Semester', NULL, 'Emily Davis', 'emily.davis@university.edu', 'Part-time', 12, 0, 'UI/UX Design, Figma', '2026-03-17 05:38:37'),
(31, '2024-2025', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 05:38:37'),
(32, '2024-2025', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 05:38:37'),
(33, '2024-2025', '1st Semester', NULL, 'Daniel Lee', 'daniel.lee@university.edu', 'Part-time', 12, 0, 'Cloud Computing, AWS', '2026-03-17 05:38:37'),
(34, '2024-2025', '1st Semester', NULL, 'Olivia Wilson', 'olivia.wilson@university.edu', 'Full-time', 18, 0, 'Cybersecurity, Ethical Hacking', '2026-03-17 05:38:37'),
(35, '2024-2025', '1st Semester', NULL, 'Kevin Chen', 'kevin.chen@university.edu', 'Full-time', 18, 0, 'Data Structures, Algorithms, Discrete Math', '2026-03-17 05:38:37'),
(36, '2024-2025', '1st Semester', NULL, 'Priya Patel', 'priya.patel@university.edu', 'Full-time', 18, 0, 'Operating Systems, Computer Architecture', '2026-03-17 05:38:37'),
(37, '2024-2026', '2nd Semester', NULL, 'Noah Williams', 'noah.williams@university.edu', 'Part-time', 12, 0, 'Web Development, JavaScript, UI', '2026-03-17 05:40:28'),
(38, '2024-2026', '2nd Semester', NULL, 'Liam Anderson', 'liam.anderson@university.edu', 'Full-time', 18, 0, 'Software Engineering, Project Management', '2026-03-17 05:40:28'),
(39, '2024-2026', '2nd Semester', NULL, 'Emma Thompson', 'emma.thompson@university.edu', 'Part-time', 12, 0, 'Probability, Statistics, Data Analytics', '2026-03-17 05:40:28'),
(40, '2024-2026', '2nd Semester', NULL, 'Isabella Cruz', 'isabella.cruz@university.edu', 'Full-time', 18, 0, 'Database Administration, SQL, ETL', '2026-03-17 05:40:28'),
(41, '2024-2026', '2nd Semester', NULL, 'Ethan Nguyen', 'ethan.nguyen@university.edu', 'Full-time', 18, 0, 'Networks, Data Communications, Routing', '2026-03-17 05:40:28'),
(42, '2024-2026', '2nd Semester', NULL, 'Mia Robinson', 'mia.robinson@university.edu', 'Part-time', 12, 0, 'Technical Writing, Communication', '2026-03-17 05:40:28'),
(43, '2024-2026', '2nd Semester', NULL, 'Lucas Perez', 'lucas.perez@university.edu', 'Full-time', 18, 0, 'Information Security, Network Security', '2026-03-17 05:40:28'),
(44, '2024-2026', '2nd Semester', NULL, 'Chloe Kim', 'chloe.kim@university.edu', 'Full-time', 18, 0, 'Mobile Development, HCI, UX', '2026-03-17 05:40:28'),
(45, '2024-2026', '2nd Semester', NULL, 'James Wilson', 'james.wilson@university.edu', 'Full-time', 18, 0, 'Telecommunications, Signals, Data Transmission', '2026-03-17 05:40:28'),
(46, '2024-2026', '2nd Semester', NULL, 'Rachel Green', 'rachel.green@university.edu', 'Part-time', 12, 0, 'Systems Design, Architecture', '2026-03-17 05:40:28'),
(47, '2024-2026', '2nd Semester', NULL, 'Victor Santos', 'victor.santos@university.edu', 'Full-time', 18, 0, 'Blockchain, Cryptography', '2026-03-17 05:40:28'),
(48, '2024-2026', '2nd Semester', NULL, 'Sophie Laurent', 'sophie.laurent@university.edu', 'Part-time', 12, 0, 'Quality Assurance, Testing', '2026-03-17 05:40:28'),
(49, '2025-2026', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Dev', '2026-03-17 07:21:48'),
(50, '2025-2026', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 07:21:48'),
(51, '2025-2026', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 07:21:48'),
(52, '2025-2026', '1st Semester', NULL, 'Maria Garcia', 'maria.garcia@university.edu', 'Full-time', 18, 0, 'Literature, Writing', '2026-03-17 07:21:48'),
(53, '2025-2026', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 07:21:48'),
(54, '2025-2026', '1st Semester', NULL, 'Emily Davis', 'emily.davis@university.edu', 'Part-time', 12, 0, 'UI/UX Design, Figma', '2026-03-17 07:21:48'),
(55, '2025-2026', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 07:21:48'),
(56, '2025-2026', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 07:21:48'),
(57, '2025-2026', '1st Semester', NULL, 'Daniel Lee', 'daniel.lee@university.edu', 'Part-time', 12, 0, 'Cloud Computing, AWS', '2026-03-17 07:21:48'),
(58, '2025-2026', '1st Semester', NULL, 'Olivia Wilson', 'olivia.wilson@university.edu', 'Full-time', 18, 0, 'Cybersecurity, Ethical Hacking', '2026-03-17 07:21:48'),
(59, '2025-2026', '1st Semester', NULL, 'Kevin Chen', 'kevin.chen@university.edu', 'Full-time', 18, 0, 'Data Structures, Algorithms, Discrete Math', '2026-03-17 07:21:48'),
(60, '2025-2026', '1st Semester', NULL, 'Priya Patel', 'priya.patel@university.edu', 'Full-time', 18, 0, 'Operating Systems, Computer Architecture', '2026-03-17 07:21:48'),
(61, '2022-2023', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 3, 'PHP, MySQL, Web Development', '2026-03-17 08:19:17'),
(62, '2022-2023', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 3, 'Networking, Security', '2026-03-17 08:19:17'),
(63, '2022-2023', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 3, 'Mathematics, Algorithms', '2026-03-17 08:19:17'),
(64, '2022-2023', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 3, 'Database, SQL, Data Modeling', '2026-03-17 08:19:17'),
(65, '2022-2023', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 3, 'Java, OOP, Software Engineering', '2026-03-17 08:19:17'),
(66, '2022-2023', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 08:19:17'),
(67, '2022-2023', '2nd Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Development', '2026-03-17 08:19:17'),
(68, '2022-2023', '2nd Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 08:19:17'),
(69, '2022-2023', '2nd Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 08:19:17'),
(70, '2022-2023', '2nd Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 08:19:17'),
(71, '2022-2023', '2nd Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 08:19:17'),
(72, '2022-2023', '2nd Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 08:19:17'),
(73, '2023-2024', '1st Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Development', '2026-03-17 08:19:17'),
(74, '2023-2024', '1st Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 08:19:17'),
(75, '2023-2024', '1st Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 0, 'Mathematics, Algorithms', '2026-03-17 08:19:17'),
(76, '2023-2024', '1st Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 0, 'Database, SQL, Data Modeling', '2026-03-17 08:19:17'),
(77, '2023-2024', '1st Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 0, 'Java, OOP, Software Engineering', '2026-03-17 08:19:17'),
(78, '2023-2024', '1st Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 0, 'Artificial Intelligence, Machine Learning', '2026-03-17 08:19:17'),
(79, '2023-2024', '2nd Semester', NULL, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 0, 'PHP, MySQL, Web Development', '2026-03-17 08:19:17'),
(80, '2023-2024', '2nd Semester', NULL, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 0, 'Networking, Security', '2026-03-17 08:19:17'),
(81, '2023-2024', '2nd Semester', NULL, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 3, 'Mathematics, Algorithms', '2026-03-17 08:19:17'),
(82, '2023-2024', '2nd Semester', NULL, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 3, 'Database, SQL, Data Modeling', '2026-03-17 08:19:17'),
(83, '2023-2024', '2nd Semester', NULL, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 3, 'Java, OOP, Software Engineering', '2026-03-17 08:19:17'),
(84, '2023-2024', '2nd Semester', NULL, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 3, 'Artificial Intelligence, Machine Learning', '2026-03-17 08:19:17');

-- --------------------------------------------------------

--
-- Table structure for table `policy_settings`
--

CREATE TABLE `policy_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `max_teaching_load` tinyint(3) UNSIGNED NOT NULL DEFAULT 18,
  `expertise_weight` tinyint(3) UNSIGNED NOT NULL DEFAULT 70,
  `availability_weight` tinyint(3) UNSIGNED NOT NULL DEFAULT 30,
  `detect_schedule_overlaps` tinyint(1) NOT NULL DEFAULT 1,
  `flag_overload_teachers` tinyint(1) NOT NULL DEFAULT 1,
  `check_prerequisites` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `policy_settings`
--

INSERT INTO `policy_settings` (`id`, `max_teaching_load`, `expertise_weight`, `availability_weight`, `detect_schedule_overlaps`, `flag_overload_teachers`, `check_prerequisites`, `updated_at`) VALUES
(1, 18, 70, 30, 1, 1, 1, '2026-03-17 07:27:14');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) NOT NULL,
  `section` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `subject_id`, `day_of_week`, `start_time`, `end_time`, `room`, `section`) VALUES
(109, 156, 'Monday', '08:00:00', '10:00:00', 'Room 101', 'BSIT-1A'),
(110, 157, 'Monday', '10:00:00', '12:00:00', 'Room 101', 'BSIT-1A'),
(111, 158, 'Tuesday', '08:00:00', '11:00:00', 'Room 102', 'BSCS-1A'),
(112, 159, 'Tuesday', '13:00:00', '15:00:00', 'Room 103', 'BSCS-1A');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `program` varchar(100) NOT NULL,
  `units` tinyint(3) UNSIGNED NOT NULL,
  `prerequisites` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `course_code`, `name`, `program`, `units`, `prerequisites`, `is_archived`, `created_at`) VALUES
(156, 'IT101', 'Introduction to IT', 'BS Information Technology', 3, 'None', 0, '2026-03-17 07:24:57'),
(157, 'IT102', 'Data Communications', 'BS Information Technology', 3, 'IT101', 0, '2026-03-17 07:24:57'),
(158, 'CS101', 'Programming Fundamentals', 'BS Computer Science', 3, 'None', 0, '2026-03-17 07:24:57'),
(159, 'CS102', 'Object-Oriented Programming', 'BS Computer Science', 3, 'CS101', 0, '2026-03-17 07:24:57'),
(160, 'CS201', 'Data Structures', 'BS Computer Science', 3, 'CS102', 0, '2026-03-17 07:24:57'),
(161, 'CS210', 'Operating Systems', 'BS Computer Science', 3, 'CS201', 0, '2026-03-17 07:24:57'),
(162, 'CS220', 'Computer Architecture', 'BS Computer Science', 3, 'CS201', 0, '2026-03-17 07:24:57'),
(163, 'CS301', 'Software Engineering', 'BS Computer Science', 3, 'CS201', 0, '2026-03-17 07:24:57'),
(164, 'IT202', 'Database Systems', 'BS Information Technology', 3, 'IT101', 0, '2026-03-17 07:24:57'),
(165, 'IT204', 'Computer Networking', 'BS Information Technology', 3, 'IT102', 0, '2026-03-17 07:24:57'),
(166, 'IT205', 'Systems Analysis and Design', 'BS Information Technology', 3, 'IT101', 0, '2026-03-17 07:24:57'),
(167, 'IT206', 'Web Systems and Technologies', 'BS Information Technology', 3, 'CS101', 0, '2026-03-17 07:24:57'),
(168, 'IT209', 'Network Security', 'BS Information Technology', 3, 'IT204', 0, '2026-03-17 07:24:57'),
(169, 'IT210', 'Database Administration', 'BS Information Technology', 3, 'IT202', 0, '2026-03-17 07:24:57'),
(170, 'IT211', 'IT Service Management', 'BS Information Technology', 3, 'IT205', 0, '2026-03-17 07:24:57'),
(171, 'IT240', 'Cloud Computing', 'BS Information Technology', 3, 'IT202', 0, '2026-03-17 07:24:57'),
(172, 'MATH101', 'Calculus I', 'BS Mathematics', 3, 'None', 0, '2026-03-17 07:24:57'),
(173, 'MATH102', 'Calculus II', 'BS Mathematics', 3, 'MATH101', 0, '2026-03-17 07:24:57'),
(174, 'MATH201', 'Linear Algebra', 'BS Mathematics', 3, 'MATH102', 0, '2026-03-17 07:24:57'),
(175, 'MATH202', 'Differential Equations', 'BS Mathematics', 3, 'MATH102', 0, '2026-03-17 07:24:57'),
(176, 'MATH203', 'Probability and Statistics', 'BS Mathematics', 3, 'MATH101', 0, '2026-03-17 07:24:57'),
(177, 'MATH205', 'Discrete Structures', 'BS Mathematics', 3, 'MATH101', 0, '2026-03-17 07:24:57'),
(178, 'GEN101', 'Communication Skills', 'General Education', 3, 'None', 0, '2026-03-17 07:24:57'),
(179, 'GEN102', 'Ethics and Society', 'General Education', 3, 'None', 0, '2026-03-17 07:24:57'),
(180, 'GEN103', 'Technical Writing', 'General Education', 3, 'GEN101', 0, '2026-03-17 07:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `type` enum('Full-time','Part-time') NOT NULL,
  `max_units` tinyint(3) UNSIGNED NOT NULL,
  `current_units` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `expertise_tags` varchar(255) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `email`, `type`, `max_units`, `current_units`, `expertise_tags`, `is_archived`, `created_at`) VALUES
(122, 'John Doe', 'john.doe@university.edu', 'Full-time', 18, 6, 'PHP, MySQL, Web Dev', 0, '2026-03-17 07:24:50'),
(123, 'Jane Smith', 'jane.smith@university.edu', 'Full-time', 18, 6, 'Networking, Security', 0, '2026-03-17 07:24:50'),
(124, 'Alan Turing', 'alan.turing@university.edu', 'Part-time', 12, 3, 'Mathematics, Algorithms', 0, '2026-03-17 07:24:50'),
(125, 'Maria Garcia', 'maria.garcia@university.edu', 'Full-time', 18, 3, 'Literature, Writing', 0, '2026-03-17 07:24:50'),
(126, 'Robert Brown', 'robert.brown@university.edu', 'Full-time', 18, 3, 'Database, SQL, Data Modeling', 0, '2026-03-17 07:24:50'),
(127, 'Emily Davis', 'emily.davis@university.edu', 'Part-time', 12, 3, 'UI/UX Design, Figma', 0, '2026-03-17 07:24:50'),
(128, 'Michael Johnson', 'michael.johnson@university.edu', 'Full-time', 18, 3, 'Java, OOP, Software Engineering', 0, '2026-03-17 07:24:50'),
(129, 'Sophia Martinez', 'sophia.martinez@university.edu', 'Full-time', 18, 3, 'Artificial Intelligence, Machine Learning', 0, '2026-03-17 07:24:50'),
(130, 'Daniel Lee', 'daniel.lee@university.edu', 'Part-time', 12, 3, 'Cloud Computing, AWS', 0, '2026-03-17 07:24:50'),
(131, 'Olivia Wilson', 'olivia.wilson@university.edu', 'Full-time', 18, 3, 'Cybersecurity, Ethical Hacking', 0, '2026-03-17 07:24:50'),
(132, 'Kevin Chen', 'kevin.chen@university.edu', 'Full-time', 18, 3, 'Data Structures, Algorithms, Discrete Math', 0, '2026-03-17 07:24:50'),
(133, 'Priya Patel', 'priya.patel@university.edu', 'Full-time', 18, 3, 'Operating Systems, Computer Architecture', 0, '2026-03-17 07:24:50'),
(134, 'Noah Williams', 'noah.williams@university.edu', 'Part-time', 12, 3, 'Web Development, JavaScript, UI', 0, '2026-03-17 07:24:50'),
(135, 'Liam Anderson', 'liam.anderson@university.edu', 'Full-time', 18, 3, 'Software Engineering, Project Management', 0, '2026-03-17 07:24:50'),
(136, 'Emma Thompson', 'emma.thompson@university.edu', 'Part-time', 12, 3, 'Probability, Statistics, Data Analytics', 0, '2026-03-17 07:24:50'),
(137, 'Isabella Cruz', 'isabella.cruz@university.edu', 'Full-time', 18, 6, 'Database Administration, SQL, ETL', 0, '2026-03-17 07:24:50'),
(138, 'Ethan Nguyen', 'ethan.nguyen@university.edu', 'Full-time', 18, 6, 'Networks, Data Communications, Routing', 0, '2026-03-17 07:24:50'),
(139, 'Mia Robinson', 'mia.robinson@university.edu', 'Part-time', 12, 3, 'Technical Writing, Communication', 0, '2026-03-17 07:24:50'),
(140, 'Lucas Perez', 'lucas.perez@university.edu', 'Full-time', 18, 3, 'Information Security, Network Security', 0, '2026-03-17 07:24:50'),
(141, 'Chloe Kim', 'chloe.kim@university.edu', 'Full-time', 18, 6, 'Mobile Development, HCI, UX', 0, '2026-03-17 07:24:50');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_availability`
--

CREATE TABLE `teacher_availability` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_availability`
--

INSERT INTO `teacher_availability` (`id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 122, 'Monday', '08:00:00', '17:00:00'),
(2, 122, 'Tuesday', '08:00:00', '17:00:00'),
(3, 122, 'Wednesday', '08:00:00', '17:00:00'),
(4, 123, 'Tuesday', '09:00:00', '15:00:00'),
(5, 123, 'Thursday', '09:00:00', '15:00:00'),
(6, 124, 'Monday', '08:00:00', '12:00:00'),
(7, 124, 'Wednesday', '08:00:00', '12:00:00'),
(8, 124, 'Friday', '08:00:00', '12:00:00'),
(9, 125, 'Monday', '10:00:00', '16:00:00'),
(10, 125, 'Tuesday', '10:00:00', '16:00:00'),
(11, 125, 'Thursday', '10:00:00', '16:00:00'),
(12, 126, 'Monday', '09:00:00', '17:00:00'),
(13, 126, 'Wednesday', '09:00:00', '17:00:00'),
(14, 127, 'Tuesday', '13:00:00', '17:00:00'),
(15, 127, 'Thursday', '13:00:00', '17:00:00'),
(16, 128, 'Monday', '08:00:00', '12:00:00'),
(17, 128, 'Tuesday', '08:00:00', '12:00:00'),
(18, 128, 'Friday', '08:00:00', '12:00:00'),
(19, 129, 'Wednesday', '10:00:00', '17:00:00'),
(20, 129, 'Thursday', '10:00:00', '17:00:00'),
(21, 130, 'Monday', '13:00:00', '18:00:00'),
(22, 130, 'Wednesday', '13:00:00', '18:00:00'),
(23, 131, 'Tuesday', '08:00:00', '16:00:00'),
(24, 131, 'Thursday', '08:00:00', '16:00:00'),
(25, 132, 'Monday', '08:00:00', '17:00:00'),
(26, 132, 'Thursday', '08:00:00', '17:00:00'),
(27, 133, 'Tuesday', '10:00:00', '17:00:00'),
(28, 133, 'Friday', '10:00:00', '17:00:00'),
(29, 134, 'Wednesday', '08:00:00', '12:00:00'),
(30, 134, 'Friday', '13:00:00', '17:00:00'),
(31, 135, 'Monday', '10:00:00', '17:00:00'),
(32, 135, 'Wednesday', '10:00:00', '17:00:00'),
(33, 136, 'Tuesday', '08:00:00', '12:00:00'),
(34, 136, 'Thursday', '08:00:00', '12:00:00'),
(35, 137, 'Monday', '13:00:00', '17:00:00'),
(36, 137, 'Tuesday', '13:00:00', '17:00:00'),
(37, 137, 'Thursday', '13:00:00', '17:00:00'),
(38, 138, 'Wednesday', '08:00:00', '16:00:00'),
(39, 138, 'Friday', '08:00:00', '16:00:00'),
(40, 139, 'Monday', '08:00:00', '12:00:00'),
(41, 139, 'Thursday', '08:00:00', '12:00:00'),
(42, 140, 'Tuesday', '09:00:00', '17:00:00'),
(43, 140, 'Wednesday', '09:00:00', '17:00:00'),
(44, 141, 'Monday', '09:00:00', '12:00:00'),
(45, 141, 'Wednesday', '09:00:00', '12:00:00'),
(46, 141, 'Friday', '09:00:00', '12:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assignments_subject` (`subject_id`),
  ADD KEY `idx_assignments_teacher` (`teacher_id`),
  ADD KEY `idx_assignments_status` (`status`),
  ADD KEY `idx_assignments_created` (`created_at`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_action` (`action_type`),
  ADD KEY `idx_audit_user` (`user`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `historical_analytics_metadata`
--
ALTER TABLE `historical_analytics_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_analytics_year_semester` (`academic_year`,`semester`),
  ADD KEY `idx_analytics_year` (`academic_year`);

--
-- Indexes for table `historical_assignments`
--
ALTER TABLE `historical_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_assignments_year` (`academic_year`),
  ADD KEY `idx_hist_assignments_semester` (`semester`),
  ADD KEY `idx_hist_assignments_year_semester` (`academic_year`,`semester`),
  ADD KEY `idx_hist_assignments_teacher_email` (`teacher_email`),
  ADD KEY `idx_hist_assignments_subject_code` (`subject_code`);

--
-- Indexes for table `historical_schedules`
--
ALTER TABLE `historical_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_schedules_year` (`academic_year`),
  ADD KEY `idx_hist_schedules_semester` (`semester`),
  ADD KEY `idx_hist_schedules_year_semester` (`academic_year`,`semester`),
  ADD KEY `idx_hist_schedules_day` (`day_of_week`);

--
-- Indexes for table `historical_subjects`
--
ALTER TABLE `historical_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_subjects_year` (`academic_year`),
  ADD KEY `idx_hist_subjects_semester` (`semester`),
  ADD KEY `idx_hist_subjects_year_semester` (`academic_year`,`semester`),
  ADD KEY `idx_hist_subjects_code` (`course_code`);

--
-- Indexes for table `historical_teachers`
--
ALTER TABLE `historical_teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_teachers_year` (`academic_year`),
  ADD KEY `idx_hist_teachers_semester` (`semester`),
  ADD KEY `idx_hist_teachers_year_semester` (`academic_year`,`semester`),
  ADD KEY `idx_hist_teachers_email` (`email`);

--
-- Indexes for table `policy_settings`
--
ALTER TABLE `policy_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedules_subject` (`subject_id`),
  ADD KEY `idx_schedules_day` (`day_of_week`),
  ADD KEY `idx_schedules_room` (`room`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_subjects_program` (`program`),
  ADD KEY `idx_subjects_archived` (`is_archived`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_teachers_type` (`type`),
  ADD KEY `idx_teachers_archived` (`is_archived`);

--
-- Indexes for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_availability_teacher` (`teacher_id`),
  ADD KEY `idx_availability_day` (`day_of_week`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=138;

--
-- AUTO_INCREMENT for table `historical_analytics_metadata`
--
ALTER TABLE `historical_analytics_metadata`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `historical_assignments`
--
ALTER TABLE `historical_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `historical_schedules`
--
ALTER TABLE `historical_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `historical_subjects`
--
ALTER TABLE `historical_subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `historical_teachers`
--
ALTER TABLE `historical_teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_availability`
--
ALTER TABLE `teacher_availability`
  ADD CONSTRAINT `teacher_availability_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
