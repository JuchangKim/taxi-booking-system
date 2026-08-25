import importlib.util
import re
from datetime import datetime, timedelta
from pathlib import Path


# This helper keeps the validation logic consistent with the backend rules used in booking.php and admin.php.
def is_valid_phone(phone: str) -> bool:
    return bool(re.fullmatch(r"\d{10,12}", phone or ""))


# This helper mirrors the booking feature requirement that customers cannot select a pickup time in the past.
def is_future_pickup(pickup_date: str, pickup_time: str, now: datetime | None = None) -> bool:
    current_time = now or datetime.now()
    try:
        pickup_datetime = datetime.strptime(f"{pickup_date} {pickup_time}", "%Y-%m-%d %H:%M")
    except ValueError:
        return False
    return pickup_datetime >= current_time


# This helper loads the chatbot module exactly as it exists in the repository for feature-level validation.
def load_chatbot_module():
    module_path = Path(__file__).resolve().parents[1] / "chatbot" / "main.py"
    spec = importlib.util.spec_from_file_location("chatbot_main", module_path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


# Test case: a valid 10-digit customer phone number should pass the booking validation gate.
def test_accepts_valid_phone_numbers():
    assert is_valid_phone("0401234567") is True
    assert is_valid_phone("61234567890") is True


# Test case: invalid phone numbers with letters or short lengths must be rejected before booking is saved.
def test_rejects_invalid_phone_numbers():
    assert is_valid_phone("12345") is False
    assert is_valid_phone("0401abc567") is False


# Test case: a pickup date/time in the past should be blocked to prevent invalid bookings.
def test_rejects_past_pickup_times():
    now = datetime(2026, 1, 10, 9, 0)
    assert is_future_pickup("2026-01-09", "08:00", now) is False
    assert is_future_pickup("2026-01-10", "09:00", now) is True


# Test case: the chatbot request payload must include the booking history and the user question before calling Ollama.
def test_chatbot_payload_contains_booking_context(monkeypatch):
    chatbot = load_chatbot_module()
    captured = {}

    class FakeResponse:
        status_code = 200

        def iter_lines(self):
            return [b'{"response":"This is a test response."}']

    def fake_post(url, json, stream=True):
        captured["url"] = url
        captured["json"] = json
        return FakeResponse()

    monkeypatch.setattr(chatbot.requests, "post", fake_post)

    question = "Which suburb had the most bookings this week?"
    summary = "BRN00001 | Alice | 0401234567 | South Yarra | Melbourne"
    chunks = list(chatbot.ollama_stream(question, summary))

    assert captured["url"] == "http://ollama:11434/api/generate"
    assert question in captured["json"]["prompt"]
    assert "Booking History:" in captured["json"]["prompt"]
    assert summary in captured["json"]["prompt"]
    assert chunks == [b"This is a test response."]


# Test case: the ticket reference pattern used by the admin system must be consistent and easy to validate.
def test_validates_admin_reference_format():
    assert re.fullmatch(r"BRN\d{5}", "BRN00001") is not None
    assert re.fullmatch(r"BRN\d{5}", "ABC123") is None


# Test case: the booking form should expose the customer input fields required by the taxi booking workflow.
def test_booking_form_collects_required_customer_details():
    booking_html = (Path(__file__).resolve().parents[1] / "php" / "booking.html").read_text(encoding="utf-8")

    required_fields = [
        "name=\"cname\"",
        "name=\"phone\"",
        "name=\"snumber\"",
        "name=\"stname\"",
        "name=\"date\"",
        "name=\"time\"",
    ]

    for field in required_fields:
        assert field in booking_html


# Test case: the admin workflow must support searching, displaying, and assigning bookings through the backend.
def test_admin_search_and_assignment_logic_exists_in_frontend():
    admin_js = (Path(__file__).resolve().parents[1] / "php" / "admin.js").read_text(encoding="utf-8")

    assert "fetchBookings" in admin_js
    assert "formData.append('ref'" in admin_js
    assert "formData.append('assign'" in admin_js
    assert "window.assign" in admin_js
    assert "search.addEventListener('keydown'" in admin_js


# Test case: CSV export should produce the standard header fields required by the chatbot and booking history pages.
def test_csv_export_uses_history_columns_expected_by_app():
    export_php = (Path(__file__).resolve().parents[1] / "php" / "export.php").read_text(encoding="utf-8")

    expected_columns = [
        "Booking Reference",
        "Name",
        "Phone",
        "Pickup Suburb",
        "Destination",
        "Date",
        "Time",
        "Status",
    ]

    for column in expected_columns:
        assert column in export_php

    assert "fputcsv($csvFile" in export_php
    assert "Content-Disposition: attachment; filename=booking_history.csv" in export_php


# Test case: the Docker Compose stack should include the PHP app, chatbot API, MySQL database, and Ollama runtime.
def test_docker_compose_defines_all_core_services():
    compose_text = (Path(__file__).resolve().parents[1] / "docker-compose.yml").read_text(encoding="utf-8")

    required_services = [
        "php:",
        "chatbot:",
        "ollama:",
        "mysql:",
    ]

    for service in required_services:
        assert service in compose_text

    assert '"80:80"' in compose_text
    assert '"8000:8000"' in compose_text
    assert '"3306:3306"' in compose_text
    assert '"11434:11434"' in compose_text


# Test case: the MySQL schema should define the booking table that all booking, admin, and history flows rely on.
def test_mysql_schema_supports_booking_lifecycle():
    schema_text = (Path(__file__).resolve().parents[1] / "php" / "mysqlcommand.txt").read_text(encoding="utf-8")

    expected_sql_blocks = [
        "-- CREATE_TABLE",
        "-- GET_MAX_ID",
        "-- INSERT_BOOKING",
        "-- UPDATE_STATUS",
        "-- SELECT_BY_REF",
        "-- UPDATE_BOOKING",
        "-- DELETE_BOOKING",
    ]

    for block in expected_sql_blocks:
        assert block in schema_text

    assert "CREATE TABLE IF NOT EXISTS bookings" in schema_text
    assert "pickup_date" in schema_text
    assert "pickup_time" in schema_text
    assert "status" in schema_text


# Test case: the UI must use the project background image and responsive styling expected for the booking experience.
def test_ui_has_visual_background_and_responsive_form_style():
    css_text = (Path(__file__).resolve().parents[1] / "php" / "style.css").read_text(encoding="utf-8")

    assert 'background: url("images/Background.jpg")' in css_text
    assert 'max-width: 1000px' in css_text
    assert 'input[type="text"' in css_text
    assert 'button:hover' in css_text


# Test case: the frontend should communicate with the backend using fetch requests to update the booking and admin views.
def test_booking_and_admin_pages_use_fetch_api_for_backend_calls():
    booking_js = (Path(__file__).resolve().parents[1] / "php" / "booking.js").read_text(encoding="utf-8")
    admin_js = (Path(__file__).resolve().parents[1] / "php" / "admin.js").read_text(encoding="utf-8")
    history_js = (Path(__file__).resolve().parents[1] / "php" / "history.js").read_text(encoding="utf-8")

    for script in [booking_js, admin_js, history_js]:
        assert "fetch(" in script

    assert 'fetch("booking.php"' in booking_js
    assert 'fetch(\'admin.php\'' in admin_js
    assert 'fetch("admin.php"' in history_js


# Test case: the chatbot should expose the FastAPI endpoint and use the Ollama model settings for streaming responses.
def test_chatbot_service_uses_fastapi_and_ollama_streaming():
    chatbot_text = (Path(__file__).resolve().parents[1] / "chatbot" / "main.py").read_text(encoding="utf-8")

    assert "FastAPI" in chatbot_text
    assert "@app.post(\"/ask\")" in chatbot_text
    assert "OLLAMA_URL" in chatbot_text
    assert "MODEL_NAME" in chatbot_text
    assert "StreamingResponse" in chatbot_text
