-- Explicit isolated-pilot installation only; no automatic migration of JSON balances.
-- Before applying: gdo_session, gdo_user and lup_room MUST use InnoDB.
-- Session engine migration is a separate Core review/deployment decision.
CREATE TABLE IF NOT EXISTS lup_gastro_store (
    store_id INT NOT NULL PRIMARY KEY,
    store_data MEDIUMTEXT NOT NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO lup_gastro_store (store_id,store_data)
VALUES (1,'{"rooms":{},"ledger":{}}');
