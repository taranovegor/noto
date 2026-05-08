<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260508211313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE push_subscriptions (id UUID NOT NULL, subscription_hash VARCHAR(255) NOT NULL, subscription JSON NOT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_3FEC449DA76ED395 ON push_subscriptions (user_id)');
        $this->addSql('ALTER TABLE push_subscriptions ADD CONSTRAINT FK_3FEC449DA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE push_subscriptions DROP CONSTRAINT FK_3FEC449DA76ED395');
        $this->addSql('DROP TABLE push_subscriptions');
    }
}
