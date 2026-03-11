# Laravel Horizon - Setup VPS con Plesk

## Requisitos previos
- VPS con Plesk
- PHP con extensión Redis
- Redis server instalado y corriendo
- Laravel con Horizon instalado (`composer require laravel/horizon`)

---

## 1. Instalar Redis en el VPS

```bash
apt update && apt install redis-server -y
systemctl enable redis-server
systemctl start redis
redis-cli ping  # debe responder PONG
```

## 2. Extensión PHP Redis

Verificar versión PHP del proyecto:
```bash
php -v
```

Instalar extensión (ajustar versión PHP):
```bash
apt install php8.4-redis  # o la versión que corresponda
```

O compilar desde PECL:
```bash
pecl install redis
```

Añadir a php.ini:
```ini
extension=redis.so
```

Reiniciar PHP-FPM:
```bash
systemctl restart php8.4-fpm  # ajustar versión
```

## 3. Configurar .env en producción

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**IMPORTANTE:** Si antes usabas `QUEUE_CONNECTION=database`, cámbialo a `redis`. Si no, los jobs se encolan en DB pero Horizon escucha en Redis y no los procesa (0 jobs/min en dashboard).

## 4. Publicar config de Horizon

```bash
php artisan horizon:install
```

## 5. Configurar `config/horizon.php`

Ajustar `maxProcesses` según recursos del VPS:

| RAM VPS | processing (maxProcesses) | downloads (maxProcesses) |
|---------|--------------------------|--------------------------|
| 2GB     | 5                        | 2                        |
| 4GB     | 8-10                     | 3                        |
| 8GB     | 15-20                    | 5                        |

Regla general: no más de 2-3x el número de CPUs para processing.

Ejemplo para VPS 8GB / 4 CPUs:
```php
'environments' => [
    'production' => [
        'downloads' => [
            'maxProcesses' => 3,
        ],
        'processing' => [
            'maxProcesses' => 10,
            'balanceMaxShift' => 3,  // escala 3 workers a la vez
            'balanceCooldown' => 3,  // cada 3 segundos
        ],
    ],
],
```

## 6. Acceso al dashboard Horizon

En `app/Providers/HorizonServiceProvider.php`, método `gate()`:

```php
// Abierto (sin auth):
Gate::define('viewHorizon', function ($user = null) {
    return true;
});

// Restringido por email (con auth):
Gate::define('viewHorizon', function ($user = null) {
    return in_array(optional($user)->email, [
        'tu@email.com',
    ]);
});
```

## 7. Crear fichero Supervisor

Crear `/etc/supervisor/conf.d/{proyecto}-horizon.conf`:

```ini
[program:{proyecto}-horizon]
process_name=%(program_name)s
command=/opt/plesk/php/{php-version}/bin/php /var/www/vhosts/{dominio-principal}/{subdominio}/artisan horizon
autostart=true
autorestart=true
user={usuario-plesk}
redirect_stderr=true
stdout_logfile=/var/www/vhosts/{dominio-principal}/{subdominio}/storage/logs/horizon.log
stopwaitsecs=3600
```

### Variables a reemplazar:

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `{proyecto}` | Nombre del proyecto | `ilicitaciones` |
| `{php-version}` | Versión PHP | `8.4` |
| `{dominio-principal}` | Dominio raíz en Plesk | `tailor-bytes.com` |
| `{subdominio}` | Carpeta del sitio | `ilicitaciones.tailor-bytes.com` |
| `{usuario-plesk}` | Usuario del sistema en Plesk | `christian` |

### Ejemplo real:
```ini
[program:miproyecto-horizon]
process_name=%(program_name)s
command=/opt/plesk/php/8.4/bin/php /var/www/vhosts/midominio.com/miproyecto.midominio.com/artisan horizon
autostart=true
autorestart=true
user=miusuario
redirect_stderr=true
stdout_logfile=/var/www/vhosts/midominio.com/miproyecto.midominio.com/storage/logs/horizon.log
stopwaitsecs=3600
```

## 8. Activar Supervisor

```bash
supervisorctl reread
supervisorctl update
supervisorctl start {proyecto}-horizon
```

Verificar:
```bash
supervisorctl status
# Debe mostrar: {proyecto}-horizon  RUNNING  pid XXXXX, uptime X:XX:XX
```

## 9. Deploy / Aplicar cambios

Después de cada deploy o cambio en config:
```bash
php artisan config:cache && php artisan horizon:terminate
```

Supervisor reinicia Horizon automáticamente.

## 10. Verificar

- Dashboard: `https://{dominio}/horizon/dashboard`
- CLI: `supervisorctl status`
- Redis: `redis-cli ping`

---

## Troubleshooting

### Jobs se encolan pero Horizon muestra 0 jobs
→ `QUEUE_CONNECTION` está en `database` en vez de `redis`. Cambiar en `.env` y `php artisan config:cache`.

### 403 FORBIDDEN en dashboard
→ El gate en `HorizonServiceProvider` no autoriza al usuario. Abrir con `return true;` o añadir email al array.

### Permisos vendor tras composer install
```bash
chown -R {usuario-plesk}:psaserv /var/www/vhosts/{dominio}/{subdominio}/vendor
```

### Comprobar recursos del VPS
```bash
free -h && nproc
```
