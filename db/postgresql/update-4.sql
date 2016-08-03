-- Email is now optional for tutorial accounts
ALTER TABLE idp_account_request ALTER COLUMN email DROP NOT NULL;
