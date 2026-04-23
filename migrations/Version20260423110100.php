<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423110100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product versioning and poison message tracking.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD version INT NOT NULL DEFAULT 1');

        $this->addSql(
            'CREATE TABLE poison_messages (
                id INT AUTO_INCREMENT NOT NULL,
                event_id VARCHAR(36) DEFAULT NULL,
                event_type VARCHAR(128) DEFAULT NULL,
                message_class VARCHAR(255) NOT NULL,
                failure_transport_name VARCHAR(128) NOT NULL,
                payload JSON NOT NULL,
                error_message LONGTEXT NOT NULL,
                status VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE poison_messages');
        $this->addSql('ALTER TABLE products DROP version');
    }
}
