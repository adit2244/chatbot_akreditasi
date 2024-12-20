import pandas as pd


class CorpusLoader:
    def __init__(self, csv_path):
        self.corpus = pd.read_csv(csv_path, delimiter=";")

    def find_intent_and_entities(self, user_input):
        user_input = user_input.lower()
        for _, row in self.corpus.iterrows():
            if row["Kalimat"].lower() in user_input:
                intent = row["Intent"]
                entities = {
                    k: v for k, v in [e.split(":") for e in row["Entitas"].split(",")]
                }
                return intent, entities
        return "unknown", {}
