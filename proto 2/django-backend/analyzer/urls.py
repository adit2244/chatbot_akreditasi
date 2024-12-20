from django.urls import path
from .views import analyze_input

urlpatterns = [
    path("", analyze_input, name="analyze-input"),
]
