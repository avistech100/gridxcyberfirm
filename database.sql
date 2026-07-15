-- ============================================================
-- GridX Cyber Solutions - Complete MySQL Schema
-- Converted from Django models
-- ============================================================



-- ============================================================
-- AUTH: Groups (Django's built-in)
-- ============================================================
CREATE TABLE IF NOT EXISTS auth_group (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USERS: GridxUser (extends AbstractUser)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_gridxuser (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    password        VARCHAR(128) NOT NULL,
    last_login      DATETIME NULL,
    is_superuser    TINYINT(1) NOT NULL DEFAULT 0,
    username        VARCHAR(150) NOT NULL UNIQUE,
    first_name      VARCHAR(150) NOT NULL DEFAULT '',
    last_name       VARCHAR(150) NOT NULL DEFAULT '',
    email           VARCHAR(25) NOT NULL UNIQUE,
    is_staff        TINYINT(1) NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    date_joined     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    balance         DECIMAL(20,2) NOT NULL DEFAULT 0.00,
    profile_photo   VARCHAR(255) NULL,
    os              ENUM('And','IOS','linux') NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USER-GROUP M2M
-- ============================================================
CREATE TABLE IF NOT EXISTS user_gridxuser_groups (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL,
    group_id INT NOT NULL,
    UNIQUE KEY uq_user_group (user_id, group_id),
    FOREIGN KEY (user_id)  REFERENCES user_gridxuser(id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES auth_group(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TRANSACTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS user_trasaction (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    amount         DECIMAL(15,2) NOT NULL,
    crypto_ammount DECIMAL(15,9) NULL,
    network        ENUM('BTC','ETH','USDT','LTC') NULL,
    address_sent   VARCHAR(255) NULL,
    gift_type      VARCHAR(20) NOT NULL DEFAULT 'failed',
    gift_code      VARCHAR(120) NULL,
    payment_method ENUM('Gift Card','Crypto') NULL,
    is_approved    ENUM('failed','initialize','pending','success') NOT NULL DEFAULT 'failed',
    FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REFUNDS
-- ============================================================
CREATE TABLE IF NOT EXISTS refund_refund (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    transactions_id INT NOT NULL,
    amount          DECIMAL(15,2) NOT NULL,
    reason          TEXT NOT NULL,
    FOREIGN KEY (user_id)         REFERENCES user_gridxuser(id)  ON DELETE CASCADE,
    FOREIGN KEY (transactions_id) REFERENCES user_trasaction(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PHP SESSIONS TABLE (optional - for DB-based sessions)
-- ============================================================
CREATE TABLE IF NOT EXISTS php_sessions (
    session_id   VARCHAR(128) NOT NULL PRIMARY KEY,
    session_data TEXT NOT NULL,
    last_activity INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- GIFT CARD IMAGE UPLOADS
-- ============================================================
CREATE TABLE IF NOT EXISTS gift_card_uploads (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id  INT NOT NULL,
    user_id         INT NOT NULL,
    image_path      VARCHAR(255) NOT NULL,
    uploaded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES user_trasaction(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)        REFERENCES user_gridxuser(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REFUND METHOD OPTIONS (admin-managed)
-- ============================================================
CREATE TABLE IF NOT EXISTS refund_method_options (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default "Other" options
INSERT IGNORE INTO refund_method_options (name, is_visible) VALUES
    ('PayPal', 1),
    ('Cash App', 1),
    ('Venmo', 1);

-- ============================================================
-- USER POP-UP CONTROL (admin per-user)
-- ============================================================
CREATE TABLE IF NOT EXISTS user_popup_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL UNIQUE,
    antispyware ENUM('on','off','default') NOT NULL DEFAULT 'default',
    storage     ENUM('on','off','default') NOT NULL DEFAULT 'default',
    FOREIGN KEY (user_id) REFERENCES user_gridxuser(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SITE SETTINGS (key/value, reuses pattern from payment_settings)
-- ============================================================
CREATE TABLE IF NOT EXISTS site_settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default: self-registration OFF
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES ('allow_self_registration', '0');
