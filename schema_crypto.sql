-- Ejecutar después de schema_content.sql. No modifica los documentos existentes.
ALTER TABLE documents ADD COLUMN IF NOT EXISTS crypto_version SMALLINT NOT NULL DEFAULT 0;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS signature TEXT;
