-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 01:18 PM
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
(1, 'admin', 'Demo Admin', '$2y$10$KbPMOwPehb4tDgtGWirmu.vWnWtcPkDQsfiAGvgobQT3m5PBLkH5C', '2026-10-19 16:48:13');

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
(1, 'BTC/USDT', 'Short', 5, 70400.000000, 89478.400000, -135.50, '2026-10-19 16:11:13'),
(2, 'ETH/USDT', 'Long', 5, 2616.000000, 2673.552000, 11.00, '2026-10-19 15:21:13'),
(3, 'BNB/USDT', 'Short', 15, 702.000000, 720.954000, -40.50, '2026-10-19 16:36:13'),
(4, 'SOL/USDT', 'Long', 15, 131.600000, 156.472400, 283.50, '2026-10-19 16:26:13'),
(5, 'XRP/USDT', 'Long', 5, 0.613600, 0.717298, 84.50, '2026-10-19 15:45:13'),
(6, 'ADA/USDT', 'Long', 15, 0.357200, 0.450786, 393.00, '2026-10-19 15:59:13'),
(7, 'DOGE/USDT', 'Long', 20, 0.141600, 0.145565, 56.00, '2026-10-19 16:06:13'),
(8, 'TRX/USDT', 'Long', 15, 0.149500, 0.212141, 628.50, '2026-10-19 16:15:13'),
(9, 'DOT/USDT', 'Long', 5, 5.208000, 6.744360, 147.50, '2026-10-19 15:23:13'),
(10, 'LINK/USDT', 'Long', 15, 16.965000, 15.149745, -160.50, '2026-10-19 15:19:13'),
(11, 'MATIC/USDT', 'Long', 10, 0.763000, 0.943831, 237.00, '2026-10-19 16:43:13'),
(12, 'LTC/USDT', 'Long', 20, 86.400000, 103.680000, 400.00, '2026-10-19 15:51:13'),
(13, 'AVAX/USDT', 'Long', 15, 26.880000, 32.229120, 298.50, '2026-10-19 15:49:13'),
(14, 'UNI/USDT', 'Long', 10, 7.326000, 7.435890, 15.00, '2026-10-19 16:25:13'),
(15, 'ATOM/USDT', 'Long', 15, 9.315000, 12.034980, 438.00, '2026-10-19 16:33:13'),
(16, 'XLM/USDT', 'Long', 5, 0.097900, 0.103480, 28.50, '2026-10-19 15:37:13'),
(17, 'FIL/USDT', 'Short', 5, 4.830000, 5.269530, -45.50, '2026-10-19 15:26:13'),
(18, 'ICP/USDT', 'Long', 5, 10.044000, 8.838720, -60.00, '2026-10-19 16:22:13'),
(19, 'VET/USDT', 'Long', 20, 0.023250, 0.021530, -148.00, '2026-10-19 16:10:13'),
(20, 'ETC/USDT', 'Long', 10, 21.340000, 25.543980, 197.00, '2026-10-19 16:35:13'),
(21, 'NEAR/USDT', 'Long', 15, 4.641000, 4.650282, 3.00, '2026-10-19 15:44:13'),
(22, 'APT/USDT', 'Long', 15, 8.526000, 7.869498, -115.50, '2026-10-19 16:34:13'),
(23, 'ARB/USDT', 'Long', 15, 1.044000, 1.026252, -25.50, '2026-10-19 15:26:13'),
(24, 'OP/USDT', 'Long', 15, 1.710000, 1.720260, 9.00, '2026-10-19 15:22:13'),
(25, 'SHIB/USDT', 'Long', 20, 0.000021, 0.000028, 698.00, '2026-10-19 16:43:13'),
(26, 'INJ/USDT', 'Long', 20, 21.360000, 27.938880, 616.00, '2026-10-19 15:21:13'),
(27, 'SUI/USDT', 'Long', 5, 1.254000, 1.361844, 43.00, '2026-10-19 16:07:13'),
(28, 'AAVE/USDT', 'Long', 15, 80.360000, 89.038880, 162.00, '2026-10-19 15:49:13'),
(29, 'ALGO/USDT', 'Long', 5, 0.149600, 0.153340, 12.50, '2026-10-19 15:42:13'),
(30, 'SAND/USDT', 'Long', 5, 0.373800, 0.494164, 161.00, '2026-10-19 16:35:13');

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

INSERT INTO `users` (`id`, `user_code`, `sponsor_id`, `name`, `email`, `mobile`, `country`, `wallet`, `password_hash`, `txn_password_hash`, `txn_fails`, `txn_locked_until`, `deposit_balance`, `roi_balance`, `income_balance`, `activated_at`, `signup_bonus_paid`, `salary_qualified_at`, `salary_months_paid`, `salary_last_month`, `is_blocked`, `created_at`) VALUES
(1, 'GL0000001', NULL, 'Top Member', 'top@example.com', '+10000000000', 'India', '0x0000000000000000000000000000000000000000', '$2y$10$9Jk7ScjkZ8tR0q163Scdguqpm8BGgr76GNbsmQIZt03jWyevsiVs2', '$2y$10$BOwnd7RIGFdN1rSojEKza.CERJhVYR6EcdwrF7BUKHFDi0biDKOnO', 0, NULL, 0.00, 0.00, 0.00, NULL, 0, NULL, 0, NULL, 0, '2026-10-19 16:48:13');

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
