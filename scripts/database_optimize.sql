-- Optimisation base de données PHPNuxBill
-- Exécuter avec: mysql -u root -p database_name < database_optimize.sql

-- Note: Ce script est conçu pour être sûr et ne supprime que les données anciennes non critiques

-- 1. Ajouter des index manquants pour améliorer les performances
-- Vérifier si les index existent avant de les créer

SET @db_name = DATABASE();

-- Index pour tbl_user_recharges (requêtes fréquentes)
SELECT 'Ajout index pour tbl_user_recharges...' AS 'Status';

ALTER TABLE tbl_user_recharges 
ADD INDEX IF NOT EXISTS idx_status_expiration (status, expiration);

ALTER TABLE tbl_user_recharges 
ADD INDEX IF NOT EXISTS idx_customer_status (customer_id, status);

ALTER TABLE tbl_user_recharges 
ADD INDEX IF NOT EXISTS idx_routers (routers);

-- Index pour rad_acct (accounting RADIUS)
SELECT 'Ajout index pour rad_acct...' AS 'Status';

ALTER TABLE rad_acct 
ADD INDEX IF NOT EXISTS idx_username_status (username, acctstatustype);

ALTER TABLE rad_acct 
ADD INDEX IF NOT EXISTS idx_dateadded (dateAdded);

ALTER TABLE rad_acct 
ADD INDEX IF NOT EXISTS idx_session_id (acctsessionid);

-- Index pour tbl_customers (recherches fréquentes)
SELECT 'Ajout index pour tbl_customers...' AS 'Status';

ALTER TABLE tbl_customers 
ADD INDEX IF NOT EXISTS idx_username_status (username, status);

ALTER TABLE tbl_customers 
ADD INDEX IF NOT EXISTS idx_email (email);

ALTER TABLE tbl_customers 
ADD INDEX IF NOT EXISTS idx_phonenumber (phonenumber);

-- Index pour tbl_plans
SELECT 'Ajout index pour tbl_plans...' AS 'Status';

ALTER TABLE tbl_plans 
ADD INDEX IF NOT EXISTS idx_enabled (enabled);

ALTER TABLE tbl_plans 
ADD INDEX IF NOT EXISTS idx_routers (routers);

-- Index pour tbl_transactions (reporting)
SELECT 'Ajout index pour tbl_transactions...' AS 'Status';

ALTER TABLE tbl_transactions 
ADD INDEX IF NOT EXISTS idx_customer_date (customer_id, recharged_on);

ALTER TABLE tbl_transactions 
ADD INDEX IF NOT EXISTS idx_invoice (invoice);

-- 2. Nettoyer les anciennes sessions RADIUS (> 90 jours)
SELECT 'Nettoyage des sessions RADIUS anciennes...' AS 'Status';

SET @rows_to_delete = (SELECT COUNT(*) FROM rad_acct 
    WHERE acctstatustype = 'Stop' 
    AND dateAdded < DATE_SUB(NOW(), INTERVAL 90 DAY));

SELECT CONCAT('Sessions à supprimer: ', @rows_to_delete) AS 'Info';

DELETE FROM rad_acct 
WHERE acctstatustype = 'Stop' 
AND dateAdded < DATE_SUB(NOW(), INTERVAL 90 DAY)
LIMIT 10000;

-- 3. Optimiser les tables
SELECT 'Optimisation des tables...' AS 'Status';

OPTIMIZE TABLE tbl_user_recharges;
OPTIMIZE TABLE rad_acct;
OPTIMIZE TABLE tbl_customers;
OPTIMIZE TABLE tbl_transactions;
OPTIMIZE TABLE tbl_plans;

-- 4. Analyser les tables pour mettre à jour les statistiques
SELECT 'Analyse des tables...' AS 'Status';

ANALYZE TABLE tbl_user_recharges;
ANALYZE TABLE rad_acct;
ANALYZE TABLE tbl_customers;
ANALYZE TABLE tbl_transactions;
ANALYZE TABLE tbl_plans;

-- 5. Afficher les statistiques
SELECT 'Statistiques des tables:' AS 'Info';

SELECT 
    table_name AS 'Table',
    ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)',
    table_rows AS 'Rows'
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name IN ('tbl_user_recharges', 'rad_acct', 'tbl_customers', 'tbl_transactions', 'tbl_plans')
ORDER BY (data_length + index_length) DESC;

SELECT 'Optimisation terminée!' AS 'Status';
