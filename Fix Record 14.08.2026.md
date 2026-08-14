# Fix Record 14.08.2026

Taxi Booking System — Configuration & Fix Summary

A complete record of all fixes applied to make the system stable, including Docker, PHP, MySQL, FastAPI, and Lightsail configuration.

## 1. Fixing CSV Export — “headers already sent”

**Problem**
export.php printed raw CSV instead of downloading it.

**Cause**
`dbsettings.php` contained a closing `?>` which produced invisible whitespace output.

**Fix**
Rewrite `dbsettings.php` with no closing tag:

```php
<?php
$host = getenv('DB_HOST') ?: 'mysql';
$user = getenv('DB_USER') ?: 'user';
$pswd = getenv('DB_PASSWORD') ?: 'password';
$dbnm = getenv('DB_NAME') ?: 'taxi_booking';
```

## 2. Fixing CSV Export — Permission Denied

**Problem**
`export.php` failed with:

```text
fopen(/var/www/html/booking_history.csv): Permission denied
```

**Cause**
Docker volume mounted `./php` → `/var/www/html`.
Inside container, files were owned by `root`, but Apache runs as `www-data`.

**Fix**
Run inside PHP container:

```bash
sudo docker exec -it taxi-booking-system-php-1 chown www-data:www-data /var/www/html/booking_history.csv
sudo docker exec -it taxi-booking-system-php-1 chmod 666 /var/www/html/booking_history.csv
```

Recommended full fix:

```bash
sudo docker exec -it taxi-booking-system-php-1 chown -R www-data:www-data /var/www/html
sudo docker exec -it taxi-booking-system-php-1 chmod -R 775 /var/www/html
```

## 3. Permanent Fix — Updated `docker-compose.yml`

Run PHP container as `www-data`:

```yaml
php:
  build: ./php
  ports:
    - "80:80"
  volumes:
    - ./php:/var/www/html
  user: "www-data"
```

This ensures:

- Apache can write CSV
- No more permission denied
- No more manual chown

## 4. Fixing FastAPI Streaming (Chatbot)

**Problem**
Chatbot returned no output when calling:

`POST /ask`

**Cause**
FastAPI streaming requires bytes, not strings.

**Fix**
Convert tokens to bytes:

```python
yield token.encode("utf-8")
```

Add chunked encoding:

```python
return StreamingResponse(
    ollama_stream(question, summary_text),
    media_type="text/plain",
    headers={"Transfer-Encoding": "chunked"}
)
```

## 5. Fixing Lightsail Firewall (Chatbot Port 8000)

**Problem**
External curl failed:

```text
curl: (28) Failed to connect to 54.79.89.195:8000
```

**Fix**
Open port 8000 in Lightsail:

- Source: `0.0.0.0/0`
- Firewall rule:

```text
Application: Custom
Protocol: TCP
Port: 8000
Source: 0.0.0.0/0
```

## 6. Dummy Data Generation (MySQL)

Insert realistic sample bookings:

```sql
INSERT INTO bookings (
    ref, cname, phone, unumber, snumber, stname,
    sbname, dsbname, pickup_date, pickup_time, created_at, status
)
VALUES
('BRN00001', 'Alice Kim', '0213456789', '12A', '45', 'Queen St', 'Auckland CBD', 'Ponsonby', '2026-08-15', '10:30:00', NOW(), 'unassigned'),
('BRN00002', 'Bob Smith', '0279876543', NULL, '88', 'K Road', 'Newton', 'Mt Eden', '2026-08-15', '11:00:00', NOW(), 'assigned');
```

Bulk dummy data (100 rows):

```sql
SET @i = 1;
WHILE @i <= 100 DO
  INSERT INTO bookings (
      ref, cname, phone, unumber, snumber, stname,
      sbname, dsbname, pickup_date, pickup_time, created_at, status
  )
  VALUES (
      CONCAT('BRN', LPAD(@i, 5, '0')),
      CONCAT('Customer ', @i),
      CONCAT('021', LPAD(@i, 7, FLOOR(RAND()*9))),
      NULL,
      FLOOR(RAND()*200),
      'Sample Street',
      'Auckland',
      'Destination',
      CURDATE(),
      SEC_TO_TIME(FLOOR(RAND()*86400)),
      NOW(),
      IF(RAND() > 0.5, 'assigned', 'unassigned')
  );
  SET @i = @i + 1;
END WHILE;
```

## 7. History Page Workflow

Your `history.html` workflow is correct:

- `export.php?mode=update` → refresh CSV
- `export.php?mode=html` → load table

Chatbot reads updated CSV

Export button downloads CSV

No changes needed.

## 8. Useful Commands

Restart Docker

```bash
sudo docker compose down
sudo docker compose up -d --build
```

Restart PHP container

```bash
sudo docker compose restart php
```

Check container logs

```bash
sudo docker compose logs php --tail=50
sudo docker compose logs chatbot --tail=50
sudo docker compose logs ollama --tail=50
```

Test chatbot

```bash
curl -X POST http://54.79.89.195:8000/ask -F "question=Hello"
```

## Final Notes

This summary captures all fixes applied to:

- Docker
- PHP
- MySQL
- FastAPI
- Lightsail
- CSV export
- Chatbot streaming
