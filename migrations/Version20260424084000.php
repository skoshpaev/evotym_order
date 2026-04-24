<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424084000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add inbox table and processing status timestamp for incoming order events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE orders ADD last_processing_status_event_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        $this->addSql(
            'CREATE TABLE inbox (
                event_id VARCHAR(36) NOT NULL,
                product_id VARCHAR(36) DEFAULT NULL,
                `event` JSON NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                status VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_DD5A75A4584665A (product_id),
                INDEX IDX_DD5A75A6BF700BD20D37E491999366 (status, created_at),
                PRIMARY KEY(event_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        /** @noinspection SqlResolve */
        $this->addSql(
            'ALTER TABLE inbox ADD CONSTRAINT FK_DD5A75A4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL'
        );
    }

    /** @noinspection SqlResolve */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inbox DROP FOREIGN KEY FK_DD5A75A4584665A');
        $this->addSql('DROP TABLE inbox');
        $this->addSql('ALTER TABLE orders DROP last_processing_status_event_at');
    }
}
