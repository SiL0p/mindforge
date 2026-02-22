<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ai_generated flag on resource to label AI vs manual resources';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource ADD ai_generated TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resource DROP ai_generated');
    }
}
