Console application
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