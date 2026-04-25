<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425163922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS vector;');
        $this->addSql('CREATE TABLE embeddings (vector vector(1024) NOT NULL, metadata JSON NOT NULL, id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, parent_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_2A281AB1727ACA70 ON embeddings (parent_id)');
        $this->addVectorIndex($schema);
        $this->addSql('ALTER TABLE embeddings ADD CONSTRAINT FK_2A281AB1727ACA70 FOREIGN KEY (parent_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE embeddings DROP CONSTRAINT FK_2A281AB1727ACA70');
        $this->addSql('DROP TABLE embeddings');
        $this->addSql('DROP EXTENSION vector;');
    }

    /**
     * A custom configuration that is not represented in the Embedding entity.
     */
    private function addVectorIndex(Schema $schema): void
    {
        $this->addSql('CREATE INDEX vector_idx ON embeddings USING hnsw (vector vector_cosine_ops)');
    }
}
