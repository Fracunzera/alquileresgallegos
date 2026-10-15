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

  // Mapas (Leaflet): se cargan recién cuando el mapa entra en pantalla.
  var mapas = document.querySelectorAll('[data-mapa], [data-mapa-selector]');
  if (!mapas.length) return;

  var config = document.body.dataset;
  var leaflet = null;
  function cargarLeaflet() {
    if (leaflet) return leaflet;
    leaflet = new Promise(function (resolve, reject) {
      var css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = '/assets/vendor/leaflet/leaflet.css';
      document.head.appendChild(css);
      var js = document.createElement('script');
      js.src = '/assets/vendor/leaflet/leaflet.js';
      js.onload = function () { resolve(window.L); };
      js.onerror = reject;
      document.head.appendChild(js);
    });
    return leaflet;
  }

  function crearMapa(L, el, lat, lng, zoom) {
    var mapa = L.map(el, { scrollWheelZoom: false, dragging: !L.Browser.mobile || el.hasAttribute('data-mapa-selector'), tap: false });
    L.tileLayer(config.mapaTiles, { maxZoom: 19, attribution: config.mapaAtribucion }).addTo(mapa);
    mapa.setView([lat, lng], zoom);
    return mapa;
  }

  function iniciar(el) {
    cargarLeaflet().then(function (L) {
      if (el.hasAttribute('data-mapa')) {
        var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
        var mapa = crearMapa(L, el, lat, lng, 15);
        if (el.dataset.aproximado === '1') {
          L.circle([lat, lng], { radius: 250, color: '#0d3b66', fillOpacity: 0.15 }).addTo(mapa);
        } else {
          L.marker([lat, lng]).addTo(mapa);
        }
        return;
      }
      // Selector de ubicación en formularios: un toque pone el pin; se puede arrastrar.
      var form = el.closest('form');
      var inLat = form.querySelector('[name="lat"]'), inLng = form.querySelector('[name="lng"]');
      var tiene = inLat.value !== '' && inLng.value !== '';
      var mapa2 = crearMapa(L, el, tiene ? +inLat.value : +config.mapaLat, tiene ? +inLng.value : +config.mapaLng, tiene ? 16 : 13);
      var pin = null;
      function poner(latlng) {
        inLat.value = latlng.lat.toFixed(6);
        inLng.value = latlng.lng.toFixed(6);
        if (pin) { pin.setLatLng(latlng); return; }
        pin = L.marker(latlng, { draggable: true }).addTo(mapa2);
        pin.on('dragend', function () { poner(pin.getLatLng()); });
      }
      if (tiene) poner(L.latLng(+inLat.value, +inLng.value));
      mapa2.on('click', function (ev) { poner(ev.latlng); });
      var quitar = form.querySelector('[data-mapa-quitar]');
      if (quitar) {
        quitar.hidden = false;
        quitar.addEventListener('click', function () {
          inLat.value = inLng.value = '';
          if (pin) { mapa2.removeLayer(pin); pin = null; }
        });
      }
    }).catch(function () { el.hidden = true; });
  }

  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (en) {
        if (en.isIntersecting) { obs.unobserve(en.target); iniciar(en.target); }
      });
    }, { rootMargin: '200px' });
    mapas.forEach(function (el) { obs.observe(el); });
  } else {
    mapas.forEach(iniciar);
  }
})();
