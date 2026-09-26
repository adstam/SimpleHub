CREATE TABLE IF NOT EXISTS `#__simplehub_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `ordering` INT NOT NULL DEFAULT 0,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NULL DEFAULT NULL,
    `modified` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ordering` (`ordering`),
    KEY `idx_published` (`published`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__simplehub_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `icon` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `target` VARCHAR(255) NOT NULL,
	`external_target` VARCHAR(20) NOT NULL DEFAULT '_blank',
    `ordering` INT NOT NULL DEFAULT 0,
    `published` TINYINT(1) NOT NULL DEFAULT 1,
    `created` DATETIME NULL DEFAULT NULL,
    `modified` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_group` (`group_id`),
    KEY `idx_ordering` (`ordering`),
    KEY `idx_published` (`published`),
    CONSTRAINT `#__simplehub_items_group`
        FOREIGN KEY (`group_id`)
        REFERENCES `#__simplehub_groups` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
DEFAULT COLLATE=utf8mb4_unicode_ci;