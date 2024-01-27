Модуль записи консольных команд
===============================

### Создать Базу данных и указать параметры в:
```
<директория установки>/config/default_db.yml
```

### Установка composer:
```sh
composer install
composer dump-autoload
```
### Генерация автозагрузчика:
```sh
composer dump-autoload
```
### Инициализация миграций:
```sh
php console.php migrate
```
### Функционал:
- Запись команд в базу данных с возможностью установить название и описание каждой команды
- Парсинг для выявления имени команды, аргументов и параметров
- Вывод в информации в консоль
### Параметры:
- аргументы запуска передаются в фигурных скобках через запятую в следующем формате:
    - одиночный аргумент: `{arg}`
    - несколько аргументов: `{arg1,arg2,arg3}` или `{arg1} {arg2} {arg3}` или `{arg1,arg2} {arg3}`
- параметры запуска передаются в квадратных скобках в следующем формате:
    - параметр с одним значением: `[name=value]`
    - параметр с несколькими значениями: `[name={value1,value2,value3}]`
### Примеры запросов:
```sh
php console.php
```
```sh
php console.php command_name {verbose,overwrite} [log_file=app.log] {unlimited} [methods={create,update,delete}] [paginate=50] {log}
```
```sh
php console.php command_name {help}
```
### Пример результата вывода записанной команды: 
```
Called command: 'command_name'
 Arguments:
  - verbose
  - overwrite
  - unlimited
  - log
 Options:
  - log_file
        - app.log
  - methods
        - create
        - update
        - delete
  - paginate
        - 50
```

