<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure analyst gamification tables exist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS badge (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            criteria_type VARCHAR(50) NOT NULL,
            criteria_value INT NOT NULL,
            rarity VARCHAR(20) NOT NULL,
            UNIQUE INDEX UNIQ_BADGE_NAME (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS gamification_stats (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            total_xp INT NOT NULL DEFAULT 0,
            current_level INT NOT NULL DEFAULT 1,
            streak_days INT NOT NULL DEFAULT 0,
            last_activity_date DATE DEFAULT NULL,
            total_focus_time INT NOT NULL DEFAULT 0,
            tasks_completed INT NOT NULL DEFAULT 0,
            UNIQUE INDEX UNIQ_GAMIFICATION_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_GAMIFICATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS user_badge (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at DATETIME NOT NULL,
            INDEX IDX_USER_BADGE_USER (user_id),
            INDEX IDX_USER_BADGE_BADGE (badge_id),
            UNIQUE INDEX UNIQ_USER_BADGE (user_id, badge_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_badge');
        $this->addSql('DROP TABLE IF EXISTS gamification_stats');
        $this->addSql('DROP TABLE IF EXISTS badge');
    }
}
