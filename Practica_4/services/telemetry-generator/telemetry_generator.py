#!/usr/bin/env python3
"""
Telemetry Generator CLI Микросервис
Замена Pascal legacy кода с сохранением контракта и функциональности
Генерирует CSV с телеметрией и загружает в PostgreSQL
"""
import os
import sys
import time
import random
import logging
from datetime import datetime
from pathlib import Path
from typing import Optional

import psycopg2
from psycopg2 import sql


# Настройка логирования (stdout/stderr как требуется)
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)


class Config:
    """Конфигурация из переменных окружения"""
    
    def __init__(self):
        self.csv_out_dir = os.getenv('CSV_OUT_DIR', '/data/csv')
        self.gen_period_sec = int(os.getenv('GEN_PERIOD_SEC', '300'))
        
        # PostgreSQL настройки
        self.pg_host = os.getenv('PGHOST', 'db')
        self.pg_port = int(os.getenv('PGPORT', '5432'))
        self.pg_user = os.getenv('PGUSER', 'monouser')
        self.pg_password = os.getenv('PGPASSWORD', 'monopass')
        self.pg_database = os.getenv('PGDATABASE', 'monolith')
    
    def get_connection_string(self) -> str:
        """Получить строку подключения к PostgreSQL"""
        return f"host={self.pg_host} port={self.pg_port} user={self.pg_user} password={self.pg_password} dbname={self.pg_database}"


class TelemetryGenerator:
    """Генератор телеметрии - основная бизнес-логика"""
    
    @staticmethod
    def generate_random_voltage() -> float:
        """Генерирует случайное напряжение в диапазоне 3.2-12.6 В"""
        return random.uniform(3.2, 12.6)
    
    @staticmethod
    def generate_random_temperature() -> float:
        """Генерирует случайную температуру в диапазоне -50 до +80 °C"""
        return random.uniform(-50.0, 80.0)
    
    def generate_csv_row(self, filename: str) -> dict:
        """
        Генерирует одну строку телеметрии
        
        Контракт данных (совместимость с Pascal версией):
        - recorded_at: timestamp записи
        - voltage: напряжение (В)
        - temp: температура (°C)
        - source_file: имя файла-источника
        """
        return {
            'recorded_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'voltage': round(self.generate_random_voltage(), 2),
            'temp': round(self.generate_random_temperature(), 2),
            'source_file': filename
        }


class CSVWriter:
    """Запись CSV файлов"""
    
    def __init__(self, output_dir: str):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
    
    def write_telemetry(self, data: dict) -> str:
        """
        Записывает телеметрию в CSV файл
        Возвращает полный путь к файлу
        """
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f'telemetry_{timestamp}.csv'
        filepath = self.output_dir / filename
        
        try:
            with open(filepath, 'w', encoding='utf-8') as f:
                # Заголовок CSV (контракт)
                f.write('recorded_at,voltage,temp,source_file\n')
                # Данные
                f.write(f"{data['recorded_at']},{data['voltage']},{data['temp']},{data['source_file']}\n")
            
            logger.info(f"CSV file created: {filepath}")
            return str(filepath)
        
        except Exception as e:
            logger.error(f"Failed to write CSV file: {e}")
            raise


class DatabaseLoader:
    """Загрузка данных в PostgreSQL"""
    
    def __init__(self, config: Config):
        self.config = config
        self.connection = None
    
    def connect(self) -> None:
        """Подключение к базе данных с retry логикой"""
        max_retries = 3
        retry_delay = 2
        
        for attempt in range(max_retries):
            try:
                self.connection = psycopg2.connect(
                    self.config.get_connection_string()
                )
                logger.info("Successfully connected to PostgreSQL")
                return
            except psycopg2.OperationalError as e:
                if attempt < max_retries - 1:
                    logger.warning(f"Connection attempt {attempt + 1} failed, retrying in {retry_delay}s...")
                    time.sleep(retry_delay)
                else:
                    logger.error(f"Failed to connect to PostgreSQL after {max_retries} attempts: {e}")
                    raise
    
    def ensure_table_exists(self) -> None:
        """Создает таблицу telemetry_legacy если её нет"""
        create_table_sql = """
        CREATE TABLE IF NOT EXISTS telemetry_legacy (
            id BIGSERIAL PRIMARY KEY,
            recorded_at TIMESTAMP NOT NULL,
            voltage NUMERIC(10, 2),
            temp NUMERIC(10, 2),
            source_file TEXT,
            inserted_at TIMESTAMP NOT NULL DEFAULT now()
        )
        """
        
        try:
            with self.connection.cursor() as cursor:
                cursor.execute(create_table_sql)
                self.connection.commit()
            logger.info("Table telemetry_legacy ensured")
        except Exception as e:
            logger.error(f"Failed to create table: {e}")
            self.connection.rollback()
            raise
    
    def load_from_csv(self, filepath: str) -> None:
        """
        Загружает данные из CSV в PostgreSQL используя COPY
        Обеспечивает высокую производительность загрузки
        """
        try:
            with self.connection.cursor() as cursor:
                with open(filepath, 'r', encoding='utf-8') as f:
                    # Пропускаем заголовок
                    next(f)
                    # COPY команда для быстрой загрузки
                    cursor.copy_from(
                        f,
                        'telemetry_legacy',
                        sep=',',
                        columns=('recorded_at', 'voltage', 'temp', 'source_file')
                    )
                self.connection.commit()
            
            logger.info(f"Successfully loaded data from {filepath} to PostgreSQL")
        
        except Exception as e:
            logger.error(f"Failed to load CSV to PostgreSQL: {e}")
            self.connection.rollback()
            raise
    
    def close(self) -> None:
        """Закрывает соединение с БД"""
        if self.connection:
            self.connection.close()
            logger.info("PostgreSQL connection closed")


class TelemetryService:
    """
    Основной сервис для генерации и загрузки телеметрии
    Orchestrator паттерн для координации всех компонентов
    """
    
    def __init__(self, config: Config):
        self.config = config
        self.generator = TelemetryGenerator()
        self.csv_writer = CSVWriter(config.csv_out_dir)
        self.db_loader = DatabaseLoader(config)
    
    def initialize(self) -> None:
        """Инициализация сервиса"""
        logger.info("Initializing Telemetry Generator Service...")
        logger.info(f"CSV output directory: {self.config.csv_out_dir}")
        logger.info(f"Generation period: {self.config.gen_period_sec} seconds")
        
        # Подключение к БД
        self.db_loader.connect()
        self.db_loader.ensure_table_exists()
        
        logger.info("Service initialized successfully")
    
    def generate_and_store(self) -> None:
        """Один цикл генерации и сохранения телеметрии"""
        try:
            # Генерируем имя файла
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            filename = f'telemetry_{timestamp}.csv'
            
            # Генерируем данные
            data = self.generator.generate_csv_row(filename)
            
            # Записываем в CSV
            filepath = self.csv_writer.write_telemetry(data)
            
            # Загружаем в PostgreSQL
            self.db_loader.load_from_csv(filepath)
            
            logger.info(f"Telemetry cycle completed: voltage={data['voltage']}V, temp={data['temp']}°C")
        
        except Exception as e:
            logger.error(f"Error in telemetry cycle: {e}")
    
    def run_forever(self) -> None:
        """Бесконечный цикл генерации телеметрии"""
        logger.info("Starting telemetry generation loop...")
        
        while True:
            try:
                self.generate_and_store()
                time.sleep(self.config.gen_period_sec)
            
            except KeyboardInterrupt:
                logger.info("Service interrupted by user")
                break
            
            except Exception as e:
                logger.error(f"Unexpected error in main loop: {e}")
                # Продолжаем работу даже при ошибках
                time.sleep(self.config.gen_period_sec)
        
        self.shutdown()
    
    def shutdown(self) -> None:
        """Graceful shutdown"""
        logger.info("Shutting down Telemetry Generator Service...")
        self.db_loader.close()
        logger.info("Service stopped")


def main():
    """Точка входа приложения"""
    logger.info("=" * 60)
    logger.info("Telemetry Generator CLI Микросервис v1.0")
    logger.info("Миграция с Pascal legacy на Python (Clean Code)")
    logger.info("=" * 60)
    
    try:
        # Загружаем конфигурацию
        config = Config()
        
        # Создаем и запускаем сервис
        service = TelemetryService(config)
        service.initialize()
        service.run_forever()
    
    except Exception as e:
        logger.error(f"Fatal error: {e}")
        sys.exit(1)


if __name__ == '__main__':
    main()

