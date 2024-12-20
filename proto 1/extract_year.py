import re
from datetime import datetime

# Fungsi untuk menangkap tahun dengan logika baru


def extract_year_from_question_v2(question):
    """
    Meningkatkan logika untuk menangkap angka tahun secara kontekstual,
    menghindari hasil parsial seperti '20'.
    """
    # Tangkap angka tahun dengan konteks seperti "tahun", "angkatan", dll.
    matches = re.findall(
        r'(?:tahun|angkatan|hingga|sampai|dari)?\s*(\d{4})', question.lower())
    # Validasi hanya angka tahun yang logis (1900–tahun saat ini)
    current_year = datetime.now().year
    return [match for match in matches if 1900 <= int(match) <= current_year]

# Fungsi untuk menyesuaikan ekstraksi tahun berdasarkan intent


def extract_year_for_intent_v4(question, intent):
    """
    Versi lebih teliti untuk memperbaiki kesalahan penangkapan tahun menjadi '20'.
    Menyesuaikan format dengan aturan intent yang dijelaskan.
    """
    current_year = datetime.now().year
    tahun_list = extract_year_from_question_v2(question)

    # Menangani frasa dinamis
    if "tahun kemarin" in question.lower():
        return [str(current_year - 1)]
    elif "dua tahun terakhir" in question.lower():
        return [str(current_year - 2), str(current_year - 1), str(current_year)]

    # Penyesuaian berdasarkan intent
    if intent == "permintaan_data_kelulusan":
        # Ekstraksi semester_lulus (tahun+ganjil/genap)
        semesters = []
        for tahun in tahun_list:
            if re.match(r'^\d{4}$', tahun):  # Validasi tahun format YYYY
                semesters.append(f"{tahun}1")  # Ganjil
                semesters.append(f"{tahun}2")  # Genap
        return semesters

    elif intent in ["permintaan_data_kegiatan", "permintaan_data_penelitian"]:
        # Ekstraksi tahun atau tanggal lengkap (DD/MM/YYYY)
        full_dates = re.findall(
            r'\d{2}/\d{2}/\d{4}', question)  # Format lengkap
        if full_dates:
            return full_dates
        # Tahun format YYYY
        return [tahun for tahun in tahun_list if re.match(r'^\d{4}$', tahun)]

    elif intent == "permintaan_data_ipk":
        # Gunakan tahun sebagai angkatan
        return [tahun for tahun in tahun_list if re.match(r'^\d{4}$', tahun)]

    # Default fallback
    return [tahun for tahun in tahun_list if re.match(r'^\d{4}$', tahun)]


# Contoh pertanyaan untuk uji coba
questions = [
    ("Berikan data kegiatan mahasiswa tahun 2022", "permintaan_data_kegiatan"),
    ("Berikan data lulusan tahun 2020", "permintaan_data_kelulusan"),
    ("Berikan data IPK mahasiswa jurusan Akuntansi angkatan 2019", "permintaan_data_ipk"),
    ("Berikan data penelitian dua tahun terakhir", "permintaan_data_penelitian")
]

# Uji coba
for question, intent in questions:
    print(f"Pertanyaan: {question}")
    years_extracted = extract_year_for_intent_v4(question, intent)
    print(f"Intent: {intent}, Tahun diekstrak: {years_extracted}\n")
