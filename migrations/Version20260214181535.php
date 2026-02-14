<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214181535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename career tables to French names, drop mentorship and focus_session tables';
    }

    public function up(Schema $schema): void
    {
        // 1. Drop foreign keys that reference tables being renamed
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY `FK_A45BDDC19A34590F`');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY `FK_A45BDDC1A76ED395`');
        $this->addSql('ALTER TABLE career_opportunity DROP FOREIGN KEY `FK_C35DB4ED979B1AD6`');
        $this->addSql('ALTER TABLE company_user DROP FOREIGN KEY `FK_CEFECCA7979B1AD6`');
        $this->addSql('ALTER TABLE company_user DROP FOREIGN KEY `FK_CEFECCA7A76ED395`');

        // 2. Drop mentorship table (feature removed)
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY `FK_ADE55FF4979B1AD6`');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY `FK_ADE55FF4CB944F1A`');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY `FK_ADE55FF4DB403044`');
        $this->addSql('DROP TABLE mentorship');

        // 3. Drop focus_session table (no entity)
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY `FK_FOCUS_TASK`');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY `FK_FOCUS_USER`');
        $this->addSql('DROP TABLE focus_session');

        // 4. Rename tables (preserves data)
        $this->addSql('RENAME TABLE company TO entreprise');
        $this->addSql('RENAME TABLE company_user TO entreprise_user');
        $this->addSql('RENAME TABLE career_opportunity TO opportunite_carriere');
        $this->addSql('RENAME TABLE application TO demande');

        // 5. Rename the FK column in entreprise_user (company_id -> entreprise_id)
        $this->addSql('ALTER TABLE entreprise_user CHANGE company_id entreprise_id INT NOT NULL');

        // 6. Re-add foreign keys with updated references
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A59A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE opportunite_carriere ADD CONSTRAINT FK_E92B9E91979B1AD6 FOREIGN KEY (company_id) REFERENCES entreprise (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // 7. Fix index naming on badge, gamification_stats, user_badge
        $this->addSql('DROP INDEX uniq_badge_name ON badge');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FEF0481D5E237E06 ON badge (name)');
        $this->addSql('ALTER TABLE gamification_stats DROP FOREIGN KEY `FK_GAMIFICATION_USER`');
        $this->addSql('DROP INDEX uniq_gamification_user ON gamification_stats');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_13885268A76ED395 ON gamification_stats (user_id)');
        $this->addSql('ALTER TABLE gamification_stats ADD CONSTRAINT FK_GAMIFICATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY `FK_USER_BADGE_BADGE`');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY `FK_USER_BADGE_USER`');
        $this->addSql('DROP INDEX idx_user_badge_user ON user_badge');
        $this->addSql('CREATE INDEX IDX_1C32B345A76ED395 ON user_badge (user_id)');
        $this->addSql('DROP INDEX idx_user_badge_badge ON user_badge');
        $this->addSql('CREATE INDEX IDX_1C32B345F7A2C2FC ON user_badge (badge_id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop new foreign keys
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5A76ED395');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A59A34590F');
        $this->addSql('ALTER TABLE opportunite_carriere DROP FOREIGN KEY FK_E92B9E91979B1AD6');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA4AEAFEA');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA76ED395');

        // Rename column back
        $this->addSql('ALTER TABLE entreprise_user CHANGE entreprise_id company_id INT NOT NULL');

        // Rename tables back
        $this->addSql('RENAME TABLE demande TO application');
        $this->addSql('RENAME TABLE opportunite_carriere TO career_opportunity');
        $this->addSql('RENAME TABLE entreprise_user TO company_user');
        $this->addSql('RENAME TABLE entreprise TO company');

        // Re-add old foreign keys
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC19A34590F FOREIGN KEY (opportunity_id) REFERENCES career_opportunity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE career_opportunity ADD CONSTRAINT FK_C35DB4ED979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_user ADD CONSTRAINT FK_CEFECCA7979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE company_user ADD CONSTRAINT FK_CEFECCA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Recreate mentorship table
        $this->addSql('CREATE TABLE mentorship (id INT AUTO_INCREMENT NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, student_id INT NOT NULL, mentor_id INT NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_ADE55FF4979B1AD6 (company_id), INDEX IDX_ADE55FF4CB944F1A (student_id), INDEX IDX_ADE55FF4DB403044 (mentor_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4DB403044 FOREIGN KEY (mentor_id) REFERENCES user (id) ON DELETE CASCADE');

        // Recreate focus_session table
        $this->addSql('CREATE TABLE focus_session (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, duration INT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, session_type VARCHAR(50) DEFAULT \'pomodoro\', INDEX IDX_FOCUS_STARTED (started_at), INDEX IDX_FOCUS_USER (user_id), INDEX IDX_FOCUS_TASK (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_FOCUS_TASK FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_FOCUS_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');

        // Revert index naming
        $this->addSql('DROP INDEX UNIQ_FEF0481D5E237E06 ON badge');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BADGE_NAME ON badge (name)');
        $this->addSql('ALTER TABLE gamification_stats DROP FOREIGN KEY FK_GAMIFICATION_USER');
        $this->addSql('DROP INDEX UNIQ_13885268A76ED395 ON gamification_stats');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_GAMIFICATION_USER ON gamification_stats (user_id)');
        $this->addSql('ALTER TABLE gamification_stats ADD CONSTRAINT FK_GAMIFICATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_USER_BADGE_BADGE');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_USER_BADGE_USER');
        $this->addSql('DROP INDEX IDX_1C32B345A76ED395 ON user_badge');
        $this->addSql('CREATE INDEX IDX_USER_BADGE_USER ON user_badge (user_id)');
        $this->addSql('DROP INDEX IDX_1C32B345F7A2C2FC ON user_badge');
        $this->addSql('CREATE INDEX IDX_USER_BADGE_BADGE ON user_badge (badge_id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }
}
