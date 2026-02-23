<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221142518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `FK_2694D7A59A34590F`');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY `FK_2694D7A5A76ED395`');
        $this->addSql('DROP INDEX idx_a45bddc1a76ed395 ON demande');
        $this->addSql('CREATE INDEX IDX_2694D7A5A76ED395 ON demande (user_id)');
        $this->addSql('DROP INDEX idx_a45bddc19a34590f ON demande');
        $this->addSql('CREATE INDEX IDX_2694D7A59A34590F ON demande (opportunity_id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `FK_2694D7A59A34590F` FOREIGN KEY (opportunity_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT `FK_2694D7A5A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise CHANGE contact_phone contact_phone INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY `FK_606C16EA4AEAFEA`');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY `FK_606C16EA76ED395`');
        $this->addSql('DROP INDEX idx_cefecca7979b1ad6 ON entreprise_user');
        $this->addSql('CREATE INDEX IDX_606C16EA4AEAFEA ON entreprise_user (entreprise_id)');
        $this->addSql('DROP INDEX idx_cefecca7a76ed395 ON entreprise_user');
        $this->addSql('CREATE INDEX IDX_606C16EA76ED395 ON entreprise_user (user_id)');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT `FK_606C16EA4AEAFEA` FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT `FK_606C16EA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX IDX_FOCUS_STARTED ON focus_session');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY `FK_FOCUS_TASK`');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY `FK_FOCUS_USER`');
        $this->addSql('ALTER TABLE focus_session CHANGE session_type session_type VARCHAR(50) DEFAULT \'pomodoro\' NOT NULL');
        $this->addSql('DROP INDEX idx_focus_user ON focus_session');
        $this->addSql('CREATE INDEX IDX_2AF21A4DA76ED395 ON focus_session (user_id)');
        $this->addSql('DROP INDEX idx_focus_task ON focus_session');
        $this->addSql('CREATE INDEX IDX_2AF21A4D8DB60186 ON focus_session (task_id)');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT `FK_FOCUS_TASK` FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT `FK_FOCUS_USER` FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE opportunite_carriere DROP FOREIGN KEY `FK_E92B9E91979B1AD6`');
        $this->addSql('DROP INDEX idx_c35db4ed979b1ad6 ON opportunite_carriere');
        $this->addSql('CREATE INDEX IDX_E92B9E91979B1AD6 ON opportunite_carriere (company_id)');
        $this->addSql('ALTER TABLE opportunite_carriere ADD CONSTRAINT `FK_E92B9E91979B1AD6` FOREIGN KEY (company_id) REFERENCES entreprise (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5A76ED395');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A59A34590F');
        $this->addSql('DROP INDEX idx_2694d7a5a76ed395 ON demande');
        $this->addSql('CREATE INDEX IDX_A45BDDC1A76ED395 ON demande (user_id)');
        $this->addSql('DROP INDEX idx_2694d7a59a34590f ON demande');
        $this->addSql('CREATE INDEX IDX_A45BDDC19A34590F ON demande (opportunity_id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A59A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise CHANGE contact_phone contact_phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA4AEAFEA');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA76ED395');
        $this->addSql('DROP INDEX idx_606c16ea4aeafea ON entreprise_user');
        $this->addSql('CREATE INDEX IDX_CEFECCA7979B1AD6 ON entreprise_user (entreprise_id)');
        $this->addSql('DROP INDEX idx_606c16ea76ed395 ON entreprise_user');
        $this->addSql('CREATE INDEX IDX_CEFECCA7A76ED395 ON entreprise_user (user_id)');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4DA76ED395');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4D8DB60186');
        $this->addSql('ALTER TABLE focus_session CHANGE session_type session_type VARCHAR(50) DEFAULT \'pomodoro\'');
        $this->addSql('CREATE INDEX IDX_FOCUS_STARTED ON focus_session (started_at)');
        $this->addSql('DROP INDEX idx_2af21a4da76ed395 ON focus_session');
        $this->addSql('CREATE INDEX IDX_FOCUS_USER ON focus_session (user_id)');
        $this->addSql('DROP INDEX idx_2af21a4d8db60186 ON focus_session');
        $this->addSql('CREATE INDEX IDX_FOCUS_TASK ON focus_session (task_id)');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE opportunite_carriere DROP FOREIGN KEY FK_E92B9E91979B1AD6');
        $this->addSql('DROP INDEX idx_e92b9e91979b1ad6 ON opportunite_carriere');
        $this->addSql('CREATE INDEX IDX_C35DB4ED979B1AD6 ON opportunite_carriere (company_id)');
        $this->addSql('ALTER TABLE opportunite_carriere ADD CONSTRAINT FK_E92B9E91979B1AD6 FOREIGN KEY (company_id) REFERENCES entreprise (id) ON DELETE SET NULL');
    }
}
