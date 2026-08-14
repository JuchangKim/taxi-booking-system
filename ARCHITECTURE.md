# System Architecture

This document describes the architecture of the Taxi Booking System.

## Overview

The system consists of four major components:

1. PHP Web Server (Apache)
2. MySQL Database
3. FastAPI Chatbot Service
4. Ollama Model Server

All components run inside Docker containers orchestrated by Docker Compose.

## Architecture Diagram (text)

```text
+------------------+       +------------------+
|   PHP / Apache   | <---> |     MySQL DB     |
|  booking.php     |       |  bookings table  |
|  admin.php       |       +------------------+
|  export.php      |
+---------+--------+
          |
          | CSV (volume mount)
          v
+------------------+       +------------------+
|   FastAPI        | <---> |     Ollama       |
|   chatbot        |       |  Llama3.2:3b     |
+------------------+       +------------------+
```

## Data Flow

1. User submits booking → PHP inserts into MySQL
2. Admin assigns booking → PHP updates MySQL
3. History page loads table via `export.php`
4. CSV is written to shared volume
5. Chatbot reads CSV and answers questions

## Docker Services

### php

- Runs Apache + PHP
- Serves UI
- Writes CSV

### mysql

- Stores bookings
- Provides SQL queries

### chatbot

- FastAPI service
- Reads CSV
- Calls Ollama

### ollama

- Runs Llama model
- Generates responses

## Volumes

```text
./php:/var/www/html
./php/booking_history.csv:/app/shared/booking_history.csv
```

## Ports

- PHP: 80
- Chatbot: 8000
- Ollama: 11434

## Security Notes

- DB credentials loaded via environment variables
- CSV shared as read-only to chatbot
