CREATE TABLE ward (
    ward_id INT AUTO_INCREMENT PRIMARY KEY,
    ward_name VARCHAR(100) NOT NULL,
    population_estimate INT,
    ward_number INT NOT NULL UNIQUE,
    ward_summary TEXT
);

create TABLE user (
username VARCHAR(50) primary key,
profile_id INT null,
first_Name VARCHAR(100) NOT NULL,
surname VARCHAR(100),
contact BIGINT NOT NULL
 CHECK (contact REGEXP '^[0-9]{10}$'),
 password_hash VARCHAR(255) NOT NULL,
 date_joined DATETIME DEFAULT CURRENT_TIMESTAMP,
 active BOOLEAN DEFAULT TRUE,
 User_role VARCHAR(50) NOT NULL
 );

CREATE TABLE community_member (
    username VARCHAR(50) PRIMARY KEY,
    ward_id INT NOT NULL,
    address_id INT NOT NULL,

    CONSTRAINT fk_member_user
        FOREIGN KEY (username)
        REFERENCES user(username),

    CONSTRAINT fk_member_ward
        FOREIGN KEY (ward_id)
        REFERENCES ward(ward_id),

    CONSTRAINT fk_member_address
        FOREIGN KEY (address_id)
        REFERENCES address_directory(address_id)
);

create TABLE Ticket_Status(
status_id INT AUTO_INCREMENT PRIMARY KEY,
status_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE municipal_service (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NULL,
    service_type VARCHAR(100) NOT NULL,

    CONSTRAINT fk_service_user
        FOREIGN KEY (username)
        REFERENCES user(username)
);

CREATE TABLE incident_ticket (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    service_id INT NOT NULL,
    ward_id INT NOT NULL,
    address_id INT NOT NULL,
    status_id INT NOT NULL,
    incident_description TEXT NOT NULL,
    location_description VARCHAR(255),
    priority VARCHAR(30) DEFAULT 'Normal',
    date_reported DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_resolved DATETIME NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_incident_user
        FOREIGN KEY (username)
        REFERENCES user(username),

    CONSTRAINT fk_incident_service
        FOREIGN KEY (service_id)
        REFERENCES municipal_service(service_id),

    CONSTRAINT fk_incident_ward
        FOREIGN KEY (ward_id)
        REFERENCES ward(ward_id),

    CONSTRAINT fk_incident_address
        FOREIGN KEY (address_id)
        REFERENCES address_directory(address_id),

    CONSTRAINT fk_incident_status
        FOREIGN KEY (status_id)
        REFERENCES ticket_status(status_id)
);

CREATE TABLE status_history (
    status_history_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    old_status_id INT NULL,
    new_status_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_incident
        FOREIGN KEY (incident_id)
        REFERENCES incident_ticket(incident_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_history_old_status
        FOREIGN KEY (old_status_id)
        REFERENCES ticket_status(status_id),

    CONSTRAINT fk_history_new_status
        FOREIGN KEY (new_status_id)
        REFERENCES ticket_status(status_id),

    CONSTRAINT fk_history_user
        FOREIGN KEY (username)
        REFERENCES user(username)
);

CREATE TABLE profile_images (
    profile_id INT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    date_uploaded TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE municipal_staff (
    username VARCHAR(50) PRIMARY KEY,
    service_id INT,
    division VARCHAR(100),
    availability_status VARCHAR(50)
);

CREATE TABLE admin (
    username VARCHAR(50) PRIMARY KEY,
    last_login_date TIMESTAMP,
    registered_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ward_councillor (
    username VARCHAR(50) PRIMARY KEY,
    ward_id INT,
    date_appointed DATE
);

CREATE TABLE notices (
    notice_id INT AUTO_INCREMENT PRIMARY KEY, -- standard serial/auto-increment ID
    username VARCHAR(50),
    notice_description TEXT NOT NULL,
    date_posted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ward_notice (
    ward_notice_id INT AUTO_INCREMENT PRIMARY KEY, -- assuming 'ward_notice' is the surrogate PK column name
    ward_id INT,
    notice_id INT
);

CREATE TABLE incident_ward (
    incident_id INT PRIMARY KEY,
    ward_id INT,
    username VARCHAR(50),
    address_id INT
);

CREATE TABLE comment (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT,
    username VARCHAR(50),
    comment_text TEXT NOT NULL,
    comment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE ticket_image (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    date_uploaded DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ticket_image_incident
        FOREIGN KEY (incident_id)
        REFERENCES incident_ticket(incident_id)
        ON DELETE CASCADE
);


-- #################### ALTERING THE RELATIONSHIPS #####################

-- 1. Link user to profile_images
ALTER TABLE user
    ADD CONSTRAINT fk_user_profile
    FOREIGN KEY (profile_id) REFERENCES profile_images(profile_id)
    ON DELETE SET NULL;

-- 2. Link municipal_staff to user and municipal_service
ALTER TABLE municipal_staff
    ADD CONSTRAINT fk_staff_user
        FOREIGN KEY (username) REFERENCES user(username)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_staff_service
        FOREIGN KEY (service_id) REFERENCES municipal_service(service_id)
        ON DELETE SET NULL;

-- 3. Link admin to user
ALTER TABLE admin
    ADD CONSTRAINT fk_admin_user
    FOREIGN KEY (username) REFERENCES user(username)
    ON DELETE CASCADE;

-- 4. Link ward_councillor to user and ward
ALTER TABLE ward_councillor
    ADD CONSTRAINT fk_councillor_user
        FOREIGN KEY (username) REFERENCES user(username)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_councillor_ward
        FOREIGN KEY (ward_id) REFERENCES ward(ward_id)
        ON DELETE SET NULL;

-- 5. Link notices to user
ALTER TABLE notices
    ADD CONSTRAINT fk_notices_user
    FOREIGN KEY (username) REFERENCES user(username)
    ON DELETE SET NULL;

-- 6. Link ward_notice junction table to ward and notices
ALTER TABLE ward_notice
    ADD CONSTRAINT fk_ward_notice_ward
        FOREIGN KEY (ward_id) REFERENCES ward(ward_id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_ward_notice_notice
        FOREIGN KEY (notice_id) REFERENCES notices(notice_id)
        ON DELETE CASCADE;

-- 7. Link incident_ward junction table to incident_ticket, ward, user, and address_directory
ALTER TABLE incident_ward
    ADD CONSTRAINT fk_incident_ward_incident
        FOREIGN KEY (incident_id) REFERENCES incident_ticket(incident_id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_incident_ward_ward
        FOREIGN KEY (ward_id) REFERENCES ward(ward_id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_incident_ward_user
        FOREIGN KEY (username) REFERENCES user(username)
        ON DELETE SET NULL,
    ADD CONSTRAINT fk_incident_ward_address
        FOREIGN KEY (address_id) REFERENCES address_directory(address_id)
        ON DELETE SET NULL;

-- 8. Link comment to incident_ticket and user
ALTER TABLE comment
    ADD CONSTRAINT fk_comment_incident
        FOREIGN KEY (incident_id) REFERENCES incident_ticket(incident_id)
        ON DELETE CASCADE,
    ADD CONSTRAINT fk_comment_user
        FOREIGN KEY (username) REFERENCES user(username)
        ON DELETE SET NULL;
        
ALTER TABLE ticket_status 
    DROP COLUMN status_name,
    ADD COLUMN pending BOOLEAN DEFAULT TRUE,
    ADD COLUMN InProgress BOOLEAN DEFAULT FALSE,
    ADD COLUMN resolved BOOLEAN DEFAULT FALSE;
        
        
        