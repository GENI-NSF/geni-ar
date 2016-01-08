-- ----------------------------------------------------------------------
-- Add table for self service password resets
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
