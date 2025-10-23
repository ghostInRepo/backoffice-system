-- Seed data
USE travel_backoffice;
INSERT INTO staff (name,role,contact,email) VALUES
('Maria Santos','Manager','09171234567','maria@example.com'),
('Juan Dela Cruz','Agent','09179876543','juan@example.com');

INSERT INTO tours (title,destination,seats_total,price,start_date,end_date) VALUES
('Japan Winter Tour','Japan',30,120000.00,'2025-12-10','2025-12-20'),
('Bohol Beach Escape','Philippines',20,15000.00,'2025-11-05','2025-11-08');

INSERT INTO bookings (tour_id,staff_id,customer_name,seats,total_price) VALUES
(1,2,'Alice Customer',2,240000.00),
(2,1,'Bob Traveler',1,15000.00);
