<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107203100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the unused products table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS products');
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE products (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                category VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                price NUMERIC(10, 2) NOT NULL,
                image_url VARCHAR(500) DEFAULT NULL,
                is_available TINYINT(1) DEFAULT 1 NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }
}
