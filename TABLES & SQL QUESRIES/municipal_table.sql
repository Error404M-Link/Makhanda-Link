CREATE TABLE municipal_servive(
service_id INT PRIMARY KEY, 
service_name VARCHAR(100) NOT NULL
); 

INSERT INTO municipal_servive(service_id, service_name) VALUES 
(1, "Water"), 
(2, "Electriciy"), 
(3, "Roads"), 
(4, "Fire"), 
(5, "Poilce"), 
(6, "Parks"); 

select * from municipal_servive 
