<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate Career Module (Application & Mentorship) from email-based to User entity relationships
 */
final class Version20260212040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Application and Mentorship tables to use User FK instead of email strings';
    }

    public function up(Schema $schema): void
    {
        // 1. Update Application table: replace user_email with user_id FK
        $this->addSql('ALTER TABLE application ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE application ADD CONSTRAINT FK_A45BDDC1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_A45BDDC1A76ED395 ON application (user_id)');

        // Note: user_email column will be dropped after data migration (manually populate users first)

        // 2. Update Mentorship table: replace student_email/mentor_email with student_id/mentor_id FKs
        $this->addSql('ALTER TABLE mentorship ADD student_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mentorship ADD mentor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4CB944F1A FOREIGN KEY (student_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mentorship ADD CONSTRAINT FK_ADE55FF4DB403044 FOREIGN KEY (mentor_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_ADE55FF4CB944F1A ON mentorship (student_id)');
        $this->addSql('CREATE INDEX IDX_ADE55FF4DB403044 ON mentorship (mentor_id)');

        // Note: student_email and mentor_email columns will be dropped after data migration (manually populate users first)
    }

    public function down(Schema $schema): void
    {
        // Reverse changes
        $this->addSql('ALTER TABLE application DROP FOREIGN KEY FK_A45BDDC1A76ED395');
        $this->addSql('DROP INDEX IDX_A45BDDC1A76ED395 ON application');
        $this->addSql('ALTER TABLE application DROP user_id');

        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY FK_ADE55FF4CB944F1A');
        $this->addSql('ALTER TABLE mentorship DROP FOREIGN KEY FK_ADE55FF4DB403044');
        $this->addSql('DROP INDEX IDX_ADE55FF4CB944F1A ON mentorship');
        $this->addSql('DROP INDEX IDX_ADE55FF4DB403044 ON mentorship');
        $this->addSql('ALTER TABLE mentorship DROP student_id, DROP mentor_id');
    }
}
