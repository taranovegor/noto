<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524184317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notebooks (title VARCHAR(255) NOT NULL, description TEXT NOT NULL, id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE notes (title VARCHAR(255) NOT NULL, content TEXT NOT NULL, id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, notebook_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_11BA68CF74303D6 ON notes (notebook_id)');
        $this->addSql('ALTER TABLE notebooks ADD CONSTRAINT FK_B7444BD1BF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT FK_11BA68CF74303D6 FOREIGN KEY (notebook_id) REFERENCES notebooks (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT FK_11BA68CBF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notebooks DROP CONSTRAINT FK_B7444BD1BF396750');
        $this->addSql('ALTER TABLE notes DROP CONSTRAINT FK_11BA68CF74303D6');
        $this->addSql('ALTER TABLE notes DROP CONSTRAINT FK_11BA68CBF396750');
        $this->addSql('DROP TABLE notebooks');
        $this->addSql('DROP TABLE notes');
    }
}
