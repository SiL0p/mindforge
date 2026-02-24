<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224104845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE emotion_log (id INT AUTO_INCREMENT NOT NULL, emotion VARCHAR(50) NOT NULL, confidence DOUBLE PRECISION NOT NULL, detected_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_E74306FFA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE emotion_log ADD CONSTRAINT FK_E74306FFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A59A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exams ADD ai_revision_plan JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY `FK_3FA62A268DB60186`');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY `FK_3FA62A26A76ED395`');
        $this->addSql('ALTER TABLE guardian_ai_insight CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_3fa62a26a76ed395 ON guardian_ai_insight');
        $this->addSql('CREATE INDEX IDX_489F9738A76ED395 ON guardian_ai_insight (user_id)');
        $this->addSql('DROP INDEX idx_3fa62a268db60186 ON guardian_ai_insight');
        $this->addSql('CREATE INDEX IDX_489F97388DB60186 ON guardian_ai_insight (task_id)');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT `FK_3FA62A268DB60186` FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT `FK_3FA62A26A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource ADD ai_generated TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE emotion_log DROP FOREIGN KEY FK_E74306FFA76ED395');
        $this->addSql('DROP TABLE emotion_log');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5A76ED395');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A59A34590F');
        $this->addSql('ALTER TABLE exams DROP ai_revision_plan');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F9738A76ED395');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F97388DB60186');
        $this->addSql('ALTER TABLE guardian_ai_insight CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_489f97388db60186 ON guardian_ai_insight');
        $this->addSql('CREATE INDEX IDX_3FA62A268DB60186 ON guardian_ai_insight (task_id)');
        $this->addSql('DROP INDEX idx_489f9738a76ed395 ON guardian_ai_insight');
        $this->addSql('CREATE INDEX IDX_3FA62A26A76ED395 ON guardian_ai_insight (user_id)');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F9738A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F97388DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource DROP ai_generated');
    }
}
