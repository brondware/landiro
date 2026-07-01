<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/OrderLog.php';

Auth::requireLogin();

$landingManager = new Landing();
$orderLog = new OrderLog();

$landings = $landingManager->getAll();
$selectedSlug = $_GET['slug'] ?? ($landings[0]['slug'] ?? '');
$filterStatus = $_GET['status'] ?? 'all';

// CSV export
if (isset($_GET['export']) && $selectedSlug) {
    $csv = $orderLog->toCsv($selectedSlug);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="orders-' . $selectedSlug . '-' . date('Ymd') . '.csv"');
    echo $csv;
    exit;
}

$allOrders = $selectedSlug ? $orderLog->getAll($selectedSlug, 500) : [];

// Count by status
$statusCounts = ['all' => count($allOrders), 'new' => 0, 'called' => 0, 'confirmed' => 0, 'canceled' => 0];
foreach ($allOrders as $o) {
    $s = $o['status'] ?? 'new';
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}

// Filter
$orders = $filterStatus === 'all' ? $allOrders : array_values(array_filter($allOrders, function($o) use ($filterStatus) { return ($o['status'] ?? 'new') === $filterStatus; }));
$total  = count($orders);

// Revenue summary for filtered orders
$revTotal = 0.0; $revCount = 0;
foreach ($orders as $o) {
    if (isset($o['price']) && $o['price'] > 0) { $revTotal += (float)$o['price']; $revCount++; }
}

// Collect data field names
$dataFields = [];
foreach (array_slice($orders, 0, 30) as $o) {
    foreach (array_keys($o['data'] ?? []) as $k) {
        if (!in_array($k, $dataFields)) $dataFields[] = $k;
    }
}

$statusLabels = ['new' => 'Новий', 'called' => 'Задзвонено', 'confirmed' => 'Підтверджено', 'canceled' => 'Скасовано'];
$statusColors = ['new' => '#3b82f6', 'called' => '#f59e0b', 'confirmed' => '#16a34a', 'canceled' => '#ef4444'];
$statusBg     = ['new' => '#eff6ff', 'called' => '#fffbeb', 'confirmed' => '#f0fdf4', 'canceled' => '#fef2f2'];
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Замовлення — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.orders-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.orders-filter select { border: 1.5px solid var(--c-border); border-radius: 8px; padding: 7px 12px; font-size: 13px; outline: none; background: #fff; cursor: pointer; }

.status-tabs { display: flex; gap: 6px; margin-bottom: 18px; flex-wrap: wrap; }
.status-tab { padding: 5px 13px; border-radius: 99px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1.5px solid transparent; transition: .15s; background: var(--c-bg); color: var(--c-muted); text-decoration: none; }
.status-tab:hover { border-color: var(--c-border); color: var(--c-text); }
.status-tab.active { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
.status-tab .tab-cnt { opacity: .8; margin-left: 4px; }

.orders-table-wrap { background: #fff; border: 1.5px solid var(--c-border); border-radius: 12px; overflow: auto; }
.orders-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.orders-table th { padding: 10px 12px; font-size: 11px; font-weight: 600; color: var(--c-muted); text-align: left; background: var(--c-bg); border-bottom: 1.5px solid var(--c-border); text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; position: sticky; top: 0; z-index: 1; }
.orders-table td { padding: 8px 12px; border-bottom: 1px solid var(--c-border); vertical-align: middle; }
.orders-table tr:last-child td { border-bottom: none; }
.orders-table tr:hover td { background: #fafafa; }
.order-time { color: var(--c-muted); font-size: 12px; white-space: nowrap; }
.order-del { opacity: .35; cursor: pointer; background: none; border: none; color: #ef4444; padding: 3px 6px; border-radius: 5px; }
.order-del:hover { opacity: 1; background: #fef2f2; }
.utm-pill { display: inline-block; background: #f0fdf4; color: #15803d; border-radius: 99px; padding: 1px 7px; font-size: 11px; font-weight: 500; }
.ab-pill { display: inline-block; background: #ede9fe; color: #7c3aed; border-radius: 99px; padding: 1px 7px; font-size: 11px; font-weight: 500; }
.no-orders { text-align: center; padding: 60px 24px; color: var(--c-muted); }

/* Status badge & dropdown */
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 600; cursor: pointer; user-select: none; white-space: nowrap; border: 1px solid transparent; transition: .1s; }
.status-badge:hover { filter: brightness(.95); }
.status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

.status-dropdown { position: relative; display: inline-block; }
.status-menu { display: none; position: absolute; left: 0; top: 100%; margin-top: 3px; background: #fff; border: 1.5px solid var(--c-border); border-radius: 10px; padding: 4px; box-shadow: 0 4px 16px rgba(0,0,0,.12); z-index: 100; min-width: 160px; }
.status-dropdown.open .status-menu { display: block; }
.status-menu-item { display: flex; align-items: center; gap: 8px; padding: 7px 10px; border-radius: 7px; cursor: pointer; font-size: 12px; font-weight: 500; transition: .1s; }
.status-menu-item:hover { background: var(--c-bg); }

/* Note cell */
.note-cell { max-width: 180px; }
.note-text { font-size: 12px; color: var(--c-muted); cursor: pointer; border-bottom: 1px dashed var(--c-border); display: inline-block; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.note-text.empty { color: #c9ccd1; }
.note-text:hover { border-color: var(--c-primary); color: var(--c-primary); }
.note-input { display: none; font-size: 12px; border: 1.5px solid var(--c-primary); border-radius: 6px; padding: 4px 7px; width: 160px; outline: none; }

/* Revenue bar */
.revenue-bar { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; background: #fff; border: 1.5px solid var(--c-border); border-radius: 10px; padding: 10px 18px; margin-bottom: 16px; }
.revenue-item { display: flex; flex-direction: column; }
.revenue-label { font-size: 11px; color: var(--c-muted); text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
.revenue-val { font-size: 20px; font-weight: 800; color: var(--c-text); }
.revenue-val.green { color: #15803d; }
.revenue-sep { width: 1px; background: var(--c-border); align-self: stretch; }

/* Price cell */
.price-text { font-size: 13px; font-weight: 600; color: #15803d; cursor: pointer; border-bottom: 1px dashed #bbf7d0; display: inline-block; white-space: nowrap; }
.price-text.empty { font-weight: 400; color: #c9ccd1; border-color: #e2e8f0; }
.price-text:hover { border-color: #15803d; }
.price-input { display: none; font-size: 13px; border: 1.5px solid #15803d; border-radius: 6px; padding: 3px 7px; width: 90px; outline: none; }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Замовлення</h1>
    </div>

    <div class="orders-filter">
      <label style="font-size:13px;color:var(--c-muted)">Лендинг:</label>
      <select onchange="location.href='?slug='+this.value">
        <?php foreach ($landings as $l): ?>
        <option value="<?= htmlspecialchars($l['slug']) ?>" <?= $l['slug'] === $selectedSlug ? 'selected' : '' ?>>
          <?= htmlspecialchars($l['title']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if ($selectedSlug && $statusCounts['all'] > 0): ?>
      <a href="?slug=<?= urlencode($selectedSlug) ?>&export=1" class="btn btn-ghost btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        CSV
      </a>
      <?php endif; ?>
      <span style="font-size:13px;color:var(--c-muted);margin-left:auto">Всього: <strong><?= $statusCounts['all'] ?></strong></span>
    </div>

    <?php if ($selectedSlug): ?>
    <!-- Status filter tabs -->
    <div class="status-tabs">
      <?php
      $tabDefs = [
        'all'       => 'Всі',
        'new'       => 'Нові',
        'called'    => 'Задзвонено',
        'confirmed' => 'Підтверджено',
        'canceled'  => 'Скасовано',
      ];
      foreach ($tabDefs as $key => $label):
        $cnt = $statusCounts[$key] ?? 0;
        $isActive = $filterStatus === $key;
      ?>
      <a href="?slug=<?= urlencode($selectedSlug) ?>&status=<?= $key ?>" class="status-tab <?= $isActive ? 'active' : '' ?>">
        <?= $label ?><span class="tab-cnt"><?= $cnt ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($revTotal > 0 || $revCount > 0): ?>
    <div class="revenue-bar">
      <div class="revenue-item">
        <span class="revenue-label">Виручка</span>
        <span class="revenue-val green"><?= number_format($revTotal, 0, ',', ' ') ?> ₴</span>
      </div>
      <div class="revenue-sep"></div>
      <div class="revenue-item">
        <span class="revenue-label">Середній чек</span>
        <span class="revenue-val"><?= $revCount > 0 ? number_format($revTotal / $revCount, 0, ',', ' ') . ' ₴' : '—' ?></span>
      </div>
      <div class="revenue-sep"></div>
      <div class="revenue-item">
        <span class="revenue-label">Замовлень з ціною</span>
        <span class="revenue-val"><?= $revCount ?> / <?= $total ?></span>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
    <div class="no-orders">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:12px"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      <p><?= $filterStatus === 'all' ? 'Замовлень ще немає.' : 'Немає замовлень з таким статусом.' ?></p>
    </div>
    <?php else: ?>
    <div class="orders-table-wrap">
      <table class="orders-table">
        <thead>
          <tr>
            <th>Статус</th>
            <th>Дата</th>
            <?php foreach ($dataFields as $f): ?>
            <th><?= htmlspecialchars($f) ?></th>
            <?php endforeach; ?>
            <th>Сума ₴</th>
            <th>Нотатка</th>
            <th>Джерело</th>
            <th>A/B</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="ordersBody">
          <?php foreach ($orders as $o):
            $ostatus = $o['status'] ?? 'new';
            $olabel  = $statusLabels[$ostatus] ?? $ostatus;
            $ocolor  = $statusColors[$ostatus] ?? '#6b7280';
            $obg     = $statusBg[$ostatus] ?? '#f9fafb';
            $onote   = $o['note'] ?? '';
          ?>
          <tr id="row-<?= htmlspecialchars($o['id']) ?>">
            <td>
              <div class="status-dropdown" id="dd-<?= htmlspecialchars($o['id']) ?>">
                <div class="status-badge" style="background:<?= $obg ?>;color:<?= $ocolor ?>" onclick="toggleStatusMenu('<?= htmlspecialchars($o['id']) ?>')">
                  <span class="status-dot" style="background:<?= $ocolor ?>"></span>
                  <?= htmlspecialchars($olabel) ?>
                </div>
                <div class="status-menu">
                  <?php foreach ($statusLabels as $skey => $slabel):
                    $sc = $statusColors[$skey]; $sbg = $statusBg[$skey];
                  ?>
                  <div class="status-menu-item" onclick="setStatus('<?= htmlspecialchars($o['id']) ?>', '<?= $skey ?>')" style="color:<?= $sc ?>">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $sc ?>;flex-shrink:0"></span>
                    <?= $slabel ?>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </td>
            <td class="order-time"><?= date('d.m.Y<\b\r>H:i', strtotime($o['created_at'])) ?></td>
            <?php foreach ($dataFields as $f): ?>
            <td title="<?= htmlspecialchars($o['data'][$f] ?? '') ?>" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($o['data'][$f] ?? '—') ?></td>
            <?php endforeach; ?>
            <td>
              <?php $oprice = (float)($o['price'] ?? 0); ?>
              <span class="price-text <?= $oprice > 0 ? '' : 'empty' ?>" id="price-text-<?= htmlspecialchars($o['id']) ?>" onclick="editPrice('<?= htmlspecialchars($o['id']) ?>')" title="Клікніть щоб вказати суму">
                <?= $oprice > 0 ? number_format($oprice, 0, ',', ' ') : '+ ціна' ?>
              </span>
              <input type="number" class="price-input" id="price-input-<?= htmlspecialchars($o['id']) ?>" value="<?= $oprice > 0 ? $oprice : '' ?>" min="0" step="1" placeholder="0"
                onblur="savePrice('<?= htmlspecialchars($o['id']) ?>')"
                onkeydown="if(event.key==='Enter')this.blur();if(event.key==='Escape'){cancelPrice('<?= htmlspecialchars($o['id']) ?>',<?= $oprice ?>);}">
            </td>
            <td class="note-cell">
              <span class="note-text <?= $onote ? '' : 'empty' ?>" id="note-text-<?= htmlspecialchars($o['id']) ?>" onclick="editNote('<?= htmlspecialchars($o['id']) ?>')" title="<?= htmlspecialchars($onote) ?>">
                <?= $onote ? htmlspecialchars($onote) : '+ нотатка' ?>
              </span>
              <input type="text" class="note-input" id="note-input-<?= htmlspecialchars($o['id']) ?>" value="<?= htmlspecialchars($onote) ?>" maxlength="500"
                onblur="saveNote('<?= htmlspecialchars($o['id']) ?>')"
                onkeydown="if(event.key==='Enter')this.blur();if(event.key==='Escape'){cancelNote('<?= htmlspecialchars($o['id']) ?>',<?= json_encode($onote) ?>);}">
            </td>
            <td>
              <?php $src = $o['utms']['utm_source'] ?? ''; $med = $o['utms']['utm_medium'] ?? ''; ?>
              <?php if ($src): ?><span class="utm-pill"><?= htmlspecialchars($src) ?><?= $med ? '/' . htmlspecialchars($med) : '' ?></span><?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <?php if ($o['ab_variant']): ?><span class="ab-pill"><?= htmlspecialchars($o['ab_variant']) ?></span><?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <button class="order-del" onclick="deleteOrder('<?= htmlspecialchars($o['id']) ?>')" title="Видалити">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total >= 500): ?>
    <p style="font-size:12px;color:var(--c-muted);margin-top:10px">Показано 500 з більше. Для повного списку використайте Експорт CSV.</p>
    <?php endif; ?>
    <?php endif; ?>
  </main>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>
const ADMIN_URL    = <?= json_encode(ADMIN_URL) ?>;
const LANDING_SLUG = <?= json_encode($selectedSlug) ?>;
const CSRF_TOKEN   = <?= json_encode(Auth::csrf()) ?>;

const STATUS_LABELS = <?= json_encode($statusLabels) ?>;
const STATUS_COLORS = <?= json_encode($statusColors) ?>;
const STATUS_BG = <?= json_encode($statusBg) ?>;

// Close all dropdowns on outside click
document.addEventListener('click', e => {
  if (!e.target.closest('.status-dropdown')) {
    document.querySelectorAll('.status-dropdown.open').forEach(d => d.classList.remove('open'));
  }
});

function toggleStatusMenu(id) {
  const dd = document.getElementById('dd-' + id);
  const wasOpen = dd.classList.contains('open');
  document.querySelectorAll('.status-dropdown.open').forEach(d => d.classList.remove('open'));
  if (!wasOpen) dd.classList.add('open');
}

async function setStatus(orderId, status) {
  const dd = document.getElementById('dd-' + orderId);
  dd.classList.remove('open');
  const res = await api('order_status', { slug: LANDING_SLUG, order_id: orderId, status });
  if (!res.success) { alert(res.error || 'Помилка'); return; }
  // Update badge in place
  const badge = dd.querySelector('.status-badge');
  badge.style.background = STATUS_BG[status];
  badge.style.color = STATUS_COLORS[status];
  badge.querySelector('.status-dot').style.background = STATUS_COLORS[status];
  badge.childNodes[badge.childNodes.length - 1].textContent = ' ' + STATUS_LABELS[status];
}

function editPrice(id) {
  const span = document.getElementById('price-text-' + id);
  const inp  = document.getElementById('price-input-' + id);
  span.style.display = 'none';
  inp.style.display  = 'inline-block';
  inp.focus();
  inp.select();
}

function cancelPrice(id, original) {
  const span = document.getElementById('price-text-' + id);
  const inp  = document.getElementById('price-input-' + id);
  inp.value = original > 0 ? original : '';
  inp.style.display  = 'none';
  span.style.display = '';
}

async function savePrice(id) {
  const span = document.getElementById('price-text-' + id);
  const inp  = document.getElementById('price-input-' + id);
  const price = parseFloat(inp.value) || 0;
  inp.style.display  = 'none';
  span.style.display = '';
  const res = await api('order_price', { slug: LANDING_SLUG, order_id: id, price });
  if (res.success) {
    span.textContent = price > 0 ? price.toLocaleString('uk-UA') : '+ ціна';
    span.classList.toggle('empty', price <= 0);
    inp.value = price > 0 ? price : '';
  }
}

function editNote(id) {
  const span = document.getElementById('note-text-' + id);
  const inp  = document.getElementById('note-input-' + id);
  span.style.display = 'none';
  inp.style.display  = 'inline-block';
  inp.focus();
  inp.select();
}

function cancelNote(id, original) {
  const span = document.getElementById('note-text-' + id);
  const inp  = document.getElementById('note-input-' + id);
  inp.value = original;
  inp.style.display  = 'none';
  span.style.display = '';
}

async function saveNote(id) {
  const span = document.getElementById('note-text-' + id);
  const inp  = document.getElementById('note-input-' + id);
  const note = inp.value.trim();
  inp.style.display  = 'none';
  span.style.display = '';
  const res = await api('order_note', { slug: LANDING_SLUG, order_id: id, note });
  if (res.success) {
    span.textContent = note || '+ нотатка';
    span.title = note;
    span.classList.toggle('empty', !note);
    inp.value = note;
  }
}

async function deleteOrder(orderId) {
  if (!confirm('Видалити це замовлення?')) return;
  const res = await api('order_delete', { slug: LANDING_SLUG, order_id: orderId });
  if (res.success) {
    const row = document.getElementById('row-' + orderId);
    if (row) row.remove();
  } else {
    alert(res.error || 'Помилка');
  }
}
</script>
</body>
</html>
