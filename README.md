# Laravel Test Codemate

Этот репозиторий содержит тестовый проект на Laravel 11 для управления балансами пользователей.

## Быстрый старт (Docker)

```bash
# 1) Клонируем репо
git clone https://github.com/dmsv312/laravel_test_codemate.git
cd laravel_test_codemate

# 2) Поднимаем контейнеры
docker compose up --build -d

# 3) Первичная инициализация
docker compose exec app php artisan migrate

# (Опционально) сидаем тестовых пользователей
docker compose exec app php artisan db:seed
```

После первого запуска контейнер "app" автоматически создаст Laravel-проект в директории `src/`. Приложение будет доступно на `http://localhost:8080`.

## Подключение к **локальному** PostgreSQL вместо docker-db
В `.env` (внутри `src/.env`) установите:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=wallet
DB_USERNAME=wallet
DB_PASSWORD=secret
```

И перезапустите сервис `app` (или просто выполните миграции).

## Структура

- `docker-compose.yml` — сервисы `app` (php-fpm), `web` (nginx), `db` (Postgres).
- `docker/php/Dockerfile` — образ PHP 8.3 + Composer, автоскрипт установки Laravel.
- `docker/nginx/default.conf` — конфиг nginx.
- `overlay/bootstrap.sh` — скрипт-инициализатор: при первом старте ставит Laravel в `src/`.
- `src/` — код Laravel (создаётся автоматически).

## Дальше
- Реализуем эндпоинты `/api/deposit`, `/api/withdraw`, `/api/transfer`, `/api/balance/{id}`.
- Добавим валидацию, транзакции, `FOR UPDATE`, журнал `transactions` и сервис `WalletService`.
- Подключим простой API ключ, идемпотентность и базовые тесты Pest.

