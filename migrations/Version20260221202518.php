<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260221202518 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE focus_session (id INT AUTO_INCREMENT NOT NULL, duration INT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, session_type VARCHAR(50) DEFAULT \'pomodoro\' NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, INDEX IDX_2AF21A4DA76ED395 (user_id), INDEX IDX_2AF21A4D8DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_alert_notification (id INT AUTO_INCREMENT NOT NULL, sent_at DATETIME NOT NULL, is_read TINYINT NOT NULL, subscription_id INT NOT NULL, opportunite_id INT NOT NULL, INDEX IDX_B0D6DBE39A1887DC (subscription_id), INDEX IDX_B0D6DBE380FBB128 (opportunite_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_alert_subscription (id INT AUTO_INCREMENT NOT NULL, keywords VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_AC44C9FAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_alert_notification ADD CONSTRAINT FK_B0D6DBE39A1887DC FOREIGN KEY (subscription_id) REFERENCES job_alert_subscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_alert_notification ADD CONSTRAINT FK_B0D6DBE380FBB128 FOREIGN KEY (opportunite_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_alert_subscription ADD CONSTRAINT FK_AC44C9FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise CHANGE contact_phone contact_phone INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4DA76ED395');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4D8DB60186');
        $this->addSql('ALTER TABLE job_alert_notification DROP FOREIGN KEY FK_B0D6DBE39A1887DC');
        $this->addSql('ALTER TABLE job_alert_notification DROP FOREIGN KEY FK_B0D6DBE380FBB128');
        $this->addSql('ALTER TABLE job_alert_subscription DROP FOREIGN KEY FK_AC44C9FAA76ED395');
        $this->addSql('DROP TABLE focus_session');
        $this->addSql('DROP TABLE job_alert_notification');
        $this->addSql('DROP TABLE job_alert_subscription');
        $this->addSql('ALTER TABLE entreprise CHANGE contact_phone contact_phone VARCHAR(50) DEFAULT NULL');
    }
}
