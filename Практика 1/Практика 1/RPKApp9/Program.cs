using System;
using System.Collections.Generic;
using System.Linq;

namespace RefactoringExample
{
    
    /// Enum для типобезопасного представления операций калькулятора
    /// Заменяем магические строковые константы ("add", "subtract" и т.д.)
    /// Replace Magic String with Enum
    public enum CalculatorOperation
    {
        Add,
        Subtract,
        Multiply,
        Divide
    }

    
    /// Интерфейс для логирования операций
    /// Extract Interface (отделение ответственности за логирование)
    public interface IOperationLogger
    {
        
        void LogOperation(string message);

        
        IReadOnlyList<string> GetHistory();

        
        void DisplayHistory();

        
        void ClearHistory();
    }

    
    /// Реализация логирования операций
    /// Extract Class
    /// Отвечает ТОЛЬКО за логирование, что соответствует SRP
    
    public class OperationLogger : IOperationLogger
    {
        private readonly List<string> _history = new List<string>();

        ///Добавляем запись о выполненной операции
        public void LogOperation(string message)
        {
            if (string.IsNullOrWhiteSpace(message))
                throw new ArgumentException("Message cannot be empty", nameof(message));

            _history.Add($"[{DateTime.Now:HH:mm:ss}] {message}");
        }

        ///Получаем историю операций (только чтение)
        public IReadOnlyList<string> GetHistory()
        {
            return _history.AsReadOnly();
        }

        ///Вывод истории на консоль
        public void DisplayHistory()
        {
            if (_history.Count == 0)
            {
                Console.WriteLine("История пуста.");
                return;
            }

            Console.WriteLine("История операций:");
            foreach (var entry in _history)
            {
                Console.WriteLine($"  - {entry}");
            }
        }

        /// Очистка
        public void ClearHistory()
        {
            _history.Clear();
        }
    }

    
    
    public class Calculator
    {
        private readonly IOperationLogger _logger;
        private double _lastResult;

        ///Последний вычисленный результат
        public double LastResult
        {
            get => _lastResult;
            private set => _lastResult = value;
        }

        
        /// Конструктор с внедрением зависимости логирования
        /// Dependency Injection
        public Calculator(IOperationLogger logger)
        {
            _logger = logger ?? throw new ArgumentNullException(nameof(logger));
            _lastResult = 0;
        }

        
        /// Добавляем число к существующему результату
        /// Rename Method (было addNumber)
        public double AddNumber(double value)
        {
            LastResult = LastResult + value;
            _logger.LogOperation($"Added {value} to number, result: {LastResult}");
            return LastResult;
        }

        
        /// Выполняем операцию с двумя операндами
        /// Split Method
        /// Используется pattern matching для более читаемого кода
        public double PerformOperation(CalculatorOperation operation, double a, double b)
        {
            double result = operation switch
            {
                CalculatorOperation.Add => Add(a, b),
                CalculatorOperation.Subtract => Subtract(a, b),
                CalculatorOperation.Multiply => Multiply(a, b),
                CalculatorOperation.Divide => Divide(a, b),
                _ => throw new ArgumentException($"Unknown operation: {operation}")
            };

            LastResult = result;
            return result;
        }

        
        /// Extract Method
        /// Выделена отдельная логика сложения для переиспользования и модульности
        private double Add(double a, double b)
        {
            double result = a + b;
            _logger.LogOperation($"Added {a} and {b}, result: {result}");
            return result;
        }

        
        /// Extract Method
        /// Выделена отдельная логика вычитания
        private double Subtract(double a, double b)
        {
            double result = a - b;
            _logger.LogOperation($"Subtracted {b} from {a}, result: {result}");
            return result;
        }

        
        /// Extract Method
        /// Выделена отдельная логика умножения
        private double Multiply(double a, double b)
        {
            double result = a * b;
            _logger.LogOperation($"Multiplied {a} by {b}, result: {result}");
            return result;
        }

        
        /// Extract Method + Guard Clause
        /// Выделена отдельная логика деления с проверкой на деление на 0
        private double Divide(double a, double b)
        {
            // Проверка ошибочного условия в начале метода
            if (Math.Abs(b) < double.Epsilon)
            {
                _logger.LogOperation($"ERROR: Attempted to divide {a} by zero");
                throw new ArgumentException("Cannot divide by zero");
            }

            double result = a / b;
            _logger.LogOperation($"Divided {a} by {b}, result: {result}");
            return result;
        }

        
        /// Суммируем переданное количество чисел
        /// Rename Method (было sum)
        public double Sum(params double[] numbers)
        {
            if (numbers.Length == 0)
            {
                _logger.LogOperation("Sum called with no arguments");
                return 0;
            }

            double total = 0;
            foreach (double num in numbers)
            {
                total += num;
            }

            LastResult = total;
            _logger.LogOperation($"Summed {numbers.Length} numbers, result: {total}");
            return total;
        }

        
        /// Получаем историю операций
        public IReadOnlyList<string> GetHistory()
        {
            return _logger.GetHistory();
        }

        
        /// Отображаем историю 
        public void DisplayHistory()
        {
            _logger.DisplayHistory();
        }
    }

    
    /// Главная программа
    /// Техники рефакторинга:
    /// 1. Remove Dead Code - удален неиспользуемый класс Configurator
    /// 2. Remove Dead Code - удалена неиспользуемая переменная arguments
    /// 3. Dependency Injection - создание зависимостей через конструкторы
    class Program
    {
        static void Main(string[] args)
        {
            // Создание логирующего компонента
            var logger = new OperationLogger();

            // Внедрение логирования в калькулятор
            var calculator = new Calculator(logger);

            Console.WriteLine("=== Демонстрация работы рефакторенного калькулятора ===\n");

            // Примеры использования
            double result1 = calculator.AddNumber(5);
            Console.WriteLine($"После AddNumber(5): {result1}\n");

            double result2 = calculator.PerformOperation(CalculatorOperation.Add, 10, 20);
            Console.WriteLine($"10 + 20 = {result2}\n");

            double result3 = calculator.Sum(1, 2, 3, 4, 5);
            Console.WriteLine($"Sum(1,2,3,4,5) = {result3}\n");

            // Демонстрация обработки ошибок
            try
            {
                calculator.PerformOperation(CalculatorOperation.Divide, 10, 0);
            }
            catch (ArgumentException ex)
            {
                Console.WriteLine($"Ошибка: {ex.Message}\n");
            }

            // Вывод истории
            calculator.DisplayHistory();

            Console.WriteLine("\n---Статистика улучшений рефакторинга---");
            Console.WriteLine("Устранено дублирование кода (6 повторений --> 0)");
            Console.WriteLine("Соответствие SRP (логирование отделено)");
            Console.WriteLine("Типобезопасность (enum вместо строк)");
            Console.WriteLine("Обработка ошибок (проверка деления на 0)");
            Console.WriteLine("Соответствие стандартам C# (PascalCase)");
            Console.WriteLine("Удален мертвый код (Configurator, arguments)");
            Console.WriteLine("Циклометрическая сложность улучшена (разделение методов)");
        }
    }

    /*
     * УБРАННЫЕ КЛАССЫ И КОД:
     * 
     * 1. УДАЛЕН класс History (дублировал функциональность OperationLogger)
     *    - Была: public class History { public List<string> entries; }
     *    - Заменена: IOperationLogger интерфейс и OperationLogger класс
     * 
     * 2. УДАЛЕН класс Configurator (не реализован, мертвый код)
     *    - Была: public static class Configurator { public static void configureCalculator() }
     *    - Причина: не использовался в коде
     * 
     * 3. УДАЛЕНА переменная arguments (мертвый код)
     *    - Была: object[] arguments = args; arguments[0] = "mutated";
     *    - Причина: не использовалась содержательно
     * 
     * 4. УДАЛЕН класс Globals (плохая практика использования глобального состояния)
     *    - Была: public static class Globals { public static Calculator calc; }
     *    - Заменена: Dependency Injection в Main
     */
}
