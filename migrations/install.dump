
/*! db_prefix_access_tokens 表1 */;
DROP TABLE IF EXISTS `db_prefix_access_tokens`;
CREATE TABLE `db_prefix_access_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `last_activity_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_prefix_access_tokens_token_unique` (`token`),
  KEY `db_prefix_access_tokens_user_id_foreign` (`user_id`),
  KEY `db_prefix_access_tokens_type_index` (`type`),
  CONSTRAINT `db_prefix_access_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_api_keys 表2 */;
DROP TABLE IF EXISTS `db_prefix_api_keys`;
CREATE TABLE `db_prefix_api_keys` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `allowed_ips` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scopes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_prefix_api_keys_key_unique` (`key`),
  KEY `db_prefix_api_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `db_prefix_api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_discussion_user 表3 */;
DROP TABLE IF EXISTS `db_prefix_discussion_user`;
CREATE TABLE `db_prefix_discussion_user` (
  `user_id` int(10) unsigned NOT NULL,
  `discussion_id` int(10) unsigned NOT NULL,
  `last_read_at` datetime DEFAULT NULL,
  `last_read_post_number` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`user_id`,`discussion_id`),
  KEY `db_prefix_discussion_user_discussion_id_foreign` (`discussion_id`),
  CONSTRAINT `db_prefix_discussion_user_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `db_prefix_discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `db_prefix_discussion_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_discussions 表4 */;
DROP TABLE IF EXISTS `db_prefix_discussions`;
CREATE TABLE `db_prefix_discussions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment_count` int(11) NOT NULL DEFAULT 1,
  `participant_count` int(10) unsigned NOT NULL DEFAULT 0,
  `post_number_index` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `first_post_id` int(10) unsigned DEFAULT NULL,
  `last_posted_at` datetime DEFAULT NULL,
  `last_posted_user_id` int(10) unsigned DEFAULT NULL,
  `last_post_id` int(10) unsigned DEFAULT NULL,
  `last_post_number` int(10) unsigned DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL,
  `hidden_user_id` int(10) unsigned DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `db_prefix_discussions_hidden_user_id_foreign` (`hidden_user_id`),
  KEY `db_prefix_discussions_first_post_id_foreign` (`first_post_id`),
  KEY `db_prefix_discussions_last_post_id_foreign` (`last_post_id`),
  KEY `db_prefix_discussions_last_posted_at_index` (`last_posted_at`),
  KEY `db_prefix_discussions_last_posted_user_id_index` (`last_posted_user_id`),
  KEY `db_prefix_discussions_created_at_index` (`created_at`),
  KEY `db_prefix_discussions_user_id_index` (`user_id`),
  KEY `db_prefix_discussions_comment_count_index` (`comment_count`),
  KEY `db_prefix_discussions_participant_count_index` (`participant_count`),
  KEY `db_prefix_discussions_hidden_at_index` (`hidden_at`),
  FULLTEXT KEY `title` (`title`),
  CONSTRAINT `db_prefix_discussions_first_post_id_foreign` FOREIGN KEY (`first_post_id`) REFERENCES `db_prefix_posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_discussions_hidden_user_id_foreign` FOREIGN KEY (`hidden_user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_discussions_last_post_id_foreign` FOREIGN KEY (`last_post_id`) REFERENCES `db_prefix_posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_discussions_last_posted_user_id_foreign` FOREIGN KEY (`last_posted_user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_discussions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_email_tokens 表5 */;
DROP TABLE IF EXISTS `db_prefix_email_tokens`;
CREATE TABLE `db_prefix_email_tokens` (
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`token`),
  KEY `db_prefix_email_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `db_prefix_email_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_group_permission 表6 */;
DROP TABLE IF EXISTS `db_prefix_group_permission`;
CREATE TABLE `db_prefix_group_permission` (
  `group_id` int(10) unsigned NOT NULL,
  `permission` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`,`permission`),
  CONSTRAINT `db_prefix_group_permission_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `db_prefix_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_group_user 表7 */;
DROP TABLE IF EXISTS `db_prefix_group_user`;
CREATE TABLE `db_prefix_group_user` (
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `db_prefix_group_user_group_id_foreign` (`group_id`),
  CONSTRAINT `db_prefix_group_user_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `db_prefix_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `db_prefix_group_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_groups 表8 */;
DROP TABLE IF EXISTS `db_prefix_groups`;
CREATE TABLE `db_prefix_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name_singular` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_plural` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_login_providers 表9 */;
DROP TABLE IF EXISTS `db_prefix_login_providers`;
CREATE TABLE `db_prefix_login_providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `provider` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_prefix_login_providers_provider_identifier_unique` (`provider`,`identifier`),
  KEY `db_prefix_login_providers_user_id_foreign` (`user_id`),
  CONSTRAINT `db_prefix_login_providers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_migrations 表10 */;
DROP TABLE IF EXISTS `db_prefix_migrations`;
CREATE TABLE `db_prefix_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_notifications 表11 */;
DROP TABLE IF EXISTS `db_prefix_notifications`;
CREATE TABLE `db_prefix_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `from_user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `data` blob DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `db_prefix_notifications_from_user_id_foreign` (`from_user_id`),
  KEY `db_prefix_notifications_user_id_index` (`user_id`),
  CONSTRAINT `db_prefix_notifications_from_user_id_foreign` FOREIGN KEY (`from_user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_password_tokens 表12 */;
DROP TABLE IF EXISTS `db_prefix_password_tokens`;
CREATE TABLE `db_prefix_password_tokens` (
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`token`),
  KEY `db_prefix_password_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `db_prefix_password_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_post_user 表13 */;
DROP TABLE IF EXISTS `db_prefix_post_user`;
CREATE TABLE `db_prefix_post_user` (
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`user_id`),
  KEY `db_prefix_post_user_user_id_foreign` (`user_id`),
  CONSTRAINT `db_prefix_post_user_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `db_prefix_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `db_prefix_post_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_posts 表14 */;
DROP TABLE IF EXISTS `db_prefix_posts`;
CREATE TABLE `db_prefix_posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` int(10) unsigned NOT NULL,
  `number` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT ' ',
  `edited_at` datetime DEFAULT NULL,
  `edited_user_id` int(10) unsigned DEFAULT NULL,
  `hidden_at` datetime DEFAULT NULL,
  `hidden_user_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_prefix_posts_discussion_id_number_unique` (`discussion_id`,`number`),
  KEY `db_prefix_posts_edited_user_id_foreign` (`edited_user_id`),
  KEY `db_prefix_posts_hidden_user_id_foreign` (`hidden_user_id`),
  KEY `db_prefix_posts_discussion_id_number_index` (`discussion_id`,`number`),
  KEY `db_prefix_posts_discussion_id_created_at_index` (`discussion_id`,`created_at`),
  KEY `db_prefix_posts_user_id_created_at_index` (`user_id`,`created_at`),
  FULLTEXT KEY `content` (`content`),
  CONSTRAINT `db_prefix_posts_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `db_prefix_discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `db_prefix_posts_edited_user_id_foreign` FOREIGN KEY (`edited_user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_posts_hidden_user_id_foreign` FOREIGN KEY (`hidden_user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `db_prefix_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `db_prefix_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_registration_tokens 表15 */;
DROP TABLE IF EXISTS `db_prefix_registration_tokens`;
CREATE TABLE `db_prefix_registration_tokens` (
  `token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_attributes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_settings 表16 */;
DROP TABLE IF EXISTS `db_prefix_settings`;
CREATE TABLE `db_prefix_settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


/*! db_prefix_users 表17 */;
DROP TABLE IF EXISTS `db_prefix_users`;
CREATE TABLE `db_prefix_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_email_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `password` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_url` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferences` blob DEFAULT NULL,
  `joined_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `marked_all_as_read_at` datetime DEFAULT NULL,
  `read_notifications_at` datetime DEFAULT NULL,
  `discussion_count` int(10) unsigned NOT NULL DEFAULT 0,
  `comment_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `db_prefix_users_username_unique` (`username`),
  UNIQUE KEY `db_prefix_users_email_unique` (`email`),
  KEY `db_prefix_users_joined_at_index` (`joined_at`),
  KEY `db_prefix_users_last_seen_at_index` (`last_seen_at`),
  KEY `db_prefix_users_discussion_count_index` (`discussion_count`),
  KEY `db_prefix_users_comment_count_index` (`comment_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/*! MIGRATIONS */;

INSERT INTO `db_prefix_migrations` VALUES (1,'2024_03_090100_create_access_tokens_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (2,'2024_03_090200_create_api_keys_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (3,'2024_03_090300_create_discussions_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (4,'2024_03_090400_create_discussion_user_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (5,'2024_03_090500_create_email_tokens_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (6,'2024_03_090600_create_groups_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (7,'2024_03_090700_create_group_permission_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (8,'2024_03_090800_create_group_user_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (9,'2024_03_090900_create_login_providers_table',NULL);

INSERT INTO `db_prefix_migrations` VALUES (10,'2024_03_091000_create_notifications_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (11,'2024_03_091100_create_password_tokens_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (12,'2024_03_091200_create_posts_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (13,'2024_03_091300_create_post_user_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (14,'2024_03_091400_create_registration_tokens_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (15,'2024_03_091500_create_settings_table',NULL);
INSERT INTO `db_prefix_migrations` VALUES (16,'2024_03_091600_create_users_table',NULL);



INSERT INTO `db_prefix_migrations` VALUES (17,'2024_05_26_000000_change_access_tokens_columns',NULL);
INSERT INTO `db_prefix_migrations` VALUES (18,'2024_05_26_000100_change_access_tokens_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (19,'2024_05_26_000200_change_api_keys_columns',NULL);
INSERT INTO `db_prefix_migrations` VALUES (20,'2024_05_26_000300_change_posts_table_to_innodb',NULL);
INSERT INTO `db_prefix_migrations` VALUES (21,'2024_05_26_000400_change_discussions_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (22,'2024_05_27_000000_change_email_tokens_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (23,'2024_05_27_000100_change_email_tokens_created_at_to_datetime',NULL);
INSERT INTO `db_prefix_migrations` VALUES (24,'2024_05_28_000000_change_group_permission_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (25,'2024_05_28_000100_change_group_user_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (26,'2024_05_28_000200_change_notifications_columns',NULL);
INSERT INTO `db_prefix_migrations` VALUES (27,'2024_05_28_000300_change_notifications_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (28,'2024_05_28_000400_change_posts_add_foreign_keys',NULL);
INSERT INTO `db_prefix_migrations` VALUES (29,'2024_05_30_000000_add_fulltext_index_to_discussions_title',NULL);
INSERT INTO `db_prefix_migrations` VALUES (30,'2024_06_12_000000_add_posts_indices',NULL);
INSERT INTO `db_prefix_migrations` VALUES (31,'2024_06_13_000000_add_shim_prefix_to_group_icons',NULL);
INSERT INTO `db_prefix_migrations` VALUES (32,'2024_06_14_000000_change_posts_add_discussion_foreign_key',NULL);
INSERT INTO `db_prefix_migrations` VALUES (33,'2024_06_15_000000_change_access_tokens_add_type',NULL);
INSERT INTO `db_prefix_migrations` VALUES (34,'2024_06_15_000100_change_access_tokens_add_id',NULL);
INSERT INTO `db_prefix_migrations` VALUES (35,'2024_06_15_000200_change_access_tokens_add_title_ip_agent',NULL);
INSERT INTO `db_prefix_migrations` VALUES (36,'2024_06_16_000000_change_posts_content_column_to_mediumtext',NULL);
