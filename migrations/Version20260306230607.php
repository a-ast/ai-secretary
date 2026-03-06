<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260306230607 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create agent table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agent (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, prompt CLOB NOT NULL, tools CLOB NOT NULL, run_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE agent');
    }
}
