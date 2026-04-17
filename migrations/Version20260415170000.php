<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories and finance transactions tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE category (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(100) NOT NULL,
                type VARCHAR(20) NOT NULL,
                color VARCHAR(7) NOT NULL,
                icon VARCHAR(100) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE finance_transaction (
                id INT AUTO_INCREMENT NOT NULL,
                category_id INT NOT NULL,
                amount NUMERIC(10, 2) NOT NULL,
                description VARCHAR(255) NOT NULL,
                date DATE NOT NULL COMMENT '(DC2Type:date_immutable)',
                type VARCHAR(20) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_BC84CDA112469DE2 (category_id),
                PRIMARY KEY(id),
                CONSTRAINT FK_BC84CDA112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE RESTRICT
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE finance_transaction');
        $this->addSql('DROP TABLE category');
    }
}
