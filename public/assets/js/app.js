/* Mejoras progresivas. Todo el sitio funciona sin este archivo. */
(function () {
  'use strict';

  // Galería: contador "2 / 8", flechas en escritorio y teclado.
  document.querySelectorAll('[data-galeria]').forEach(function (galeria) {
    var pista = galeria.querySelector('.galeria-pista');
    var fotos = pista ? pista.children : [];
    if (fotos.length < 2) return;

    var contador = document.createElement('span');
    contador.className = 'galeria-contador';
    galeria.appendChild(contador);
    var ayuda = galeria.querySelector('.galeria-ayuda');
    if (ayuda) ayuda.hidden = true;

    function actual() { return Math.round(pista.scrollLeft / pista.clientWidth); }
    function ir(n) {
      n = Math.max(0, Math.min(fotos.length - 1, n));
      pista.scrollTo({ left: n * pista.clientWidth, behavior: 'smooth' });
    }
    function actualizar() { contador.textContent = (actual() + 1) + ' / ' + fotos.length; }

    [['izq', '‹', -1, 'Foto anterior'], ['der', '›', 1, 'Foto siguiente']].forEach(function (d) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'galeria-flecha galeria-flecha-' + d[0];
      b.textContent = d[1];
      b.setAttribute('aria-label', d[3]);
      b.addEventListener('click', function () { ir(actual() + d[2]); });
      galeria.appendChild(b);
    });

    pista.tabIndex = 0;
    pista.addEventListener('keydown', function (ev) {
      if (ev.key === 'ArrowRight') { ir(actual() + 1); ev.preventDefault(); }
      if (ev.key === 'ArrowLeft') { ir(actual() - 1); ev.preventDefault(); }
    });
    pista.addEventListener('scroll', function () { window.requestAnimationFrame(actualizar); }, { passive: true });
    actualizar();
  });

  // Filtros: al cambiar un select se envía el formulario (sin JS hay botón "Filtrar").
  document.querySelectorAll('[data-filtros] select').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });
})();
