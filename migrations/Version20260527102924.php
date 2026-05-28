<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527102924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notebooks ADD extraction_instructions TEXT DEFAULT NULL');
        $this->addSql('CREATE TABLE extractions (status VARCHAR(32) NOT NULL, prompt TEXT DEFAULT NULL, target_type VARCHAR(50) NOT NULL, error_message TEXT DEFAULT NULL, id UUID NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, target_parent_id UUID DEFAULT NULL, fragments JSONB DEFAULT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_7484645F46CEB85C ON extractions (target_parent_id)');
        $this->addSql('ALTER TABLE extractions ADD CONSTRAINT FK_7484645F46CEB85C FOREIGN KEY (target_parent_id) REFERENCES refs (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE extractions ADD CONSTRAINT FK_7484645FBF396750 FOREIGN KEY (id) REFERENCES refs (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notebooks DROP extraction_instructions');
        $this->addSql('ALTER TABLE extractions DROP CONSTRAINT FK_7484645F46CEB85C');
        $this->addSql('ALTER TABLE extractions DROP CONSTRAINT FK_7484645FBF396750');
        $this->addSql('DROP TABLE extractions');
    }
}
