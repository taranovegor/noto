<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507075701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachments ALTER size TYPE BIGINT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachments ALTER size TYPE INT');
    }
}
