<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250205000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed development dataset for dashboard API demo.';
    }

    public function up(Schema $schema): void
    {
        // customers
        $this->addSql(<<<'SQL'
INSERT INTO customers (id, full_name, email, status, created_at, updated_at) VALUES
    ('019a4641-16fd-748d-aadb-c25c811c832b', 'Екатерина Смирнова', 'ekaterina.smirnova@example.com', 'active', '2024-03-18 09:00:00', '2024-11-27 16:30:00'),
    ('019a4641-33e2-76a4-8048-2ac18e8d723f', 'ИП ''Цифровые решения''', 'info@digits.example', 'active', '2024-02-05 10:00:00', '2024-11-18 10:15:00'),
    ('019a4641-4fc1-72d2-9cb6-53dc2e0066d3', 'ООО ''Техномир''', 'office@technomir.example', 'active', '2024-01-24 12:00:00', '2024-12-01 09:00:00');
SQL);

        // loan applications
        $this->addSql(<<<'SQL'
INSERT INTO loan_applications (
    id,
    customer_id,
    principal_amount,
    principal_currency,
    interest_rate,
    term_months,
    status,
    schedule,
    created_at,
    updated_at,
    submitted_at,
    approved_at,
    activated_at,
    closed_at,
    rejected_at
) VALUES
    (
        '019a4640-7003-784a-9fd2-ae5ec1a14687',
        '019a4641-16fd-748d-aadb-c25c811c832b',
        35000000,
        'RUB',
        16.8,
        24,
        'active',
        '[
            {"due_date": "2024-12-15", "amount": 1820000},
            {"due_date": "2025-01-15", "amount": 1820000},
            {"due_date": "2025-02-15", "amount": 1820000},
            {"due_date": "2025-03-15", "amount": 1820000},
            {"due_date": "2025-04-15", "amount": 1820000}
        ]'::jsonb,
        '2024-04-12 09:30:00',
        '2024-11-15 10:00:00',
        '2024-04-12 09:45:00',
        '2024-04-13 12:00:00',
        '2024-04-14 10:15:00',
        NULL,
        NULL
    ),
    (
        '019a4640-8465-70b8-adb0-0b20307e566a',
        '019a4641-16fd-748d-aadb-c25c811c832b',
        50000000,
        'RUB',
        15.2,
        18,
        'approved',
        NULL,
        '2024-11-20 14:00:00',
        '2024-11-27 16:30:00',
        '2024-11-20 14:15:00',
        '2024-11-27 16:30:00',
        NULL,
        NULL,
        NULL
    ),
    (
        '019a4640-9c50-7af4-837a-b58cf970517f',
        '019a4641-16fd-748d-aadb-c25c811c832b',
        40000000,
        'RUB',
        17.5,
        12,
        'submitted',
        NULL,
        '2024-11-08 11:00:00',
        '2024-11-25 09:00:00',
        '2024-11-08 11:30:00',
        NULL,
        NULL,
        NULL,
        NULL
    ),
    (
        '019a4640-c6bf-7314-bee5-24b72f97c2b6',
        '019a4641-33e2-76a4-8048-2ac18e8d723f',
        42000000,
        'RUB',
        14.8,
        12,
        'active',
        '[
            {"due_date": "2024-11-30", "amount": 1240000},
            {"due_date": "2024-12-30", "amount": 1240000},
            {"due_date": "2025-01-30", "amount": 1240000},
            {"due_date": "2025-02-28", "amount": 1240000}
        ]'::jsonb,
        '2024-10-10 10:00:00',
        '2024-11-05 09:00:00',
        '2024-10-10 10:15:00',
        '2024-10-11 15:00:00',
        '2024-10-12 10:00:00',
        NULL,
        NULL
    ),
    (
        '019a4640-dff6-7ad8-9583-53319632e6dd',
        '019a4641-33e2-76a4-8048-2ac18e8d723f',
        95000000,
        'RUB',
        18.1,
        18,
        'rejected',
        NULL,
        '2024-10-20 13:00:00',
        '2024-10-27 10:00:00',
        '2024-10-20 13:30:00',
        NULL,
        NULL,
        NULL,
        '2024-10-27 10:00:00'
    ),
    (
        '019a4640-fb1b-7fba-8a2c-5e12a0489ea3',
        '019a4641-4fc1-72d2-9cb6-53dc2e0066d3',
        132000000,
        'RUB',
        13.6,
        36,
        'closed',
        '[
            {"due_date": "2024-10-01", "amount": 3660000},
            {"due_date": "2024-11-01", "amount": 3660000},
            {"due_date": "2024-12-01", "amount": 3660000},
            {"due_date": "2025-01-01", "amount": 3660000}
        ]'::jsonb,
        '2024-09-10 10:00:00',
        '2024-12-01 09:00:00',
        '2024-09-10 10:30:00',
        '2024-09-15 11:00:00',
        '2024-09-20 09:30:00',
        '2024-12-01 09:00:00',
        NULL
    );
SQL);

        // payments
        $this->addSql(<<<'SQL'
INSERT INTO payments (
    id,
    loan_id,
    due_date,
    expected_amount,
    expected_currency,
    paid_amount,
    paid_currency,
    status,
    created_at,
    updated_at
) VALUES
    ('019a4643-45be-7fa2-8ad1-2b85a4c9d920', '019a4640-7003-784a-9fd2-ae5ec1a14687', '2024-10-15', 1820000, 'RUB', 1820000, 'RUB', 'paid', '2024-04-14 10:20:00', '2024-10-14 09:00:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a4ffa979', '019a4640-7003-784a-9fd2-ae5ec1a14687', '2024-11-15', 1820000, 'RUB', 1820000, 'RUB', 'paid', '2024-04-14 10:25:00', '2024-11-14 09:10:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a5704fbe', '019a4640-7003-784a-9fd2-ae5ec1a14687', '2024-12-15', 1820000, 'RUB', 0, 'RUB', 'due', '2024-04-14 10:30:00', '2024-11-30 08:00:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a581e6d7', '019a4640-7003-784a-9fd2-ae5ec1a14687', '2025-01-15', 1820000, 'RUB', 0, 'RUB', 'scheduled', '2024-04-14 10:35:00', '2024-11-30 08:05:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a600c28d', '019a4640-c6bf-7314-bee5-24b72f97c2b6', '2024-10-30', 1240000, 'RUB', 1240000, 'RUB', 'paid', '2024-10-12 10:10:00', '2024-10-29 09:00:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a62bd32e', '019a4640-c6bf-7314-bee5-24b72f97c2b6', '2024-11-30', 1240000, 'RUB', 0, 'RUB', 'overdue', '2024-10-12 10:12:00', '2024-12-02 09:30:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a6d3a59c', '019a4640-c6bf-7314-bee5-24b72f97c2b6', '2024-12-30', 1240000, 'RUB', 0, 'RUB', 'due', '2024-10-12 10:14:00', '2024-12-02 09:35:00'),
    ('019a4643-45be-7fa2-8ad1-2b85a7a3348f', '019a4640-fb1b-7fba-8a2c-5e12a0489ea3', '2024-11-01', 3660000, 'RUB', 3660000, 'RUB', 'paid', '2024-09-20 09:45:00', '2024-11-01 09:10:00');
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM payments WHERE id IN (
            '019a4643-45be-7fa2-8ad1-2b85a4c9d920',
            '019a4643-45be-7fa2-8ad1-2b85a4ffa979',
            '019a4643-45be-7fa2-8ad1-2b85a5704fbe',
            '019a4643-45be-7fa2-8ad1-2b85a581e6d7',
            '019a4643-45be-7fa2-8ad1-2b85a600c28d',
            '019a4643-45be-7fa2-8ad1-2b85a62bd32e',
            '019a4643-45be-7fa2-8ad1-2b85a6d3a59c',
            '019a4643-45be-7fa2-8ad1-2b85a7a3348f'
        )");

        $this->addSql("DELETE FROM loan_applications WHERE id IN (
            '019a4640-7003-784a-9fd2-ae5ec1a14687',
            '019a4640-8465-70b8-adb0-0b20307e566a',
            '019a4640-9c50-7af4-837a-b58cf970517f',
            '019a4640-c6bf-7314-bee5-24b72f97c2b6',
            '019a4640-dff6-7ad8-9583-53319632e6dd',
            '019a4640-fb1b-7fba-8a2c-5e12a0489ea3'
        )");

        $this->addSql("DELETE FROM customers WHERE id IN (
            '019a4641-16fd-748d-aadb-c25c811c832b',
            '019a4641-33e2-76a4-8048-2ac18e8d723f',
            '019a4641-4fc1-72d2-9cb6-53dc2e0066d3'
        )");
    }
}
