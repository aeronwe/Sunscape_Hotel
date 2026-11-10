<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261110150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add role and status columns to admins, and created_by_id foreign key to rooms and services.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE admins ADD role VARCHAR(30) DEFAULT 'ROLE_ADMIN' NOT NULL, ADD status VARCHAR(30) DEFAULT 'active' NOT NULL");
        $this->addSql('ALTER TABLE rooms ADD created_by_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A9DB03A8386 FOREIGN KEY (created_by_id) REFERENCES admins (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7CA11A9DB03A8386 ON rooms (created_by_id)');
        $this->addSql('ALTER TABLE services ADD created_by_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE services ADD CONSTRAINT FK_7332E169B03A8386 FOREIGN KEY (created_by_id) REFERENCES admins (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_7332E169B03A8386 ON services (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A9DB03A8386');
        $this->addSql('DROP INDEX IDX_7CA11A9DB03A8386 ON rooms');
        $this->addSql('ALTER TABLE rooms DROP created_by_id');
        $this->addSql('ALTER TABLE services DROP FOREIGN KEY FK_7332E169B03A8386');
        $this->addSql('DROP INDEX IDX_7332E169B03A8386 ON services');
        $this->addSql('ALTER TABLE services DROP created_by_id');
        $this->addSql('ALTER TABLE admins DROP role, DROP status');
    }
}

