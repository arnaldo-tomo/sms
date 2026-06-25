# API REST pública — v1 (multi-empresa, estilo Twilio)

A plataforma expõe uma API REST para que **cada empresa** envie SMS a partir das suas
próprias apps, usando um **token Bearer** (`sk_live_…`).

- **Base URL:** `https://SEU_DOMINIO/api/v1`
- **Autenticação:** header `Authorization: Bearer sk_live_…` (ou `x-api-key: sk_live_…`)
- **Rate limit:** por empresa (`messages_per_minute`, configurável no painel)
- **Formato:** JSON

> Os tokens são geridos no painel em **Empresas / API**. Cada token é mostrado **uma única
> vez** ao ser gerado — guarda-o num local seguro. Podes revogar tokens a qualquer momento.

---

## Autenticação

```bash
curl https://SEU_DOMINIO/api/v1/me \
  -H "Authorization: Bearer sk_live_xxxxxxxx"
```

Respostas de erro de auth:
```json
{ "error": "unauthorized", "message": "Token inválido ou revogado." }   // 401
```

---

## Enviar SMS — `POST /api/v1/sms`

Corpo:

| Campo | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `to` | string \| string[] | sim | Número(s) destino em E.164 (`+258…`). Array para envio em série. |
| `content` | string | sim | Texto da mensagem (até 1600 caracteres). |
| `from` | string | não | Número de origem (tem de ser um número da empresa). Por omissão usa o 1º. |

### Um destinatário
```bash
curl -X POST https://SEU_DOMINIO/api/v1/sms \
  -H "Authorization: Bearer sk_live_xxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{ "to": "+258840000000", "content": "O seu código é 1234" }'
```
Resposta `201`:
```json
{
  "id": "131d17ae-2cb4-4766-bdff-61d7c272dcfb",
  "status": "queued",
  "direction": "outbound",
  "from": "+258846474687",
  "to": "+258840000000",
  "content": "O seu código é 1234",
  "segments": 1,
  "error": null,
  "created_at": "2026-06-25T10:02:12+00:00",
  "sent_at": null,
  "delivered_at": null
}
```

### Vários destinatários (batch)
```bash
-d '{ "to": ["+258840000001", "+258840000002"], "content": "Promoção!" }'
```
Resposta `201`:
```json
{ "count": 2, "messages": [ { "id": "…", "status": "queued", ... }, ... ] }
```

### Erros possíveis
```json
{ "error": "company_not_configured", "message": "A empresa não tem a integração httpSMS configurada." } // 422
{ "error": "no_sender_number", "message": "A empresa não tem nenhum número associado." }                // 422
{ "error": "validation_failed", "errors": { "to": ["..."] } }                                            // 422
```

---

## Estado de uma mensagem — `GET /api/v1/sms/{id}`

```bash
curl https://SEU_DOMINIO/api/v1/sms/131d17ae-… \
  -H "Authorization: Bearer sk_live_xxxxxxxx"
```
Estados possíveis: `queued` → `sending` → `sent` → `delivered` (ou `failed` / `expired`).

## Listar mensagens — `GET /api/v1/sms`
Query: `?status=delivered&per_page=50`

## Números da empresa — `GET /api/v1/numbers`
```json
{ "data": [ { "phone_number": "+258846474687", "name": "…", "status": "online", "last_seen_at": "…" } ] }
```

## Identidade — `GET /api/v1/me`
```json
{ "id": "empresa-teste", "name": "Empresa Teste", "numbers": 1, "rate_limit_per_minute": 60 }
```

---

## Status Callback (webhook para a tua app)

Se a empresa definir um **Status Callback URL** no painel, a plataforma faz `POST` a esse
URL sempre que uma mensagem muda para `sent`, `delivered`, `failed` ou `expired`:

```json
{
  "id": "131d17ae-…",
  "status": "delivered",
  "to": "+258840000000",
  "from": "+258846474687",
  "error": null,
  "sent_at": "…",
  "delivered_at": "…",
  "timestamp": "…"
}
```

Se houver **segredo de callback** configurado, vai o header `X-Signature` com o HMAC-SHA256
do corpo (chave = segredo). Valida-o para confirmar a origem:

```php
$assinaturaOk = hash_equals(
    hash_hmac('sha256', $request->getContent(), $segredo),
    $request->header('X-Signature')
);
```

---

## Modelo

- Cada **empresa** traz a **sua conta httpSMS** (API key própria) e os **seus números**.
- Os SMS enviados pela API saem **dos números dessa empresa**.
- As mensagens, números e tokens estão **isolados por empresa** (multi-tenant).
