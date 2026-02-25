<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260213103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create focus_session table required by admin analytics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS focus_session (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            task_id INT DEFAULT NULL,
            duration INT NOT NULL,
            started_at DATETIME NOT NULL,
            ended_at DATETIME DEFAULT NULL,
            session_type VARCHAR(50) DEFAULT \'pomodoro\',
            INDEX IDX_FOCUS_USER (user_id),
            INDEX IDX_FOCUS_TASK (task_id),
            INDEX IDX_FOCUS_STARTED (started_at),
            PRIMARY KEY(id),
            CONSTRAINT FK_FOCUS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_FOCUS_TASK FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS focus_session');
    }
}
