CREATE TABLE ticket_status(
status_id INT PRIMARY KEY, 
status_name VARCHAR(50) NOT NULL
); 

INSERT INTO ticket_status(status_id, status_name) VALUES
(1, "Pending"), 
(2, "In Progress"), 
(3, "Resolved");  

SELECT * FROM ticket_status; 