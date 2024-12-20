import json
import re
from flask import Flask, request, jsonify

app = Flask(__name__)

# Load intent configuration from JSON file
with open('intent_entities.json', 'r') as file:
    intent_config = json.load(file)

intent = intent_config["intent"]

# Helper function to extract years from date formats


def extract_year(date_str):
    match = re.search(r'\b(\d{4})\b', date_str)
    return match.group(1) if match else None

# Helper function to determine if a keyword exists in a question


def has_keyword(question, keywords):
    return any(keyword in question.lower() for keyword in keywords)

# Main function to determine intent and entities


def get_intent_and_entities(question):
    result = {
        "intent": None,
        "entities": {},
        "response": None
    }

    # Check greetings or introduction first
    if has_keyword(question, intent["sapaan"]["keywords"]):
        result["intent"] = "sapaan"
        result["response"] = intent["sapaan"]["responses"][0]
        return result

    if has_keyword(question, intent["perkenalan"]["keywords"]):
        result["intent"] = "perkenalan"
        result["response"] = intent["perkenalan"]["responses"][0]
        return result

    # Priority-based intent matching (specific keywords first)
    if has_keyword(question, intent["permintaan_data_penelitian"]["keywords"]):
        result["intent"] = "permintaan_data_penelitian"
    elif has_keyword(question, intent["permintaan_data_kegiatan"]["keywords"]):
        result["intent"] = "permintaan_data_kegiatan"
    elif has_keyword(question, intent["permintaan_data_ipk"]["keywords"]):
        result["intent"] = "permintaan_data_ipk"
    elif has_keyword(question, intent["permintaan_data_kelulusan"]["keywords"]):
        result["intent"] = "permintaan_data_kelulusan"
    else:
        result["error"] = "Unable to determine intent"
        return result

    # Extract entities
    intent_name = result["intent"]
    intent_data = intent[intent_name]

    for entity_name, entity_values in intent_data.get("entities", {}).items():
        if isinstance(entity_values, list):
            matches = []
            for item in entity_values:
                if isinstance(item, dict):  # Handle entities with additional attributes
                    if any(str(val).lower() in question.lower() for val in item.values()):
                        matches.append(item)
                elif str(item).lower() in question.lower():
                    matches.append(item)
            if matches:
                result["entities"][entity_name] = matches

    # Custom entity handling for years and filtering entities for permintaan_data_kelulusan
    if intent_name == "permintaan_data_kelulusan":
        # Check if year is mentioned and filter accordingly
        semesters = result["entities"].get("semester_lulus", [])
        years = [sem["tahun"] for sem in semesters if "tahun" in sem]

        # If year is mentioned but not angkatan, extract semester_lulus
        if not result["entities"].get("angkatan") and years:
            result["entities"]["semester_lulus"] = [{
                "kode": f"{years[0]}1",  # Ganjil
                "tahun": years[0],
                "semester": "Ganjil"
            }, {
                "kode": f"{years[0]}2",  # Genap
                "tahun": years[0],
                "semester": "Genap"
            }]
            result["entities"]["tahun"] = years

        # If angkatan is mentioned, remove semester_lulus
        angkatan = result["entities"].get("angkatan", [])
        if angkatan:
            result["entities"]["angkatan"] = angkatan
            result["entities"].pop("semester_lulus", None)

    elif intent_name == "permintaan_data_kegiatan":
        match = re.search(r'\b(\d{4})\b', question)
        if match:
            result["entities"]["tahun"] = [match.group(1)]

    elif intent_name == "permintaan_data_penelitian":
        match = re.search(r'\b(\d{4})\b', question)
        if match:
            result["entities"]["tahun"] = [match.group(1)]

    elif intent_name == "permintaan_data_ipk":
        angkatan = result["entities"].get("angkatan", [])
        if angkatan:
            result["entities"]["tahun"] = angkatan

        # Remove irrelevant entities for IPK intent
        result["entities"].pop("semester_lulus", None)

    return result


@app.route('/predict', methods=['POST'])
def predict_intent():
    data = request.json
    question = data.get("question", "")

    if not question:
        return jsonify({"error": "No question provided"}), 400

    result = get_intent_and_entities(question)

    # Tambahkan default response jika response kosong
    response = result.get("response")
    if not response:
        response = f"Intent terdeteksi: {result.get('intent')}, tapi tidak ada respons langsung."

    print(f"DEBUG: Intent detected: {result.get('intent')}")
    print(f"DEBUG: Entities extracted: {result.get('entities')}")
    print(f"DEBUG: Response: {response}")

    return jsonify({
        "intent": result.get("intent"),
        "entities": result.get("entities"),
        "response": response
    })


if __name__ == '__main__':
    app.run(debug=True)
