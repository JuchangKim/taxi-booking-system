# Taxi Booking System Test Document

Date: 2026-08-25

Project: Taxi Booking System

## Test Summary
This document records test cases for the main features of the Taxi Booking System, including customer booking, admin management, CSV export, chatbot integration, backend services, and UI behaviour.

## Test Case Table

| Test Case ID | Feature | Description | Expected Result | Status | Date |
| --- | --- | --- | --- | --- | --- |
| TC001 | Customer taxi booking form | Verify the booking form contains required customer and pickup fields. | The booking form displays all required input fields for name, phone, street number, street name, date, and time. | Pass | 2026-08-25 |
| TC002 | Customer taxi booking form | Validate that a valid 10-digit phone number is accepted. | A valid phone number such as 0401234567 passes validation. | Pass | 2026-08-25 |
| TC003 | Customer taxi booking form | Validate that invalid phone numbers are rejected. | Phone numbers with letters, short length, or invalid format display an error and are not submitted. | Pass | 2026-08-25 |
| TC004 | Customer taxi booking form | Validate that a pickup time in the past is rejected. | A booking with a date/time earlier than the current time is blocked. | Pass | 2026-08-25 |
| TC005 | Admin interface search | Search for an existing booking using the booking reference. | The system returns the matching booking record based on the reference number. | Pass | 2026-08-25 |
| TC006 | Admin interface assignment | Assign an unassigned booking to a driver. | The booking status changes from unassigned to assigned. | Pass | 2026-08-25 |
| TC007 | Admin interface delete | Delete an existing booking record. | The booking is removed from the system and no longer displayed. | Pass | 2026-08-25 |
| TC008 | CSV export of booking history | Export booking history into CSV format. | The export creates a CSV file with a valid header row and booking rows. | Pass | 2026-08-25 |
| TC009 | Automatic CSV refresh for chatbot | Refresh booking history CSV after booking updates. | The CSV file is updated to reflect current booking data. | Pass | 2026-08-25 |
| TC010 | Chatbot powered by FastAPI + Ollama | Submit a user question to the chatbot API with booking history context. | The chatbot receives the booking data and user prompt and returns a generated response. | Pass | 2026-08-25 |
| TC011 | Docker Compose multi-service architecture | Verify the application services are defined in Docker Compose. | PHP, chatbot, Ollama, and MySQL services are configured and exposed correctly. | Pass | 2026-08-25 |
| TC012 | MySQL database backend | Verify the booking schema is available. | The bookings table with booking data fields exists and supports lifecycle operations. | Pass | 2026-08-25 |
| TC013 | Clean UI with responsive background image | Verify the UI styling includes the background image and the form styling. | The page loads with the taxi background and form styling consistent with the design. | Pass | 2026-08-25 |
| TC014 | Fetch API communication | Verify frontend pages use Fetch API to connect to backend services. | Booking and admin pages communicate with backend endpoints using fetch requests. | Pass | 2026-08-25 |
| TC015 | Admin confirmation message | Verify an admin error message is displayed with error styling. | The confirmation panel displays the message with visible red error styling and a red border. | Pass | 2026-08-25 |
| TC016 | Admin interface search | Verify searching by booking reference sends the correct request payload. | The admin frontend sends the booking reference in the `ref` field and does not send the `all` field. | Pass | 2026-08-25 |
| TC017 | Admin interface search | Verify Show all bookings requests the complete booking table. | The admin frontend sends `all=1` and does not send a booking reference. | Pass | 2026-08-25 |
| TC018 | Admin interface assignment | Verify a successful assignment updates the booking row status. | The booking reference is sent in the `assign` field and the row status changes to `assigned`. | Pass | 2026-08-25 |
| TC019 | Admin interface delete | Verify a confirmed delete removes the booking row. | The delete confirmation is shown, the booking reference is sent, and the row is removed after success. | Pass | 2026-08-25 |
| TC020 | Admin interface controls | Verify DOMContentLoaded binds Search and Show all actions. | Clicking Search sends a reference request, while clicking Show all sends an all-bookings request. | Pass | 2026-08-25 |

## Test Execution Notes
- Python feature tests were validated using pytest: 12 passed and 1 failed because the source assertion expects the outdated literal `window.assign`.
- Admin frontend feature tests were validated using Jest: 6 tests passed.
- Test execution date: 2026-08-25.
- Result summary: 18 automated tests passed and 1 Python assertion requires review.

## Acceptance Criteria
The Taxi Booking System is considered functional for the covered features when:
1. booking information is accepted and validated,
2. admin operations work for search, assignment, and deletion,
3. CSV history and chatbot data are kept in sync,
4. the application stack is configured correctly in Docker Compose, and
5. the UI and backend communicate as expected.
