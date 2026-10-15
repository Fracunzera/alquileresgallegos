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

- PHP 8.3+ sin framework (router propio, PDO, plantillas PHP con escape). **Recomendado: PHP 8.4.**
- MySQL 8.4 LTS o MariaDB 10.11/11.4 (MySQL 8.0 ya no tiene soporte).
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
cp .env.example .env            # completar DB_*, APP_URL=http://localhost:8000 y APP_KEY
php -r "echo bin2hex(random_bytes(32));"   # valor para APP_KEY
composer install                # solo hace falta para correr los tests
php bin/migrate.php --seed      # crea tablas y siembra los barrios
php bin/crear-admin.php vos@dominio.com "Tu nombre"   # usuario del panel
php bin/datos-demo.php 60       # opcional: avisos de prueba (no corre en producción)
composer serve                  # http://localhost:8000
```

El panel de administración está en `/admin`.

## Páginas

| Ruta | Qué es | Indexable |
|---|---|---|
| `/` | Últimos avisos, accesos, barrios e inmobiliarias | sí |
| `/alquileres-mensuales-rio-gallegos`, `/alquiler-temporario-rio-gallegos`, `/departamentos-alquiler-rio-gallegos`, `/casas-alquiler-rio-gallegos`, `/monoambientes-rio-gallegos` | Landings con title, description e intro propios (`src/Seo/Landings.php`) | sí, si tienen avisos |
| `/alquileres-{barrio}-rio-gallegos` | Landing por barrio (intro editable en el panel) | sí, si tiene avisos |
| `/aviso/{id}-{slug}` | Ficha. Si cambia el título, la URL vieja hace 301 | si está activo |
| `/inmobiliaria/{slug}` | Página de la inmobiliaria con sus avisos | sí, si tiene avisos |
| `/publicar` | Formulario público sin registro: confirmación por email y después moderación | sí |
| `/aviso/{id}/denunciar` | Denuncia de un aviso (puede pausarlo solo) | no |
| `/cuenta` | Panel del anunciante registrado (ingreso con link por email) | no |
| `/buscar?…` | Buscador con varios filtros combinados | no |
| `/sitemap.xml`, `/robots.txt` | Dinámicos | — |

Cualquier filtro por querystring (`?dormitorios=2`, `?orden=…`) vuelve la página `noindex,follow` con
canonical a la landing base, para no generar contenido duplicado. La paginación usa `?pagina=N`
con `rel=prev/next`.

## Cuentas de anunciantes (`/cuenta`)

Para quien publica varios avisos o es inmobiliaria. Sin contraseña: se entra con un link de un solo
uso que llega por email.

- **Mi cuenta:** lista de avisos con estado, visitas y consultas de los últimos 30 días; acciones
  "sigue disponible", "ya se alquiló", pausar, volver a publicar, editar y fotos.
- **Particulares:** hasta 3 avisos vigentes (`CUENTA_MAX_AVISOS_PARTICULAR`).
- **Inmobiliarias:** sin límite, pero recién publican cuando las vinculás a su ficha desde
  `/admin/cuentas` (así nadie se hace pasar por una inmobiliaria).
- **Moderación:** los avisos nuevos de una cuenta pasan por moderación hasta que la marques como
  confiable. Si una cuenta no confiable edita un aviso publicado, el cambio sale al instante pero
  te queda en "Cambios para revisar".
- En el primer ingreso, la cuenta adopta los avisos que esa persona había publicado antes sin
  cuenta (mismo email).

## Estadísticas

Cada ficha cuenta visitas (una por aviso y sesión del navegador) y clics en WhatsApp, teléfono y
email con `navigator.sendBeacon`, descartando bots y vistas previas. Se ven en el panel del
anunciante, en el admin y en el mensaje de "¿sigue disponible?". Son aproximadas.

## Panel (`/admin`)

- **Avisos:** alta rápida pensada para sembrar el sitio: pegás el texto del posteo de Facebook o de
  la web de la inmobiliaria, tocás *Autocompletar* (detecta precio, expensas, WhatsApp, dormitorios,
  tipo, barrio…), revisás y guardás. *Guardar y cargar otro* repite tipo, barrio, contacto e
  inmobiliaria para cargar en tanda.
- **Moderación:** lo que llega por `/publicar`, recién cuando la persona confirmó su email (los que
  no confirman en 72 h se borran solos). Se aprueba asignando barrio si escribieron "Otro", se
  rechaza con motivo (en los dos casos se le avisa por email) o se rechaza y se bloquea.
- **Denuncias:** cada ficha tiene "Denunciar este aviso". Con una denuncia de "usan mi número", o
  con 3 desde conexiones distintas, el aviso se pausa solo hasta que lo revises.
- **Bloqueos:** WhatsApp y email bloqueados para siempre; IP por 7 días (en los celulares muchas
  personas comparten IP). Lo que llega de un bloqueado se descarta en silencio.
- **Verificación:** cola de frescura (ver abajo).
- **Cuentas:** vincular inmobiliarias, marcar confiables, suspender (pausa sus avisos).
- **Inmobiliarias y barrios:** ABM. Los barrios tienen intro propia para su landing.

## Despliegue

> Antes de publicar, recorré el **checklist de [docs/SEGURIDAD.md](docs/SEGURIDAD.md)**.

- **Apache / hosting compartido (cPanel):** apuntar el document root a `public/`.
  El `public/.htaccess` ya redirige todo a `index.php`. Si el hosting no deja cambiar el document
  root y el proyecto queda entero en `public_html`, el `.htaccess` de la raíz manda todo a
  `public/` y bloquea el resto (`.env`, `storage/`, `src/`…).
- En producción no hace falta `composer install`: el sitio tiene autoload propio (Composer es
  solo para correr los tests).
- **nginx:**

  ```nginx
  root /ruta/al/proyecto/public;
  location / { try_files $uri /index.php?$query_string; }
  location ^~ /uploads/ { location ~ \.php$ { deny all; } }   # nunca ejecutar PHP subido
  location ~ /\. { deny all; }
  location = /index.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/index.php; fastcgi_pass unix:/run/php/php8.4-fpm.sock; }
  location ~ \.php$ { return 404; }                            # el único PHP público es index.php
  ```

- **Cron diario** (frescura de avisos):

  ```
  15 6 * * * php /ruta/al/proyecto/bin/frescura.php >> /ruta/al/proyecto/storage/logs/frescura.log 2>&1
  ```

  Además de la frescura, borra los avisos de `/publicar` que no confirmaron el email.

## Sitio de prueba y paso al dominio final

Primero el sitio corre en **alquileres.blucaro.site** (prueba) y después en
**alquileresriogallegos.com.ar** (definitivo).

En el `.env` del sitio de prueba:

```
APP_URL=https://alquileres.blucaro.site
APP_INDEXAR=0
```

Con `APP_INDEXAR=0` todas las páginas salen con `noindex`, no hay sitemap y se ve un aviso de
"versión de prueba", así Google no indexa el dominio de prueba (sería contenido duplicado del
definitivo). Las vistas previas de WhatsApp siguen funcionando para probar.

Para pasar al dominio final (la base no guarda URLs absolutas, así que alcanza con esto):

1. Copiar la base y `public/uploads/` al servidor definitivo (o apuntar el dominio nuevo al mismo).
2. En el `.env`: `APP_URL=https://alquileresriogallegos.com.ar` y `APP_INDEXAR=1`.
3. Redirigir `alquileres.blucaro.site` con 301 al dominio final, conservando la ruta, o darlo de baja.
4. Dar de alta el dominio final en Google Search Console y enviar el sitemap.

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

## Email (Brevo)

Los emails (confirmación, aviso aprobado/rechazado, verificación con `NOTIFICADOR=email` y avisos
internos a `MODERACION_EMAIL`) salen por la API de [Brevo](https://www.brevo.com):

1. Crear la cuenta y una API key (*SMTP & API → API keys*).
2. Autenticar el dominio del sitio (*Senders, domains → Domains*): agregar los registros SPF, DKIM y
   DMARC que indica Brevo en el DNS. Sin esto los emails van a spam.
3. En `.env`: `MAIL_DRIVER=brevo`, `BREVO_API_KEY=…`, `MAIL_FROM=avisos@tudominio` y
   `MODERACION_EMAIL=` con tu correo.

En desarrollo, `MAIL_DRIVER=log` escribe los emails en `storage/logs/mail.log` en vez de mandarlos.

## Mapa

Quien publica marca la ubicación tocando un mapa (opcional) y elige el barrio de la lista.
En la ficha:

- si la dirección **no** es pública, las coordenadas se redondean a ~100 m en el servidor y se
  muestra un círculo: la ubicación exacta nunca llega al HTML;
- Leaflet (`public/assets/vendor/leaflet`, BSD-2) se carga recién cuando el mapa entra en pantalla;
- sin JavaScript queda el link "Abrir en el mapa".

Los tiles salen de `MAPA_TILES_URL`. Los de `tile.openstreetmap.org` alcanzan para el
lanzamiento, pero su [política de uso](https://operations.osmfoundation.org/policies/tiles/) no
admite tráfico alto: si el sitio crece, cambiar a un proveedor (MapTiler, Stadia, etc.) es solo
editar el `.env`.

## Pendiente / próximos pasos

- Abono para inmobiliarias y avisos destacados pagos (la estructura ya está: cuentas vinculadas y `destacado`).
- Implementar `WhatsappNotificador` con la API de WhatsApp Business (requiere plantilla aprobada por Meta).
- Imagen de Open Graph propia para las landings (hoy usan la genérica del sitio).
- Monetización: el campo `destacado` ya ordena primero en todos los listados.
