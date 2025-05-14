-- Create the system_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS system_logs (
    id int unsigned NOT NULL AUTO_INCREMENT,
    type varchar(20) NOT NULL COMMENT 'error, access, activity, system',
    message text NOT NULL,
    user_id int unsigned NULL DEFAULT NULL,
    ip_address varchar(45) NOT NULL,
    created datetime NOT NULL,
    CONSTRAINT system_logs_pk PRIMARY KEY (id)
);

-- Add index for faster searching by type and created date
CREATE INDEX log_type_date ON system_logs (type, created);

-- Add foreign key constraint only if the users table exists and has an id column
-- You can safely remove this if it causes errors
ALTER TABLE system_logs ADD CONSTRAINT system_logs_users FOREIGN KEY system_logs_users (user_id)
    REFERENCES users (id)
    ON DELETE SET NULL
    ON UPDATE CASCADE; 