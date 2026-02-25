<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225142941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(255) DEFAULT NULL, criteria_type VARCHAR(50) NOT NULL, criteria_value INT NOT NULL, rarity VARCHAR(20) NOT NULL, UNIQUE INDEX UNIQ_FEF0481D5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_edited TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, sender_id INT NOT NULL, virtual_room_id INT NOT NULL, INDEX IDX_FAB3FC16F624B39D (sender_id), INDEX IDX_FAB3FC166260A35C (virtual_room_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE claim (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(50) DEFAULT \'open\' NOT NULL, priority VARCHAR(50) DEFAULT \'medium\' NOT NULL, admin_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, created_by_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, INDEX IDX_A769DE27B03A8386 (created_by_id), INDEX IDX_A769DE27F4BD7827 (assigned_to_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, cover_letter LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, applied_at DATETIME NOT NULL, user_id INT NOT NULL, opportunity_id INT NOT NULL, INDEX IDX_2694D7A5A76ED395 (user_id), INDEX IDX_2694D7A59A34590F (opportunity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE emotion_log (id INT AUTO_INCREMENT NOT NULL, emotion VARCHAR(50) NOT NULL, confidence DOUBLE PRECISION NOT NULL, detected_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_E74306FFA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, industry VARCHAR(100) DEFAULT NULL, contact_email VARCHAR(180) DEFAULT NULL, contact_phone INT DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE entreprise_user (entreprise_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_606C16EA4AEAFEA (entreprise_id), INDEX IDX_606C16EA76ED395 (user_id), PRIMARY KEY (entreprise_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exams (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, exam_date DATETIME NOT NULL, duration_minutes INT DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, importance INT NOT NULL, created_at DATETIME NOT NULL, ai_revision_plan JSON DEFAULT NULL, owner_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_693113287E3C61F9 (owner_id), INDEX IDX_6931132823EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE focus_session (id INT AUTO_INCREMENT NOT NULL, duration INT NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, session_type VARCHAR(50) DEFAULT \'pomodoro\' NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, INDEX IDX_2AF21A4DA76ED395 (user_id), INDEX IDX_2AF21A4D8DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE gamification_stats (id INT AUTO_INCREMENT NOT NULL, total_xp INT DEFAULT 0 NOT NULL, current_level INT DEFAULT 1 NOT NULL, streak_days INT DEFAULT 0 NOT NULL, last_activity_date DATE DEFAULT NULL, total_focus_time INT DEFAULT 0 NOT NULL, tasks_completed INT DEFAULT 0 NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_13885268A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE guardian_ai_insight (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, source VARCHAR(20) DEFAULT \'rule\' NOT NULL, payload LONGTEXT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, task_id INT DEFAULT NULL, INDEX IDX_489F9738A76ED395 (user_id), INDEX IDX_489F97388DB60186 (task_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_alert_notification (id INT AUTO_INCREMENT NOT NULL, sent_at DATETIME NOT NULL, is_read TINYINT NOT NULL, subscription_id INT NOT NULL, opportunite_id INT NOT NULL, INDEX IDX_B0D6DBE39A1887DC (subscription_id), INDEX IDX_B0D6DBE380FBB128 (opportunite_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE job_alert_subscription (id INT AUTO_INCREMENT NOT NULL, keywords VARCHAR(255) DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_AC44C9FAA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE opportunite_carriere (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, location VARCHAR(255) DEFAULT NULL, duration VARCHAR(100) DEFAULT NULL, deadline DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_E92B9E91979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, timezone VARCHAR(50) NOT NULL, locale VARCHAR(10) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_8157AA0FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, ai_generated TINYINT DEFAULT 0 NOT NULL, download_count INT DEFAULT 0 NOT NULL, rating SMALLINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subject_id INT NOT NULL, uploader_id INT NOT NULL, INDEX IDX_BC91F41623EDC87 (subject_id), INDEX IDX_BC91F41616678C77 (uploader_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_request (id INT AUTO_INCREMENT NOT NULL, motivation LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, admin_notes LONGTEXT DEFAULT NULL, requested_at DATETIME NOT NULL, reviewed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_875A2A64A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shared_task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(50) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, shared_by_id INT NOT NULL, shared_with_id INT NOT NULL, INDEX IDX_E888B0BD5489CD19 (shared_by_id), INDEX IDX_E888B0BDD14FE63F (shared_with_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subject (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority INT NOT NULL, due_date DATETIME DEFAULT NULL, estimated_minutes INT DEFAULT NULL, actual_minutes INT DEFAULT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_527EDB257E3C61F9 (owner_id), INDEX IDX_527EDB2523EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expire_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_badge (id INT AUTO_INCREMENT NOT NULL, earned_at DATETIME NOT NULL, user_id INT NOT NULL, badge_id INT NOT NULL, INDEX IDX_1C32B345A76ED395 (user_id), INDEX IDX_1C32B345F7A2C2FC (badge_id), UNIQUE INDEX uniq_user_badge (user_id, badge_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE virtual_room (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, max_participants INT DEFAULT 10 NOT NULL, created_at DATETIME NOT NULL, creator_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_9B174CA361220EA6 (creator_id), INDEX IDX_9B174CA323EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE virtual_room_participants (virtual_room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E0348306260A35C (virtual_room_id), INDEX IDX_E034830A76ED395 (user_id), PRIMARY KEY (virtual_room_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC166260A35C FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE27B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE27F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A59A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE emotion_log ADD CONSTRAINT FK_E74306FFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entreprise_user ADD CONSTRAINT FK_606C16EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exams ADD CONSTRAINT FK_693113287E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE exams ADD CONSTRAINT FK_6931132823EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE focus_session ADD CONSTRAINT FK_2AF21A4D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE gamification_stats ADD CONSTRAINT FK_13885268A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F9738A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE guardian_ai_insight ADD CONSTRAINT FK_489F97388DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_alert_notification ADD CONSTRAINT FK_B0D6DBE39A1887DC FOREIGN KEY (subscription_id) REFERENCES job_alert_subscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_alert_notification ADD CONSTRAINT FK_B0D6DBE380FBB128 FOREIGN KEY (opportunite_id) REFERENCES opportunite_carriere (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_alert_subscription ADD CONSTRAINT FK_AC44C9FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE opportunite_carriere ADD CONSTRAINT FK_E92B9E91979B1AD6 FOREIGN KEY (company_id) REFERENCES entreprise (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41623EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41616678C77 FOREIGN KEY (uploader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE role_request ADD CONSTRAINT FK_875A2A64A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_task ADD CONSTRAINT FK_E888B0BD5489CD19 FOREIGN KEY (shared_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_task ADD CONSTRAINT FK_E888B0BDD14FE63F FOREIGN KEY (shared_with_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2523EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_badge ADD CONSTRAINT FK_1C32B345F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE virtual_room ADD CONSTRAINT FK_9B174CA361220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE virtual_room ADD CONSTRAINT FK_9B174CA323EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE virtual_room_participants ADD CONSTRAINT FK_E0348306260A35C FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE virtual_room_participants ADD CONSTRAINT FK_E034830A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC166260A35C');
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE27B03A8386');
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE27F4BD7827');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A5A76ED395');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A59A34590F');
        $this->addSql('ALTER TABLE emotion_log DROP FOREIGN KEY FK_E74306FFA76ED395');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA4AEAFEA');
        $this->addSql('ALTER TABLE entreprise_user DROP FOREIGN KEY FK_606C16EA76ED395');
        $this->addSql('ALTER TABLE exams DROP FOREIGN KEY FK_693113287E3C61F9');
        $this->addSql('ALTER TABLE exams DROP FOREIGN KEY FK_6931132823EDC87');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4DA76ED395');
        $this->addSql('ALTER TABLE focus_session DROP FOREIGN KEY FK_2AF21A4D8DB60186');
        $this->addSql('ALTER TABLE gamification_stats DROP FOREIGN KEY FK_13885268A76ED395');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F9738A76ED395');
        $this->addSql('ALTER TABLE guardian_ai_insight DROP FOREIGN KEY FK_489F97388DB60186');
        $this->addSql('ALTER TABLE job_alert_notification DROP FOREIGN KEY FK_B0D6DBE39A1887DC');
        $this->addSql('ALTER TABLE job_alert_notification DROP FOREIGN KEY FK_B0D6DBE380FBB128');
        $this->addSql('ALTER TABLE job_alert_subscription DROP FOREIGN KEY FK_AC44C9FAA76ED395');
        $this->addSql('ALTER TABLE opportunite_carriere DROP FOREIGN KEY FK_E92B9E91979B1AD6');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0FA76ED395');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41623EDC87');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41616678C77');
        $this->addSql('ALTER TABLE role_request DROP FOREIGN KEY FK_875A2A64A76ED395');
        $this->addSql('ALTER TABLE shared_task DROP FOREIGN KEY FK_E888B0BD5489CD19');
        $this->addSql('ALTER TABLE shared_task DROP FOREIGN KEY FK_E888B0BDD14FE63F');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2523EDC87');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345A76ED395');
        $this->addSql('ALTER TABLE user_badge DROP FOREIGN KEY FK_1C32B345F7A2C2FC');
        $this->addSql('ALTER TABLE virtual_room DROP FOREIGN KEY FK_9B174CA361220EA6');
        $this->addSql('ALTER TABLE virtual_room DROP FOREIGN KEY FK_9B174CA323EDC87');
        $this->addSql('ALTER TABLE virtual_room_participants DROP FOREIGN KEY FK_E0348306260A35C');
        $this->addSql('ALTER TABLE virtual_room_participants DROP FOREIGN KEY FK_E034830A76ED395');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE claim');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE emotion_log');
        $this->addSql('DROP TABLE entreprise');
        $this->addSql('DROP TABLE entreprise_user');
        $this->addSql('DROP TABLE exams');
        $this->addSql('DROP TABLE focus_session');
        $this->addSql('DROP TABLE gamification_stats');
        $this->addSql('DROP TABLE guardian_ai_insight');
        $this->addSql('DROP TABLE job_alert_notification');
        $this->addSql('DROP TABLE job_alert_subscription');
        $this->addSql('DROP TABLE opportunite_carriere');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE role_request');
        $this->addSql('DROP TABLE shared_task');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_badge');
        $this->addSql('DROP TABLE virtual_room');
        $this->addSql('DROP TABLE virtual_room_participants');
    }
}
