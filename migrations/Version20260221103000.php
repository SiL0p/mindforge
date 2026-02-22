<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Guardian AI insights history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE guardian_ai_insight (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, source VARCHAR(20) NOT NULL DEFAULT \'rule\', payload LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3FA62A26A76ED395 (user_id), INDEX IDX_3FA62A268DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_3FA62A26A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_3FA62A268DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_3FA62A26A76ED395');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_3FA62A268DB60186');
        $this->addSql('DROP TABLE guardian_ai_insight');
    }
}
