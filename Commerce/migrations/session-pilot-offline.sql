-- OFFLINE REVIEW CANDIDATE. Never execute automatically on application startup.
-- Stop HTTP/WS writers, verify a complete backup and restore first.
-- Invalidates ALL existing sessions, including on a repeated execution.
-- MariaDB DDL implicitly commits; recovery is an offline backup restore.
ALTER TABLE gdo_session ENGINE=InnoDB;
ALTER TABLE gdo_user ENGINE=InnoDB;
ALTER TABLE lup_room ENGINE=InnoDB;
ALTER TABLE gdo_session MODIFY sess_token CHAR(32) NOT NULL;
ALTER TABLE gdo_session ADD COLUMN IF NOT EXISTS sess_auth CHAR(32) NULL;
UPDATE gdo_session SET sess_user=NULL, sess_data=NULL,
 sess_token=LOWER(HEX(RANDOM_BYTES(16))), sess_auth=LOWER(HEX(RANDOM_BYTES(16)));
ALTER TABLE gdo_session MODIFY sess_auth CHAR(32) NOT NULL;
