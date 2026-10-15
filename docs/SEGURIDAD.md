# Seguridad — Alquileres Río Gallegos

Revisión hecha antes del primer despliegue (octubre de 2026). Incluye lo que se corrigió, qué
protecciones tiene cada formulario, el checklist para el servidor y las versiones a usar.

## 1. Qué se revisó y qué se corrigió

| Área | Resultado |
|---|---|
| Inyección SQL | OK. Todas las consultas usan parámetros (PDO con prepares reales). Lo único interpolado son nombres de columna de listas fijas y enteros. |
| XSS | OK. Toda salida pasa por `e()`; el JSON-LD se codifica con `JSON_HEX_TAG`. Además hay Content-Security-Policy (ver abajo). |
| CSRF | OK. Todo POST del panel y de las cuentas exige token de sesión; cookie `SameSite=Lax`. Los formularios públicos llevan un token firmado. |
| Control de acceso | OK. Todas las rutas `/admin` pasan por `Admin\Base` (sesión obligatoria) y en `/cuenta` cada acción verifica que el aviso sea de esa cuenta. |
| Subida de fotos | OK. Solo JPEG/PNG/WebP verificados por contenido, máximo 40 MP, se re-codifican con GD (se descartan metadatos y GPS) y se guardan con nombre aleatorio `.webp`/`.jpg`. `uploads/.htaccess` impide ejecutar PHP. |
| Contraseñas | OK. `password_hash` (bcrypt); el admin pide 10+ caracteres. Los anunciantes no tienen contraseña (link por email de un solo uso). |
| Tokens (links por email) | OK. 192 bits aleatorios, en la base solo el hash SHA-256, un solo uso, vencimiento. Los links se confirman con POST (los filtros de correo y WhatsApp abren los GET). |
| **Redirección abierta** | **Corregido.** `/\evil.com/` redirigía a `/\evil.com`, que los navegadores interpretan como `//evil.com` (otro sitio): servía para phishing con links "del portal". Ahora esos paths dan 404. |
| **Cabeceras** | **Corregido.** Se agregaron Content-Security-Policy, Permissions-Policy, Cross-Origin-Opener-Policy y HSTS (si `APP_URL` es https). Se quitó `X-Powered-By` (mostraba la versión de PHP). |
| **JavaScript inline** | **Corregido.** Los `onsubmit="confirm(...)"` del panel pasaron a `data-confirmar`; la CSP solo permite un script inline, autorizado por hash. |
| **Sesión** | **Mejorado.** Cookie `Secure` cuando el sitio es https; sesiones guardadas en `storage/sessions` (en muchos servidores el sistema borra las sesiones por su cuenta y te desloguea a los 24 min); la sesión del admin vence a las 12 h sin uso. |
| **Fuerza bruta** | **Mejorado.** Login del admin: 5 intentos por IP y 10 por email cada 15 min (frena ataques desde muchas IP). Links de ingreso: máximo 5 por hora a la misma casilla (que nadie use el sitio para inundar el correo de otro). |
| **IP detrás de un proxy** | **Corregido para el despliegue.** Detrás de Cloudflare o del balanceador del hosting todos los visitantes llegan con la IP del proxy: los límites ("5 avisos por día") serían para todo el sitio y bloquear una IP bloquearía a todos. Configurar `PROXIES_CONFIABLES` (ver checklist). |
| **Estadísticas** | **Mejorado.** Se ignoran los beacons enviados desde otros sitios. Siguen siendo aproximadas: un script puede inflarlas imitando un navegador. |
| Dependencias | `composer audit`: sin vulnerabilidades. En producción no hace falta `vendor/` (el sitio tiene autoload propio; Composer es solo para los tests). |

## 2. ¿Hace falta captcha?

**Al lanzar, no.** Los captchas espantan a la gente desde el celular y cada formulario ya tiene
varias barreras:

| Formulario | Protecciones |
|---|---|
| `/publicar` | **Ya no recibe avisos anónimos:** para publicar hace falta una cuenta con email confirmado. Los avisos de cuentas nuevas pasan por **moderación** hasta que las marques como confiables; una cuenta problemática se suspende entera. |
| `/cuenta/registro` | Campo trampa, token con tiempo mínimo, 5 cuentas por IP por día, lista de bloqueo, confirmación por email (primer ingreso) y moderación hasta marcar la cuenta confiable. |
| `/cuenta/ingresar` | Token, 10 pedidos por IP por hora, 5 links por hora por casilla. Solo manda emails a cuentas existentes. |
| `/aviso/{id}/denunciar` | Campo trampa, token, 10 denuncias por IP por día, una denuncia abierta por IP y aviso, lista de bloqueo. |
| `/admin/login` | 5 intentos por IP y 10 por email cada 15 minutos. |

**Captcha listo para prender:** si aparece spam que pase esas barreras (avisos basura en la cola
de moderación todos los días, cuentas falsas en serie), se activa **Cloudflare Turnstile** en
`/cuenta/registro` y `/denunciar` cargando dos claves en el `.env`:

```
TURNSTILE_SITE_KEY=...
TURNSTILE_SECRET=...
```

Las claves se crean gratis en dash.cloudflare.com → Turnstile. Es casi siempre invisible (no hay
que marcar semáforos) y la CSP se ajusta sola. Si Cloudflare no responde, el envío se acepta y se
registra en el log, para que una caída de ellos no deje el sitio sin formularios.

## 3. Checklist del servidor (antes de publicar)

- [ ] **Document root apuntando a `public/`.** Si el hosting no deja cambiarlo y el proyecto queda
      entero dentro de `public_html`, el `.htaccess` de la raíz redirige todo a `public/` y
      bloquea el resto, pero probá que `https://tusitio/.env` y `https://tusitio/storage/` den
      error.
- [ ] `.env` con `APP_ENV=production`, **`APP_DEBUG=0`**, `APP_URL=https://…` y una `APP_KEY` nueva
      (`php -r "echo bin2hex(random_bytes(32));"`). Permisos del archivo: `chmod 600 .env`.
- [ ] **HTTPS** activo (Let's Encrypt o el certificado del hosting) y redirección de http a https.
- [ ] Usuario de base de datos **solo para este sitio**, con permisos únicamente sobre su base
      (`GRANT ALL ON alquileres.* TO …`), nunca root.
- [ ] `storage/` y `public/uploads/` con permiso de escritura para PHP; el resto, solo lectura.
- [ ] Si hay un proxy adelante (Cloudflare, balanceador del hosting): `PROXIES_CONFIABLES` con sus
      IPs y `IP_CABECERA` (`CF-Connecting-IP` con Cloudflare; rangos en cloudflare.com/ips).
- [ ] php.ini: `expose_php = Off`, `display_errors = Off`, `upload_max_filesize = 8M`,
      `post_max_size = 100M`, `max_file_uploads = 12`, `memory_limit = 256M`.
- [ ] nginx: que no ejecute PHP dentro de `/uploads` (ver README). Apache ya lo bloquea con
      `public/uploads/.htaccess`.
- [ ] Contraseña del admin larga y única (gestor de contraseñas). Opcional, una capa más:
      proteger `/admin` con usuario y clave del servidor (Basic Auth) o limitarlo a tu IP.
- [ ] Brevo con el dominio autenticado (SPF, DKIM y DMARC) para que los emails no vayan a spam.
- [ ] **Backups** diarios de la base y de `public/uploads/` fuera del servidor.
- [ ] Cron diario de `bin/frescura.php`.
- [ ] No subir al servidor: `.git/`, `tests/`, `vendor/`, `.env` de desarrollo.

## 4. Versiones y avisos de seguridad conocidos (octubre de 2026)

### PHP

| Versión | Soporte de seguridad hasta | Recomendación |
|---|---|---|
| 8.2 | 31/12/2026 | Evitar: le quedan pocos meses. |
| 8.3 | 31/12/2027 | Aceptable. |
| **8.4** | 31/12/2028 | **Recomendada.** |
| 8.5 | 31/12/2029 | Recomendada si el hosting la ofrece. |

Usar siempre la **última versión menor** de la rama (las de agosto de 2026 son 8.3.33, 8.4.24 y
8.5.9). Avisos de 2026 y si afectan a este sitio:

- **CVE-2026-9672 (libgd, archivos GIF):** el sitio procesa fotos con GD, pero **rechaza los GIF**
  antes de decodificarlos, así que no está expuesto. Igual conviene PHP 8.4.24 / 8.5.9 o posterior.
- **CVE-2026-17543 (ext-pgsql):** no aplica, se usa MySQL.
- **CVE-2026-6735 (página de estado de PHP-FPM):** no exponer `/status` de FPM a internet.
- **CVE-2024-4577 (PHP-CGI en Windows):** solo afecta servidores Windows con PHP en modo CGI.

### Base de datos

- **MySQL 8.0 terminó su soporte el 30/04/2026:** no usarla. Usar **MySQL 8.4 LTS** (soporte hasta 2032).
- **MariaDB:** usar una LTS vigente (10.11 u 11.4). Revisar la fecha de la 10.6 en el calendario
  de mariadb.org antes de elegirla.

### JavaScript: Leaflet 1.9.4

- **CVE-2025-69993 (XSS en `bindPopup()`):** afecta a Leaflet hasta 1.9.4 y todavía no hay versión
  estable corregida (2.0 está en alpha). **El sitio no usa `bindPopup()`** ni pasa contenido de
  usuarios a Leaflet, así que no está expuesto. Si en el futuro se agregan popups con datos de
  avisos, escaparlos antes. Revisar en unos meses si sale 1.9.5 o 2.0 estable.

### Dependencias de Composer

- Solo PHPUnit (desarrollo). `composer audit` sin avisos.

## 5. Riesgos que quedan (aceptados)

- **El admin no tiene segundo factor (2FA).** Lo cubren el límite de intentos, una contraseña
  fuerte y, opcionalmente, Basic Auth o restricción por IP en `/admin`.
- **Las cuentas dependen de la seguridad del email del anunciante** (como todo login por link).
- **"Usan mi número" pausa un aviso con una sola denuncia:** alguien podría usarlo para bajar
  avisos ajenos; queda limitado por el email obligatorio, 10 denuncias por IP por día y la revisión
  en `/admin/denuncias`.
- **Ataques de volumen (DoS):** no se mitigan desde la aplicación. Poner Cloudflare (plan gratis)
  adelante del sitio y configurar `PROXIES_CONFIABLES`.

## Fuentes

- [php.net — Supported Versions](https://www.php.net/supported-versions.php)
- [Pantheon — PHP 8.2–8.5 security updates (agosto 2026)](https://docs.pantheon.io/release-notes/2026/08/php-82-83-84-85-security-updates)
- [HKCERT — PHP Multiple Vulnerabilities](https://www.hkcert.org/security-bulletin/php-multiple-vulnerabilities_20260803)
- [Zend — CVE-2026-6735](https://www.zend.com/blog/cve-2026-6735)
- [Debian security tracker — CVE-2026-9672](https://security-tracker.debian.org/tracker/CVE-2026-9672)
- [Amazon Linux — CVE-2026-9672](https://explore.alas.aws.amazon.com/CVE-2026-9672.html)
- [Oracle — fin de vida de MySQL 8.0](https://blogs.oracle.com/mysql/post/endoflife-of-heatwave-mysql-80-version)
- [MySQL 8.0 end of life (datavail)](https://www.datavail.com/blog/data-platforms-reaching-end-of-life-in-april-2026/)
- [OSV — CVE-2025-69993 (Leaflet)](https://osv.dev/vulnerability/CVE-2025-69993)
- [Debian security tracker — CVE-2025-69993](https://security-tracker.debian.org/tracker/CVE-2025-69993)
