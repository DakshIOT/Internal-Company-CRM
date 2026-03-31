-- Local login seed for XAMPP testing after Laravel migrations run.
-- Plaintext password for every user below: Password@123
-- Preferred path: use the Laravel seeder instead of raw SQL.

INSERT INTO venues (id, name, code, is_active, created_at, updated_at) VALUES
(1, 'Sky Hall', 'SKY-HALL', 1, NOW(), NOW()),
(2, 'Garden Court', 'GARDEN-COURT', 1, NOW(), NOW());

INSERT INTO users (id, name, email, role, is_active, email_verified_at, password, created_at, updated_at) VALUES
(1, 'CRM Admin', 'admin@interiorcrm.local', 'admin', 1, NOW(), '$2y$10$9Op/ALGQZOzcHOJeNUn4iO5wba7TTpXs/axWMDJFKDcteB7041eyK', NOW(), NOW()),
(2, 'Employee A', 'employee.a@interiorcrm.local', 'employee_a', 1, NOW(), '$2y$10$9Op/ALGQZOzcHOJeNUn4iO5wba7TTpXs/axWMDJFKDcteB7041eyK', NOW(), NOW()),
(3, 'Employee B', 'employee.b@interiorcrm.local', 'employee_b', 1, NOW(), '$2y$10$9Op/ALGQZOzcHOJeNUn4iO5wba7TTpXs/axWMDJFKDcteB7041eyK', NOW(), NOW()),
(4, 'Employee C', 'employee.c@interiorcrm.local', 'employee_c', 1, NOW(), '$2y$10$9Op/ALGQZOzcHOJeNUn4iO5wba7TTpXs/axWMDJFKDcteB7041eyK', NOW(), NOW());

INSERT INTO venue_user (user_id, venue_id, frozen_fund_minor, created_at, updated_at) VALUES
(2, 1, 15000, NOW(), NOW()),
(2, 2, 10000, NOW(), NOW()),
(3, 1, 0, NOW(), NOW()),
(3, 2, 0, NOW(), NOW()),
(4, 1, 0, NOW(), NOW());
