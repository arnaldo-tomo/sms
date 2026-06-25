# SMS Gateway Manager

Aplicação web moderna (estilo SaaS) para **enviar SMS reais** através de um telemóvel
Android ligado à API do [httpSMS](https://docs.httpsms.com). Construída com **Laravel 12 +
Inertia + Vue 3 (TypeScript) + Tailwind**, com envio assíncrono via **filas**, webhooks de
estado de entrega, gestão de contactos/grupos, dispositivos, utilizadores com perfis &
permissões e logs de auditoria.

---

## ✨ Funcionalidades

| Módulo | Detalhes |
|---|---|
| **Dashboard** | Totais de enviados/pendentes/falhados, estado dos dispositivos, gráfico de utilização diária (Chart.js) |
| **Contactos** | CRUD, grupos/listas, importação Excel/CSV |
| **Envio de SMS** | Para números, contactos ou grupos · contador de caracteres/segmentos · envio imediato ou **agendado** |
| **Histórico** | Listagem com estado de entrega, pesquisa e filtros, paginação |
| **Dispositivos** | Estado online/offline, bateria, sinal, última sincronização |
| **Configurações** | API Key httpSMS, URL base, webhooks, filas |
| **Segurança** | Login, registo, recuperação de senha (Breeze), utilizadores, **perfis & permissões** (Spatie), **logs de auditoria** (Activitylog) |
| **Interface** | Design SaaS, **modo claro/escuro**, responsivo, ícones Heroicons |

## 🏗️ Arquitetura

```
app/
├── Http/
│   ├── Controllers/         Dashboard, Contact, ContactList, ContactImport,
│   │                        Send, Message, Device, Setting, User, AuditLog, Api/Webhook
│   ├── Requests/            Validação + autorização (FormRequests)
│   ├── Resources/           API Resources (Contact, Message, Device, User, ContactList)
│   └── Middleware/          HandleInertiaRequests (partilha auth/permissões/flash)
├── Jobs/                    SendSmsJob, SyncDevicesJob, RefreshMessageStatusJob  (filas)
├── Models/                  User, Contact, ContactList, Device, Message, Setting
├── Policies/                Contact, ContactList, Message, Device, User
├── Repositories/            MessageRepository, ContactRepository
├── Services/
│   ├── SmsService.php       Orquestra criação + enfileiramento + envio
│   ├── DeviceService.php    Sincronização de dispositivos
│   ├── SettingsService.php  Config gerida pela UI (com cache)
│   └── HttpSms/             HttpSmsClient, WebhookHandler
└── Imports/                 ContactsImport (maatwebsite/excel)

resources/js/
├── Layouts/AppLayout.vue    Sidebar SaaS + topbar + dark mode + flash
├── Components/ui/           Card, StatCard, StatusBadge, Pagination
├── Components/charts/       UsageChart (Chart.js)
├── composables/             useDarkMode, usePermissions
└── Pages/                   Dashboard, Messages/{Send,Index,Show}, Contacts,
                             Devices, Settings, Users, AuditLogs
```

## 🚀 Instalação

### Requisitos
- PHP 8.2+ · Composer · Node 18+ · MySQL 8 (ou MariaDB)

### Passos

```bash
# 1. Dependências (já instaladas neste projeto)
composer install
npm install

# 2. Ambiente
cp .env.example .env        # se ainda não existir
php artisan key:generate

# 3. Base de dados — edita o bloco DB_* no .env (ver abaixo) e depois:
php artisan migrate --seed

# 4. Frontend
npm run build               # ou: npm run dev   (durante desenvolvimento)

# 5. Servir
php artisan serve

# 6. Worker das filas (NECESSÁRIO para o envio de SMS)
php artisan queue:work

# 7. (opcional) Agendador — sincroniza dispositivos e estados periodicamente
php artisan schedule:work
```

### Base de dados (MySQL)

No `.env`, define o bloco da BD com o teu **MySQL remoto**:

```env
DB_CONNECTION=mysql
DB_HOST=o-teu-host
DB_PORT=3306
DB_DATABASE=sms_gateway
DB_USERNAME=o-teu-user
DB_PASSWORD=a-tua-pass
```

> 💡 Para testar localmente **sem MySQL**, o projeto vem pronto para SQLite
> (`DB_CONNECTION=sqlite`). Já foi verificado a correr nesse modo. Para passar a MySQL,
> basta editar o bloco acima e voltar a correr `php artisan migrate --seed`.

## 🔑 Credenciais de demonstração (após `migrate --seed`)

| Perfil | Email | Password |
|---|---|---|
| Administrador | `admin@smsgateway.local` | `password` |
| Operador | `operador@smsgateway.local` | `password` |

## 📲 Configurar o httpSMS

1. Instala a app **httpSMS** no telemóvel Android e cria conta em https://httpsms.com.
2. Copia a tua **API Key**.
3. Na app: **Configurações → Integração httpSMS** → cola a API Key, define a URL base
   (`https://api.httpsms.com/v1`) e o número por omissão. Clica em **Testar ligação**.
4. Em **Dispositivos**, clica **Sincronizar** para importar o telemóvel.
5. (Recomendado) Em **Configurações → Webhooks**, copia a URL e regista-a no painel do
   httpSMS para receber atualizações de entrega e SMS recebidos em tempo real. Define um
   **segredo do webhook** para validar os pedidos.

> A API Key e o webhook secret são guardados **encriptados** na tabela `settings`.

## 🔐 Perfis & Permissões

- **admin** — acesso total (bypass).
- **manager** — contactos, envio, histórico, dispositivos.
- **operator** — envio, histórico, ver contactos/dispositivos.

Permissões: `contacts.view/manage`, `sms.send`, `messages.view`, `devices.view/manage`,
`settings.manage`, `users.manage`, `audit.view`.

## 🧩 Fluxo de envio

```
UI (Send) → SendController → SmsService::dispatchBulk()
   → cria Message(s) [status: queued|scheduled]
   → SendSmsJob (fila, com retry/backoff)
        → HttpSmsClient::sendMessage()  →  API httpSMS  →  telemóvel  →  SMS
   → webhook httpSMS → WebhookHandler → atualiza status (sent/delivered/failed)
```

## 🧪 Importação de contactos

Ficheiro `.xlsx`/`.csv` com cabeçalho `name, phone_number, email, notes`.
Vê o exemplo em [`contacts-sample.csv`](contacts-sample.csv).

## 📦 Stack

Laravel 12 · Inertia 2 · Vue 3 + TS · Tailwind 3 · Breeze · spatie/laravel-permission ·
spatie/laravel-activitylog · maatwebsite/excel · Chart.js
