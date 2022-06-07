Console application
===============================

Создать Базу данных и указать имя в params.php

###Инициализация миграций:
```
php migration/migrate.php
```
###Примеры запросов:

```
php app.php
php app.php command_name {verbose,overwrite} [log_file=app.log] {unlimited} [methods={create,update,delete}] [paginate=50] {log}
php app.php command_name {help}
```