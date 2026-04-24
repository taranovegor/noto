<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424191038 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('truncate table links restart identity;');

        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT fk_50586597166d1f9c');

        $this->addSql('UPDATE tasks t SET project_id = p.ref_id FROM projects p WHERE t.project_id = p.id AND p.id <> p.ref_id');
        $this->addSql('UPDATE projects SET id = ref_id WHERE id <> ref_id');
        $this->addSql('UPDATE tasks SET id = ref_id WHERE id <> ref_id');

        $this->addSql('ALTER TABLE links ADD source_type VARCHAR(50)');
        $this->addSql('ALTER TABLE links ADD target_type VARCHAR(50)');
        $this->addSql('ALTER TABLE links ADD kind VARCHAR(32)');
        $this->addSql('UPDATE links SET source_type = r.type FROM refs r WHERE links.source_id = r.id');
        $this->addSql('UPDATE links SET target_type = r.type FROM refs r WHERE links.target_id = r.id');
        $this->addSql("UPDATE links SET kind = 'reference'");
        $this->addSql('ALTER TABLE links ALTER COLUMN source_type SET NOT NULL');
        $this->addSql('ALTER TABLE links ALTER COLUMN target_type SET NOT NULL');
        $this->addSql('ALTER TABLE links ALTER COLUMN kind SET NOT NULL');

        $this->addSql('DROP INDEX uniq_d182a118953c1c61158e0b663bf454a4');
        $this->addSql('ALTER TABLE links DROP relation_type');
        $this->addSql('CREATE INDEX IDX_D182A1188D54D22A ON links (source_type)');
        $this->addSql('CREATE INDEX IDX_D182A11841C64198 ON links (target_type)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D182A118953C1C61158E0B663BC4BCD9 ON links (source_id, target_id, kind)');

        $this->addSql('ALTER TABLE projects DROP CONSTRAINT fk_5c93b3a421b741a9');
        $this->addSql('DROP INDEX uniq_5c93b3a421b741a9');
        $this->addSql('ALTER TABLE projects DROP ref_id');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT FK_5C93B3A4BF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT fk_5058659721b741a9');
        $this->addSql('DROP INDEX uniq_5058659721b741a9');
        $this->addSql('ALTER TABLE tasks DROP ref_id');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597BF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('truncate table links restart identity;');

        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT fk_50586597166d1f9c');

        $this->addSql('ALTER TABLE tasks DROP CONSTRAINT FK_50586597BF396750');
        $this->addSql('ALTER TABLE tasks ADD ref_id UUID');
        $this->addSql('UPDATE tasks SET ref_id = id');
        $this->addSql('ALTER TABLE tasks ALTER COLUMN ref_id SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_5058659721b741a9 ON tasks (ref_id)');
        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT fk_5058659721b741a9 FOREIGN KEY (ref_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('ALTER TABLE projects DROP CONSTRAINT FK_5C93B3A4BF396750');
        $this->addSql('ALTER TABLE projects ADD ref_id UUID');
        $this->addSql('UPDATE projects SET ref_id = id');
        $this->addSql('ALTER TABLE projects ALTER COLUMN ref_id SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_5c93b3a421b741a9 ON projects (ref_id)');
        $this->addSql('ALTER TABLE projects ADD CONSTRAINT fk_5c93b3a421b741a9 FOREIGN KEY (ref_id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql('DROP INDEX UNIQ_D182A118953C1C61158E0B663BC4BCD9');
        $this->addSql('DROP INDEX IDX_D182A11841C64198');
        $this->addSql('DROP INDEX IDX_D182A1188D54D22A');
        $this->addSql('ALTER TABLE links ADD relation_type VARCHAR(64)');
        $this->addSql("UPDATE links SET relation_type = source_type || '_to_' || target_type");
        $this->addSql('ALTER TABLE links ALTER COLUMN relation_type SET NOT NULL');
        $this->addSql('ALTER TABLE links DROP kind');
        $this->addSql('ALTER TABLE links DROP target_type');
        $this->addSql('ALTER TABLE links DROP source_type');
        $this->addSql('CREATE UNIQUE INDEX uniq_d182a118953c1c61158e0b663bf454a4 ON links (source_id, target_id, relation_type)');

        $this->addSql('ALTER TABLE tasks ADD CONSTRAINT FK_50586597166D1F9C FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE SET NULL NOT DEFERRABLE');
    }
}
