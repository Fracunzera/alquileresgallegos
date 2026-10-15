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
| `/publicar` | Formulario público sin registro (queda pendiente de moderación) | sí |
| `/buscar?…` | Buscador con varios filtros combinados | no |
| `/sitemap.xml`, `/robots.txt` | Dinámicos | — |

Cualquier filtro por querystring (`?dormitorios=2`, `?orden=…`) vuelve la página `noindex,follow` con
canonical a la landing base, para no generar contenido duplicado. La paginación usa `?pagina=N`
con `rel=prev/next`.

## Panel (`/admin`)

- **Avisos:** alta rápida pensada para sembrar el sitio: pegás el texto del posteo de Facebook o de
  la web de la inmobiliaria, tocás *Autocompletar* (detecta precio, expensas, WhatsApp, dormitorios,
  tipo, barrio…), revisás y guardás. *Guardar y cargar otro* repite tipo, barrio, contacto e
  inmobiliaria para cargar en tanda.
- **Moderación:** lo que llega por `/publicar`. Se aprueba (asignando barrio si escribieron "Otro")
  o se rechaza con motivo.
- **Verificación:** cola de frescura (ver abajo).
- **Inmobiliarias y barrios:** ABM. Los barrios tienen intro propia para su landing.

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

- Mandar email al anunciante cuando su aviso se aprueba o se rechaza (hoy solo se ve en el panel).
- Implementar `WhatsappNotificador` con la API de WhatsApp Business (requiere plantilla aprobada por Meta).
- Imagen de Open Graph propia para las landings (hoy usan la genérica del sitio).
- Monetización: el campo `destacado` ya ordena primero en todos los listados.
