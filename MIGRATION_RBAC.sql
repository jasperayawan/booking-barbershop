-- ============================================================
-- SharpCuts: Role-Based Access Control (RBAC) Migration
-- ============================================================
-- This SQL file adds seed data for admin and barber users
-- Run this in phpMyAdmin or MySQL CLI to populate test accounts
--
-- Passwords included (hashed with bcrypt):
-- Admin user: email=admin@sharpcuts.local, password=Admin@123
-- Barbers: password=Barber@123 for all
-- ============================================================

-- Ensure users table has role column (if not already)
ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'barber', 'admin') DEFAULT 'customer';

-- ============================================================
-- Clear existing test data (optional - uncomment if needed)
-- ============================================================
-- DELETE FROM users WHERE role IN ('admin', 'barber');

-- ============================================================
-- INSERT ADMIN USER (1)
-- ============================================================
-- Username: admin_sharpcuts
-- Email: admin@sharpcuts.local
-- Password: Admin@123
-- Hash: $2y$10$YoMft.rrFRizTguFD26YveLKRfwpsIcUC7K88SIyJlRkKfNy1p1dO
INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, created_at)
VALUES (
    'admin_sharpcuts',
    'admin@sharpcuts.local',
    '$2y$10$YoMft.rrFRizTguFD26YveLKRfwpsIcUC7K88SIyJlRkKfNy1p1dO',
    'Admin User',
    '09123456789',
    'admin',
    TRUE,
    NOW()
);

-- ============================================================
-- INSERT BARBER USERS (4)
-- ============================================================
-- All passwords: Barber@123
-- Hash: $2y$10$.lSNtnWbmrG1yWrRQGOjlext5smuIlcau1kwyyDlYqOAwh7hsPEXq

-- Barber 1: Marcus Johnson
INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, created_at)
VALUES (
    'barber_marcus',
    'marcus@sharpcuts.local',
    '$2y$10$.lSNtnWbmrG1yWrRQGOjlext5smuIlcau1kwyyDlYqOAwh7hsPEXq',
    'Marcus Johnson',
    '09111111111',
    'barber',
    TRUE,
    NOW()
);

-- Barber 2: David Smith
INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, created_at)
VALUES (
    'barber_david',
    'david@sharpcuts.local',
    '$2y$10$.lSNtnWbmrG1yWrRQGOjlext5smuIlcau1kwyyDlYqOAwh7hsPEXq',
    'David Smith',
    '09222222222',
    'barber',
    TRUE,
    NOW()
);

-- Barber 3: Alex Rodriguez
INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, created_at)
VALUES (
    'barber_alex',
    'alex@sharpcuts.local',
    '$2y$10$.lSNtnWbmrG1yWrRQGOjlext5smuIlcau1kwyyDlYqOAwh7hsPEXq',
    'Alex Rodriguez',
    '09333333333',
    'barber',
    TRUE,
    NOW()
);

-- Barber 4: James Brown
INSERT INTO users (username, email, password_hash, full_name, phone, role, is_active, created_at)
VALUES (
    'barber_james',
    'james@sharpcuts.local',
    '$2y$10$.lSNtnWbmrG1yWrRQGOjlext5smuIlcau1kwyyDlYqOAwh7hsPEXq',
    'James Brown',
    '09444444444',
    'barber',
    TRUE,
    NOW()
);

-- ============================================================
-- VERIFICATION QUERY (run this to see all created users)
-- ============================================================
-- SELECT id, username, email, full_name, role, is_active FROM users WHERE role IN ('admin', 'barber') ORDER BY created_at DESC;

-- ============================================================
-- LOGIN CREDENTIALS
-- ============================================================
-- ADMIN:
--   Email: admin@sharpcuts.local
--   Password: Admin@123
--
-- BARBERS (all same password):
--   marcus@sharpcuts.local / Barber@123
--   david@sharpcuts.local / Barber@123
--   alex@sharpcuts.local / Barber@123
--   james@sharpcuts.local / Barber@123
-- ============================================================
