from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
import pandas as pd
import os

# Path Absolut ke File CSV
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CORPUS_PATH = os.path.join(BASE_DIR, "data", "corpus-data-akreditasi.csv")


@csrf_exempt
def analyze_input(request):
    if request.method == "POST":
        try:
            # Baca input dari request
            user_input = request.POST.get("text", "").lower()

            # Baca data dari CSV
            corpus = pd.read_csv(CORPUS_PATH, delimiter=";")

            # Cari intent dan entitas
            for _, row in corpus.iterrows():
                if row["Kalimat"].lower() in user_input:
                    intent = row["Intent"]
                    entities = {
                        k: v
                        for k, v in [e.split(":") for e in row["Entitas"].split(",")]
                    }
                    return JsonResponse({"intent": intent, "entities": entities})

            # Jika tidak ditemukan
            return JsonResponse({"intent": "unknown", "entities": {}})
        except Exception as e:
            return JsonResponse({"error": str(e)}, status=500)
    return JsonResponse({"error": "Invalid request method"}, status=405)
