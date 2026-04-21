<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421165000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create local products and orders tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE products (
                id VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                price DECIMAL(10, 2) NOT NULL,
                quantity INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE orders (
                id VARCHAR(36) NOT NULL,
                product_id VARCHAR(36) NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                quantity_ordered INT NOT NULL,
                order_status VARCHAR(32) NOT NULL,
                INDEX IDX_E52FFDEE4584665A (product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4584665A FOREIGN KEY (product_id) REFERENCES products (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4584665A');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE products');
    }
}
