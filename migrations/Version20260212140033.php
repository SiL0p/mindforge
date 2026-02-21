<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212140033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE application (id INT AUTO_INCREMENT NOT NULL, cover_letter LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, applied_at DATETIME NOT NULL, user_id INT NOT NULL, opportunity_id INT NOT NULL, INDEX IDX_A45BDDC1A76ED395 (user_id), INDEX IDX_A45BDDC19A34590F (opportunity_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE career_opportunity (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, location VARCHAR(255) DEFAULT NULL, duration VARCHAR(100) DEFAULT NULL, deadline DATE DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_C35DB4ED979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, is_edited TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, edited_at DATETIME DEFAULT NULL, sender_id INT NOT NULL, virtual_room_id INT NOT NULL, INDEX IDX_FAB3FC16F624B39D (sender_id), INDEX IDX_FAB3FC166260A35C (virtual_room_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE claim (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, status VARCHAR(50) DEFAULT \'open\' NOT NULL, priority VARCHAR(50) DEFAULT \'medium\' NOT NULL, admin_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, resolved_at DATETIME DEFAULT NULL, created_by_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, INDEX IDX_A769DE27B03A8386 (created_by_id), INDEX IDX_A769DE27F4BD7827 (assigned_to_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, industry VARCHAR(100) DEFAULT NULL, contact_email VARCHAR(180) DEFAULT NULL, contact_phone VARCHAR(50) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE company_user (company_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_CEFECCA7979B1AD6 (company_id), INDEX IDX_CEFECCA7A76ED395 (user_id), PRIMARY KEY (company_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exams (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, exam_date DATETIME NOT NULL, duration_minutes INT DEFAULT NULL, location VARCHAR(100) DEFAULT NULL, importance INT NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_693113287E3C61F9 (owner_id), INDEX IDX_6931132823EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mentorship (id INT AUTO_INCREMENT NOT NULL, notes LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, student_id INT NOT NULL, mentor_id INT NOT NULL, company_id INT DEFAULT NULL, INDEX IDX_ADE55FF4CB944F1A (student_id), INDEX IDX_ADE55FF4DB403044 (mentor_id), INDEX IDX_ADE55FF4979B1AD6 (company_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE profile (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) DEFAULT NULL, last_name VARCHAR(100) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, timezone VARCHAR(50) NOT NULL, locale VARCHAR(10) NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_8157AA0FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, file_path VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, download_count INT DEFAULT 0 NOT NULL, rating SMALLINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subject_id INT NOT NULL, uploader_id INT NOT NULL, INDEX IDX_BC91F41623EDC87 (subject_id), INDEX IDX_BC91F41616678C77 (uploader_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE role_request (id INT AUTO_INCREMENT NOT NULL, motivation LONGTEXT NOT NULL, status VARCHAR(20) NOT NULL, admin_notes LONGTEXT DEFAULT NULL, requested_at DATETIME NOT NULL, reviewed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_875A2A64A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shared_task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(50) DEFAULT \'pending\' NOT NULL, created_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, shared_by_id INT NOT NULL, shared_with_id INT NOT NULL, INDEX IDX_E888B0BD5489CD19 (shared_by_id), INDEX IDX_E888B0BDD14FE63F (shared_with_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subject (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority INT NOT NULL, due_date DATETIME DEFAULT NULL, estimated_minutes INT DEFAULT NULL, actual_minutes INT DEFAULT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, owner_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_527EDB257E3C61F9 (owner_id), INDEX IDX_527EDB2523EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE virtual_room (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, max_participants INT DEFAULT 10 NOT NULL, created_at DATETIME NOT NULL, creator_id INT NOT NULL, subject_id INT DEFAULT NULL, INDEX IDX_9B174CA361220EA6 (creator_id), INDEX IDX_9B174CA323EDC87 (subject_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE virtual_room_participants (virtual_room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E0348306260A35C (virtual_room_id), INDEX IDX_E034830A76ED395 (user_id), PRIMARY KEY (virtual_room_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC19A34590F FOREIGN KEY (opportunity_id) REFERENCES career_opportunity (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE career_opportunity ADD CONSTRAINT FK_C35DB4ED979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC166260A35C FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE27B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE claim ADD CONSTRAINT FK_A769DE27F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE company_user ADD CONSTRAINT FK_CEFECCA7979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE company_user ADD CONSTRAINT FK_CEFECCA7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exams ADD CONSTRAINT FK_693113287E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE exams ADD CONSTRAINT FK_6931132823EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4DB403044 FOREIGN KEY (mentor_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41623EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41616678C77 FOREIGN KEY (uploader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE role_request ADD CONSTRAINT FK_875A2A64A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_task ADD CONSTRAINT FK_E888B0BD5489CD19 FOREIGN KEY (shared_by_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shared_task ADD CONSTRAINT FK_E888B0BDD14FE63F FOREIGN KEY (shared_with_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB257E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2523EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE virtual_room ADD CONSTRAINT FK_9B174CA361220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE virtual_room ADD CONSTRAINT FK_9B174CA323EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE virtual_room_participants ADD CONSTRAINT FK_E0348306260A35C FOREIGN KEY (virtual_room_id) REFERENCES virtual_room (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE virtual_room_participants ADD CONSTRAINT FK_E034830A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC19A34590F');
        $this->addSql('ALTER TABLE career_opportunity DROP FOREIGN KEY FK_C35DB4ED979B1AD6');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC166260A35C');
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE27B03A8386');
        $this->addSql('ALTER TABLE claim DROP FOREIGN KEY FK_A769DE27F4BD7827');
        $this->addSql('ALTER TABLE company_user DROP FOREIGN KEY FK_CEFECCA7979B1AD6');
        $this->addSql('ALTER TABLE company_user DROP FOREIGN KEY FK_CEFECCA7A76ED395');
        $this->addSql('ALTER TABLE exams DROP FOREIGN KEY FK_693113287E3C61F9');
        $this->addSql('ALTER TABLE exams DROP FOREIGN KEY FK_6931132823EDC87');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY FK_ADE55FF4CB944F1A');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY FK_ADE55FF4DB403044');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY FK_ADE55FF4979B1AD6');
        $this->addSql('ALTER TABLE profile DROP FOREIGN KEY FK_8157AA0FA76ED395');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41623EDC87');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41616678C77');
        $this->addSql('ALTER TABLE role_request DROP FOREIGN KEY FK_875A2A64A76ED395');
        $this->addSql('ALTER TABLE shared_task DROP FOREIGN KEY FK_E888B0BD5489CD19');
        $this->addSql('ALTER TABLE shared_task DROP FOREIGN KEY FK_E888B0BDD14FE63F');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB257E3C61F9');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2523EDC87');
        $this->addSql('ALTER TABLE virtual_room DROP FOREIGN KEY FK_9B174CA361220EA6');
        $this->addSql('ALTER TABLE virtual_room DROP FOREIGN KEY FK_9B174CA323EDC87');
        $this->addSql('ALTER TABLE virtual_room_participants DROP FOREIGN KEY FK_E0348306260A35C');
        $this->addSql('ALTER TABLE virtual_room_participants DROP FOREIGN KEY FK_E034830A76ED395');
        $this->addSql('DROP TABLE application');
        $this->addSql('DROP TABLE career_opportunity');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE claim');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE company_user');
        $this->addSql('DROP TABLE exams');
        $this->addSql('DROP TABLE mentorship');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE role_request');
        $this->addSql('DROP TABLE shared_task');
        $this->addSql('DROP TABLE subject');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE virtual_room');
        $this->addSql('DROP TABLE virtual_room_participants');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
