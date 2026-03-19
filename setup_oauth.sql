-- Add OAuth columns to users table
ALTER TABLE users 
ADD COLUMN oauth_provider VARCHAR(50) NULL AFTER password,
ADD COLUMN oauth_id VARCHAR(255) NULL AFTER oauth_provider,
ADD COLUMN avatar_url VARCHAR(500) NULL AFTER oauth_id,
ADD COLUMN email_verified BOOLEAN DEFAULT FALSE AFTER email;

-- Create index for faster OAuth lookups
CREATE INDEX idx_oauth ON users(oauth_provider, oauth_id);

-- Update existing users to have email_verified = TRUE
UPDATE users SET email_verified = TRUE WHERE oauth_provider IS NULL;
