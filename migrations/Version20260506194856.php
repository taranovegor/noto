<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506194856 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE attachments (origin_filename VARCHAR(255) NOT NULL, mime_type VARCHAR(255) NOT NULL, size INT NOT NULL, path VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE attachments ADD CONSTRAINT FK_47C4FAD6BF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE attachments DROP CONSTRAINT FK_47C4FAD6BF396750');
        $this->addSql('DROP TABLE attachments');
    }
}
