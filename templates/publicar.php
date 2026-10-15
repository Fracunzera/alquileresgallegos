<?php /** @var int $maxParticular */ ?>
<div class="contenedor pagina-angosta">
  <h1>Publicá tu alquiler gratis</h1>
  <p class="intro">Publicar en <?= e(app_nombre()) ?> es gratis. Para cuidar que los avisos sean reales, cada aviso se publica desde una cuenta con email confirmado: la creás en un minuto y no necesitás contraseña.</p>

  <div class="tarjeta-admin">
    <p><a class="boton boton-whatsapp" href="/cuenta/registro">Crear mi cuenta gratis</a></p>
    <p>¿Ya tenés cuenta? <a href="/cuenta/ingresar">Entrá con tu email</a> y publicá.</p>
  </div>

  <section class="seccion">
    <h2>Cómo funciona</h2>
    <ol class="pasos">
      <li><strong>Creá tu cuenta</strong> con tu nombre y tu email. Te llega un link para entrar: no hay contraseñas que recordar.</li>
      <li><strong>Cargá el aviso</strong>: tipo de propiedad, barrio, precio, expensas, fotos y una buena descripción.</li>
      <li><strong>Lo revisamos y lo publicamos</strong>, normalmente en menos de 24 horas. Te avisamos por email.</li>
      <li><strong>Te consultan directo por WhatsApp.</strong> Desde tu cuenta ves cuántas visitas y consultas tiene, y lo pausás o lo marcás alquilado cuando quieras.</li>
    </ol>
  </section>

  <section class="seccion">
    <h2>Dueños e inmobiliarias</h2>
    <p>Si sos dueño o dueña, podés tener hasta <?= $maxParticular ?> avisos publicados a la vez. Si sos una inmobiliaria, <a href="/cuenta/registro?tipo=inmobiliaria">creá una cuenta de inmobiliaria</a>: la verificamos y después publicás sin límite, con tu nombre y tu logo.</p>
  </section>
</div>
