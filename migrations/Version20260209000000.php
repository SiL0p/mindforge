<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Career Module Migration - Creates tables for Company, CareerOpportunity, and Application
 */
final class Version20260209000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates and updates Career module tables (company, career_opportunity, application)';
    }

    public function up(Schema $schema): void
    {
        // Check if tables exist and only create/modify what's needed

        // Company table - modify if exists, create if doesn't
        $this->addSql('ALTER TABLE company
            MODIFY industry VARCHAR(100) DEFAULT NULL,
            MODIFY contact_email VARCHAR(180) DEFAULT NULL,
            MODIFY contact_phone VARCHAR(50) DEFAULT NULL,
            MODIFY website VARCHAR(255) DEFAULT NULL,
            MODIFY created_at DATETIME NOT NULL');

        // Career Opportunity table - modify if exists
        $this->addSql('ALTER TABLE career_opportunity
            MODIFY type VARCHAR(50) NOT NULL,
            MODIFY location VARCHAR(255) DEFAULT NULL,
            MODIFY duration VARCHAR(100) DEFAULT NULL,
            MODIFY deadline DATE DEFAULT NULL,
            MODIFY status VARCHAR(20) NOT NULL,
            MODIFY created_at DATETIME NOT NULL');

        // Application table - replace user_id with user_email
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY IF EXISTS FK_APPLICATION_USER');
        $this->addSql('DROP INDEX IF EXISTS IDX_APPLICATION_USER ON application');
        $this->addSql('ALTER TABLE application
            ADD user_email VARCHAR(180) NOT NULL AFTER id,
            DROP user_id,
            MODIFY status VARCHAR(20) NOT NULL,
            MODIFY applied_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert changes
        $this->addSql('ALTER TABLE application
            ADD user_id INT NOT NULL AFTER id,
            DROP user_email,
            MODIFY status VARCHAR(20) DEFAULT \'pending\',
            MODIFY applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_APPLICATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_APPLICATION_USER ON application (user_id)');

        $this->addSql('ALTER TABLE career_opportunity
            MODIFY type VARCHAR(50) DEFAULT \'internship\',
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');

        $this->addSql('ALTER TABLE company
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
}
