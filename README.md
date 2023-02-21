Console application
===============================

### Создать Базу данных и указать параметры в:
```
<директория установки>/src/Module/Database/Application/Params.php
```

### Установка composer:
```sh
composer install
```
### Генерация автозагрузчика:
```sh
composer dump-autoload
```
### Инициализация миграций:
```sh
php app.php migrate
```

### Примеры запросов:
```sh
php app.php
```
```sh
php app.php command_name {verbose,overwrite} [log_file=app.log] {unlimited} [methods={create,update,delete}] [paginate=50] {log}
```
```sh
php app.php command_name {help}
```