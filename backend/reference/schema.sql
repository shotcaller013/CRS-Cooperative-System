-- ============================================================
--  CRS HOLDINGS CORPORATIONS — EMPLOYEES CREDIT COOPERATIVE
--  Database Schema v1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS crs_coop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crs_coop;

-- ------------------------------------------------------------
-- MEMBERS
-- ------------------------------------------------------------
CREATE TABLE members (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  member_no     VARCHAR(20) NOT NULL UNIQUE,          -- e.g. CRS-00572
  last_name     VARCHAR(100) NOT NULL,
  first_name    VARCHAR(100) NOT NULL,
  middle_name   VARCHAR(100),
  address       TEXT,
  contact       VARCHAR(20),
  email         VARCHAR(150),
  company       VARCHAR(200),
  branch        VARCHAR(100),
  department    VARCHAR(100),
  status        ENUM('REGULAR','PROBI','SUSPENDED','INACTIVE') DEFAULT 'PROBI',
  position      VARCHAR(150),
  supervisor    VARCHAR(150),
  date_hired    DATE,
  monthly_salary DECIMAL(12,2) DEFAULT 0,
  share_capital  DECIMAL(12,2) DEFAULT 0,
  member_status  ENUM('ACTIVE','INACTIVE','RESIGNED') DEFAULT 'ACTIVE',
  photo_url     VARCHAR(255),
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- LOAN TYPES
-- ------------------------------------------------------------
CREATE TABLE loan_types (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(30) NOT NULL UNIQUE,          -- commodity, salary, emergency, educ, multi
  label         VARCHAR(100) NOT NULL,
  min_amount    DECIMAL(12,2) NOT NULL,
  max_amount    DECIMAL(12,2) NOT NULL,
  min_term      INT NOT NULL,                          -- months
  max_term      INT NOT NULL,
  annual_rate   DECIMAL(6,4) NOT NULL DEFAULT 0.12,
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- LOAN APPLICATIONS
-- ------------------------------------------------------------
CREATE TABLE loans (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  loan_no           VARCHAR(30) NOT NULL UNIQUE,        -- e.g. LN-2026-00001
  member_id         INT NOT NULL,
  loan_type_id      INT NOT NULL,
  amount            DECIMAL(12,2) NOT NULL,
  term_months       INT NOT NULL,
  frequency         ENUM('monthly','bimonthly','weekly') DEFAULT 'bimonthly',
  annual_rate       DECIMAL(6,4) NOT NULL,
  purpose           TEXT,
  co_maker_1_id     INT,
  co_maker_2_id     INT,
  status            ENUM('DRAFT','PENDING','APPROVED','ACTIVE','CLOSED','REJECTED') DEFAULT 'DRAFT',
  -- computed / cached
  total_payment     DECIMAL(14,2),
  total_interest    DECIMAL(14,2),
  n_periods         INT,
  first_payment_amt DECIMAL(12,2),
  last_payment_amt  DECIMAL(12,2),
  -- dates
  application_date  DATE,
  approval_date     DATE,
  first_due_date    DATE,
  end_date          DATE,
  -- approvals
  approved_by_hr    VARCHAR(150),
  approved_by_coop  VARCHAR(150),
  -- attachments
  signed_form_url   VARCHAR(255),
  notes             TEXT,
  created_by        INT,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id)     REFERENCES members(id),
  FOREIGN KEY (loan_type_id)  REFERENCES loan_types(id),
  FOREIGN KEY (co_maker_1_id) REFERENCES members(id),
  FOREIGN KEY (co_maker_2_id) REFERENCES members(id)
);

-- ------------------------------------------------------------
-- AMORTIZATION SCHEDULE
-- ------------------------------------------------------------
CREATE TABLE amortization_schedule (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  loan_id       INT NOT NULL,
  period_no     INT NOT NULL,
  due_date      DATE,
  principal     DECIMAL(12,2) NOT NULL,
  interest      DECIMAL(12,2) NOT NULL,
  amount_due    DECIMAL(12,2) NOT NULL,
  balance       DECIMAL(12,2) NOT NULL,
  status        ENUM('PENDING','PAID','PARTIAL','OVERDUE') DEFAULT 'PENDING',
  paid_amount   DECIMAL(12,2) DEFAULT 0,
  paid_date     DATE,
  or_number     VARCHAR(50),
  FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
  INDEX idx_loan_period (loan_id, period_no)
);

-- ------------------------------------------------------------
-- USERS (officers / admins)
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(150) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('ADMIN','LOAN_OFFICER','STAFF') DEFAULT 'STAFF',
  is_active     TINYINT(1) DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- SEED DATA
-- ------------------------------------------------------------
INSERT INTO loan_types (code, label, min_amount, max_amount, min_term, max_term, annual_rate) VALUES
  ('commodity', 'Commodity Loan',     10000, 100000, 6, 36, 0.12),
  ('salary',    'Salary / Cash Loan', 5000,  50000,  3, 24, 0.12),
  ('emergency', 'Emergency Loan',     3000,  30000,  3, 12, 0.12),
  ('educ',      'Educational Loan',   5000,  80000,  6, 24, 0.10),
  ('multi',     'Multi-purpose Loan', 10000, 150000, 6, 48, 0.12);

INSERT INTO users (name, email, password_hash, role) VALUES
  ('J. Monteverde', 'j.monteverde@crsholdings.ph', '$2y$10$examplehashhere', 'LOAN_OFFICER'),
  ('Admin', 'admin@crsholdings.ph', '$2y$10$examplehashhere', 'ADMIN');

INSERT INTO members (member_no, last_name, first_name, middle_name, address, contact, email, company, status, position, supervisor, date_hired, monthly_salary, share_capital, member_status) VALUES
  ('CRS-00234', 'Santos',    'Maria Clara',  'D.', '127 Mabolo St., Mandaue City, Cebu',     '0917-234-5678', 'mc.santos@crsholdings.ph',    'CRS Holdings Corp.', 'REGULAR', 'Sr. Accountant',        'Engr. R. Villanueva', '2021-03-15', 32000, 15000, 'ACTIVE'),
  ('CRS-00411', 'Dela Cruz', 'Juan Ponce',   'C.', 'Blk 7 Lot 3, Basak, Lapu-Lapu City',    '0928-115-9901', 'jp.delacruz@crsholdings.ph',  'CRS Holdings Corp.', 'REGULAR', 'Logistics Lead',        'Ma. T. Abellanosa',   '2019-08-02', 28500, 22000, 'ACTIVE'),
  ('CRS-00572', 'Bacolod',   'Aileen',       'R.', '42 Guizo Rd., Mandaue City',             '0945-880-4412', 'a.bacolod@crsholdings.ph',    'CRS Holdings Corp.', 'PROBI',   'HR Assistant',          'J. Monteverde',        '2025-11-10', 19500, 3000,  'ACTIVE'),
  ('CRS-00103', 'Tan',       'Rodrigo',      'M.', '88 Cabancalan, Mandaue City',            '0919-442-0087', 'r.tan@crsholdings.ph',         'CRS Holdings Corp.', 'REGULAR', 'Warehouse Supervisor',  'A. Lim',              '2017-01-20', 34500, 40000, 'ACTIVE');
