-- Add password column to users table if not present
-- Required for user authentication in warehouse system

SET @dbname = DATABASE();

SET @has_password = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'password'
);

SET @sql_password = IF(
    @has_password > 0,
    'SELECT 1 -- password column already exists',
    'ALTER TABLE `users` ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '' AFTER `email`'
);

PREPARE stmt_password FROM @sql_password;
EXECUTE stmt_password;
DEALLOCATE PREPARE stmt_password;
