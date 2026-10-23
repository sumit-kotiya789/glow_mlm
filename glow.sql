-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 23, 2026 at 08:35 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `glow`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `name`, `password_hash`, `created_at`) VALUES
(1, 'ADMIN001', 'Admin', '$2y$10$9agPHg8kOS.Px65HokJhsO94Q9XqAnFLtnu6V4OFCnZltRgHm7n9a', '2026-10-23 12:03:03');

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `tx_hash` varchar(100) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'Pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investments`
--

CREATE TABLE `investments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan` varchar(10) NOT NULL,
  `term_days` int(11) NOT NULL,
  `principal_rate` decimal(6,3) NOT NULL,
  `profit_rate` decimal(6,3) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `principal` decimal(14,2) NOT NULL,
  `pending` decimal(14,2) NOT NULL DEFAULT 0.00,
  `latest_part` decimal(14,2) NOT NULL DEFAULT 0.00,
  `accrued` decimal(14,2) NOT NULL DEFAULT 0.00,
  `days_paid` int(11) NOT NULL DEFAULT 0,
  `status` varchar(12) NOT NULL DEFAULT 'Active',
  `started_at` datetime NOT NULL,
  `last_paid_on` varchar(16) DEFAULT NULL,
  `matured_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ledger`
--

CREATE TABLE `ledger` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet` varchar(10) NOT NULL,
  `type` varchar(20) NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `from_user_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `note` varchar(160) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lucky_draws`
--

CREATE TABLE `lucky_draws` (
  `id` int(11) NOT NULL,
  `title` varchar(80) NOT NULL,
  `prize` decimal(14,2) NOT NULL,
  `winners_count` int(11) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'Draft',
  `created_at` datetime NOT NULL,
  `distributed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lucky_draw_winners`
--

CREATE TABLE `lucky_draw_winners` (
  `id` int(11) NOT NULL,
  `draw_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `picked_by` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payout_runs`
--

CREATE TABLE `payout_runs` (
  `run_date` varchar(16) NOT NULL,
  `investments_paid` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_terms`
--

CREATE TABLE `plan_terms` (
  `id` int(11) NOT NULL,
  `days` int(11) NOT NULL,
  `principal_rate` decimal(6,3) NOT NULL,
  `profit_rate` decimal(6,3) NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plan_terms`
--

INSERT INTO `plan_terms` (`id`, `days`, `principal_rate`, `profit_rate`, `is_active`) VALUES
(1, 25, 4.000, 1.000, 1),
(2, 50, 4.000, 1.000, 1),
(3, 100, 4.000, 1.000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(60) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`) VALUES
('deposit_address', '0x0000000000000000000000000000000000000000'),
('direct_pct', '10'),
('level_deposit_pct', '1'),
('lucky_prize', '100'),
('lucky_winners', '100'),
('max_invest', '1000'),
('max_withdraw', '1000'),
('min_deposit', '5'),
('min_invest', '5'),
('min_invest_bonus', '10'),
('min_transfer', '1'),
('min_withdraw', '1'),
('payout_period', 'day'),
('payout_time', '00:00'),
('roi_level_pcts', '5,1,1,1,1,1,1,1,1,1'),
('salary_amount', '100'),
('salary_directs', '5'),
('salary_level2', '10'),
('salary_min_self', '50'),
('salary_months', '12'),
('salary_pay_day', '1'),
('salary_window_days', '15'),
('signup_bonus_amount', '5'),
('signup_bonus_enabled', '1'),
('withdraw_fee_pct', '10');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `replied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade_pairs`
--

CREATE TABLE `trade_pairs` (
  `id` int(11) NOT NULL,
  `pair` varchar(20) NOT NULL,
  `side` varchar(10) NOT NULL,
  `leverage` int(11) NOT NULL,
  `entry_price` decimal(20,6) NOT NULL,
  `latest_price` decimal(20,6) NOT NULL,
  `pnl` decimal(10,2) NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trade_pairs`
--

INSERT INTO `trade_pairs` (`id`, `pair`, `side`, `leverage`, `entry_price`, `latest_price`, `pnl`, `updated_at`) VALUES
(1, 'BTC/USDT', 'Short', 5, 60160.000000, 67439.360000, -60.50, '2026-10-23 10:35:03'),
(2, 'ETH/USDT', 'Short', 20, 2376.000000, 2950.992000, -484.00, '2026-10-23 10:40:03'),
(3, 'BNB/USDT', 'Long', 5, 714.000000, 823.242000, 76.50, '2026-10-23 11:38:03'),
(4, 'SOL/USDT', 'Long', 5, 141.400000, 127.542800, -49.00, '2026-10-23 10:56:03'),
(5, 'XRP/USDT', 'Short', 5, 0.468000, 0.544752, -82.00, '2026-10-23 11:49:03'),
(6, 'ADA/USDT', 'Long', 5, 0.345800, 0.371389, 37.00, '2026-10-23 11:15:03'),
(7, 'DOGE/USDT', 'Long', 15, 0.110400, 0.098146, -166.50, '2026-10-23 11:08:03'),
(8, 'TRX/USDT', 'Long', 10, 0.104000, 0.100568, -33.00, '2026-10-23 11:29:03'),
(9, 'DOT/USDT', 'Long', 5, 6.820000, 8.777340, 143.50, '2026-10-23 10:33:03'),
(10, 'LINK/USDT', 'Long', 10, 15.370000, 19.719710, 283.00, '2026-10-23 11:00:03'),
(11, 'MATIC/USDT', 'Long', 10, 0.665000, 0.712215, 71.00, '2026-10-23 11:12:03'),
(12, 'LTC/USDT', 'Long', 15, 66.960000, 60.799680, -138.00, '2026-10-23 11:50:03'),
(13, 'AVAX/USDT', 'Long', 15, 27.160000, 34.384560, 399.00, '2026-10-23 11:22:03'),
(14, 'UNI/USDT', 'Long', 10, 7.326000, 9.714276, 326.00, '2026-10-23 11:42:03'),
(15, 'ATOM/USDT', 'Short', 20, 6.561000, 8.181567, -494.00, '2026-10-23 11:00:03'),
(16, 'XLM/USDT', 'Long', 10, 0.121000, 0.150524, 244.00, '2026-10-23 11:51:03'),
(17, 'FIL/USDT', 'Long', 10, 3.680000, 3.396640, -77.00, '2026-10-23 11:10:03'),
(18, 'ICP/USDT', 'Short', 10, 10.695000, 10.769865, -7.00, '2026-10-23 11:01:03'),
(19, 'VET/USDT', 'Long', 20, 0.029250, 0.034164, 336.00, '2026-10-23 11:19:03'),
(20, 'ETC/USDT', 'Long', 15, 22.660000, 20.348680, -153.00, '2026-10-23 11:06:03'),
(21, 'NEAR/USDT', 'Long', 20, 5.763000, 5.797578, 12.00, '2026-10-23 11:27:03'),
(22, 'APT/USDT', 'Long', 20, 7.917000, 7.054047, -218.00, '2026-10-23 10:59:03'),
(23, 'ARB/USDT', 'Long', 15, 0.792000, 0.700920, -172.50, '2026-10-23 10:38:03'),
(24, 'OP/USDT', 'Long', 5, 1.710000, 2.005830, 86.50, '2026-10-23 10:53:03'),
(25, 'SHIB/USDT', 'Short', 5, 0.000021, 0.000027, -135.50, '2026-10-23 10:53:03'),
(26, 'INJ/USDT', 'Long', 10, 24.720000, 32.407920, 311.00, '2026-10-23 11:40:03'),
(27, 'SUI/USDT', 'Long', 20, 1.298000, 1.112386, -286.00, '2026-10-23 11:26:03'),
(28, 'AAVE/USDT', 'Short', 10, 89.180000, 96.849480, -86.00, '2026-10-23 10:39:03'),
(29, 'ALGO/USDT', 'Long', 20, 0.139400, 0.196833, 824.00, '2026-10-23 11:48:03'),
(30, 'SAND/USDT', 'Long', 20, 0.474600, 0.535349, 256.00, '2026-10-23 11:08:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_code` varchar(12) NOT NULL,
  `sponsor_id` int(11) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `country` varchar(60) NOT NULL,
  `wallet` varchar(42) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `txn_password_hash` varchar(255) DEFAULT NULL,
  `txn_fails` int(11) NOT NULL DEFAULT 0,
  `txn_locked_until` datetime DEFAULT NULL,
  `deposit_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `roi_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `fd_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `income_balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `activated_at` datetime DEFAULT NULL,
  `signup_bonus_paid` int(11) NOT NULL DEFAULT 0,
  `salary_qualified_at` datetime DEFAULT NULL,
  `salary_months_paid` int(11) NOT NULL DEFAULT 0,
  `salary_last_month` varchar(7) DEFAULT NULL,
  `is_blocked` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_code`, `sponsor_id`, `name`, `email`, `mobile`, `country`, `wallet`, `password_hash`, `txn_password_hash`, `txn_fails`, `txn_locked_until`, `deposit_balance`, `roi_balance`, `fd_balance`, `income_balance`, `activated_at`, `signup_bonus_paid`, `salary_qualified_at`, `salary_months_paid`, `salary_last_month`, `is_blocked`, `created_at`) VALUES
(1, 'GL0000001', NULL, 'DigitalMoney Earn', 'top@example.com', '+10000000000', 'India', '0x0000000000000000000000000000000000000000', '$2y$10$z9T4TAwx6OKGG3PbETZcmO7gM9qiR7APWEsBQApoV9ziMOdmjUvKe', '$2y$10$Mx44cu/vc1IVbo/kYTv0j.xE7EkMbYJVe3g.Hjlfw1bFiGevWN2d2', 0, NULL, 0.00, 0.00, 0.00, 0.00, NULL, 0, NULL, 0, NULL, 0, '2026-10-23 12:03:03');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawals`
--

CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `source` varchar(10) NOT NULL,
  `investment_id` int(11) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(14,2) NOT NULL,
  `wallet` varchar(42) NOT NULL,
  `status` varchar(12) NOT NULL DEFAULT 'Pending',
  `admin_note` varchar(255) DEFAULT NULL,
  `payout_hash` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tx_hash` (`tx_hash`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_deposits_status` (`status`);

--
-- Indexes for table `investments`
--
ALTER TABLE `investments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_investments_user` (`user_id`,`status`);

--
-- Indexes for table `ledger`
--
ALTER TABLE `ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_user_type` (`user_id`,`type`);

--
-- Indexes for table `lucky_draws`
--
ALTER TABLE `lucky_draws`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lucky_draw_winners`
--
ALTER TABLE `lucky_draw_winners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `draw_id` (`draw_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payout_runs`
--
ALTER TABLE `payout_runs`
  ADD PRIMARY KEY (`run_date`);

--
-- Indexes for table `plan_terms`
--
ALTER TABLE `plan_terms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `days` (`days`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trade_pairs`
--
ALTER TABLE `trade_pairs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_code` (`user_code`),
  ADD KEY `idx_users_sponsor` (`sponsor_id`);

--
-- Indexes for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `investment_id` (`investment_id`),
  ADD KEY `idx_withdrawals_user` (`user_id`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investments`
--
ALTER TABLE `investments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ledger`
--
ALTER TABLE `ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lucky_draws`
--
ALTER TABLE `lucky_draws`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lucky_draw_winners`
--
ALTER TABLE `lucky_draw_winners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plan_terms`
--
ALTER TABLE `plan_terms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade_pairs`
--
ALTER TABLE `trade_pairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawals`
--
ALTER TABLE `withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `deposits`
--
ALTER TABLE `deposits`
  ADD CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `investments`
--
ALTER TABLE `investments`
  ADD CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `ledger`
--
ALTER TABLE `ledger`
  ADD CONSTRAINT `ledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `lucky_draw_winners`
--
ALTER TABLE `lucky_draw_winners`
  ADD CONSTRAINT `lucky_draw_winners_ibfk_1` FOREIGN KEY (`draw_id`) REFERENCES `lucky_draws` (`id`),
  ADD CONSTRAINT `lucky_draw_winners_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`sponsor_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `withdrawals`
--
ALTER TABLE `withdrawals`
  ADD CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `withdrawals_ibfk_2` FOREIGN KEY (`investment_id`) REFERENCES `investments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
