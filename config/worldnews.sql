-- Import this file in phpMyAdmin to create the World News database.
-- XAMPP defaults: host=localhost, port=3306, user=root, password=(blank).

CREATE DATABASE IF NOT EXISTS `worldnews`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `worldnews`;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(150) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','user') DEFAULT 'user',
    `account_status` enum('active','banned') NOT NULL DEFAULT 'active',
    `banned_at` datetime DEFAULT NULL,
    `ban_reason` varchar(500) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category_id` int(11) NOT NULL,
    `author_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `image` varchar(255) DEFAULT NULL,
    `source` varchar(255) DEFAULT NULL,
    `views` int(11) DEFAULT 0,
    `status` enum('draft','published') DEFAULT 'draft',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
        ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `category_id` (`category_id`),
    KEY `author_id` (`author_id`),
    CONSTRAINT `news_ibfk_1`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `news_ibfk_2`
        FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `comments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `news_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `comment` text NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `news_id` (`news_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `comments_ibfk_1`
        FOREIGN KEY (`news_id`) REFERENCES `news` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `comments_ibfk_2`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `categories` (`id`, `name`, `slug`) VALUES
    (1, 'การเมือง', 'politics'),
    (2, 'เศรษฐกิจ', 'economy'),
    (3, 'เทคโนโลยี', 'technology'),
    (4, 'กีฬา', 'sports'),
    (5, 'สังคม', 'society'),
    (6, 'บันเทิง', 'entertainment');
