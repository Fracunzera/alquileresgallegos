# Alquileres Río Gallegos

Portal de avisos de alquileres (mensuales y temporarios) de Río Gallegos, Santa Cruz, Argentina.
Junta en un solo lugar lo que hoy está disperso entre Airbnb, grupos de Facebook y las webs
de cada inmobiliaria.

## Regla innegociable: todo se renderiza en el servidor

El objetivo principal del sitio es posicionar en Google. Por eso **todas las páginas públicas
devuelven el HTML completo de los avisos en la respuesta inicial**. Nada de listados cargados
por JavaScript. El JS es solo mejora progresiva:

- galería de fotos (swipe),
- mapa de la ficha y selector de ubicación,
- auto-envío de filtros (que sin JS funcionan igual vía querystring).

Si una funcionalidad nueva no funciona con JavaScript desactivado, está mal hecha.

## Stack

- PHP 8.2+ sin framework (router propio, PDO, plantillas PHP con escape).
- MySQL 8 o MariaDB 10.6+.
- GD para procesar fotos (redimensiona y convierte a WebP).
- Leaflet + OpenStreetMap para mapas (servido localmente, se carga solo si hace falta).
- Composer solo para el autoload y PHPUnit. No hay paso de build.

## Estructura

```
public/            document root (único directorio expuesto)
  index.php        front controller
  assets/          css, js, vendor (Leaflet)
  uploads/         fotos procesadas (no versionado)
src/               código de la aplicación (namespace App\)
  Controllers/     públicos y Admin/
  Repositories/    acceso a datos
  Seo/             landings, metadatos, JSON-LD
  Services/        slugs, imágenes, CSRF, auth, rate limit
  Frescura/        cola de verificación y notificadores (hook)
templates/         vistas PHP (layout, parciales, admin)
database/          migraciones SQL y seeds
bin/               scripts de consola (migrar, crear admin, cron de frescura)
storage/           logs y caché (no versionado)
tests/             PHPUnit
```

## Instalación local

```bash
cp .env.example .env            # completar DB_* y APP_URL=http://localhost:8000
composer install
php bin/migrate.php --seed      # crea tablas y siembra los barrios
php bin/crear-admin.php         # crea el usuario del panel
php bin/datos-demo.php 60       # opcional: avisos de prueba (no corre en producción)
composer serve                  # http://localhost:8000
```

El panel de administración está en `/admin`.

## Despliegue

- **Apache / hosting compartido (cPanel):** apuntar el document root a `public/`.
  El `public/.htaccess` ya redirige todo a `index.php`.
- **nginx:**

  ```nginx
  root /ruta/al/proyecto/public;
  location / { try_files $uri /index.php?$query_string; }
  location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.2-fpm.sock; }
  ```

- **Cron diario** (frescura de avisos):

  ```
  15 6 * * * php /ruta/al/proyecto/bin/frescura.php >> /ruta/al/proyecto/storage/logs/frescura.log 2>&1
  ```

## Frescura de avisos

Un aviso activo con más de `FRESCURA_DIAS_VERIFICACION` días (15 por defecto) sin verificar
entra en la **cola de revisión** del panel. Desde ahí se puede:

1. confirmar a mano que sigue disponible,
2. marcarlo alquilado o pausarlo,
3. pedir confirmación al anunciante: el panel arma el mensaje de WhatsApp con un link único
   (`/verificar/{id}/{token}`) para que responda con un clic, sin registrarse.

El envío automático está detrás de la interfaz `App\Frescura\Notificador`
(`NOTIFICADOR=null|email|whatsapp`). Con `FRESCURA_AUTODESPUBLICAR=1`, el cron pausa los avisos
que no respondieron en `FRESCURA_DIAS_GRACIA` días.

## Tests

```bash
composer test
```
