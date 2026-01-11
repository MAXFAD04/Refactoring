# Telemetry Generator CLI Микросервис

## Описание

Современная замена Pascal legacy кода с полным сохранением функциональности и контракта данных.

### Миграция с Pascal

**До (Pascal legacy):**
- Монолитный код без структуры
- Отсутствие обработки ошибок
- Нет логирования
- Тяжело тестировать
- Мёртвый код (3.2% кодовой базы)

**После (Python CLI):**
- Модульная архитектура с разделением ответственности
- Полная обработка ошибок с retry логикой
- Структурированное логирование (stdout/stderr)
- 100% тестируемый код
- Clean Code принципы

## Архитектура

### Компоненты

1. **Config** - конфигурация из env переменных
2. **TelemetryGenerator** - бизнес-логика генерации данных
3. **CSVWriter** - запись CSV файлов
4. **DatabaseLoader** - загрузка в PostgreSQL
5. **TelemetryService** - orchestrator всех компонентов

### Контракт данных (CSV)

```csv
recorded_at,voltage,temp,source_file
2025-01-09 12:34:56,8.45,-12.34,telemetry_20250109_123456.csv
```

**Поля:**
- `recorded_at` - время записи (TIMESTAMP)
- `voltage` - напряжение в диапазоне 3.2-12.6 В
- `temp` - температура в диапазоне -50 до +80 °C
- `source_file` - имя CSV файла

### Таблица PostgreSQL

```sql
CREATE TABLE telemetry_legacy (
    id BIGSERIAL PRIMARY KEY,
    recorded_at TIMESTAMP NOT NULL,
    voltage NUMERIC(10, 2),
    temp NUMERIC(10, 2),
    source_file TEXT,
    inserted_at TIMESTAMP NOT NULL DEFAULT now()
);
```

## Конфигурация

### Переменные окружения

| Переменная | Описание | По умолчанию |
|-----------|----------|--------------|
| `CSV_OUT_DIR` | Директория для CSV файлов | `/data/csv` |
| `GEN_PERIOD_SEC` | Период генерации (секунды) | `300` |
| `PGHOST` | PostgreSQL хост | `db` |
| `PGPORT` | PostgreSQL порт | `5432` |
| `PGUSER` | PostgreSQL пользователь | `monouser` |
| `PGPASSWORD` | PostgreSQL пароль | `monopass` |
| `PGDATABASE` | PostgreSQL база данных | `monolith` |

## Запуск

### Docker

```bash
docker build -t telemetry-generator .
docker run -v /data/csv:/data/csv telemetry-generator
```

### Локально

```bash
pip install -r requirements.txt
python telemetry_generator.py
```

## Логирование

Все логи пишутся в stdout/stderr для интеграции с Docker:

```
2025-01-09 12:34:56 [INFO] Telemetry Generator CLI Микросервис v1.0
2025-01-09 12:34:56 [INFO] Initializing Telemetry Generator Service...
2025-01-09 12:34:56 [INFO] Successfully connected to PostgreSQL
2025-01-09 12:34:56 [INFO] CSV file created: /data/csv/telemetry_20250109_123456.csv
2025-01-09 12:34:56 [INFO] Successfully loaded data from /data/csv/telemetry_20250109_123456.csv to PostgreSQL
2025-01-09 12:34:56 [INFO] Telemetry cycle completed: voltage=8.45V, temp=-12.34°C
```

## Преимущества миграции

1. **Читаемость** - чистый, понятный код
2. **Надежность** - полная обработка ошибок
3. **Наблюдаемость** - структурированные логи
4. **Тестируемость** - модульная архитектура
5. **Производительность** - использование PostgreSQL COPY для быстрой загрузки
6. **Масштабируемость** - легко добавлять новые источники данных

## Паттерны проектирования

- **Dependency Injection** - через Config
- **Single Responsibility** - каждый класс делает одно дело
- **Strategy Pattern** - разделение генерации, записи и загрузки
- **Orchestrator Pattern** - TelemetryService координирует компоненты

