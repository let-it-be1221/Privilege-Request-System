-- Seed machines and example data
INSERT INTO machines (name, owner_user_id) VALUES ('Server-A', NULL),('DB-Server', NULL),('Web-App', NULL);

-- Use scripts/create_user.php to add users with hashed passwords, e.g.:
-- php scripts/create_user.php alice StrongPass123 employee alice@example.local
-- php scripts/create_user.php manager1 StrongPass123 manager manager1@example.local
-- php scripts/create_user.php admin StrongPass123 machine_admin admin@example.local
