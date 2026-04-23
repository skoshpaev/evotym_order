<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423090100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add last processed product event timestamp.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD last_product_event_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP last_product_event_at');
    }
}
