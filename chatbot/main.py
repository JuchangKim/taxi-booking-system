from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import StreamingResponse
import requests
import json

app = FastAPI()

OLLAMA_URL = "http://ollama:11434/api/generate"
MODEL_NAME = "tinyllama"

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

    response = requests.post(OLLAMA_URL, json=payload, stream=True)

    if response.status_code != 200:
        yield f"Error: {response.text}"
        return

    for line in response.iter_lines():
        if line:
            try:
                data = json.loads(line.decode('utf-8'))
                token = data.get("response", "")
                yield token
            except json.JSONDecodeError:
                continue  # Ignore malformed lines (Ollama sometimes sends keep-alive)

@app.post("/ask")
async def ask_question(question: str = Form(...)):
    with open("booking_summary.txt", "r", encoding="utf-8") as f:
        summary_text = f.read()

    return StreamingResponse(ollama_stream(question, summary_text), media_type="text/plain")
