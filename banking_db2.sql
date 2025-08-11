-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 08:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `banking_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int(30) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `pin` text DEFAULT NULL,
  `firstname` varchar(250) NOT NULL,
  `lastname` varchar(250) NOT NULL,
  `middlename` varchar(250) NOT NULL,
  `address` text DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `id_type` varchar(100) DEFAULT NULL,
  `id_number` varchar(100) DEFAULT NULL,
  `email` text NOT NULL,
  `firebase_uid` varchar(128) DEFAULT NULL,
  `password` text NOT NULL,
  `transaction_pin` varchar(255) DEFAULT NULL COMMENT 'Hashed 5-digit transaction PIN',
  `first_login_done` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not shown, 1=PIN shown on first login',
  `generated_password` text NOT NULL,
  `balance` float NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` varchar(255) NOT NULL DEFAULT 'Pending',
  `login_type` tinyint(1) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `account_number`, `pin`, `firstname`, `lastname`, `middlename`, `address`, `marital_status`, `gender`, `phone_number`, `date_of_birth`, `id_type`, `id_number`, `email`, `firebase_uid`, `password`, `transaction_pin`, `first_login_done`, `generated_password`, `balance`, `date_created`, `date_updated`, `status`, `login_type`) VALUES
(4, '2996114078', '', 'Donald', 'Young', '', '34 cresent avenue', 'Widowed', 'Female', '90987654328', '1972-10-10', 'Passport', '8765409823778', 'smith@gmail.com', 'y0ID9A7HrlRxNp1GY9QE5lyodkO2', '', NULL, 1, '', 20000, '2025-06-09 22:32:02', '2025-06-12 11:34:00', '1', 2),
(5, '5994588630', '', 'George', 'Hans', 'Hans Christy', '35 Gerald avenue, West Midlands', 'Married', 'Male', '98765234091', '1967-06-11', 'National ID', '123754287667754', 'henrythebest2023@gmail.com', 'AdeRNSP5reUMF4eZ2brIzV8AGtx1', '', NULL, 1, '', 30500, '2025-06-11 10:13:52', '2025-06-20 22:27:18', 'Active', 2),
(10, '4280912401', '', 'Don', 'Philip', '', '2592 Harrison Street', 'Divorced', 'Male', '90987654328', '1976-08-08', 'National ID', '8765430987', 'phill@gmail.com', 'uLTH2xqcJvfG1OTM4X7rhtYSjIJ3', '', '75271', 1, '', 1730, '2025-06-12 05:17:00', '2025-06-28 20:09:51', 'Active', 2),
(11, '9494405173', '', 'Morris', 'Abali', '', '67 saint Anthony cresent, West indis', 'Single', 'Male', '3906541098', '1971-09-07', 'Drivers License', '7651290872', 'morris@gmail.com', 'ESvcqxmFZxfVTxucMEdY1NaXYVv2', '', '93143', 1, '', 12247, '2025-06-12 11:52:21', '2025-06-28 17:40:01', 'Active', 2),
(12, '3963787860', '', 'Miracle', 'Thomas', '', '2592 Harrison Street', 'Single', 'Female', '562 249-5297', '1976-07-04', 'National ID', '7623098463', 'angel1@gmail.com', 'o3TMCSh9d3P1jwTVYS0G83fBI9k1', '', '12851', 1, '', 13940, '2025-06-12 12:13:44', '2025-06-28 18:52:15', 'Active', 2),
(13, '5827241966', '', 'Charles', 'Awuru', '', '2592 Harrison Street', 'Married', 'Male', '0975467987', '1969-08-09', 'National ID', '7651290872', 'awuru@gmail.com', 'J38ddr1045h7zJhi2R6JpZALRwq2', '', '67627', 1, '', 10633, '2025-06-12 12:35:11', '2025-06-29 00:22:04', 'Active', 2),
(14, '9180554139', '', 'Imo', 'Dominic', '', '12 church street ', 'Married', 'Male', '8907664320', '1975-06-18', 'National ID', '5676542987', 'imo@gmail.com', 'fnNmb0byaggRoIsj3pO4KVIKNo02', '', '87847', 1, '', 800, '2025-06-18 23:16:50', '2025-06-24 18:28:31', 'Active', 2),
(17, '3169923433', '', 'Philip ', 'Mark ', '', '234 rounding Avenue, London E17 ', 'Divorced', 'Male', '987654097', '1981-06-20', 'Passport', '7652987652', 'philip@gmail.com', 'cbzLtpOKfoQG4jxbkfslYh126qn1', '', '27480', 1, '', 0, '2025-06-20 20:40:53', '2025-06-20 20:44:04', 'Active', 2),
(18, '9435993512', '', 'Humphrey', 'Dom', '', '38b Rabiatu Aghedo St', 'Married', 'Male', '09125271199', '1970-09-09', 'Passport', '7654321987', 'hugh@gmail.com', 'TmVdv9rKNXQdcTPCECMs1OygxKh1', '', '63919', 1, '', 0, '2025-06-22 04:15:52', '2025-06-22 04:19:33', 'Active', 2),
(19, '6635943707', '', 'Henry', 'Larsen', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '09125271100', '1975-07-07', 'Drivers License', '7654098712', 'hen@gmail.com', 'zZ3HsPktC9Z6YCXGGv6T3iVT16v2', '', '74159', 1, '', 0, '2025-06-26 13:37:13', '2025-06-26 13:52:16', 'Active', 2),
(20, '5387112226', NULL, 'George', 'Thom', '', '38b Rabiatu Aghedo St', 'Single', 'Female', '09030099', '1976-08-09', 'Passport', '7654321987', 'jjj@gmail.com', 'a1R2TSYlsUSPeJtDZJ8GpFTI7jq2', '', '83278', 1, '', 1000, '2025-06-27 06:26:43', '2025-06-29 00:22:04', 'Active', 2),
(21, '5622353355', '87249', 'Ang', 'Abali', '', '2592 Harrison Street', 'Single', 'Female', '98765309128', '1978-09-04', 'National ID', '878763212123', 'ann@gmail.com', 'tCJ2XCCUmKfzDDXY1HV6VsHNtov1', '', NULL, 1, '', 0, '2025-06-27 07:58:57', '2025-06-27 08:12:32', 'Active', 2),
(22, '3777431476', '10929', 'Hen', 'Awu', '', '38b Rabiatu Aghedo St', 'Married', 'Male', '09030099', '1975-09-09', 'National ID', '7654098712', 'awuu@gmail.com', 'heQ0TIMxOpW0hA89CZ4q1HRyd5e2', '', '23415', 1, '', 0, '2025-06-28 04:31:12', '2025-06-28 06:46:26', 'Active', 2),
(23, '6853099281', NULL, 'George', 'Fred', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '0903009', '1977-05-04', 'Passport', '9875340987', 'fred@gmail.com', 'Z00WUu34Y6fcuk43CfKafif8NDn1', '', '60710', 1, '', 0, '2025-06-28 07:48:01', '2025-06-28 11:33:14', 'Active', 2),
(24, '4231498915', NULL, 'Sunday', 'Ozon', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '09030099662', '1790-05-04', 'Passport', '7654309898', 'fre3@gmail.com', 'ppmIYBmGqHPecU8vauZNZXUgJ7A2', '', '31646', 1, '', 200, '2025-06-28 15:42:15', '2025-06-28 17:42:00', 'Active', 2);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(30) NOT NULL,
  `title` text NOT NULL,
  `announcement` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `announcement`, `date_created`, `date_updated`) VALUES
(1, 'Sample 101', '&lt;p&gt;This is a sample Announcement only.&lt;/p&gt;', '2021-07-14 14:10:09', '2021-07-14 14:11:30'),
(3, 'Sample', '&lt;hr style=&quot;margin: 0px; padding: 0px; clear: both; border-top: 0px; height: 1px; background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0)); color: rgb(0, 0, 0); font-family: &amp;quot;Open Sans&amp;quot;, Arial, sans-serif; font-size: 14px; text-align: center;&quot;&gt;&lt;div id=&quot;Content&quot; style=&quot;margin: 0px; padding: 0px; position: relative; color: rgb(0, 0, 0); font-family: &amp;quot;Open Sans&amp;quot;, Arial, sans-serif; font-size: 14px; text-align: center;&quot;&gt;&lt;div id=&quot;bannerL&quot; style=&quot;margin: 0px 0px 0px -160px; padding: 0px; position: sticky; top: 20px; width: 160px; height: 10px; float: left; text-align: right;&quot;&gt;&lt;/div&gt;&lt;div id=&quot;bannerR&quot; style=&quot;margin: 0px -160px 0px 0px; padding: 0px; position: sticky; top: 20px; width: 160px; height: 10px; float: right; text-align: left;&quot;&gt;&lt;/div&gt;&lt;div class=&quot;boxed&quot; style=&quot;margin: 10px 28.7969px; padding: 0px; clear: both;&quot;&gt;&lt;div id=&quot;lipsum&quot; style=&quot;margin: 0px; padding: 0px; text-align: justify;&quot;&gt;&lt;p style=&quot;margin-right: 0px; margin-bottom: 15px; margin-left: 0px; padding: 0px;&quot;&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam et tempus leo. Nulla id sagittis libero. Cras est nisi, consequat ut vulputate eget, mattis non ante. Phasellus a lorem at nunc venenatis commodo. Donec vitae cursus augue. Donec eleifend molestie laoreet. Praesent dictum arcu ac congue molestie. Etiam nisl risus, blandit vel scelerisque eu, ultricies at mauris. Suspendisse non bibendum magna. Vestibulum porttitor enim a elit feugiat bibendum eu malesuada eros. Etiam eu est at neque dictum efficitur. Integer fermentum porttitor scelerisque.&lt;/p&gt;&lt;p style=&quot;margin-right: 0px; margin-bottom: 15px; margin-left: 0px; padding: 0px;&quot;&gt;Nunc a leo rutrum, congue ex sit amet, laoreet tortor. Nunc at bibendum sapien. Cras libero nunc, varius quis ultricies non, finibus in quam. Aenean quis justo vitae purus ultrices luctus. Curabitur viverra non lacus vehicula malesuada. Phasellus convallis mattis libero eget accumsan. Ut sollicitudin mattis enim, bibendum eleifend felis euismod et. Etiam ut libero purus. Aenean vel sceleris&lt;/p&gt;&lt;/div&gt;&lt;/div&gt;&lt;/div&gt;', '2021-07-14 14:33:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` text NOT NULL,
  `message` text NOT NULL,
  `type` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Unread, 1=Read',
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `name`, `email`, `phone`, `subject`, `message`, `type`, `user_id`, `status`, `date_created`) VALUES
(1, 'Henry Emailgen', 'henrythebest2023@gmail.com', '0908765432', 'about atm', 'ABOUT ATM CARD', 'General Enquiry', NULL, 1, '2025-06-19 00:37:09'),
(2, 'lilian', 'tony@gmail.com', '96170845586', 'for enquiry', 'this is test enquiry', 'General Enquiry', NULL, 1, '2025-06-19 00:46:46'),
(3, 'John', 'jon@gmail.com', '0985214635', 'I cant connect to my server', 'I cant connect to my server', 'Technical Support', NULL, 1, '2025-06-19 00:48:23');

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--

DROP TABLE IF EXISTS `system_info`;
CREATE TABLE `system_info` (
  `id` int(30) NOT NULL,
  `meta_field` text NOT NULL,
  `meta_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_info`
--

INSERT INTO `system_info` (`id`, `meta_field`, `meta_value`) VALUES
(1, 'name', '- IMF Micro Finance Bank'),
(6, 'short_name', 'IMF'),
(11, 'logo', 'uploads/1626243720_bank.jpg'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'cover', 'uploads/1626249540_dark-bg.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(30) NOT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `account_id` int(30) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT '1=Cash in, 2= Withdraw, 3=transfer',
  `amount` float NOT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'completed' COMMENT 'Transaction status (e.g., pending, completed, failed, rejected)',
  `linked_account_id` int(11) DEFAULT NULL COMMENT 'FK to user_linked_accounts.id for source/destination linked account',
  `transaction_type` varchar(100) DEFAULT NULL COMMENT 'Specific type of transaction (e.g., deposit_internal, deposit_external_pending, transfer_internal)',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON for additional transaction details like source account info, etc.' CHECK (json_valid(`meta_data`)),
  `sender_account_number` varchar(20) DEFAULT NULL,
  `receiver_account_number` varchar(20) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_code`, `account_id`, `type`, `amount`, `remarks`, `status`, `linked_account_id`, `transaction_type`, `meta_data`, `sender_account_number`, `receiver_account_number`, `date_created`) VALUES
(1, NULL, 1, 1, 10000, 'Beginning balance', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 12:03:58'),
(2, NULL, 2, 1, 20000, 'Beginning balance', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 12:05:19'),
(3, NULL, 1, 1, 5000, 'Deposits', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 13:32:03'),
(6, NULL, 2, 1, 2500, 'Withdraw', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 13:37:59'),
(7, NULL, 1, 3, 3000, 'Transferred to 10140715', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 13:51:04'),
(8, NULL, 2, 3, 3000, 'Transferred from 6231415', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 13:51:04'),
(10, NULL, 1, 1, 3000, 'Deposits', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:23:21'),
(11, NULL, 1, 1, 1000, 'Withdraw', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:25:20'),
(12, NULL, 1, 3, 1000, 'Transferred to 10140715', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:35:16'),
(13, NULL, 2, 3, 1000, 'Transferred from 6231415', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:35:16'),
(14, NULL, 1, 1, 5000, 'Deposits', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:49:15'),
(15, NULL, 1, 1, 1000, 'Deposits', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:55:54'),
(16, NULL, 1, 1, 1000, 'Withdraw', 'completed', NULL, NULL, NULL, NULL, NULL, '2021-07-14 15:56:12'),
(21, '-20250615-041612-755421b9-S', 13, 3, 1000, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":15000}', '5827241966', '3963787860', '2025-06-14 21:16:12'),
(22, '-20250615-041612-755421b9-R', 12, 1, 1000, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":15000}', '5827241966', '3963787860', '2025-06-14 21:16:12'),
(23, '-20250615-044903-9202309f-S', 13, 3, 1000, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":14000}', '5827241966', '3963787860', '2025-06-14 21:49:03'),
(24, '-20250615-044903-9202309f-R', 12, 1, 1000, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":14000}', '5827241966', '3963787860', '2025-06-14 21:49:03'),
(25, '-20250615-193038-649d436a-S', 13, 3, 1000, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":13000}', '5827241966', '3963787860', '2025-06-15 12:30:38'),
(26, '-20250615-193038-649d436a-R', 12, 1, 1000, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":13000}', '5827241966', '3963787860', '2025-06-15 12:30:38'),
(27, '-20250615-200500-a704d46d-S', 13, 3, 500, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12000}', '5827241966', '3963787860', '2025-06-15 13:05:00'),
(28, '-20250615-200500-a704d46d-R', 12, 1, 500, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12000}', '5827241966', '3963787860', '2025-06-15 13:05:00'),
(29, '-20250615-214718-4dfba873-R', 13, 1, 1000, 'Deposit from Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'deposit_internal_completed', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11500}', '3963787860', '5827241966', '2025-06-15 14:47:18'),
(30, '-20250615-214718-4dfba873-S', 12, 3, 1000, 'Transfer to Linked Account (5827241966) - ', 'completed', 6, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11500}', '3963787860', '5827241966', '2025-06-15 14:47:18'),
(31, '-20250615-215409-34fad36e-S', 13, 3, 1000, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12500}', '5827241966', '3963787860', '2025-06-15 14:54:09'),
(32, '-20250615-215409-34fad36e-R', 12, 1, 1000, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12500}', '5827241966', '3963787860', '2025-06-15 14:54:09'),
(33, '-20250615-220859-bf168422-S', 13, 3, 1000, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11500}', '5827241966', '3963787860', '2025-06-15 15:08:59'),
(34, '-20250615-220859-bf168422-R', 12, 1, 1000, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11500}', '5827241966', '3963787860', '2025-06-15 15:08:59'),
(35, '-20250615-232102-459f1310-R', 13, 1, 500, 'Deposit from Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'deposit_internal_completed', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":10500}', '3963787860', '5827241966', '2025-06-15 16:21:02'),
(36, '-20250615-232102-459f1310-S', 12, 3, 500, 'Transfer to Linked Account (5827241966) - ', 'completed', 6, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":10500}', '3963787860', '5827241966', '2025-06-15 16:21:02'),
(37, '-20250615-232302-a2ddfea4-R', 13, 1, 800, 'Deposit from Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'deposit_internal_completed', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11000}', '3963787860', '5827241966', '2025-06-15 16:23:02'),
(38, '-20250615-232302-a2ddfea4-S', 12, 3, 800, 'Transfer to Linked Account (5827241966) - ', 'completed', 6, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11000}', '3963787860', '5827241966', '2025-06-15 16:23:02'),
(39, '-20250615-232638-d2a8d2ee-R', 13, 1, 700, 'Deposit from Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'deposit_internal_completed', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11800}', '3963787860', '5827241966', '2025-06-15 16:26:38'),
(40, '-20250615-232638-d2a8d2ee-S', 12, 3, 700, 'Transfer to Linked Account (5827241966) - ', 'completed', 6, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"6\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11800}', '3963787860', '5827241966', '2025-06-15 16:26:38'),
(41, '-20250615-232947-a9abf4b3-S', 13, 3, 1200, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12500}', '5827241966', '3963787860', '2025-06-15 16:29:47'),
(42, '-20250615-232947-a9abf4b3-R', 12, 1, 1200, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":12500}', '5827241966', '3963787860', '2025-06-15 16:29:47'),
(43, '-20250615-233340-7ba34e23-S', 13, 3, 1900, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11300}', '5827241966', '3963787860', '2025-06-15 16:33:40'),
(44, '-20250615-233340-7ba34e23-R', 12, 1, 1900, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":11300}', '5827241966', '3963787860', '2025-06-15 16:33:40'),
(45, '-20250616-052658-a026f899-S', 13, 3, 700, 'Transfer to Morris Abali (Account: 9494405173)', 'completed', NULL, '0', '{\"sender_balance_before\":9400,\"receiver_account_number\":\"9494405173\",\"receiver_account_name\":\"Morris Abali\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 22:26:58'),
(46, '-20250616-052658-a026f899-R', 11, 1, 700, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":4300,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 22:26:58'),
(47, '-20250616-053252-EXT-1e6d283c', 13, 3, 700, 'External Transfer Request to Frank Henshaw (ACESS BANK, Account: 987654398765)', 'pending', NULL, 'transfer_external_pending', '{\"sender_account_number\":\"5827241966\",\"sender_account_name\":null,\"sender_balance_before\":8700,\"recipient_bank_name\":\"ACESS BANK\",\"recipient_account_number\":\"987654398765\",\"recipient_account_name\":\"Frank Henshaw\",\"swift_bic\":\"9876543098123490\",\"routing_number\":\"\",\"iban\":\"\",\"beneficiary_address\":\"12 christiain centre\",\"beneficiary_phone\":\"8976542345\",\"narration\":\"\"}', '5827241966', '987654398765', '2025-06-15 22:32:52'),
(48, '-20250616-064423-bfc2e83f-S', 13, 3, 600, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 6, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":8000}', '5827241966', '3963787860', '2025-06-15 23:44:23'),
(49, '-20250616-064423-bfc2e83f-R', 12, 1, 600, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 6, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"6\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":8000}', '5827241966', '3963787860', '2025-06-15 23:44:23'),
(50, '-20250616-065014-8e1d7d80-S', 13, 3, 690, 'Transfer to Morris Abali (Account: 9494405173)', 'completed', NULL, '0', '{\"sender_balance_before\":7400,\"receiver_account_number\":\"9494405173\",\"receiver_account_name\":\"Morris Abali\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 23:50:14'),
(51, '-20250616-065014-8e1d7d80-R', 11, 1, 690, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":5000,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 23:50:14'),
(52, '-20250616-065718-3936803d-S', 13, 3, 400, 'Transfer to Morris Abali (Account: 9494405173)', 'completed', NULL, '0', '{\"sender_balance_before\":6710,\"receiver_account_number\":\"9494405173\",\"receiver_account_name\":\"Morris Abali\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 23:57:18'),
(53, '-20250616-065718-3936803d-R', 11, 1, 400, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":5690,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-15 23:57:18'),
(54, '-20250616-072304-22f86201-S', 13, 3, 520, 'Transfer to Miracle Thomas (Account: 3963787860)', 'completed', NULL, '0', '{\"sender_balance_before\":6310,\"receiver_account_number\":\"3963787860\",\"receiver_account_name\":\"Miracle Thomas\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 00:23:04'),
(55, '-20250616-072304-22f86201-R', 12, 1, 520, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":18200,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 00:23:04'),
(56, '-20250616-075052-8447b6c4-S', 13, 3, 567, 'Transfer to Morris Abali (Account: 9494405173)', 'completed', NULL, '0', '{\"sender_balance_before\":5790,\"receiver_account_number\":\"9494405173\",\"receiver_account_name\":\"Morris Abali\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-16 00:50:52'),
(57, '-20250616-075052-8447b6c4-R', 11, 1, 567, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":6090,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '9494405173', '2025-06-16 00:50:52'),
(58, '-20250616-075842-d0e40aff-S', 13, 3, 420, 'Transfer to Miracle Thomas (Account: 3963787860)', 'completed', NULL, '0', '{\"sender_balance_before\":5223,\"receiver_account_number\":\"3963787860\",\"receiver_account_name\":\"Miracle Thomas\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 00:58:42'),
(59, '-20250616-075842-d0e40aff-R', 12, 1, 420, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":18720,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 00:58:42'),
(60, '-20250616-081927-137dcc9e-S', 13, 3, 200, 'Transfer to Miracle Thomas (Account: 3963787860)', 'completed', NULL, '0', '{\"sender_balance_before\":4803,\"receiver_account_number\":\"3963787860\",\"receiver_account_name\":\"Miracle Thomas\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 01:19:27'),
(61, '-20250616-081927-137dcc9e-R', 12, 1, 200, 'Transfer from Charles Anwurum (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":19140,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"\"}', '5827241966', '3963787860', '2025-06-16 01:19:27'),
(62, '-20250619-044158-66b2d93f-S', 13, 3, 500, 'Transfer to Internal Linked Account (5287941418) - Charles Thomas', 'completed', 20, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"20\",\"destination_account_number_linked\":\"5287941418\",\"destination_account_holder_name_linked\":\"Charles Thomas\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":4603}', '5827241966', '5287941418', '2025-06-18 21:41:58'),
(64, '-20250619-044409-0ae946ff-R', 13, 1, 200, 'Deposit from Internal Linked Account (5287941418) - Charles Thomas', 'completed', 20, 'deposit_internal_completed', '{\"source_linked_account_id\":\"20\",\"source_account_number_linked\":\"5287941418\",\"source_account_holder_name_linked\":\"Charles Thomas\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":4103}', '5287941418', '5827241966', '2025-06-18 21:44:09'),
(66, '-20250619-044644-0b05512a-S', 13, 3, 150, 'Transfer to Charles Thomas (Account: 5287941418)', 'completed', NULL, '0', '{\"sender_balance_before\":4303,\"receiver_account_number\":\"5287941418\",\"receiver_account_name\":\"Charles Thomas\",\"narration\":\"\"}', '5827241966', '5287941418', '2025-06-18 21:46:44'),
(68, '-20250619-044948-EXT-df4e5db1', 13, 3, 1000, 'External Transfer Request to FRANKLIN ANDERSON (DIAMOND FINANCIAL, Account: 78765432987)', 'pending', NULL, 'transfer_external_pending', '{\"sender_account_number\":\"5827241966\",\"sender_account_name\":null,\"sender_balance_before\":4153,\"recipient_bank_name\":\"DIAMOND FINANCIAL\",\"recipient_account_number\":\"78765432987\",\"recipient_account_name\":\"FRANKLIN ANDERSON\",\"swift_bic\":\"9876543298\",\"routing_number\":\"\",\"iban\":\"\",\"beneficiary_address\":\"234 YORKSHAIRE AVENUE LONDON\",\"beneficiary_phone\":\"0987654309\",\"narration\":\"\"}', '5827241966', '78765432987', '2025-06-18 21:49:48'),
(69, '-20250619-060316-206ffd3e-S', 13, 3, 500, 'Transfer to Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"21\",\"destination_account_number_linked\":\"9494405173\",\"destination_account_holder_name_linked\":\"Morris Abali\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":3153}', '5827241966', '9494405173', '2025-06-18 23:03:16'),
(70, '-20250619-060316-206ffd3e-R', 11, 1, 500, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 21, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"21\",\"destination_account_number_linked\":\"9494405173\",\"destination_account_holder_name_linked\":\"Morris Abali\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":3153}', '5827241966', '9494405173', '2025-06-18 23:03:16'),
(71, '-20250619-062338-30189228-S', 13, 3, 500, 'Transfer to Internal Linked Account (9180554139) - Imo  Dominic', 'completed', 22, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"22\",\"destination_account_number_linked\":\"9180554139\",\"destination_account_holder_name_linked\":\"Imo  Dominic\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":2653}', '5827241966', '9180554139', '2025-06-18 23:23:38'),
(72, '-20250619-062338-30189228-R', 14, 1, 500, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 22, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"22\",\"destination_account_number_linked\":\"9180554139\",\"destination_account_holder_name_linked\":\"Imo  Dominic\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":2653}', '5827241966', '9180554139', '2025-06-18 23:23:38'),
(73, '-20250619-103614-9034f02b-S', 13, 3, 300, 'Transfer to Internal Linked Account (9180554139) - Imo  Dominic', 'completed', 22, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"22\",\"destination_account_number_linked\":\"9180554139\",\"destination_account_holder_name_linked\":\"Imo  Dominic\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":2153}', '5827241966', '9180554139', '2025-06-19 03:36:14'),
(74, '-20250619-103614-9034f02b-R', 14, 1, 300, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 22, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"22\",\"destination_account_number_linked\":\"9180554139\",\"destination_account_holder_name_linked\":\"Imo  Dominic\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":2153}', '5827241966', '9180554139', '2025-06-19 03:36:14'),
(75, NULL, 5, 1, 1000, 'BONUS', 'completed', NULL, NULL, NULL, NULL, NULL, '2025-06-20 16:44:25'),
(76, NULL, 5, 2, 500, 'Fees', 'completed', NULL, NULL, NULL, NULL, NULL, '2025-06-20 16:50:18'),
(77, NULL, 5, 2, 200, 'SMS FEES', 'completed', NULL, NULL, NULL, NULL, NULL, '2025-06-20 17:32:13'),
(78, 'IMF-20250621-022350-a6a3cf77-S', 11, 3, 100, 'Transfer to Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 23, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"23\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":7157}', '9494405173', '3963787860', '2025-06-20 19:23:50'),
(79, 'IMF-20250621-022350-a6a3cf77-R', 12, 1, 100, 'Transfer from Linked Primary Account (9494405173) - ', 'completed', 23, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"23\",\"destination_account_number_linked\":\"3963787860\",\"destination_account_holder_name_linked\":\"Miracle Thomas\",\"destination_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":7157}', '9494405173', '3963787860', '2025-06-20 19:23:50'),
(80, 'IMF-20250621-023122-c2c6b9f5-R', 12, 1, 6000, 'Deposit from Internal Linked Account (9494405173) - Morris Abali', 'completed', 24, 'deposit_internal_completed', '{\"source_linked_account_id\":\"24\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":19440}', '9494405173', '3963787860', '2025-06-20 19:31:22'),
(81, 'IMF-20250621-023122-c2c6b9f5-S', 11, 3, 6000, 'Transfer to Linked Account (3963787860) - ', 'completed', 24, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"24\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":19440}', '9494405173', '3963787860', '2025-06-20 19:31:22'),
(82, 'IMF-20250621-023556-cbcdd6e5-R', 11, 1, 10000, 'Deposit from Internal Linked Account (3963787860) - Miracle Thomas', 'completed', 23, 'deposit_internal_completed', '{\"source_linked_account_id\":\"23\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":1057}', '3963787860', '9494405173', '2025-06-20 19:35:56'),
(83, 'IMF-20250621-023556-cbcdd6e5-S', 12, 3, 10000, 'Transfer to Linked Account (9494405173) - ', 'completed', 23, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"23\",\"source_account_number_linked\":\"3963787860\",\"source_account_holder_name_linked\":\"Miracle Thomas\",\"source_bank_name_linked\":\"- IMF Micro Finance Bank\",\"primary_account_balance_before\":1057}', '3963787860', '9494405173', '2025-06-20 19:35:56'),
(84, 'IMF-20250621-024151-22455bdc-S', 12, 3, 1500, 'Transfer to Morris Abali (Account: 9494405173)', 'completed', NULL, '0', '{\"sender_balance_before\":15440,\"receiver_account_number\":\"9494405173\",\"receiver_account_name\":\"Morris Abali\",\"narration\":\"\"}', '3963787860', '9494405173', '2025-06-20 19:41:51'),
(85, 'IMF-20250621-024151-22455bdc-R', 11, 1, 1500, 'Transfer from Miracle Thomas (Account: 3963787860)', 'completed', NULL, '0', '{\"receiver_balance_before\":11057,\"sender_account_number\":\"3963787860\",\"sender_account_name\":\"Miracle Thomas\",\"narration\":\"\"}', '3963787860', '9494405173', '2025-06-20 19:41:51'),
(86, 'IMF-20250621-050633-dc352862-S', 13, 3, 200, 'Transfer to George Hans Christy Anderson (Account: 5994588630) - For grocery', 'completed', NULL, '0', '{\"sender_balance_before\":1853,\"receiver_account_number\":\"5994588630\",\"receiver_account_name\":\"George Hans Christy Anderson\",\"narration\":\"For grocery\"}', '5827241966', '5994588630', '2025-06-20 22:06:33'),
(87, 'IMF-20250621-050633-dc352862-R', 5, 1, 200, 'Transfer from Charles Anwurum (Account: 5827241966) - For grocery', 'completed', NULL, '0', '{\"receiver_balance_before\":30300,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Anwurum\",\"narration\":\"For grocery\"}', '5827241966', '5994588630', '2025-06-20 22:06:33'),
(88, 'IMF-20250621-050831-EXT-726ad8d8', 13, 3, 400, 'External Transfer Request to Daniel Vic (Acess, Account: 9876543098)', 'pending', NULL, 'transfer_external_pending', '{\"sender_account_number\":\"5827241966\",\"sender_account_name\":null,\"sender_balance_before\":1653,\"recipient_bank_name\":\"Acess\",\"recipient_account_number\":\"9876543098\",\"recipient_account_name\":\"Daniel Vic\",\"swift_bic\":\"9876523456\",\"routing_number\":\"\",\"iban\":\"\",\"beneficiary_address\":\"\",\"beneficiary_phone\":\"\",\"narration\":\"\"}', '5827241966', '9876543098', '2025-06-20 22:08:31'),
(89, 'IMF-20250624-061514-57bf61bf-R', 13, 1, 100, 'Deposit from Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'deposit_internal_completed', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1253}', '9494405173', '5827241966', '2025-06-23 23:15:14'),
(90, 'IMF-20250624-061514-57bf61bf-S', 11, 3, 100, 'Transfer to Linked Account (5827241966) - ', 'completed', 21, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1253}', '9494405173', '5827241966', '2025-06-23 23:15:15'),
(91, 'IMF-20250624-124226-62a6f414-R', 13, 1, 100, 'Deposit from Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'deposit_internal_completed', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1353}', '9494405173', '5827241966', '2025-06-24 05:42:26'),
(92, 'IMF-20250624-124226-62a6f414-S', 11, 3, 100, 'Transfer to Linked Account (5827241966) - ', 'completed', 21, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1353}', '9494405173', '5827241966', '2025-06-24 05:42:26'),
(93, 'IMF-20250625-012641-9aab0aac-R', 13, 1, 100, 'Deposit from Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'deposit_internal_completed', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1453}', '9494405173', '5827241966', '2025-06-24 18:26:41'),
(94, 'IMF-20250625-012641-9aab0aac-S', 11, 3, 100, 'Transfer to Linked Account (5827241966) - ', 'completed', 21, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1453}', '9494405173', '5827241966', '2025-06-24 18:26:41'),
(95, 'IMF-20250625-063445-616d6be7-S', 13, 3, 50, 'Transfer to Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'internal_transfer_outgoing', '{\"destination_linked_account_id\":\"21\",\"destination_account_number_linked\":\"9494405173\",\"destination_account_holder_name_linked\":\"Morris Abali\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1553}', '5827241966', '9494405173', '2025-06-24 23:34:45'),
(96, 'IMF-20250625-063445-616d6be7-R', 11, 1, 50, 'Transfer from Linked Primary Account (5827241966) - ', 'completed', 21, 'internal_transfer_incoming', '{\"destination_linked_account_id\":\"21\",\"destination_account_number_linked\":\"9494405173\",\"destination_account_holder_name_linked\":\"Morris Abali\",\"destination_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1553}', '5827241966', '9494405173', '2025-06-24 23:34:45'),
(97, 'IMF-20250625-094219-d7f495d2-R', 13, 1, 200, 'Deposit from Internal Linked Account (4280912401) - Don Philip', 'completed', 19, 'deposit_internal_completed', '{\"source_linked_account_id\":\"19\",\"source_account_number_linked\":\"4280912401\",\"source_account_holder_name_linked\":\"Don Philip\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1503}', '4280912401', '5827241966', '2025-06-25 02:42:19'),
(98, 'IMF-20250625-094219-d7f495d2-S', 10, 3, 200, 'Transfer to Linked Account (5827241966) - ', 'completed', 19, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"19\",\"source_account_number_linked\":\"4280912401\",\"source_account_holder_name_linked\":\"Don Philip\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1503}', '4280912401', '5827241966', '2025-06-25 02:42:19'),
(99, 'IMF-20250627-132357-0db3b90d-R', 13, 1, 70, 'Deposit from Internal Linked Account (4280912401) - Don Philip', 'completed', 19, 'deposit_internal_completed', '{\"source_linked_account_id\":\"19\",\"source_account_number_linked\":\"4280912401\",\"source_account_holder_name_linked\":\"Don Philip\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1703}', '4280912401', '5827241966', '2025-06-27 06:23:57'),
(100, 'IMF-20250627-132357-0db3b90d-S', 10, 3, 70, 'Transfer to Linked Account (5827241966) - ', 'completed', 19, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"19\",\"source_account_number_linked\":\"4280912401\",\"source_account_holder_name_linked\":\"Don Philip\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1703}', '4280912401', '5827241966', '2025-06-27 06:23:57'),
(101, 'IMF-20250629-004001-b0873a93-R', 13, 1, 60, 'Deposit from Internal Linked Account (9494405173) - Morris Abali', 'completed', 21, 'deposit_internal_completed', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1773}', '9494405173', '5827241966', '2025-06-28 17:40:01'),
(102, 'IMF-20250629-004001-b0873a93-S', 11, 3, 60, 'Transfer to Linked Account (5827241966) - ', 'completed', 21, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"21\",\"source_account_number_linked\":\"9494405173\",\"source_account_holder_name_linked\":\"Morris Abali\",\"source_bank_name_linked\":\"- UBS Micro Finance Bank\",\"primary_account_balance_before\":1773}', '9494405173', '5827241966', '2025-06-28 17:40:01'),
(103, 'IMF-20250629-004200-3b11e8e5-S', 13, 3, 200, 'Transfer to Sunday Ozon (Account: 4231498915)', 'completed', NULL, '0', '{\"sender_balance_before\":1833,\"receiver_account_number\":\"4231498915\",\"receiver_account_name\":\"Sunday Ozon\",\"narration\":\"\"}', '5827241966', '4231498915', '2025-06-28 17:42:00'),
(104, 'IMF-20250629-004200-3b11e8e5-R', 24, 1, 200, 'Transfer from Charles Awuru (Account: 5827241966)', 'completed', NULL, '0', '{\"receiver_balance_before\":0,\"sender_account_number\":\"5827241966\",\"sender_account_name\":\"Charles Awuru\",\"narration\":\"\"}', '5827241966', '4231498915', '2025-06-28 17:42:00'),
(105, NULL, 12, 1, 10000, 'Bonus', 'completed', NULL, NULL, NULL, NULL, NULL, '2025-06-28 18:46:09'),
(106, 'IMF-20250629-015215-b6005cf6-S', 12, 3, 10000, 'Transfer to Charles Awuru (Account: 5827241966)', 'completed', NULL, '0', '{\"sender_balance_before\":23940,\"receiver_account_number\":\"5827241966\",\"receiver_account_name\":\"Charles Awuru\",\"narration\":\"\"}', '3963787860', '5827241966', '2025-06-28 18:52:15'),
(107, 'IMF-20250629-015215-b6005cf6-R', 13, 1, 10000, 'Transfer from Miracle Thomas (Account: 3963787860)', 'completed', NULL, '0', '{\"receiver_balance_before\":1633,\"sender_account_number\":\"3963787860\",\"sender_account_name\":\"Miracle Thomas\",\"narration\":\"\"}', '3963787860', '5827241966', '2025-06-28 18:52:15'),
(108, 'IMF-20250629-072204-6420d309-R', 20, 1, 1000, 'Deposit from Internal Linked Account (5827241966) - Charles Awuru', 'completed', 27, 'deposit_internal_completed', '{\"source_linked_account_id\":\"27\",\"source_account_number_linked\":\"5827241966\",\"source_account_holder_name_linked\":\"Charles Awuru\",\"source_bank_name_linked\":\"IMF\",\"primary_account_balance_before\":0}', '5827241966', '5387112226', '2025-06-29 00:22:04'),
(109, 'IMF-20250629-072204-6420d309-S', 13, 3, 1000, 'Transfer to Linked Account (5387112226) - ', 'completed', 27, 'internal_pull_outgoing', '{\"source_linked_account_id\":\"27\",\"source_account_number_linked\":\"5827241966\",\"source_account_holder_name_linked\":\"Charles Awuru\",\"source_bank_name_linked\":\"IMF\",\"primary_account_balance_before\":0}', '5827241966', '5387112226', '2025-06-29 00:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(50) NOT NULL,
  `firstname` varchar(250) NOT NULL,
  `lastname` varchar(250) NOT NULL,
  `username` text NOT NULL,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `password`, `avatar`, `last_login`, `type`, `date_added`, `date_updated`) VALUES
(1, 'Adminstrator', 'Admin', 'admin', '$2y$10$ptv5.rayGI01m0SX1A0NOex0CvVNUTbBkyLMcnOTAChFUxQtLH7oq', 'uploads/1624240500_avatar.png', NULL, 1, '2021-01-20 14:02:37', '2025-06-28 11:32:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_linked_accounts`
--

DROP TABLE IF EXISTS `user_linked_accounts`;
CREATE TABLE `user_linked_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_label` varchar(255) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `routing_number` varchar(50) DEFAULT NULL,
  `iban` varchar(50) DEFAULT NULL,
  `beneficiary_address` text DEFAULT NULL,
  `beneficiary_phone` varchar(20) DEFAULT NULL,
  `swift_bic` varchar(20) DEFAULT NULL,
  `account_number` varchar(255) NOT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `is_internal_bank` tinyint(1) NOT NULL DEFAULT 0,
  `account_type` varchar(50) DEFAULT NULL,
  `link_type` enum('source','beneficiary') NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `date_added` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_linked_accounts`
--

INSERT INTO `user_linked_accounts` (`id`, `user_id`, `account_label`, `bank_name`, `routing_number`, `iban`, `beneficiary_address`, `beneficiary_phone`, `swift_bic`, `account_number`, `account_holder_name`, `is_internal_bank`, `account_type`, `link_type`, `status`, `date_added`) VALUES
(2, 4, 'HOUSE HOLD', '- UBS Micro Finance Bank', NULL, NULL, NULL, NULL, NULL, '5994588630', 'George   Anderson', 1, 'Savings', 'source', 'active', '2025-06-11 22:56:12'),
(3, 4, 'WIFY', 'STANDARD BANK', NULL, NULL, NULL, NULL, NULL, '7654387652', 'WIFY CHIDON', 0, 'Checking', 'beneficiary', 'active', '2025-06-11 22:57:50'),
(21, 13, 'MY FRIEND', '- UBS Micro Finance Bank', '', '', '', '', '', '9494405173', 'Morris Abali', 1, 'Savings', 'source', 'active', '2025-06-18 21:59:02'),
(23, 11, 'My Sister ', '- IMF Micro Finance Bank', '', '', '', '', '', '3963787860', 'Miracle Thomas', 1, 'Loan', 'beneficiary', 'active', '2025-06-20 18:20:20'),
(24, 12, 'brother', '- IMF Micro Finance Bank', '', '', '', '', '', '9494405173', 'Morris Abali', 1, 'Savings', 'source', 'active', '2025-06-20 18:30:13'),
(25, 22, 'MY DAD', 'IMF', '', '', '', 'awuu@gmail.com', '', '3777431476', 'Hen Awu', 1, 'Savings', 'source', 'active', '2025-06-28 06:42:30'),
(26, 20, 'My Sister', 'IMF', '', '', '', 'jjj@gmail.com', '', '4231498915', 'Sunday Ozon', 1, 'Savings', 'source', 'active', '2025-06-28 23:18:39'),
(27, 20, 'MY DADS', 'IMF', '', '', '', 'jjj@gmail.com', '', '5827241966', 'Charles Awuru', 1, 'Savings', 'source', 'active', '2025-06-28 23:19:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_info`
--
ALTER TABLE `system_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_linked_accounts`
--
ALTER TABLE `user_linked_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_linked_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_info`
--
ALTER TABLE `system_info`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_linked_accounts`
--
ALTER TABLE `user_linked_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_linked_accounts`
--
ALTER TABLE `user_linked_accounts`
  ADD CONSTRAINT `fk_linked_user_id` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
