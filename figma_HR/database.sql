-- ==========================================================
--  PeopleHub Database Schema
--  Import this file once into MySQL (phpMyAdmin > Import,
--  or `mysql -u root -p peoplehub < database.sql`) before
--  using any of the PHP pages in /api.
-- ==========================================================

CREATE DATABASE IF NOT EXISTS peoplehub CHARACTER SET utf8mb4;
USE peoplehub;

-- ----------------------------------------------------------
-- Employee accounts (created via register.html / register.php)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS accounts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(150)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,      -- stored in plain text (no hashing)
    position     VARCHAR(100)  DEFAULT 'Employee',
    date_joined  DATETIME      NOT NULL
);

-- ----------------------------------------------------------
-- Admin accounts (separate table/session from employees)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(150)  NOT NULL,
    email             VARCHAR(150)  NOT NULL UNIQUE,
    password          VARCHAR(255)  NOT NULL,      -- stored in plain text (no hashing)
    date_joined       DATETIME      NOT NULL,
    is_system_admin   TINYINT(1)    NOT NULL DEFAULT 0   -- 1 = the original/main admin, never removable
);

-- After importing this file and creating your first admin via
-- api/create_admin.php, mark that row as the system admin so it
-- can never be removed from the Admin Dashboard:
--   UPDATE admins SET is_system_admin = 1 WHERE id = 1;
-- (assuming it's the very first row - adjust the id if not)

-- After importing this file, open api/create_admin.php ONCE in
-- your browser to create your first admin account (edit the
-- name/email/password values in that file first). Delete
-- create_admin.php afterwards for security.

-- ----------------------------------------------------------
-- Attendance / time log for employees.
-- One row = one login "shift". Break columns let an employee
-- start/end a single break during that shift.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS attendance (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    account_id    INT NOT NULL,
    login_time    DATETIME NOT NULL,
    logout_time   DATETIME NULL,
    break_start   DATETIME NULL,
    break_end     DATETIME NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- ----------------------------------------------------------
-- Job applicants (from careers.html application form)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS applicants (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    position      VARCHAR(150) NOT NULL,
    name          VARCHAR(150) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    phone         VARCHAR(30)  NOT NULL,
    date_applied  DATETIME     NOT NULL,
    status        ENUM('Pending','Under Review','Interview Scheduled','Hired','Rejected')
                  NOT NULL DEFAULT 'Pending'
);

-- NOTE: CREATE TABLE IF NOT EXISTS will NOT add the new `status`
-- column to a table that already exists from an earlier import.
-- If your `applicants` table already exists, run this once instead:
--   ALTER TABLE applicants
--     ADD COLUMN status ENUM('Pending','Under Review','Interview Scheduled','Hired','Rejected')
--     NOT NULL DEFAULT 'Pending';

-- ----------------------------------------------------------
-- Feedback submissions (from the "Feedback" form in the
-- site footer on Coralde.html). Every message sent through
-- that form is stored here so admins can review it later.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS feedback (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(150) NOT NULL,
    message       TEXT         NOT NULL,
    date_sent     DATETIME     NOT NULL,
    reply         TEXT         NULL,        -- admin's reply text, NULL until answered
    replied_at    DATETIME     NULL,        -- when the admin sent the reply
    replied_by    INT          NULL,        -- admins.id of who replied
    FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- NOTE: if your `feedback` table already exists from an earlier
-- import, run this once instead to add the new reply columns:
--   ALTER TABLE feedback
--     ADD COLUMN reply TEXT NULL,
--     ADD COLUMN replied_at DATETIME NULL,
--     ADD COLUMN replied_by INT NULL,
--     ADD FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL;

-- ----------------------------------------------------------
-- Contact messages (from the "Send us a Message" form on
-- contact.html). Every message sent through that form is
-- stored here so admins can review it later.
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    subject       VARCHAR(200) NOT NULL,
    message       TEXT         NOT NULL,
    date_sent     DATETIME     NOT NULL,
    reply         TEXT         NULL,        -- admin's reply text, NULL until answered
    replied_at    DATETIME     NULL,        -- when the admin sent the reply
    replied_by    INT          NULL,        -- admins.id of who replied
    FOREIGN KEY (replied_by) REFERENCES admins(id) ON DELETE SET NULL
);
