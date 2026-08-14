# Taxi Booking System

A full-stack taxi booking and history management system with CSV export, chatbot integration, Dockerized services, and MySQL backend.

## Assignment Information
**Assignment 2: CabsOnline Booking System**  
**Student Name:** Juchang Kim
**Student ID:** 22180242

## Features

- Customer taxi booking form
- Admin interface for searching and assigning bookings
- CSV export of booking history
- Automatic CSV refresh for chatbot
- Chatbot powered by FastAPI + Ollama
- Docker Compose multi-service architecture
- MySQL database backend
- Clean UI with responsive background image
- Fetch API communication between frontend and backend

## Files Included

- `booking.html` — Customer booking form
- `booking.js` — Client-side validation + Fetch API
- `booking.php` — Inserts booking into MySQL
- `admin.html` — Admin interface
- `admin.js` — Search + assign logic
- `admin.php` — Backend for admin operations
- `style.css` — Unified styling
- `mysqlcommand.txt` — SQL schema + queries
- `dbsettings.php` — Database credentials
- `images/Background.jpg` — Background image
- `export.php` — CSV export + HTML table rendering
- `history.html` — Booking history + chatbot
- `docker-compose.yml` — Multi-service orchestration
- `chatbot/main.py` — FastAPI chatbot
- `chatbot/requirements.txt` — Python dependencies

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP (Apache), FastAPI (Python)
- **Database:** MySQL 8
- **AI:** Ollama (llama3.2:3b)
- **Infrastructure:** Docker Compose
- **Hosting:** AWS Lightsail

## Directory Structure
Code

```text
taxi-booking-system/
│
├── php/
│   ├── booking.html
│   ├── booking.js
│   ├── booking.php
│   ├── admin.html
│   ├── admin.js
│   ├── admin.php
│   ├── export.php
│   ├── history.html
│   ├── dbsettings.php
│   ├── booking_history.csv
│   └── images/
│       └── Background.jpg
│
├── chatbot/
│   ├── main.py
│   ├── requirements.txt
│   └── shared/
│       └── booking_history.csv
│
├── docker-compose.yml
├── mysqlcommand.txt
└── README.md
```

## How It Works

1. Customer submits booking via `booking.html` → `booking.php` inserts into MySQL
2. Admin manages bookings via `admin.html`
3. History page loads booking table using:

- `export.php?mode=update`
- `export.php?mode=html`
4. Chatbot reads refreshed CSV
5. Export button downloads CSV file

## Requirements

- Docker
- Docker Compose
- AWS Lightsail (optional)
- Ollama installed inside container

## License

MIT
