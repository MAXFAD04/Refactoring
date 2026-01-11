FROM python:3.11-slim

# Метаданные
LABEL maintainer="Cassiopeia Team"
LABEL description="Telemetry Generator CLI - Migration from Pascal legacy"

# Рабочая директория
WORKDIR /app

# Установка зависимостей системы
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    && rm -rf /var/lib/apt/lists/*

# Копируем requirements
COPY requirements.txt .

# Установка Python зависимостей
RUN pip install --no-cache-dir -r requirements.txt

# Копируем код приложения
COPY telemetry_generator.py .

# Создаем директорию для CSV
RUN mkdir -p /data/csv

# Делаем скрипт исполняемым
RUN chmod +x telemetry_generator.py

# Переменные окружения по умолчанию
ENV CSV_OUT_DIR=/data/csv \
    GEN_PERIOD_SEC=300 \
    PGHOST=db \
    PGPORT=5432 \
    PGUSER=monouser \
    PGPASSWORD=monopass \
    PGDATABASE=monolith

# Запуск приложения
CMD ["python", "telemetry_generator.py"]

