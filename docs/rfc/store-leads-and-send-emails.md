**Да, можно.** В EmDash как раз для этого есть готовый плагин.

### Готовое решение: `@emdash-cms/plugin-forms`

Официальный плагин **Forms** делает именно то, что нужно:

- создание форм в админке;
- сбор заявок (submissions) и хранение их в БД плагина;
- просмотр заявок в админке (inbox);
- уведомления на email;
- защита от спама (honeypot / Turnstile);
- экспорт (CSV и т.п.);
- встраивание форм в контент через Portable Text.

Описание: «Forms plugin for EmDash CMS — build forms, collect submissions, send notifications».

В демо Cloudflare он уже подключается как `formsPlugin()`.

#### Как подключить

```bash
npm install @emdash-cms/plugin-forms
```

В `astro.config.mjs` (или где у тебя конфиг EmDash):

```js
import { defineConfig } from "astro/config";
import emdash from "emdash/astro";
import { formsPlugin } from "@emdash-cms/plugin-forms";
// ... другие импорты (sqlite/d1, storage и т.д.)

export default defineConfig({
  integrations: [
    emdash({
      // database, storage и т.д.
      plugins: [
        formsPlugin({
          // опционально
          defaultSpamProtection: "honeypot", // "none" | "honeypot" | "turnstile"
        }),
      ],
    }),
  ],
});
```

После этого в админке появятся пункты **Forms** и **Submissions**.

Нужно, чтобы в EmDash был настроен email-провайдер (Resend, Postmark и т.п.) — плагин шлёт уведомления через `ctx.email.send()`.

### Telegram

В самом плагине Forms нативной отправки в Telegram нет. Варианты:

1. **Webhook Notifier** (`@emdash-cms/plugin-webhook-notifier`) — шлёт webhook’и при событиях. Можно повесить на свой endpoint / Cloudflare Worker / n8n / Make, который дальше шлёт в Telegram.
2. Свой небольшой плагин или endpoint, который:
   - принимает submission (или слушает событие),
   - делает `fetch` к Telegram Bot API.

Пример отправки в Telegram (можно вызвать из своего плагина/роута):

```ts
await fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    chat_id: CHAT_ID,
    text: `Новая заявка:\nИмя: ${name}\nEmail: ${email}\nСообщение: ${message}`,
    parse_mode: "HTML",
  }),
});
```

В песочном плагине нужно объявить capability `network:request` и `allowedHosts` (например, `api.telegram.org`).

### Альтернатива без плагина Forms

Можно сделать свою коллекцию (content type) «Заявки» через visual schema builder в админке и принимать данные через свой API-роут / Astro endpoint, а потом через hooks (`content:afterSave`) слать email + Telegram. Но для обычных контактных форм Forms-плагин удобнее: уже есть UI форм, inbox, спам-защита и email-уведомления.

### Кратко по шагам

1. Поставить `@emdash-cms/plugin-forms`.
2. Добавить `formsPlugin()` в конфиг EmDash.
3. Настроить email-провайдер в EmDash.
4. В админке создать форму, указать email для уведомлений.
5. Для Telegram — webhook-notifier или свой небольшой код с Bot API.

Если нужно — могу расписать конкретный пример своего endpoint’а под заявки + Telegram или как правильно повесить webhook на submissions.