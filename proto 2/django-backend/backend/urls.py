from django.contrib import admin
from django.urls import path, include
from django.http import HttpResponse


def index(request):
    return HttpResponse("Django server is running.")


urlpatterns = [
    path("", index),
    path("admin/", admin.site.urls),
    path("analyze/", include("analyzer.urls")),
]
