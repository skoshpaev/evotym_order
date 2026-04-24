# order

Это сервис заказов.

Он умеет:
- создать заказ
- отдать список заказов
- отдать один заказ
- держать локальную копию товаров
- принимать `product.updated`
- отправлять `order.created`
- получать статус обработки заказа обратно

Списки сейчас без пагинации. Это сознательно не доделывал, чтобы успеть собрать основной сценарий.

Важно:
- env, DSN, логины, пароли и всё этьо тут закоммичены только для быстрого локального теста
- сделано для быстроты на правах тестового задания

## Как запускать

лучше всего запускать из корневого `evotym_general`, потому что там сразу поднимаются оба сервиса и RabbitMQ.

Если отдельно:

```bash
cd order
docker compose up -d --build
```

Но тогда для полного сценария всё равно нужен RabbitMQ.

## HTTP

Health:

```bash
curl -fsS http://127.0.0.1:8082/health.php
```

Список:

```bash
curl -sS http://127.0.0.1:8082/orders
```

Список без пагинации.

Создание:

```bash
curl -sS \
  -X POST http://127.0.0.1:8082/orders \
  -H 'Content-Type: application/json' \
  -d '{
    "productId": "PUT_PRODUCT_UUID_HERE",
    "customerName": "John Doe",
    "quantityOrdered": 2
  }'
```

Один заказ:

```bash
curl -sS http://127.0.0.1:8082/orders/PUT_ORDER_UUID_HERE
```

## Про статус заказа

Небольшой важный момент.

Заказ создаётся как `Processing`, потом `product` сервис через RabbitMQ подтверждает его и статус становится `Processed` или `Failed`.

То есть тут логика не "всё мгновенно в одной базе", а через сообщения.

## Тесты

Из контейнера:

```bash
cd /workspace/order && vendor/bin/phpunit -c phpunit.dist.xml
```

С хоста, если сервис поднят через корневой compose:

```bash
docker compose -f ../docker-compose.yaml exec -T order-app vendor/bin/phpunit -c phpunit.dist.xml
```

## Что внутри

- Symfony 6.4
- MySQL
- локальная product projection
- outbox для исходящих событий
- inbox для входящих событий
- consumer для `product.updated` и `order.processing.status`
