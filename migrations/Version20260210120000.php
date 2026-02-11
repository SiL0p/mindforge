<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Community Module Migration - Chat Messages, Shared Tasks, and Claims
 */
final class Version20260210120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Community module tables: chat_message, shared_task, claim';
    }

    public function up(Schema $schema): void
    {
        // ChatMessage table
        $this->addSql('CREATE TABLE chat_message (
            id INT AUTO_INCREMENT NOT NULL,
            sender_id INT NOT NULL,
            virtual_room_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            is_edited TINYINT(1) DEFAULT 0 NOT NULL,
            created_at DATETIME NOT NULL,
            edited_at DATETIME,
            PRIMARY KEY(id),
            INDEX IDX_FAB3FC16F624B195 (sender_id),
            INDEX IDX_FAB3FC161F8BBE55 (virtual_room_id),
            FOREIGN KEY (sender_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (virtual_room_id) REFERENCES virtual_room(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // SharedTask table
        $this->addSql('CREATE TABLE shared_task (
            id INT AUTO_INCREMENT NOT NULL,
            shared_by_id INT NOT NULL,
            shared_with_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT,
            status VARCHAR(50) DEFAULT "pending" NOT NULL,
            created_at DATETIME NOT NULL,
            responded_at DATETIME,
            PRIMARY KEY(id),
            INDEX IDX_1D2A2A3F9F6A7CA (shared_by_id),
            INDEX IDX_1D2A2A3FB7A96DB (shared_with_id),
            FOREIGN KEY (shared_by_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (shared_with_id) REFERENCES user(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Claim table
        $this->addSql('CREATE TABLE claim (
            id INT AUTO_INCREMENT NOT NULL,
            created_by_id INT NOT NULL,
            assigned_to_id INT,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            status VARCHAR(50) DEFAULT "open" NOT NULL,
            priority VARCHAR(50) DEFAULT "medium" NOT NULL,
            admin_notes LONGTEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            resolved_at DATETIME,
            PRIMARY KEY(id),
            INDEX IDX_A769D17B8DE820D9 (created_by_id),
            INDEX IDX_A769D17BE55138F8 (assigned_to_id),
            FOREIGN KEY (created_by_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to_id) REFERENCES user(id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS claim');
        $this->addSql('DROP TABLE IF EXISTS shared_task');
        $this->addSql('DROP TABLE IF EXISTS chat_message');
    }
}
