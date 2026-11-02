/*guys please don't remove these comments*/

/*    */
SELECT w.ward_name, COUNT(i.incident_id) AS total_incident_tickets_per_ward
FROM incident_ticket i
JOIN wards w ON i.ward_id = w.ward_id
GROUP BY w.ward_id, w.ward_name;

SELECT ms.service_name, COUNT(i.incident_id) AS total_service_problems_ticket
FROM incident_ticket i
JOIN municipal_servive ms ON i.service_id = ms.service_id
GROUP BY ms.service_id, ms.service_name;

SELECT ts.status_name, COUNT(i.incident_id) AS total
FROM incident_ticket i
JOIN ticket_status ts ON i.status_id = ts.status_id
GROUP BY ts.status_id, ts.status_name;

SELECT priority, COUNT(*) AS total
FROM incident_ticket
GROUP BY priority;