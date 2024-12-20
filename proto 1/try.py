import requests

url = "http://127.0.0.1:5000/predict"
data = {"question": "Saya ingin tahu data kelulusan untuk tahun 2022"}
response = requests.post(url, json=data)

print(response.json())
