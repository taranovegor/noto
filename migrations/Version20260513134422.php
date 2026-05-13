<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513134422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Merge note title into content as h1, drop title column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE notes SET content = '# ' || title || chr(10) || content");
        $this->addSql('ALTER TABLE notes DROP title');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE notes ADD title VARCHAR(255) DEFAULT '' NOT NULL");
        $this->addSql("UPDATE notes SET title = split_part(content, chr(10), 1) WHERE content LIKE '# %'");
        $this->addSql("UPDATE notes SET title = 'Untitled' WHERE title = ''");
        $this->addSql('ALTER TABLE notes ALTER title DROP DEFAULT');
    }
}
