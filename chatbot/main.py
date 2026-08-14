from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import StreamingResponse
import requests
import json
import os

app = FastAPI()

OLLAMA_URL = os.getenv("OLLAMA_URL", "http://ollama:11434/api/generate")
MODEL_NAME = os.getenv("MODEL_NAME")

# Allow CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # For production, restrict this
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

def ollama_stream(question, summary_text):
    prompt = f"""
You are a taxi booking assistant.

Booking History:

{summary_text}

User question: {question}

Answer concisely based on the booking data.
"""

    payload = {
        "model": MODEL_NAME,
        "prompt": prompt,
        "stream": True
    }

    try:
        response = requests.post(OLLAMA_URL, json=payload, stream=True)
    except Exception as e:
        yield f"Error connecting to Ollama: {str(e)}".encode("utf-8")
        return

    if response.status_code != 200:
        yield f"Error: {response.text}".encode("utf-8")
        return

    # STREAM FIX: yield bytes, not strings
    for line in response.iter_lines():
        if line:
            try:
                data = json.loads(line.decode("utf-8"))
                token = data.get("response", "")
                yield token.encode("utf-8")   # <-- FIXED
            except json.JSONDecodeError:
                continue


@app.post("/ask")
async def ask_question(question: str = Form(...)):
    # Read booking history CSV
    try:
        with open("/app/shared/booking_history.csv", "r", encoding="utf-8") as f:
            summary_text = f.read()
    except Exception as e:
        return StreamingResponse(
            iter([f"Error reading booking history: {str(e)}".encode("utf-8")]),
            media_type="text/plain"
        )

    # STREAM FIX: enable chunked encoding
    return StreamingResponse(
        ollama_stream(question, summary_text),
        media_type="text/plain",
        headers={"Transfer-Encoding": "chunked"}
    )
