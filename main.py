from fastapi import FastAPI, Form
from fastapi.middleware.cors import CORSMiddleware
from langchain_experimental.agents import create_pandas_dataframe_agent
from langchain.llms import OpenAI
import pandas as pd
import os

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/ask")
async def ask_question(question: str = Form(...)):
    try:
        df = pd.read_csv("booking_history.csv")
        agent = create_pandas_dataframe_agent(OpenAI(temperature=0), df, verbose=False)
        result = agent.run(question)
        return {"response": result}
    except Exception as e:
        return {"error": str(e)}