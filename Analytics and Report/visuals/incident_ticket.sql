CREATE TABLE incident_ticket (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    image_id INT,
    service_id INT NOT NULL,
    ward_id INT NOT NULL,
    address_id INT,
    status_id INT NOT NULL,
    incident_description TEXT NOT NULL,
    location_description TEXT,
    priority ENUM('Low', 'Medium', 'High') NOT NULL,
    date_reported DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_resolved DATETIME NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),

    FOREIGN KEY (service_id)
        REFERENCES municipal_servive(service_id),

    FOREIGN KEY (ward_id)
        REFERENCES wards(ward_id),

    FOREIGN KEY (status_id)
        REFERENCES ticket_status(status_id)
);
INSERT INTO incident_ticket
(service_id, ward_id, status_id, incident_description,
location_description, priority, date_reported)
VALUES

(1, 1, 1, 'Water pipe leaking',
 'High Street', 'High', '2026-08-01'),

(2, 2, 2, 'Street lights not working',
 'African Street', 'Medium', '2026-08-01'),

(3, 3, 3, 'Large pothole on road',
 'Raglan Road', 'High', '2026-08-02'),

(1, 1, 2, 'Broken water pipe',
 'High Street', 'low', '2026-08-02'),

(4, 4, 1, 'Blocked drainage',
 'Hill Street', 'Medium', '2026-08-03'),

(3, 2, 3, 'Road damaged',
 'New Street', 'Low', '2026-08-03'),

(2, 5, 2, 'Power outage',
 'Beaufort Street', 'High', '2026-08-04'),

(1, 3, 1, 'Water leak',
 'Somerset Street', 'Medium', '2026-08-04'),

(5, 6, 3, 'Damaged park equipment',
 'Victoria Park', 'Low', '2026-08-05'),

(3, 1, 2, 'Pothole',
 'High Street', 'High', '2026-08-05');
 
