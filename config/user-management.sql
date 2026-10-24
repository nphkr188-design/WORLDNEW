USE `worldnews`;

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `account_status` enum('active','banned') NOT NULL DEFAULT 'active',
    ADD COLUMN IF NOT EXISTS `banned_at` datetime DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ban_reason` varchar(500) DEFAULT NULL;
