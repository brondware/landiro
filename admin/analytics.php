<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/core/Auth.php';
require_once dirname(__DIR__) . '/core/Landing.php';
require_once dirname(__DIR__) . '/core/Analytics.php';
require_once dirname(__DIR__) . '/core/OrderLog.php';

Auth::requireLogin();

$landingManager = new Landing();
$analytics = new Analytics();

$landings = $landingManager->getAll();
$allStats = $analytics->getAllStats();

// Обраний лендинг (фільтр)
$selectedSlug = $_GET['slug'] ?? ($landings[0]['slug'] ?? '');
$stats = $selectedSlug ? $analytics->getStats($selectedSlug) : [];
$landing = $selectedSlug ? $landingManager->get($selectedSlug) : null;
$revenue = $selectedSlug ? (new OrderLog())->getRevenueSummary($selectedSlug) : ['total' => 0, 'count' => 0, 'avg' => 0];

// 30-денний ряд даних для графіку
$daily = $stats['daily'] ?? [];
$days = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $days[] = [
        'date'   => $date,
        'label'  => date('d.m', strtotime($date)),
        'views'  => $daily[$date]['views'] ?? 0,
        'orders' => $daily[$date]['orders'] ?? 0,
    ];
}

$maxViews = max(1, max(array_column($days, 'views')));
?><!DOCTYPE html>
<html lang="uk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>Аналітика — Landiro CMS</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
.analytics-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
.analytics-filter label { font-size: 13px; color: var(--c-muted); }
.analytics-filter select { border: 1.5px solid var(--c-border); border-radius: 8px; padding: 7px 12px; font-size: 13px; outline: none; background: #fff; cursor: pointer; }

.kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 28px; }
.kpi-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 12px; padding: 18px 20px; }
.kpi-card-label { font-size: 12px; color: var(--c-muted); margin-bottom: 6px; font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
.kpi-card-val { font-size: 28px; font-weight: 800; color: var(--c-text); }
.kpi-card-val.green { color: #15803d; }
.kpi-card-val.purple { color: #6366f1; }

.chart-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 12px; padding: 20px; margin-bottom: 24px; }
.chart-title { font-size: 13px; font-weight: 600; color: var(--c-muted); margin-bottom: 16px; }
.bar-chart { display: flex; align-items: flex-end; gap: 4px; height: 140px; overflow-x: auto; padding-bottom: 24px; }
.bar-col { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; flex: 1; min-width: 22px; position: relative; }
.bar-pair { display: flex; align-items: flex-end; gap: 2px; width: 100%; }
.bar-views { background: #c7d2fe; border-radius: 3px 3px 0 0; flex: 2; transition: height .3s; min-height: 2px; }
.bar-orders { background: #86efac; border-radius: 3px 3px 0 0; flex: 1; transition: height .3s; min-height: 2px; }
.bar-label { font-size: 9px; color: var(--c-muted); white-space: nowrap; position: absolute; bottom: -18px; left: 50%; transform: translateX(-50%) rotate(-45deg); transform-origin: top center; }
.chart-legend { display: flex; gap: 14px; margin-top: 12px; }
.legend-item { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--c-muted); }
.legend-dot { width: 10px; height: 10px; border-radius: 2px; }

.table-card { background: #fff; border: 1.5px solid var(--c-border); border-radius: 12px; overflow: hidden; }
.table-card table { width: 100%; border-collapse: collapse; }
.table-card th { padding: 10px 16px; font-size: 12px; font-weight: 600; color: var(--c-muted); text-align: left; background: var(--c-bg); border-bottom: 1.5px solid var(--c-border); text-transform: uppercase; letter-spacing: .4px; }
.table-card td { padding: 10px 16px; font-size: 13px; border-bottom: 1px solid var(--c-border); }
.table-card tr:last-child td { border-bottom: none; }
.table-card tr:hover td { background: var(--c-bg); }
.table-conv { font-weight: 600; color: #6366f1; }
</style>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Аналітика</h1>
    </div>

    <!-- Landing filter -->
    <div class="analytics-filter">
      <label>Лендинг:</label>
      <select onchange="location.href='?slug='+this.value">
        <?php foreach ($landings as $l): ?>
        <option value="<?= htmlspecialchars($l['slug']) ?>" <?= $l['slug'] === $selectedSlug ? 'selected' : '' ?>>
          <?= htmlspecialchars($l['title']) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <?php if ($selectedSlug): ?>
      <a href="landing.php?slug=<?= urlencode($selectedSlug) ?>" class="btn btn-ghost btn-sm">Редагувати лендинг</a>
      <?php endif; ?>
    </div>

    <?php if ($selectedSlug && $stats): ?>

    <!-- KPI Cards -->
    <div class="kpi-row">
      <div class="kpi-card">
        <div class="kpi-card-label">Переглядів всього</div>
        <div class="kpi-card-val"><?= number_format($stats['views'] ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card-label">Замовлень всього</div>
        <div class="kpi-card-val green"><?= number_format($stats['orders'] ?? 0) ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card-label">Конверсія</div>
        <div class="kpi-card-val purple">
          <?= ($stats['views'] ?? 0) > 0 ? round(($stats['orders'] ?? 0) / $stats['views'] * 100, 2) . '%' : '—' ?>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card-label">Переглядів сьогодні</div>
        <div class="kpi-card-val"><?= $stats['daily'][date('Y-m-d')]['views'] ?? 0 ?></div>
      </div>
      <?php if ($revenue['total'] > 0): ?>
      <div class="kpi-card">
        <div class="kpi-card-label">Виручка (з ціною)</div>
        <div class="kpi-card-val green"><?= number_format($revenue['total'], 0, ',', ' ') ?> ₴</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-card-label">Середній чек</div>
        <div class="kpi-card-val"><?= number_format($revenue['avg'], 0, ',', ' ') ?> ₴</div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Bar Chart -->
    <div class="chart-card">
      <div class="chart-title">Останні 30 днів</div>
      <div class="bar-chart" id="barChart">
        <?php foreach ($days as $day): ?>
        <?php
            $hv = max(2, round($day['views'] / $maxViews * 100));
            $ho = $day['views'] > 0 ? max(2, round($day['orders'] / $maxViews * 100)) : 0;
        ?>
        <div class="bar-col" title="<?= $day['label'] ?>: <?= $day['views'] ?> переглядів, <?= $day['orders'] ?> замовлень">
          <div class="bar-pair">
            <div class="bar-views" style="height:<?= $hv ?>px"></div>
            <?php if ($ho): ?><div class="bar-orders" style="height:<?= $ho ?>px"></div><?php endif; ?>
          </div>
          <div class="bar-label"><?= $day['label'] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-legend">
        <div class="legend-item"><div class="legend-dot" style="background:#e0e7ff;border:1px solid #6366f1"></div> Перегляди</div>
        <div class="legend-item"><div class="legend-dot" style="background:#bbf7d0;border:1px solid #16a34a"></div> Замовлення</div>
      </div>
    </div>

    <!-- Daily table (last 14 days) -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Дата</th>
            <th>Перегляди</th>
            <th>Замовлення</th>
            <th>Конверсія</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($days) as $day): ?>
          <?php if ($day['views'] === 0 && $day['orders'] === 0) continue; ?>
          <tr>
            <td><?= $day['label'] ?></td>
            <td><?= $day['views'] ?></td>
            <td><?= $day['orders'] ?></td>
            <td class="table-conv"><?= $day['views'] > 0 ? round($day['orders'] / $day['views'] * 100, 1) . '%' : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php
    // A/B stats section
    $abStats = (new Analytics())->getAbStats($selectedSlug);
    if (!empty($abStats)):
    // Match short IDs to section names
    $sectionMap = [];
    foreach ($landing['sections'] ?? [] as $s) {
        $short = 'ab_' . substr(str_replace('-', '', $s['id']), 0, 8);
        $sectionMap[$short] = $s['type'] . ($s['template'] ? ' / ' . $s['template'] : '');
    }
    ?>
    <div class="chart-card" style="margin-top:0">
      <div class="chart-title">A/B тести</div>
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:var(--c-bg)">
            <th style="padding:8px 12px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">Секція</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">A: перегл.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">A: замов.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">A: конв.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">B: перегл.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">B: замов.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">B: конв.</th>
            <th style="padding:8px 12px;text-align:center;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--c-muted);border-bottom:1.5px solid var(--c-border)">Переможець</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($abStats as $key => $ab): ?>
          <?php
          $av = $ab['a_views'] ?? 0; $ao = $ab['a_orders'] ?? 0;
          $bv = $ab['b_views'] ?? 0; $bo = $ab['b_orders'] ?? 0;
          $acr = $av > 0 ? round($ao / $av * 100, 1) : 0;
          $bcr = $bv > 0 ? round($bo / $bv * 100, 1) : 0;
          $winner = $acr > $bcr ? 'A' : ($bcr > $acr ? 'B' : '—');
          $winnerColor = $winner === 'B' ? '#15803d' : ($winner === 'A' ? '#6366f1' : '#94a3b8');
          ?>
          <tr>
            <td style="padding:9px 12px;border-bottom:1px solid var(--c-border)"><?= htmlspecialchars($sectionMap[$key] ?? $key) ?></td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border)"><?= $av ?></td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border)"><?= $ao ?></td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border);font-weight:600;color:#6366f1"><?= $acr ?>%</td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border)"><?= $bv ?></td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border)"><?= $bo ?></td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border);font-weight:600;color:#15803d"><?= $bcr ?>%</td>
            <td style="padding:9px 12px;text-align:center;border-bottom:1px solid var(--c-border);font-weight:700;color:<?= $winnerColor ?>"><?= $winner ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <h3>Немає даних</h3>
      <p>Після першого відвідування лендингу тут з'явиться статистика</p>
    </div>
    <?php endif; ?>

  </main>
</div>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<script>const ADMIN_URL = <?= json_encode(ADMIN_URL) ?>;</script>
</body>
</html>
