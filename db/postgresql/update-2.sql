-- ----------------------------------------------------------------------
-- Add tables for self service password resets, email confirmations,
-- and an auto-approve white list.
-- ----------------------------------------------------------------------

-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

set TIME ZONE 'utc';

DROP TABLE IF EXISTS idp_passwd_reset;

CREATE TABLE idp_passwd_reset (
    id SERIAL PRIMARY KEY,
    email VARCHAR NOT NULL,
    nonce VARCHAR NOT NULL,
    created timestamp DEFAULT (NOW() at time zone 'utc') NOT NULL
);

DROP TABLE IF EXISTS idp_email_confirm;

CREATE TABLE idp_email_confirm (
    id SERIAL PRIMARY KEY,
    email VARCHAR NOT NULL,
    nonce VARCHAR NOT NULL,
    created timestamp DEFAULT (NOW() at time zone 'utc') NOT NULL
);

DROP TABLE IF EXISTS idp_whitelist;

CREATE TABLE idp_whitelist (
    id SERIAL PRIMARY KEY,
    institution VARCHAR NOT NULL
);
