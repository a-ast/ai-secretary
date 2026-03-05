<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305090736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add initial schema with the agent table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agent (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, prompt CLOB NOT NULL, tools CLOB NOT NULL, run_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('DROP TABLE agent_run');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE agent_run (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE "BINARY", prompt CLOB NOT NULL COLLATE "BINARY", tools CLOB NOT NULL COLLATE "BINARY", run_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('DROP TABLE agent');
    }
}
