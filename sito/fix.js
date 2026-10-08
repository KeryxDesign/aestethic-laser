/* Stati del modulo + bottone WhatsApp che si sposta + banner cookie che si ritira. Nessuna dipendenza. */
(() => {
  // Testi: la landing Search (body.ep) usa la copy v7 di MUSE; le altre pagine restano come sono.
  const EP = document.body.classList.contains('ep');
  const T = EP ? {
    nome: 'Scrivi il tuo nome.',
    email: 'Scrivi la tua email.',
    emailBad: 'Questa email non sembra completa. Controlla la @ e il punto.',
    telefono: 'Scrivi il tuo numero di telefono.',
    telShort: 'Il numero sembra incompleto. Controlla le cifre.',
    privacy: 'Per prenotare serve la spunta sulla privacy.',
    summary: () => 'Manca qualcosa: controlla i campi segnati e riprova.',
    sending: 'Stiamo inviando i tuoi dati…'
  } : {
    nome: 'Scrivi il tuo nome, così sappiamo come chiamarti.',
    email: 'Controlla l’indirizzo email: manca qualcosa.',
    telefono: 'Serve un numero valido per ricontattarti.',
    privacy: 'Per prenotare serve la spunta sulla privacy.',
    summary: (n) => n === 1 ? 'Manca un campo: controlla qui sopra.' : 'Mancano ' + n + ' campi: controlla qui sopra.',
    sending: 'Invio in corso…'
  };
  const ok = (el) => {
    if (el.type === 'checkbox') return el.checked;
    const v = el.value.trim();
    if (!v) return false;
    if (el.type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
    if (el.type === 'tel') return v.replace(/\D/g, '').length >= 8;
    return v.length >= 2;
  };
  // Vuoto e incompleto hanno due messaggi diversi, dove la copy li prevede.
  const text = (el) => {
    if (el.type === 'checkbox') return T.privacy;
    const full = !!el.value.trim();
    if (el.name === 'email') return full && T.emailBad ? T.emailBad : T.email;
    if (el.name === 'telefono') return full && T.telShort ? T.telShort : T.telefono;
    return T[el.name] || 'Campo obbligatorio.';
  };
  document.querySelectorAll('form.form').forEach((form) => {
    const fields = Array.from(form.querySelectorAll('input[required]'));
    fields.forEach((el) => {
      const row = el.closest('.form__row');
      if (!row || row.querySelector('.form__err')) return;
      const p = document.createElement('p');
      p.className = 'form__err';
      p.id = el.id + '-err';
      p.textContent = text(el);
      row.appendChild(p);
      const check = () => {
        const good = ok(el);
        p.textContent = text(el);
        row.classList.toggle('is-bad', !good);
        el.setAttribute('aria-invalid', good ? 'false' : 'true');
        if (good) el.removeAttribute('aria-describedby'); else el.setAttribute('aria-describedby', p.id);
        return good;
      };
      if (el.type === 'checkbox') {
        el.addEventListener('change', () => { if (row.classList.contains('is-bad')) check(); });
      } else {
        el.addEventListener('blur', check);
        el.addEventListener('input', () => { if (row.classList.contains('is-bad')) check(); });
      }
      el._check = check;
    });
    const btn = form.querySelector('button[type=submit]');
    const status = document.createElement('p');
    status.className = 'form__status';
    status.setAttribute('role', 'alert');
    // Sulla landing Search la riga riassuntiva sta subito sopra il bottone.
    if (EP && btn) form.insertBefore(status, btn); else form.appendChild(status);
    form.setAttribute('novalidate', '');
    form.addEventListener('submit', (e) => {
      const bad = fields.filter((el) => !el._check());
      if (bad.length) {
        e.preventDefault();
        status.textContent = T.summary(bad.length);
        status.classList.add('is-on');
        bad[0].focus();
        return;
      }
      status.classList.remove('is-on');
      status.textContent = '';
      form.classList.add('is-sending');
      if (btn) { btn.dataset.label = btn.textContent; btn.textContent = T.sending; }
      setTimeout(() => {
        if (!document.body.contains(form)) return;
        form.classList.remove('is-sending');
        if (btn && btn.dataset.label) btn.textContent = btn.dataset.label;
      }, 12000);
    });
  });

  const wa = document.querySelector('.wa-float');
  const last = document.querySelector('.ctaform .form') || document.querySelector('form.form');
  if (wa && last && 'IntersectionObserver' in window) {
    new IntersectionObserver((es) => {
      es.forEach((en) => wa.setAttribute('data-tucked', String(en.isIntersecting)));
    }, { threshold: 0.25 }).observe(last);
  }

  // Banner cookie: non copre mai ciò che serve per prenotare. Sulla landing (body.ep) sono il bottone dell'hero
  // e il modulo (#prenota2, dentro #condizioni dal giro prezzi 07/10); altrove il modulo dell'hero, se c'è.
  // '#condizioni .btn' ora trova il bottone d'invio del modulo, che sta già dentro cta: doppione innocuo.
  // Se uno entra nella fascia bassa dello schermo il banner si ritira, e torna appena ne esce.
  // Serve lo stile .ccb[data-yield] della pagina: dove manca, l'attributo non fa nulla.
  // Landing (brief LORI 08/10): protegge anche la frase .ep-hero__limit («15 posti gratuiti, poi 97 €», vincolo LEX)
  // e si ritira quando la barra #ep-bar è accesa. La barra decide da sola; qui si osserva la barra, mai il contrario.
  // La ritirata tocca solo data-yield: nessun salvataggio della scelta, nessun Google Ads (lo scroll non è consenso).
  const banner = document.getElementById('cookie-banner');
  const hero = document.querySelector('form[data-form-location="hero"]');
  const cta = document.querySelector('body.ep form[data-form-location="cta"]');
  const forms = [hero, cta].filter(Boolean);
  if (banner && forms.length) {
    const guard = [hero, document.querySelector('.ep-hero__btn'), document.querySelector('.ep-hero__limit'), document.querySelector('#condizioni .btn'), cta].filter(Boolean);
    const bar = document.getElementById('ep-bar');
    let ticking = false;
    const yieldCheck = () => {
      ticking = false;
      if (!banner.classList.contains('ccb--show')) { banner.removeAttribute('data-yield'); return; }
      const band = window.innerHeight - banner.offsetHeight - 8;
      const overlap = guard.some((el) => {
        const r = el.getBoundingClientRect();
        return r.bottom > band && r.top < window.innerHeight;
      });
      const barOn = !!bar && bar.hasAttribute('data-on');
      const want = overlap || barOn;
      if (want === banner.hasAttribute('data-yield')) return; // scrive solo quando lo stato cambia
      if (want) banner.setAttribute('data-yield', ''); else banner.removeAttribute('data-yield');
    };
    const onMove = () => { if (!ticking) { ticking = true; requestAnimationFrame(yieldCheck); } };
    window.addEventListener('scroll', onMove, { passive: true });
    window.addEventListener('resize', onMove);
    forms.forEach((f) => f.addEventListener('focusin', () => banner.setAttribute('data-yield', '')));
    if (bar && 'MutationObserver' in window) new MutationObserver(onMove).observe(bar, { attributes: true, attributeFilter: ['data-on'] });
    // Ricalcolo dopo i font veri: il primo controllo gira coi font di ripiego e le altezze dell'hero cambiano.
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(onMove);
    window.addEventListener('load', onMove);
    yieldCheck();
  }
})();
