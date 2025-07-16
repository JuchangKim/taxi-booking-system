from fastapi import FastAPI, Form
import requests
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()

OLLAMA_URL = "http://ollama:11434/api/generate"
MODEL_NAME = "mistral"


# Allow CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Allow all origins (use ["http://localhost:8080"] for more security)
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/ask")
async def ask_question(question: str = Form(...)):
    with open("booking_summary.txt", "r", encoding="utf-8") as f:
        summary_text = f.read()

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
        "stream": False
    }

    response = requests.post(OLLAMA_URL, json=payload)

    if response.status_code != 200:
        return {"error": response.text}

    result = response.json()
    generated = result.get("response", "").strip()

    if not generated:
        return {"error": "Ollama returned empty output."}

    return {"response": generated}
