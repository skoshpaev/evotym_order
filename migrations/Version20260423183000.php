<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add outbox table for outgoing order events.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE outbox (
                event_id VARCHAR(36) NOT NULL,
                product_id VARCHAR(36) DEFAULT NULL,
                `event` JSON NOT NULL,
                event_type VARCHAR(64) NOT NULL,
                status VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_67D7686B4584665A (product_id),
                INDEX IDX_67D7686B6BF700BD20D37E491999366 (status, created_at),
                PRIMARY KEY(event_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        /** @noinspection SqlResolve */
        $this->addSql(
            'ALTER TABLE outbox ADD CONSTRAINT FK_67D7686B4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL'
        );
    }

    /** @noinspection SqlResolve */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox DROP FOREIGN KEY FK_67D7686B4584665A');
        $this->addSql('DROP TABLE outbox');
    }
}
