<img width="1540" height="771" alt="2026-11-19_16-54" src="https://github.com/user-attachments/assets/de0ba779-3c0c-4c33-9221-a107f4d6c0c8" />



# Об приложении
Это веб приложение - реплика одного известного блога. Оно легко кастомизируется и предоставляет качественно настроенный редактор статей - используется опен-сорсный EditorJS, который был настроен и имеет мои кастомные плагины.

Стек - php laravel, inertia, vue, scss, tailwind, filament, EditorJS .

В админке можно добавлять реакции, одобрять комментарии.
При желании можно указать своего Бота в телеграме - в файле .env - он будет уведомалять при реакциях, так же присутствует бот некстклауда.

Redis нужен для того чтобы показывать статистику в реальном времени, сколько человек зашло на сайт.

# Сделать себя админом
1) открыть файл howdyho_klondike_reimplementation\app\Http\Middleware\RestrictAuthRoutes.php
2) указать свой ip адрес
```php 
    // Укажи СВОЙ IP-адрес здесь
        $allowedIps = [
            '127.0.0.1', // ← ЗАМЕНИ НА СВОЙ РЕАЛЬНЫЙ IP
                '::1',
        ];
```
3) 
- регистрируемся по адресу /register 
- логиниться в /login
- и зайти в админку по адресу /admin 

# Установка

## Системные требования:
- Windows/Linux
- PHP 8.3 или выше
- Composer последней версии
- NPM последней версии
- REDIS


## Linux (Ubuntu/Debian)

### Шаг 1. Клонирование
```bash
git clone https://github.com/SnappsiSnappes/howdyho_klondike_reimplementation
# если папка уже существует — этот шаг можно пропустить
```

### Шаг 2. Переход в папку
```bash
cd howdyho_klondike_reimplementation
```

### Шаг 3. Подготовка .env
```bash
mv env .env
# переименовать env в .env
```

### Шаг 4. PHP-зависимости
```bash
composer install
# если падает из-за блокировки пакетов - попробуйте: composer update
```

### Шаг 5. JS-зависимости
```bash
npm install
```

### Шаг 6. Ключ приложения
```bash
php artisan key:generate
```

### Шаг 7. Удаление старого симлинка storage
```bash
rm -rf public/storage
# если папки нет — шаг безопасно пропускается, ничего делать не надо
```

### Шаг 8. Создание симлинка
```bash
php artisan storage:link
# если ругается, что ссылка уже есть — сначала удалите public/storage вручную и повторите
```

### Шаг 9. Папка для загрузок
```bash
mkdir -p storage/app/public
# если папка уже есть — команда не помешает
```

### Шаг 10. Миграции
```bash
php artisan migrate
# если SQLite/MySQL не настроены — проверьте строки DB_* в .env
```

### Шаг 11. Запуск сервера (терминал 1)
```bash
php artisan serve
```

### Шаг 12. Запуск фронтенда (терминал 2)
```bash
cd howdyho_klondike_reimplementation
npm run dev
# или для продакшн-сборки: npm run build (тогда второй терминал нужен только для этой команды)
```

---

## Windows (PowerShell)

### Шаг 1. Клонирование
```powershell
git clone https://github.com/SnappsiSnappes/howdyho_klondike_reimplementation
# если папка уже существует — этот шаг можно пропустить
```

### Шаг 2. Переход в папку
```powershell
cd howdyho_klondike_reimplementation
```

### Шаг 3. Подготовка .env
```powershell
Move-Item -Force env .env
# переименовать env в .env
```

### Шаг 4. PHP-зависимости
```powershell
composer install
# если падает из-за блокировки пакетов — попробуйте: composer update
```

### Шаг 5. JS-зависимости
```powershell
npm install
```

### Шаг 6. Ключ приложения
```powershell
php artisan key:generate
```

### Шаг 7. Удаление старого симлинка storage
```powershell
Remove-Item -Recurse -Force public\storage -ErrorAction SilentlyContinue
# если папки нет - команда просто ничего не сделает; можно также удалить public\storage вручную через проводник
```

### Шаг 8. Создание симлинка
```powershell
php artisan storage:link
# если ругается, что ссылка уже есть - сначала удалите public\storage вручную и повторите
# (на Windows для storage:link может потребоваться запуск PowerShell от имени администратора)
```

### Шаг 9. Папка для загрузок
```powershell
New-Item -ItemType Directory -Force -Path storage\app\public | Out-Null
# если папка уже есть — команда не помешает
```

### Шаг 10. Миграции
```powershell
php artisan migrate
# если SQLite/MySQL не настроены — проверьте строки DB_* в .env
```

### Шаг 11. Запуск сервера (терминал 1)
```powershell
php artisan serve
```

### Шаг 12. Запуск фронтенда (терминал 2)
```powershell
cd howdyho_klondike_reimplementation
npm run dev
# или для продакшн-сборки: npm run build
```

---

## 🐳 Docker (опционально, возможны танцы с бубном)

### Шаг 1. Клонирование
```bash
git clone https://github.com/SnappsiSnappes/howdyho_klondike_reimplementation
```

### Шаг 2. Переход в папку
```bash
cd howdyho_klondike_reimplementation
```

### Шаг 3. Подготовка .env
```bash
mv env .env
# если файла env нет — удалите его вручную или: cp .env.example .env
```

### Шаг 4. PHP-зависимости
```bash
composer install
```

### Шаг 5. JS-зависимости
```bash
npm install
```

### Шаг 6. Ключ приложения
```bash
php artisan key:generate
```

### Шаг 7. Симлинк storage
```bash
php artisan storage:link
# если ругается — удалите public/storage вручную и повторите
```

### Шаг 8. Установка Sail
```bash
php artisan sail:install
# интерактивно выберите mysql
```

### Шаг 9. Правка .env (вручную в редакторе)
Изменить .env на:
```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=sail
DB_USERNAME=root
DB_PASSWORD=password
```


### Шаг 10. Запуск контейнеров
```bash
./vendor/bin/sail up -d
```

### Шаг 11. Создание БД
```bash
./vendor/bin/sail mysql -u root -p
```
Внутри MySQL:
```sql
CREATE DATABASE sail;
exit;
```

### Шаг 12. Миграции
```bash
./vendor/bin/sail php artisan migrate
```

### Шаг 13. Запуск фронтенда
```bash
./vendor/bin/sail npm run dev
```
