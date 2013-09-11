-- avoid innocuous NOTICEs about automatic sequence creation
set client_min_messages='WARNING';

-- Tell psql to stop on an error. Default behavior is to proceed.
\set ON_ERROR_STOP 1

set TIME ZONE 'utc';

-- Tables for IdP accounts

-- ----------------------------------------------------------------------
DROP TABLE IF EXISTS idp_account_request;

CREATE TABLE idp_account_request (
  id SERIAL PRIMARY KEY,
  first_name VARCHAR NOT NULL,
  last_name VARCHAR NOT NULL,
  email VARCHAR NOT NULL,
  username_requested VARCHAR NOT NULL,
  phone VARCHAR NOT NULL,
  password_hash VARCHAR NOT NULL,
  organization VARCHAR NOT NULL,
  title VARCHAR NOT NULL,
  url VARCHAR,
  reason VARCHAR NOT NULL,
  request_ts timestamp DEFAULT NOW() at time zone 'utc',
  username_assigned VARCHAR,
  created_ts timestamp DEFAULT NULL,
  request_state VARCHAR DEFAULT 'REQUESTED'
);

DROP TABLE IF EXISTS idp_account_actions;

CREATE TABLE idp_account_actions (
     id SERIAL PRIMARY KEY,
     uid VARCHAR NOT NULL,
     action_ts timestamp DEFAULT NOW() at time zone 'utc',
     performer VARCHAR,
     action_performed VARCHAR NOT NULL,
     comment VARCHAR
);
     
     
