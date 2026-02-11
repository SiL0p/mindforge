<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260211120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline migration for existing schema';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty: schema already exists from SQL import.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty.
    }
}
