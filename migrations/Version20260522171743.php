<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522171743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename notes table to memos, update RefType enum from note to memo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notes DROP CONSTRAINT fk_11ba68cbf396750');
        $this->addSql('ALTER TABLE notes RENAME TO memos');
        $this->addSql('ALTER TABLE memos ADD CONSTRAINT FK_C01E4C4DBF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql("UPDATE refs SET type = 'memo' WHERE type = 'note'");

        $this->addSql("UPDATE links SET source_type = 'memo' WHERE source_type = 'note'");
        $this->addSql("UPDATE links SET target_type = 'memo' WHERE target_type = 'note'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE links SET source_type = 'note' WHERE source_type = 'memo'");
        $this->addSql("UPDATE links SET target_type = 'note' WHERE target_type = 'memo'");

        $this->addSql("UPDATE refs SET type = 'note' WHERE type = 'memo'");

        $this->addSql('ALTER TABLE memos DROP CONSTRAINT FK_C01E4C4DBF396750');
        $this->addSql('ALTER TABLE memos RENAME TO notes');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT fk_11ba68cbf396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
