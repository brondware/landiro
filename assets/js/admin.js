// Shared admin utilities

async function api(action, data = {}) {
  try {
    const res = await fetch(ADMIN_URL + '/api.php?action=' + action, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': (typeof CSRF_TOKEN !== 'undefined' ? CSRF_TOKEN : '')
      },
      body: JSON.stringify(data)
    });
    return await res.json();
  } catch (e) {
    return { success: false, error: e.message };
  }
}

// Tabs
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      const tabId = tab.dataset.tab;
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      const content = document.getElementById('tab-' + tabId);
      if (content) content.classList.add('active');
    });
  });

  // Color inputs sync
  document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textId = colorInput.id + '_text';
    const textInput = document.getElementById(textId);
    if (!textInput) return;
    colorInput.addEventListener('input', () => { textInput.value = colorInput.value; });
    textInput.addEventListener('input', () => {
      if (/^#[0-9a-f]{6}$/i.test(textInput.value)) {
        colorInput.value = textInput.value;
      }
    });
  });
});
