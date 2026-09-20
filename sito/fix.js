/* Stati del modulo + bottone WhatsApp che si sposta. Nessuna dipendenza. */
(() => {
  const msg = {
    nome: 'Scrivi il tuo nome, così sappiamo come chiamarti.',
    email: 'Controlla l\u2019indirizzo email: manca qualcosa.',
    telefono: 'Serve un numero valido per ricontattarti.'
  };
  const ok = (el) => {
    const v = el.value.trim();
    if (!v) return false;
    if (el.type === 'email') return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
    if (el.type === 'tel') return v.replace(/\D/g, '').length >= 8;
    return v.length >= 2;
  };
  document.querySelectorAll('form.form').forEach((form) => {
    const fields = Array.from(form.querySelectorAll('input[required]'));
    fields.forEach((el) => {
      const row = el.closest('.form__row');
      if (!row || row.querySelector('.form__err')) return;
      const p = document.createElement('p');
      p.className = 'form__err';
      p.id = el.id + '-err';
      p.textContent = msg[el.name] || 'Campo obbligatorio.';
      row.appendChild(p);
      const check = () => {
        const good = ok(el);
        row.classList.toggle('is-bad', !good);
        el.setAttribute('aria-invalid', good ? 'false' : 'true');
        el.setAttribute('aria-describedby', good ? '' : p.id);
        return good;
      };
      el.addEventListener('blur', check);
      el.addEventListener('input', () => { if (row.classList.contains('is-bad')) check(); });
      el._check = check;
    });
    const status = document.createElement('p');
    status.className = 'form__status';
    status.setAttribute('role', 'alert');
    form.appendChild(status);
    form.setAttribute('novalidate', '');
    form.addEventListener('submit', (e) => {
      const bad = fields.filter((el) => !el._check());
      if (bad.length) {
        e.preventDefault();
        status.textContent = bad.length === 1
          ? 'Manca un campo: controlla qui sopra.'
          : 'Mancano ' + bad.length + ' campi: controlla qui sopra.';
        status.classList.add('is-on');
        bad[0].focus();
        return;
      }
      status.classList.remove('is-on');
      form.classList.add('is-sending');
      const btn = form.querySelector('button[type=submit]');
      if (btn) { btn.dataset.label = btn.textContent; btn.textContent = 'Invio in corso…'; }
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
})();
