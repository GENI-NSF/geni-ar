-- Add expiration on account requests (and accounts)

ALTER TABLE idp_account_request ADD COLUMN expiration timestamp DEFAULT NULL;
