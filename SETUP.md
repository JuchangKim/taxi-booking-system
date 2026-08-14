# Setup Guide

This document explains how to set up the Taxi Booking System locally for development.

## 1. Requirements

- Docker
- Docker Compose
- Python 3.10+
- Ollama installed (optional)

## 2. Clone Project

```bash
git clone https://github.com/JuchangKim/taxi-booking-system.git
cd taxi-booking-system
```

## 3. Configure Environment Variables

Create a `.env` file in the project root with the following values:

```env
DB_HOST=mysql
DB_USER=user
DB_PASSWORD=password
DB_NAME=taxi_booking
MODEL_NAME=mistral/mistral-7b-instruct
```

## 4. Start Docker Services

```bash
sudo docker compose up -d --build
```

## 5. Verify MySQL

```bash
sudo docker exec -it taxi-booking-system-mysql-1 mysql -uuser -ppassword taxi_booking
```

## 6. Insert Dummy Data

Use the SQL in `mysqlcommand.txt` or run a dummy data generator to populate the database.

## 7. Test PHP

Open in your browser:

```text
http://localhost/history.html
```

## 8. Test Chatbot

```bash
curl -X POST http://localhost:8000/ask -F "question=Hello"
```

## 9. Export CSV

Use the **Export CSV** button on the history page to download booking history.

## Setup Complete
