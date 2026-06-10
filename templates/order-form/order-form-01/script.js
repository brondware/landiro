async function submitOrderForm(e, form) {
  e.preventDefault();
  const btn = form.querySelector('.of-btn');
  const origText = btn.textContent;
  btn.textContent = 'Відправляємо...';
  btn.disabled = true;
  const data = Object.fromEntries(new FormData(form));
  try { Object.assign(data, JSON.parse(sessionStorage.getItem('_cms_utms') || '{}')); } catch(e) {}
  // A/B variant tracking
  try {
    const ab = {};
    document.cookie.split(';').forEach(c => {
      const [k, v] = c.trim().split('=');
      if (k && k.startsWith('_ab_')) ab[k.slice(4)] = v;
    });
    if (Object.keys(ab).length) data._ab_variants = ab;
  } catch(e) {}
  try {
    const res = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CMS-Form': '1' },
      body: JSON.stringify(data)
    });
    const json = await res.json();
    if (json.success) {
      if (typeof fbq !== 'undefined') fbq('track', 'Lead');
      if (typeof gtag !== 'undefined') gtag('event', 'conversion');
      if (window._CMS_SUCCESS_URL) {
        setTimeout(() => { location.href = window._CMS_SUCCESS_URL; }, 800);
        return;
      }
      form.style.display = 'none';
      const success = document.getElementById('of-success');
      if (success) {
        success.style.display = 'block';
        if (json.message) success.querySelector('p').textContent = json.message;
      }
    } else {
      alert(json.message || 'Помилка. Спробуйте ще раз.');
      btn.textContent = origText;
      btn.disabled = false;
    }
  } catch(err) {
    alert('Помилка мережі. Перевірте підключення.');
    btn.textContent = origText;
    btn.disabled = false;
  }
}
