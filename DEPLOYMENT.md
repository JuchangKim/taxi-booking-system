# Deployment Guide

This document explains how to deploy the Taxi Booking System on AWS Lightsail or any Linux server.

## 1. Install Dependencies

```bash
sudo apt update
sudo apt install docker.io docker-compose -y
```

## 2. Clone Repository

```bash
git clone https://github.com/JuchangKim/taxi-booking-system.git
cd taxi-booking-system
```

## 3. Start Services

```bash
sudo docker compose up -d --build
```

## 4. Open Firewall Ports

Required ports:

| Port | Purpose               |
|------|------------------------|
| 80   | PHP Web UI             |
| 8000 | Chatbot API            |
|11434 | Ollama Model Server    |

Lightsail → Networking → Add rules:

```text
Port: 80   Source: 0.0.0.0/0
Port: 8000 Source: 0.0.0.0/0
Port: 11434 Source: 0.0.0.0/0
```

## 5. Verify Containers

```bash
sudo docker compose ps
sudo docker compose logs php --tail=50
sudo docker compose logs chatbot --tail=50
sudo docker compose logs ollama --tail=50
```

## 6. Test Chatbot

```bash
curl -X POST http://YOUR_IP:8000/ask -F "question=Hello"
```

## 7. Update Deployment

```bash
git pull
sudo docker compose down
sudo docker compose up -d --build
```

## 8. Backup Database

```bash
sudo docker exec -it taxi-booking-system-mysql-1 mysqldump -uuser -ppassword taxi_booking > backup.sql
```

## 9. Restore Database

```bash
mysql -uuser -ppassword taxi_booking < backup.sql
```

## Deployment Complete
