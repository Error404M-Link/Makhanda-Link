CREATE TABLE wards(
ward_id INT PRIMARY KEY, 
ward_name VARCHAR(50) NOT NULL
); 

INSERT INTO wards(ward_id, ward_name) VALUES
(1,"ward 1"), 
(2,"ward 2"), 
(3,"ward 3"),
(4,"ward 4"), 
(5,"ward 5"), 
(6,"ward 6"), 
(7,"ward 7"), 
(8,"ward 8"), 
(9,"ward 9"), 
(10,"ward 10"), 
(11,"ward 11"),
(12,"ward 12"),
(13,"ward 13"),
(14,"ward 14");

SELECT * FROM wards; 