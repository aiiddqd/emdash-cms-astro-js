**RFC: Astro + EmDash CMS (SSG/Hybrid) + Bunny.net (Storage / Magic Containers)**

**Статус:** Draft  
**Дата:** 2026-08-28  
**Цель:** Обкатка стека для тестирования. Сначала локальный прототип, затем путь деплоя на Bunny.net (статика + Magic Containers для hybrid/SSR).  
**Область:** Proof-of-concept, не production-ready система.

---

### 1. Резюме

Собираем Astro-сайт с гибридным рендерингом (часть страниц SSG, часть on-demand SSR). Контент управляется через EmDash CMS (Astro-native).

Деплой:

- Чистый SSG → Bunny Edge Storage + Pull Zone (дёшево и быстро).
- Hybrid / SSR + админка EmDash → Magic Containers (Node.js контейнер).

EmDash официально поддерживает Node.js (`@astrojs/node` standalone) + SQLite / S3. Bunny имеет готовый гайд по Astro на Magic Containers.

---

### 2. Цели и критерии успеха

| Цель                | Критерий успеха                                                                                      |
| ------------------- | ---------------------------------------------------------------------------------------------------- |
| Локальная проверка  | `npm run dev` + админка `/_emdash/admin` работает, контент редактируется, hybrid-страницы рендерятся |
| SSG-путь            | `astro build` → `dist/` → upload на Bunny Storage → сайт открывается через Pull Zone                 |
| Hybrid-путь         | Docker-образ с Node adapter + EmDash → Magic Containers → админка и SSR работают                     |
| Обновление контента | В hybrid — мгновенно (Live Collections). В SSG — через rebuild + deploy                              |
| Стоимость теста     | Magic Containers — pay-as-you-go (CPU/RAM/egress). Storage — копейки                                 |

**Не цели:** высокая доступность, сложные плагины EmDash, production-безопасность, multi-region autoscale с первого дня.

---

### 3. Архитектура (высокоуровнево)

```
[Редактор] → EmDash Admin UI
                ↓
         SQL (SQLite / libSQL / Bunny DB) + Media (local / S3 / Bunny Storage)
                ↓
         Astro (hybrid)
         ├── SSG pages (prerender)
         └── SSR pages + Live Collections (on-demand)
                ↓
         Деплой:
         A. Pure SSG → Bunny Edge Storage + Pull Zone
         B. Hybrid   → Magic Containers (Node standalone) + CDN endpoint
```

---

### 4. Этапы реализации

#### Этап 0. Подготовка (1–2 часа)

- Аккаунты: Bunny.net (trial), GitHub (для GHCR).
- Node.js ≥ 22.12.
- Базовые знания Docker.

#### Этап 1. Локальный прототип (основной фокус)

1. Создать проект:

   ```bash
   npm create astro@latest -- --template @emdash-cms/template-blog
   # или npm create emdash@latest
   ```

2. Конфиг (`astro.config.mjs`) — Node + SQLite + local storage:

   ```js
   import { defineConfig } from "astro/config";
   import node from "@astrojs/node";
   import react from "@astrojs/react";
   import emdash, { local } from "emdash/astro";
   import { sqlite } from "emdash/db";

   export default defineConfig({
     output: "hybrid", // или "server"
     adapter: node({ mode: "standalone" }),
     integrations: [
       react(),
       emdash({
         database: sqlite({ url: "file:./data/emdash.db" }),
         storage: local({
           directory: "./data/uploads",
           baseUrl: "/_emdash/api/media/file",
         }),
       }),
     ],
   });
   ```

3. `src/live.config.ts` — Live Collection (как в доках EmDash).

4. Запуск:

   ```bash
   npm install
   npm run dev
   ```

   - Сайт: `http://localhost:4321`
   - Админка: `http://localhost:4321/_emdash/admin`

5. Проверки:
   - Создать/отредактировать пост → изменения видны без rebuild.
   - Сделать часть страниц `export const prerender = true`.
   - Проверить медиа-загрузки.
   - `npm run build` + `node ./dist/server/entry.mjs` (standalone).

**Выход этапа:** рабочий локальный hybrid-сайт с EmDash.

#### Этап 2. Pure SSG-путь на Bunny Storage (быстрый тест)

1. Временно переключить на `output: "static"` (или оставить hybrid и prerender всё, что можно).
2. На билде контент должен быть доступен (локальная БД или fetch из API EmDash, если вынести CMS отдельно).
3. `npm run build` → `dist/`.
4. В Bunny:
   - Создать **Storage Zone** (Edge SSD).
   - Upload содержимого `dist/` (через UI / FTP / Storage API / скрипт).
   - Создать **Pull Zone** с origin = Storage Zone.
   - Настроить кэширование (HTML — короткий TTL или Smart Cache + override).
5. Опционально: GitHub Actions → build → upload + purge.

**Ограничение:** обновления контента требуют rebuild + deploy. Админки на Bunny Storage не будет.

#### Этап 3. Hybrid / SSR на Magic Containers

1. **Dockerfile** (на основе официального гайда EmDash + Bunny Astro guide):

   ```dockerfile
   FROM node:22-alpine AS builder
   WORKDIR /app
   COPY package*.json ./
   RUN npm ci
   COPY . .
   RUN npm run build

   FROM node:22-alpine
   WORKDIR /app
   COPY --from=builder /app/dist ./dist
   COPY --from=builder /app/node_modules ./node_modules
   COPY --from=builder /app/package.json ./
   RUN mkdir -p data
   ENV HOST=0.0.0.0
   ENV PORT=4321
   EXPOSE 4321
   CMD ["node", "./dist/server/entry.mjs"]
   ```

2. Сборка под `linux/amd64` (требование Magic Containers):

   ```bash
   docker build --platform linux/amd64 -t ghcr.io/USERNAME/emdash-astro:latest .
   docker push ghcr.io/USERNAME/emdash-astro:latest
   ```

3. В Bunny Magic Containers:
   - Добавить Image Registry (GitHub Container Registry).
   - Создать App → выбрать image.
   - Environment variables: `DATABASE_PATH`, S3-ключи (если media на Bunny Storage / внешнем S3), секреты auth.
   - Endpoint: CDN (HTTP) на порт 4321.
   - Persistent Volume для `./data` (SQLite + uploads) — важно, иначе данные пропадут при рестарте.
   - Health check, min/max replicas (для теста — 1).

4. Альтернативы хранилища:
   - SQLite на Persistent Volume (просто).
   - Bunny Database (libSQL) + env vars (`BUNNY_DATABASE_URL` и т.д.).
   - Media → Bunny Edge Storage (S3-compatible, сейчас в preview) или внешний S3/R2.

5. Проверки после деплоя:
   - Сайт и `/_emdash/admin` открываются.
   - Создание контента → сразу видно.
   - Логи (Log Forwarding при необходимости).
   - Стоимость в реальном времени.

#### Этап 4. CI/CD и обкатка

- GitHub Actions:
  - На push → build Docker → push в GHCR → (опционально) trigger redeploy через Magic Containers API.
  - Отдельный workflow для pure-SSG → Storage.
- Скрипт сравнения latency / cost между Storage-only и Magic Containers.
- Документирование боли (миграции БД, volume binding, cold starts, лимиты trial).

#### Этап 5. Оценка и решение

После 1–2 недель теста:

- Удобство DX EmDash vs классический headless.
- Реальная стоимость Magic Containers при низком трафике.
- Нужен ли hybrid или достаточно SSG + rebuild.
- Стоит ли оставаться на Bunny или уходить на Cloudflare (где EmDash «родной»).

---

### 5. Риски и митигации

| Риск                                 | Вероятность | Влияние | Митигация                                                                              |
| ------------------------------------ | ----------- | ------- | -------------------------------------------------------------------------------------- |
| EmDash 0.x — breaking changes        | Высокая     | Высокое | Фиксировать версии, следить за changelog, не строить критичный код на нестабильных API |
| Persistent Volume привязан к ноде    | Средняя     | Среднее | Для теста ок. Для прода — рассмотреть Bunny DB / внешнюю БД                            |
| Trial-лимиты Magic Containers        | Высокая     | Среднее | Платный аккаунт или минимальная конфигурация                                           |
| SQLite на edge + concurrent writes   | Средняя     | Среднее | Один инстанс или перейти на libSQL/PostgreSQL                                          |
| Media и публичные URL                | Средняя     | Низкое  | Явно настроить `publicUrl` / CDN                                                       |
| Нет «одного клика» как на Cloudflare | —           | —       | Ожидаемо, Magic Containers — generic containers                                        |

---

### 6. Альтернативные пути (если что-то не взлетит)

- EmDash оставить на Cloudflare (D1 + R2), фронт SSG на Bunny.
- Полностью отказаться от EmDash → Astro Content Collections + Decap/Tina + Bunny Storage.
- Magic Containers только для API/админки, публичный сайт — чистая статика.

---

### 7. Следующие шаги (порядок действий)

1. Сейчас → Этап 1 (локальный сервер + hybrid).
2. После успешного `dev` + `build` + standalone → Этап 2 (Storage).
3. Параллельно или следом → Docker + Magic Containers (Этап 3).
4. Зафиксировать результаты в этом RFC (раздел «Результаты»).

Готов помочь с конкретными конфигами, Dockerfile, GitHub Actions или разбором ошибок на любом этапе. С чего начинаем — сразу `create emdash` / template-blog?
