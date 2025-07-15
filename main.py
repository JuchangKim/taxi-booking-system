# pyright: ignore[reportMissingImports]
# This code is a FastAPI application that allows users to ask questions about a booking history dataset.
# It uses LangChain to create an agent that can process the dataset and respond to queries.

import subprocess
from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_experimental.agents import create_pandas_dataframe_agent
from langchain_community.llms import Together
from langchain.agents.agent_types import AgentType
import pandas as pd
from dotenv import load_dotenv

load_dotenv()

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.get("/generate-summary")
async def generate_summary():
    subprocess.run(["python", "csv_to_summary.py"])
    return {"message": "Booking summary file created."}

@app.get("/")
async def root():
    # Run the CSV to Summary converter on server start or page open
    subprocess.run(["python", "csv_to_summary.py"])
    return {"message": "Booking summary updated. FastAPI Langchain Booking Chatbot is running."}

@app.post("/ask")
async def ask_question(question: str = Form(...)):
    try:
        # Load booking_summary.txt safely with size limit
        with open("booking_summary.txt", "r", encoding="utf-8") as f:
            summary_text = f.read()

        # Limit to first N characters to prevent token overflow
        MAX_CHARS = 4000  # Adjust depending on Together.ai model (Mixtral ~8K tokens input/output combined)
        if len(summary_text) > MAX_CHARS:
            summary_text = summary_text[:MAX_CHARS] + "\n...(summary truncated)\n"

        # Define prompt
        prompt = f"""
            This is the booking history summary:

            {summary_text}

            Based on this, answer the following question:

            {question}
            """

        # Use Together.ai LLM directly
        llm = Together(
            model="mistralai/Mixtral-8x7B-Instruct-v0.1", 
            temperature=0,
            max_tokens=512
        )

        response = llm.invoke(prompt)

        return {"response": response}

    except Exception as e:
        return {"error": str(e)}