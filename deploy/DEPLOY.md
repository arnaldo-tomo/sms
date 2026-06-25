# Deploy em produção — Ubuntu 22.04/24.04 + Nginx + PHP-FPM + MySQL

Guia para alojar o **SMS Gateway Manager** num VPS com URL fixo e SSL.
Substitui `SEU_DOMINIO` pelo teu domínio (ex.: `sms.dintell.co.mz`).

---

## 0. Pré-requisitos
- Um VPS com Ubuntu (DigitalOcean, Hetzner, Contabo…).
- Um domínio com um registo **A** a apontar para o IP do VPS.

## 1. Pacotes base
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server supervisor git unzip \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 (para compilar os assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Base de dados
```bash
sudo mysql <<'SQL'
CREATE DATABASE sms_gateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sms'@'localhost' IDENTIFIED BY 'UMA_PASS_FORTE';
GRANT ALL PRIVILEGES ON sms_gateway.* TO 'sms'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## 3. Código
```bash
sudo mkdir -p /var/www/sms && sudo chown -R $USER:$USER /var/www/sms
git clone <REPO_OU_COPIA_OS_FICHEIROS> /var/www/sms
cd /var/www/sms

composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## 4. Ambiente (.env)
```bash
cp .env.example .env
php artisan key:generate
nano .env
```
Define pelo menos:
```env
APP_NAME="SMS Gateway Manager"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://SEU_DOMINIO

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=sms_gateway
DB_USERNAME=sms
DB_PASSWORD=UMA_PASS_FORTE

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

HTTPSMS_BASE_URL=https://api.httpsms.com/v1
# A API Key e o webhook secret podem ser geridos depois na página Configurações.
```

## 5. Migrar, semear e cachear
```bash
php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Permissões
```bash
sudo chown -R www-data:www-data /var/www/sms
sudo find /var/www/sms/storage -type d -exec chmod 775 {} \;
sudo find /var/www/sms/bootstrap/cache -type d -exec chmod 775 {} \;
```

## 7. Nginx
```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/sms-gateway
sudo nano /etc/nginx/sites-available/sms-gateway   # mete o SEU_DOMINIO
sudo ln -s /etc/nginx/sites-available/sms-gateway /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

## 8. SSL (HTTPS grátis)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d SEU_DOMINIO
```

## 9. Worker das filas (Supervisor) — ESSENCIAL
```bash
sudo cp deploy/supervisor-worker.conf /etc/supervisor/conf.d/sms-gateway-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status            # deve mostrar RUNNING
```

## 10. Agendador (cron) — atualiza estados/dispositivos
```bash
sudo crontab -u www-data -e
```
Adiciona:
```
* * * * * cd /var/www/sms && php artisan schedule:run >> /dev/null 2>&1
```

## 11. Configurar o httpSMS
1. Entra na app em `https://SEU_DOMINIO` (admin@smsgateway.local / password) e **muda a password**.
2. **Configurações** → cola a **Account API Key** (de https://httpsms.com/settings) → Guardar → Testar ligação.
3. Define um **Segredo do Webhook** e regista no httpSMS o endpoint:
   ```
   https://SEU_DOMINIO/api/webhooks/httpsms?secret=O_TEU_SEGREDO
   ```
   Eventos: `message.phone.sent`, `message.phone.delivered`, `message.phone.failed`, `message.phone.received`.
4. **Dispositivos → Sincronizar**.

---

## Atualizações futuras (deploy de nova versão)
```bash
cd /var/www/sms
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo supervisorctl restart sms-gateway-worker:*
```

## Checklist de segurança em produção
- [ ] `APP_DEBUG=false`
- [ ] Password do `admin` alterada
- [ ] Segredo do webhook definido
- [ ] HTTPS ativo (certbot)
- [ ] Firewall: `sudo ufw allow 'Nginx Full' && sudo ufw allow OpenSSH && sudo ufw enable`
- [ ] Backups da BD (`mysqldump`) agendados
