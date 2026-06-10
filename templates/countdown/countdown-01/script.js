(function() {
  const storageKey = 'cms_cd_end';
  let endTime = localStorage.getItem(storageKey);
  if (!endTime || Date.now() > parseInt(endTime)) {
    endTime = Date.now() + 24 * 60 * 60 * 1000;
    localStorage.setItem(storageKey, endTime);
  }
  function update() {
    const diff = Math.max(0, parseInt(endTime) - Date.now());
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    const pad = n => String(n).padStart(2, '0');
    const hEl = document.getElementById('cd-h');
    const mEl = document.getElementById('cd-m');
    const sEl = document.getElementById('cd-s');
    if (hEl) hEl.textContent = pad(h);
    if (mEl) mEl.textContent = pad(m);
    if (sEl) sEl.textContent = pad(s);
  }
  update();
  setInterval(update, 1000);
})();
