<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415174000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename finance_transaction category index to match Doctrine naming';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE finance_transaction RENAME INDEX idx_bc84cda112469de2 TO IDX_8D0AD41A12469DE2');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE finance_transaction RENAME INDEX IDX_8D0AD41A12469DE2 TO idx_bc84cda112469de2');
    }
}
