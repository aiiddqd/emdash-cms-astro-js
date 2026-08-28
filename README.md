# Astro + EmDash CMS

CMS-блог на [Astro](https://astro.build/) и [EmDash](https://emdashcms.com/): публичный SSR-сайт и полноценная административная панель в одном Node.js-приложении. Контент хранится в SQLite, а медиа — в локальном файловом хранилище.

## Возможности

- статьи, отдельные страницы, рубрики и теги;
- полнотекстовый поиск и RSS;
- черновики, ревизии, SEO-поля и аудит действий CMS;
- редактирование контента через EmDash Admin UI;
- серверный рендеринг через `@astrojs/node`.

## Быстрый старт

Нужны Node.js 22.12+ и npm 10+.

```bash
npm ci
make dev
```

Откройте <http://localhost:4321> для сайта или <http://localhost:4321/_emdash/admin> для админки.

В репозитории есть `package-lock.json`, поэтому используем npm. Полный порядок первого запуска, работа с локальными данными и команды описаны в [docs/specs/local-env.md](docs/specs/local-env.md).

## Основные команды

```bash
make help       # все команды
make dev        # сервер разработки
make typecheck  # проверка типов
make build      # production-сборка
make start      # запуск standalone-сборки
make verify     # typecheck + build
```

`make types` запускает генерацию типов EmDash и требует работающий dev-сервер.

## Архитектура и контент

- Astro работает в SSR-режиме (`output: "server"`) с standalone Node adapter.
- EmDash подключён как Astro integration; схема и demo-контент описаны в `seed/seed.json`.
- SQLite находится в `data.db`, загружаемые медиа — в `uploads/`.
- Публичные маршруты: `/`, `/posts`, `/posts/[slug]`, `/pages/[slug]`, `/category/[slug]`, `/tag/[slug]`, `/search` и `/rss.xml`.

Подробная схема решения, границы MVP и план следующего этапа доступны в [docs/specs/basic.md](docs/specs/basic.md). Исходные исследовательские материалы лежат в `docs/rfc/`.

## Структура проекта

| Путь | Назначение |
| --- | --- |
| `astro.config.mjs` | Astro, EmDash, SQLite и локальное хранилище. |
| `seed/seed.json` | Схема CMS, меню, таксономии, виджеты и demo-контент. |
| `src/live.config.ts` | Регистрация EmDash Live Collections; не изменять без необходимости. |
| `src/pages/` | Серверные маршруты публичного сайта. |
| `src/layouts/Base.astro` | Базовый layout, меню, поиск и contributions EmDash. |
| `docs/specs/` | Актуальные спецификации решения и локальной среды. |

## План публикации

MVP проверяется локально. Затем возможны два сценария Bunny.net: SSG-сборка в Storage/Pull Zone или SSR-приложение в Magic Containers с persistent volume для SQLite и медиа. Это решение ещё не реализовано; контекст — в [RFC](RFC.md).
