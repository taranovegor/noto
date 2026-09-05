<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905135805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('ALTER TABLE users DROP password');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE refresh_tokens (username VARCHAR(320) NOT NULL, refresh_token VARCHAR(128) NOT NULL, valid TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_9bace7e1c74f2195 ON refresh_tokens (refresh_token)');
        $this->addSql('ALTER TABLE users ADD password VARCHAR(100) NOT NULL');
    }
}
