-- This is a PostgreSQL script to create and populate the database schema.
-- It has been converted from the MySQL scripts you provided and includes
-- all the tables we have worked on so far, along with indexes and constraints.

-- Function to handle `ON UPDATE` functionality for `date_updated`
-- This replaces the MySQL-specific `ON UPDATE current_timestamp()`
CREATE OR REPLACE FUNCTION update_timestamp_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.date_updated = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

--
-- Table structure for table `accounts`
--
DROP TABLE IF EXISTS accounts CASCADE;
CREATE TABLE accounts (
    id SERIAL PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL,
    pin TEXT DEFAULT NULL,
    firstname VARCHAR(250) NOT NULL,
    lastname VARCHAR(250) NOT NULL,
    middlename VARCHAR(250) NOT NULL,
    address TEXT DEFAULT NULL,
    marital_status VARCHAR(50) DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    phone_number VARCHAR(50) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    id_type VARCHAR(100) DEFAULT NULL,
    id_number VARCHAR(100) DEFAULT NULL,
    email TEXT NOT NULL,
    firebase_uid VARCHAR(128) DEFAULT NULL,
    password TEXT NOT NULL,
    transaction_pin VARCHAR(255) DEFAULT NULL,
    first_login_done BOOLEAN NOT NULL DEFAULT FALSE,
    generated_password TEXT NOT NULL,
    balance NUMERIC NOT NULL,
    date_created TIMESTAMP NOT NULL DEFAULT NOW(),
    date_updated TIMESTAMP DEFAULT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'Pending',
    login_type SMALLINT DEFAULT 2
);

-- Applying the `ON UPDATE` trigger for `date_updated` column
CREATE TRIGGER update_accounts_date_updated BEFORE UPDATE ON accounts FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();

-- Adding comments for the `accounts` table, as converted from MySQL
COMMENT ON COLUMN accounts.transaction_pin IS 'Hashed 5-digit transaction PIN';
COMMENT ON COLUMN accounts.first_login_done IS '0=not shown, 1=PIN shown on first login';

--
-- Dumping data for table `accounts`
--
INSERT INTO accounts (id, account_number, pin, firstname, lastname, middlename, address, marital_status, gender, phone_number, date_of_birth, id_type, id_number, email, firebase_uid, password, transaction_pin, first_login_done, generated_password, balance, date_created, date_updated, status, login_type) VALUES
(4, '2996114078', '', 'Donald', 'Young', '', '34 cresent avenue', 'Widowed', 'Female', '90987654328', '1972-10-10', 'Passport', '8765409823778', 'smith@gmail.com', 'y0ID9A7HrlRxNp1GY9QE5lyodkO2', '', NULL, TRUE, '', 20000, '2025-06-09 22:32:02'::timestamp, '2025-06-12 11:34:00'::timestamp, '1', 2),
(5, '5994588630', '', 'George', 'Hans', 'Hans Christy', '35 Gerald avenue, West Midlands', 'Married', 'Male', '98765234091', '1967-06-11', 'National ID', '123754287667754', 'henrythebest2023@gmail.com', 'AdeRNSP5reUMF4eZ2brIzV8AGtx1', '', NULL, TRUE, '', 30500, '2025-06-11 10:13:52'::timestamp, '2025-06-20 22:27:18'::timestamp, 'Active', 2),
(10, '4280912401', '', 'Don', 'Philip', '', '2592 Harrison Street', 'Divorced', 'Male', '90987654328', '1976-08-08', 'National ID', '8765430987', 'phill@gmail.com', 'uLTH2xqcJvfG1OTM4X7rhtYSjIJ3', '', '75271', TRUE, '', 1730, '2025-06-12 05:17:00'::timestamp, '2025-06-28 20:09:51'::timestamp, 'Active', 2),
(11, '9494405173', '', 'Morris', 'Abali', '', '67 saint Anthony cresent, West indis', 'Single', 'Male', '3906541098', '1971-09-07', 'Drivers License', '7651290872', 'morris@gmail.com', 'ESvcqxmFZxfVTxucMEdY1NaXYVv2', '', '93143', TRUE, '', 12247, '2025-06-12 11:52:21'::timestamp, '2025-06-28 17:40:01'::timestamp, 'Active', 2),
(12, '3963787860', '', 'Miracle', 'Thomas', '', '2592 Harrison Street', 'Single', 'Female', '562 249-5297', '1976-07-04', 'National ID', '7623098463', 'angel1@gmail.com', 'o3TMCSh9d3P1jwTVYS0G83fBI9k1', '', '12851', TRUE, '', 13940, '2025-06-12 12:13:44'::timestamp, '2025-06-28 18:52:15'::timestamp, 'Active', 2),
(13, '5827241966', '', 'Charles', 'Awuru', '', '2592 Harrison Street', 'Married', 'Male', '0975467987', '1969-08-09', 'National ID', '7651290872', 'awuru@gmail.com', 'J38ddr1045h7zJhi2R6JpZALRwq2', '', '67627', TRUE, '', 10633, '2025-06-12 12:35:11'::timestamp, '2025-06-29 00:22:04'::timestamp, 'Active', 2),
(14, '9180554139', '', 'Imo', 'Dominic', '', '12 church street ', 'Married', 'Male', '8907664320', '1975-06-18', 'National ID', '5676542987', 'imo@gmail.com', 'fnNmb0byaggRoIsj3pO4KVIKNo02', '', '87847', TRUE, '', 800, '2025-06-18 23:16:50'::timestamp, '2025-06-24 18:28:31'::timestamp, 'Active', 2),
(17, '3169923433', '', 'Philip ', 'Mark ', '', '234 rounding Avenue, London E17 ', 'Divorced', 'Male', '987654097', '1981-06-20', 'Passport', '7652987652', 'philip@gmail.com', 'cbzLtpOKfoQG4jxbkfslYh126qn1', '', '27480', TRUE, '', 0, '2025-06-20 20:40:53'::timestamp, '2025-06-20 20:44:04'::timestamp, 'Active', 2),
(18, '9435993512', '', 'Humphrey', 'Dom', '', '38b Rabiatu Aghedo St', 'Married', 'Male', '09125271199', '1970-09-09', 'Passport', '7654321987', 'hugh@gmail.com', 'TmVdv9rKNXQdcTPCECMs1OygxKh1', '', '63919', TRUE, '', 0, '2025-06-22 04:15:52'::timestamp, '2025-06-22 04:19:33'::timestamp, 'Active', 2),
(19, '6635943707', '', 'Henry', 'Larsen', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '09125271100', '1975-07-07', 'Drivers License', '7654098712', 'hen@gmail.com', 'zZ3HsPktC9Z6YCXGGv6T3iVT16v2', '', '74159', TRUE, '', 0, '2025-06-26 13:37:13'::timestamp, '2025-06-26 13:52:16'::timestamp, 'Active', 2),
(20, '5387112226', NULL, 'George', 'Thom', '', '38b Rabiatu Aghedo St', 'Single', 'Female', '09030099', '1976-08-09', 'Passport', '7654321987', 'jjj@gmail.com', 'a1R2TSYlsUSPeJtDZJ8GpFTI7jq2', '', '83278', TRUE, '', 1000, '2025-06-27 06:26:43'::timestamp, '2025-06-29 00:22:04'::timestamp, 'Active', 2),
(21, '5622353355', '87249', 'Ang', 'Abali', '', '2592 Harrison Street', 'Single', 'Female', '98765309128', '1978-09-04', 'National ID', '878763212123', 'ann@gmail.com', 'tCJ2XCCUmKfzDDXY1HV6VsHNtov1', '', NULL, TRUE, '', 0, '2025-06-27 07:58:57'::timestamp, '2025-06-27 08:12:32'::timestamp, 'Active', 2),
(22, '3777431476', '10929', 'Hen', 'Awu', '', '38b Rabiatu Aghedo St', 'Married', 'Male', '09030099', '1975-09-09', 'National ID', '7654098712', 'awuu@gmail.com', 'heQ0TIMxOpW0hA89CZ4q1HRyd5e2', '', '23415', TRUE, '', 0, '2025-06-28 04:31:12'::timestamp, '2025-06-28 06:46:26'::timestamp, 'Active', 2),
(23, '6853099281', NULL, 'George', 'Fred', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '0903009', '1977-05-04', 'Passport', '9875340987', 'fred@gmail.com', 'Z00WUu34Y6fcuk43CfKafif8NDn1', '', '60710', TRUE, '', 0, '2025-06-28 07:48:01'::timestamp, '2025-06-28 11:33:14'::timestamp, 'Active', 2),
(24, '4231498915', NULL, 'Sunday', 'Ozon', '', '38b Rabiatu Aghedo St', 'Married', 'Female', '09030099662', '1790-05-04', 'Passport', '7654309898', 'fre3@gmail.com', 'ppmIYBmGqHPecU8vauZNZXUgJ7A2', '', '31646', TRUE, '', 200, '2025-06-28 15:42:15'::timestamp, '2025-06-28 17:42:00'::timestamp, 'Active', 2);

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--
-- Note: This table structure is inferred from the indexes and auto-increment provided.
--
DROP TABLE IF EXISTS announcements CASCADE;
CREATE TABLE announcements (
    id SERIAL PRIMARY KEY,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    date_created TIMESTAMP NOT NULL DEFAULT NOW()
);

--
-- Dumping data for table `announcements`
--
-- No data dump was provided for this table.

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--
DROP TABLE IF EXISTS customer CASCADE;
CREATE TABLE customer (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(255) NOT NULL
);

--
-- Dumping data for table `customer`
--
INSERT INTO customer (firstname, lastname, email, password, otp) VALUES
('Joe', 'Biden', 'joebiden@yahoo.com', '$2b$10$C82K72d42S9iQ1fL4q5q5.d3a.q5q5.f4q5q5.g7Q1fL4q5q5.h1Q1fL4q5q5.i8Q1fL4q5q5.j3', '852179'),
('Donald', 'Trump', 'donaldtrump@gmail.com', '$2b$10$C82K72d42S9iQ1fL4q5q5.d3a.q5q5.f4q5q5.g7Q1fL4q5q5.h1Q1fL4q5q5.i8Q1fL4q5q5.j3', '123456');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--
DROP TABLE IF EXISTS inquiries CASCADE;
CREATE TABLE inquiries (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    subject TEXT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(100) NOT NULL,
    user_id INTEGER DEFAULT NULL,
    status BOOLEAN NOT NULL DEFAULT FALSE,
    date_created TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Adding comments for the `inquiries` table, as converted from MySQL
COMMENT ON COLUMN inquiries.status IS '0=Unread, 1=Read';

--
-- Dumping data for table `inquiries`
--
INSERT INTO inquiries (id, name, email, phone, subject, message, type, user_id, status, date_created) VALUES
(1, 'Henry Emailgen', 'henrythebest2023@gmail.com', '0908765432', 'about atm', 'ABOUT ATM CARD', 'General Enquiry', NULL, TRUE, '2025-06-19 00:37:09'::timestamp),
(2, 'lilian', 'tony@gmail.com', '96170845586', 'for enquiry', 'this is test enquiry', 'General Enquiry', NULL, TRUE, '2025-06-19 00:46:46'::timestamp),
(3, 'John', 'jon@gmail.com', '0985214635', 'I cant connect to my server', 'I cant connect to my server', 'Technical Support', NULL, TRUE, '2025-06-19 00:48:23'::timestamp);

-- --------------------------------------------------------

--
-- Table structure for table `pending_transactions`
--
DROP TABLE IF EXISTS pending_transactions CASCADE;
CREATE TABLE pending_transactions (
    id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL,
    recipient_id INTEGER NOT NULL,
    amount NUMERIC(10, 2) NOT NULL,
    description TEXT,
    timestamp TIMESTAMP NOT NULL DEFAULT NOW(),
    status VARCHAR(50) NOT NULL DEFAULT 'pending'
);

--
-- Dumping data for table `pending_transactions`
--
-- No data to dump.

-- --------------------------------------------------------

--
-- Table structure for table `recipients`
--
DROP TABLE IF EXISTS recipients CASCADE;
CREATE TABLE recipients (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    bank_name VARCHAR(100) NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

--
-- Dumping data for table `recipients`
--
INSERT INTO recipients (user_id, account_number, bank_name, account_name) VALUES
(5, '4280912401', 'First Bank', 'Don Philip'),
(10, '5994588630', 'UBA', 'George Hans'),
(12, '6853099281', 'Chase', 'George Fred');

-- --------------------------------------------------------

--
-- Table structure for table `system_info`
--
DROP TABLE IF EXISTS system_info CASCADE;
CREATE TABLE system_info (
    id SERIAL PRIMARY KEY,
    meta_field TEXT NOT NULL,
    meta_value TEXT NOT NULL
);

--
-- Dumping data for table `system_info`
--
INSERT INTO system_info (id, meta_field, meta_value) VALUES
(1, 'name', '- IMF Micro Finance Bank'),
(6, 'short_name', 'IMF'),
(11, 'logo', 'uploads/1626243720_bank.jpg'),
(13, 'user_avatar', 'uploads/user_avatar.jpg'),
(14, 'cover', 'uploads/1626249540_dark-bg.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--
DROP TABLE IF EXISTS transactions CASCADE;
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    transaction_code VARCHAR(50) UNIQUE,
    account_id INTEGER NOT NULL,
    type SMALLINT NOT NULL,
    amount NUMERIC NOT NULL,
    remarks TEXT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'completed',
    linked_account_id INTEGER DEFAULT NULL,
    transaction_type VARCHAR(100) DEFAULT NULL,
    meta_data JSONB DEFAULT NULL,
    sender_account_number VARCHAR(20) DEFAULT NULL,
    receiver_account_number VARCHAR(20) DEFAULT NULL,
    date_created TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Adding comments for the `transactions` table, as converted from MySQL
COMMENT ON COLUMN transactions.type IS '1=Cash in, 2= Withdraw, 3=transfer';
COMMENT ON COLUMN transactions.status IS 'Transaction status (e.g., pending, completed, failed, rejected)';
COMMENT ON COLUMN transactions.linked_account_id IS 'FK to user_linked_accounts.id for source/destination linked account';
COMMENT ON COLUMN transactions.transaction_type IS 'Specific type of transaction (e.g., deposit_internal, deposit_external_pending, transfer_internal)';
COMMENT ON COLUMN transactions.meta_data IS 'JSON for additional transaction details like source account info, etc.';

--
-- Dumping data for table `transactions`
--
-- The data dump for this table was not provided.

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(250) NOT NULL,
    lastname VARCHAR(250) NOT NULL,
    username TEXT NOT NULL,
    password TEXT NOT NULL,
    avatar TEXT DEFAULT NULL,
    last_login TIMESTAMP DEFAULT NULL,
    type SMALLINT NOT NULL DEFAULT 0,
    date_added TIMESTAMP NOT NULL DEFAULT NOW(),
    date_updated TIMESTAMP DEFAULT NULL
);

-- Applying the `ON UPDATE` trigger for `date_updated` column
CREATE TRIGGER update_users_date_updated BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_timestamp_column();

--
-- Dumping data for table `users`
--
INSERT INTO users (id, firstname, lastname, username, password, avatar, last_login, type, date_added, date_updated) VALUES
(1, 'Adminstrator', 'Admin', 'admin', '$2y$10$ptv5.rayGI01m0SX1A0NOex0CvVNUTbBkyLMcnOTAChFUxQtLH7oq', 'uploads/1624240500_avatar.png', NULL, 1, '2021-01-20 14:02:37'::timestamp, '2025-06-28 11:32:06'::timestamp);

-- --------------------------------------------------------

--
-- Table structure for table `user_linked_accounts`
--
DROP TABLE IF EXISTS user_linked_accounts CASCADE;
CREATE TABLE user_linked_accounts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    account_label VARCHAR(255) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    routing_number VARCHAR(50) DEFAULT NULL,
    iban VARCHAR(50) DEFAULT NULL,
    beneficiary_address TEXT DEFAULT NULL,
    beneficiary_phone VARCHAR(20) DEFAULT NULL,
    swift_bic VARCHAR(20) DEFAULT NULL,
    account_number VARCHAR(255) NOT NULL,
    account_holder_name VARCHAR(255) NOT NULL,
    is_internal_bank BOOLEAN NOT NULL DEFAULT FALSE,
    account_type VARCHAR(50) DEFAULT NULL,
    link_type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    date_added TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT fk_linked_user_id FOREIGN KEY (user_id) REFERENCES accounts (id) ON DELETE CASCADE
);

--
-- Dumping data for table `user_linked_accounts`
--
-- The data dump for this table was not provided.
