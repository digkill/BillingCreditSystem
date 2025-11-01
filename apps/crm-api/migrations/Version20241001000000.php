<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241001000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial CRM schema for customers, loan applications, and payments.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE customers (
            id UUID NOT NULL,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(180) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CUSTOMERS_EMAIL ON customers (email)');

        $this->addSql('CREATE TABLE loan_applications (
            id UUID NOT NULL,
            customer_id UUID NOT NULL,
            principal_amount BIGINT NOT NULL,
            principal_currency VARCHAR(3) NOT NULL,
            interest_rate DOUBLE PRECISION NOT NULL,
            term_months INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            schedule JSONB DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            submitted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            approved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            activated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            rejected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_LOAN_APPLICATION_CUSTOMER ON loan_applications (customer_id)');
        $this->addSql('ALTER TABLE loan_applications ADD CONSTRAINT FK_LOAN_APPLICATION_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customers (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE payments (
            id UUID NOT NULL,
            loan_id UUID NOT NULL,
            due_date DATE NOT NULL,
            expected_amount BIGINT NOT NULL,
            expected_currency VARCHAR(3) NOT NULL,
            paid_amount BIGINT NOT NULL,
            paid_currency VARCHAR(3) NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_PAYMENTS_LOAN ON payments (loan_id)');
        $this->addSql('ALTER TABLE payments ADD CONSTRAINT FK_PAYMENTS_LOAN FOREIGN KEY (loan_id) REFERENCES loan_applications (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payments DROP CONSTRAINT FK_PAYMENTS_LOAN');
        $this->addSql('ALTER TABLE loan_applications DROP CONSTRAINT FK_LOAN_APPLICATION_CUSTOMER');
        $this->addSql('DROP TABLE payments');
        $this->addSql('DROP TABLE loan_applications');
        $this->addSql('DROP TABLE customers');
    }
}
