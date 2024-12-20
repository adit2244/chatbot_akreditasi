import os
from flask import Flask, request, jsonify
import pandas as pd

app = Flask(__name__)

# Load corpus data

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
corpus_path = os.path.join(BASE_DIR, "../data/corpus-data-akreditasi.csv")
corpus = pd.read_csv(corpus_path, delimiter=";")


@app.route("/analyze", methods=["POST"])
def analyze():
    user_input = request.form["text"].lower()

    for _, row in corpus.iterrows():
        if row["Kalimat"].lower() in user_input:
            intent = row["Intent"]
            entitas = {
                k: v for k, v in [e.split(":") for e in row["Entitas"].split(",")]
            }
            return jsonify({"intent": intent, "entities": entitas})

    return jsonify({"intent": "unknown", "entities": {}})


if __name__ == "__main__":
    app.run(debug=True, port=8000)
