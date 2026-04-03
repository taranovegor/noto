<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403124400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE links (id UUID NOT NULL, relation_type VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, source_id UUID NOT NULL, target_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D182A118953C1C61 ON links (source_id)');
        $this->addSql('CREATE INDEX IDX_D182A118158E0B66 ON links (target_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D182A118953C1C61158E0B663BF454A4 ON links (source_id, target_id, relation_type)');
        $this->addSql('CREATE TABLE projects (id UUID NOT NULL, name VARCHAR(255) NOT NULL, prefix VARCHAR(3) NOT NULL, task_counter INT DEFAULT 0 NOT NULL, aliases JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ref_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5C93B3A421B741A9 ON projects (ref_id)');
        $this->addSql('CREATE TABLE refs (id UUID NOT NULL, type VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE tasks (id UUID NOT NULL, code VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) DEFAULT NULL, deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, note TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ref_id UUID NOT NULL, project_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5058659777153098 ON tasks (code)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5058659721B741A9 ON tasks (ref_id)');
        $this->addSql('CREATE INDEX IDX_50586597166D1F9C ON tasks (project_id)');
        $this->addSql('ALTER TABLE links ADD CONSTRAINT FK_D182A118953C1C61 FOREIGN KEY (source_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE links ADD CONSTRAINT FK_D182A118158E0B66 FOREIGN KEY (target_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A421B741A9 FOREIGN KEY (ref_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_5058659721B741A9 FOREIGN KEY (ref_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE links DROP CONSTRAINT FK_D182A118953C1C61');
        $this->addSql('ALTER TABLE links DROP CONSTRAINT FK_D182A118158E0B66');
        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A421B741A9');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_5058659721B741A9');
        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597166D1F9C');
        $this->addSql('DROP TABLE links');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE refs');
        $this->addSql('DROP TABLE tasks');
    }
}
