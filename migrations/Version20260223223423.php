<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223223423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE guardian_ai_insight (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, source VARCHAR(20) DEFAULT \'rule\' NOT NULL, payload LONGTEXT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, INDEX IDX_489F9738A76ED395 (user_id), INDEX IDX_489F97388DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F9738A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F97388DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE exams ADD ai_revision_plan JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD ai_generated TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user DROP reset_token, DROP reset_token_expire_at');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F9738A76ED395');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F97388DB60186');
        $this->addSql('DROP TABLE guardian_ai_insight');
        $this->addSql('ALTER TABLE exams DROP ai_revision_plan');
        $this->addSql('ALTER TABLE resource DROP ai_generated');
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expire_at DATETIME DEFAULT NULL');
    }
}
