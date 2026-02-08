<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial MindForge database schema for Java/Symfony shared access.
 */
final class Version20240208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial MindForge database schema with core tables for Java/Symfony shared access';
    }

    public function up(Schema $schema): void
    {
        // 1. User table (core identity - used by both Symfony and Java)
        $this->addSql('CREATE TABLE user (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Profile table (extended user info)
        $this->addSql('CREATE TABLE profile (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            first_name VARCHAR(100) DEFAULT NULL,
            last_name VARCHAR(100) DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            bio LONGTEXT DEFAULT NULL,
            timezone VARCHAR(50) DEFAULT "UTC",
            locale VARCHAR(10) DEFAULT "en",
            UNIQUE INDEX UNIQ_PROFILE_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PROFILE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Subject table (global academic structure - managed by Admin)
        $this->addSql('CREATE TABLE subject (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            color VARCHAR(7) DEFAULT "#6840d6",
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE INDEX UNIQ_SUBJECT_CODE (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 4. RoleRequest table (Student+ upgrade requests)
        $this->addSql('CREATE TABLE role_request (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            motivation LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            admin_notes LONGTEXT DEFAULT NULL,
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME DEFAULT NULL,
            INDEX IDX_ROLE_REQUEST_USER (user_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_ROLE_REQUEST_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 5. Task table (personal organization)
        $this->addSql('CREATE TABLE task (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            subject_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            difficulty VARCHAR(20) DEFAULT "medium",
            status VARCHAR(20) DEFAULT "todo",
            due_date DATETIME DEFAULT NULL,
            estimated_duration INT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_TASK_USER (user_id),
            INDEX IDX_TASK_SUBJECT (subject_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_TASK_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_TASK_SUBJECT FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 6. Exam table (scheduled assessments)
        $this->addSql('CREATE TABLE exam (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            subject_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            exam_date DATETIME NOT NULL,
            location VARCHAR(255) DEFAULT NULL,
            priority INT DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX IDX_EXAM_USER (user_id),
            INDEX IDX_EXAM_SUBJECT (subject_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_EXAM_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_EXAM_SUBJECT FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 7. Resource table (Module 3 - Guardian - files uploaded by Student+)
        $this->addSql('CREATE TABLE resource (
            id INT AUTO_INCREMENT NOT NULL,
            uploader_id INT NOT NULL,
            subject_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            file_path VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            download_count INT NOT NULL DEFAULT 0,
            rating SMALLINT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_RESOURCE_UPLOADER (uploader_id),
            INDEX IDX_RESOURCE_SUBJECT (subject_id),
            INDEX IDX_RESOURCE_TYPE (type),
            PRIMARY KEY(id),
            CONSTRAINT FK_RESOURCE_UPLOADER FOREIGN KEY (uploader_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_RESOURCE_SUBJECT FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 8. VirtualRoom table (Module 3 - Guardian - study groups)
        $this->addSql('CREATE TABLE virtual_room (
            id INT AUTO_INCREMENT NOT NULL,
            creator_id INT NOT NULL,
            subject_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            max_participants INT NOT NULL DEFAULT 10,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX IDX_ROOM_CREATOR (creator_id),
            INDEX IDX_ROOM_SUBJECT (subject_id),
            INDEX IDX_ROOM_ACTIVE (is_active),
            PRIMARY KEY(id),
            CONSTRAINT FK_ROOM_CREATOR FOREIGN KEY (creator_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_ROOM_SUBJECT FOREIGN KEY (subject_id) REFERENCES subject (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 9. VirtualRoom participants (many-to-many)
        $this->addSql('CREATE TABLE virtual_room_participants (
            virtual_room_id INT NOT NULL,
            user_id INT NOT NULL,
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(virtual_room_id, user_id),
            CONSTRAINT FK_PARTICIPANTS_ROOM FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE,
            CONSTRAINT FK_PARTICIPANTS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 10. FocusSession table (Module 3 - Java Desktop tracking)
        $this->addSql('CREATE TABLE focus_session (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            task_id INT DEFAULT NULL,
            duration INT NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            session_type VARCHAR(50) DEFAULT "pomodoro",
            INDEX IDX_FOCUS_USER (user_id),
            INDEX IDX_FOCUS_TASK (task_id),
            INDEX IDX_FOCUS_STARTED (started_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_FOCUS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_FOCUS_TASK FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 11. Badge table (Module 4 - Gamification)
        $this->addSql('CREATE TABLE badge (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            criteria_type VARCHAR(50) NOT NULL,
            criteria_value INT NOT NULL,
            rarity VARCHAR(20) DEFAULT "common",
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 12. UserBadge table (earned achievements)
        $this->addSql('CREATE TABLE user_badge (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            badge_id INT NOT NULL,
            earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX IDX_USER_BADGE_USER (user_id),
            INDEX IDX_USER_BADGE_BADGE (badge_id),
            UNIQUE INDEX UNIQ_USER_BADGE (user_id, badge_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 13. GamificationStats table (XP tracking)
        $this->addSql('CREATE TABLE gamification_stats (
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

        // 14. ChatMessage table (Module 5 - Community)
        $this->addSql('CREATE TABLE chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            virtual_room_id INT NOT NULL,
            sender_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_edited TINYINT(1) NOT NULL DEFAULT 0,
            INDEX IDX_CHAT_ROOM (virtual_room_id),
            INDEX IDX_CHAT_SENDER (sender_id),
            INDEX IDX_CHAT_TIME (sent_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_CHAT_ROOM FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE,
            CONSTRAINT FK_CHAT_SENDER FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 15. Claim table (support tickets)
        $this->addSql('CREATE TABLE claim (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT "open",
            priority VARCHAR(20) DEFAULT "medium",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME DEFAULT NULL,
            INDEX IDX_CLAIM_USER (user_id),
            INDEX IDX_CLAIM_STATUS (status),
            PRIMARY KEY(id),
            CONSTRAINT FK_CLAIM_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 16. SharedTask table (challenges between friends)
        $this->addSql('CREATE TABLE shared_task (
            id INT AUTO_INCREMENT NOT NULL,
            task_id INT NOT NULL,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            message LONGTEXT DEFAULT NULL,
            status VARCHAR(20) DEFAULT "pending",
            shared_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            INDEX IDX_SHARED_TASK (task_id),
            INDEX IDX_SHARED_SENDER (sender_id),
            INDEX IDX_SHARED_RECIPIENT (recipient_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_SHARED_TASK FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE,
            CONSTRAINT FK_SHARED_SENDER FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_SHARED_RECIPIENT FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Drop in reverse order to respect foreign keys
        $this->addSql('DROP TABLE shared_task');
        $this->addSql('DROP TABLE claim');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE gamification_stats');
        $this->addSql('DROP TABLE user_badge');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE focus_session');
        $this->addSql('DROP TABLE virtual_room_participants');
        $this->addSql('DROP TABLE virtual_room');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE exam');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE role_request');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE user');
    }
}