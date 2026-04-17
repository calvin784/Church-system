<?php
include 'config.php';

$typeAliases = [
    'tithe'          => 'Tithe',
    'building fund'  => 'Building Fund',
    'biulding fund'  => 'Building Fund',
    'building'       => 'Building Fund',
    'vesper service' => 'Vesper Service',
    'vesper'         => 'Vesper Service',
    'poor fund'      => 'Poor Fund',
    'poor'           => 'Poor Fund',
];

$allocationRules = [
    'Tithe'            => ['bmc_pct' => 100, 'church_pct' => 0],
    'Building Fund'    => ['bmc_pct' => 0,   'church_pct' => 100],
    'Vesper Service'   => ['bmc_pct' => 0,   'church_pct' => 100],
    'Poor Fund'        => ['bmc_pct' => 0,   'church_pct' => 100],
    'General Offering' => ['bmc_pct' => 50,  'church_pct' => 50],
];

$chipColors = [
    'Tithe'            => ['bg' => 'rgba(16,185,129,0.15)',  'border' => 'rgba(16,185,129,0.4)',  'color' => '#6ee7b7'],
    'Building Fund'    => ['bg' => 'rgba(245,158,11,0.15)',  'border' => 'rgba(245,158,11,0.4)',  'color' => '#fcd34d'],
    'Vesper Service'   => ['bg' => 'rgba(168,85,247,0.15)',  'border' => 'rgba(168,85,247,0.4)',  'color' => '#d8b4fe'],
    'Poor Fund'        => ['bg' => 'rgba(239,68,68,0.15)',   'border' => 'rgba(239,68,68,0.4)',   'color' => '#fca5a5'],
    'General Offering' => ['bg' => 'rgba(148,163,184,0.18)', 'border' => 'rgba(148,163,184,0.45)', 'color' => '#cbd5e1'],
];

/**
 * Scans EVERY column of a row for a known offering-type keyword.
 * Falls back to row-provided type text, then to General Offering.
 */
function detectOfferingType(array $row, array $aliases): string
{
    $priority = [
        'offering_type', 'offerings_type', 'type', 'purpose',
        'fund_type', 'category', 'receipt_type', 'description', 'notes',
    ];

    // longest-match first so "building fund" beats "building"
    uksort($aliases, static fn($a, $b) => strlen($b) <=> strlen($a));

    $ordered = array_unique(array_merge($priority, array_keys($row)));
    foreach ($ordered as $key) {
        $val = strtolower(trim((string)($row[$key] ?? '')));
        if ($val === '') {
            continue;
        }

        foreach ($aliases as $keyword => $canonical) {
            if (strpos($val, $keyword) !== false) {
                return $canonical;
            }
        }
    }

    // if no alias matches, preserve explicit type text from known columns
    foreach ($priority as $key) {
        $raw = trim((string)($row[$key] ?? ''));
        if ($raw !== '') {
            return ucwords(strtolower($raw));
        }
    }

    return 'General Offering';
}

function readOfferingDate(array $row): string
{
    foreach (['offering_date', 'date', 'created_at', 'created_on', 'entry_date'] as $key) {
        if (!empty($row[$key])) {
            return (string)$row[$key];
        }
    }

    return date('Y-m-d');
}

$result = mysqli_query($conn, 'SELECT * FROM offerings ORDER BY id DESC');
if (!$result) {
    die('Error loading offerings records.');
}
$rawRows = mysqli_fetch_all($result, MYSQLI_ASSOC);

$period        = (isset($_GET['period']) && $_GET['period'] === 'yearly') ? 'yearly' : 'monthly';
$selectedYear  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$export        = $_GET['export'] ?? '';
$pdfMode       = $export === 'pdf';

if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = (int)date('n');
}
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int)date('Y');
}

$rows             = [];
$availableYears   = [];
$grandTotalAmount = 0;

foreach ($rawRows as $row) {
    $amount       = (float)($row['total'] ?? 0);
    $offeringType = detectOfferingType($row, $typeAliases);
    $ts           = strtotime(readOfferingDate($row)) ?: time();
    $rowYear      = (int)date('Y', $ts);
    $rowMonth     = (int)date('n', $ts);

    $availableYears[$rowYear] = true;
    $grandTotalAmount += $amount;

    $isMatch = ($period === 'yearly')
        ? ($rowYear === $selectedYear)
        : ($rowYear === $selectedYear && $rowMonth === $selectedMonth);

    if (!$isMatch) {
        continue;
    }

    $rule   = $allocationRules[$offeringType] ?? ['bmc_pct' => 50, 'church_pct' => 50];
    $rows[] = [
        'id'            => $row['id'] ?? '',
        'receipt_no'    => (string)($row['receipt_no'] ?? '-'),
        'name'          => (string)($row['name']       ?? '-'),
        'offering_type' => $offeringType,
        'offering_date' => date('Y-m-d', $ts),
        'total'         => $amount,
        'bmc_amount'    => $amount * ($rule['bmc_pct']    / 100),
        'church_amount' => $amount * ($rule['church_pct'] / 100),
    ];
}

krsort($availableYears);
if (!$availableYears) {
    $availableYears[(int)date('Y')] = true;
}

$totalRecords      = count($rows);
$totalAmount       = 0;
$totalBmcAmount    = 0;
$totalChurchAmount = 0;
$categoryTotals    = [];

foreach ($rows as $r) {
    $totalAmount += $r['total'];
    $totalBmcAmount += $r['bmc_amount'];
    $totalChurchAmount += $r['church_amount'];
    $categoryTotals[$r['offering_type']] = ($categoryTotals[$r['offering_type']] ?? 0) + $r['total'];
}
arsort($categoryTotals);

// ── Excel export ──────────────────────────────────────────────────────────────
if ($export === 'excel') {
    $fn = $period === 'yearly'
        ? 'offerings_year_' . $selectedYear . '.csv'
        : 'offerings_' . $selectedYear . '_' . str_pad((string)$selectedMonth, 2, '0', STR_PAD_LEFT) . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $fn);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Receipt No', 'Name', 'Offering Type', 'Date', 'Total', 'BMC Share', 'Church Share']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['receipt_no'],
            $r['name'],
            $r['offering_type'],
            $r['offering_date'],
            number_format($r['total'], 2, '.', ''),
            number_format($r['bmc_amount'], 2, '.', ''),
            number_format($r['church_amount'], 2, '.', ''),
        ]);
    }

    fputcsv($out, []);
    fputcsv($out, [
        'Totals', '', '', '',
        number_format($totalAmount, 2, '.', ''),
        number_format($totalBmcAmount, 2, '.', ''),
        number_format($totalChurchAmount, 2, '.', ''),
    ]);
    fclose($out);
    exit;
}

$periodLabel = $period === 'yearly'
    ? 'Yearly Report — ' . $selectedYear
    : 'Monthly Report — ' . date('F', mktime(0, 0, 0, $selectedMonth, 1)) . ' ' . $selectedYear;

// ── PDF / print mode ──────────────────────────────────────────────────────────
if ($pdfMode): ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Offerings PDF Report</title>
<style>
  body{font-family:Arial,sans-serif;margin:0;color:#0f172a;}
  .page{padding:28px;}
  .head{border-bottom:2px solid #1e3a8a;padding-bottom:12px;margin-bottom:16px;}
  .head h1{margin:0;color:#1e3a8a;}
  .meta{margin-top:6px;font-size:13px;color:#334155;}
  table{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:12px;}
  th,td{border:1px solid #cbd5e1;padding:7px;text-align:left;}
  th{background:#e2e8f0;}
  .right{text-align:right;}
</style>
</head>
<body>
<div class="page">
  <div class="head">
    <h1>Church Offerings Statement</h1>
    <div class="meta"><?= htmlspecialchars($periodLabel) ?> | Generated <?= date('Y-m-d H:i') ?></div>
  </div>
  <table>
    <tr><td>Total Records</td><td><?= number_format($totalRecords) ?></td><td>Total Offerings</td><td class="right">₹<?= number_format($totalAmount, 2) ?></td></tr>
    <tr><td>Amount to Send BMC</td><td class="right">₹<?= number_format($totalBmcAmount, 2) ?></td><td>Amount for Church</td><td class="right">₹<?= number_format($totalChurchAmount, 2) ?></td></tr>
    <tr><td>Grand Total in System</td><td colspan="3" class="right">₹<?= number_format($grandTotalAmount, 2) ?></td></tr>
  </table>
  <table>
    <thead><tr><th>#</th><th>Receipt</th><th>Name</th><th>Type</th><th>Date</th><th>Total</th><th>BMC</th><th>Church</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="8">No records found.</td></tr>
    <?php else: foreach($rows as $i => $r): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($r['receipt_no']) ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['offering_type']) ?></td>
        <td><?= htmlspecialchars($r['offering_date']) ?></td>
        <td class="right">₹<?= number_format($r['total'], 2) ?></td>
        <td class="right">₹<?= number_format($r['bmc_amount'], 2) ?></td>
        <td class="right">₹<?= number_format($r['church_amount'], 2) ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<script>window.print();</script>
</body>
</html>
<?php exit; endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Church Offerings Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
  --bg:#020c14;
  --surface:#071420;
  --panel:rgba(8,20,32,0.92);
  --panel2:rgba(10,24,38,0.96);
  --border:rgba(100,160,200,0.14);
  --border2:rgba(100,160,200,0.22);
  --text:#edf4f8;
  --muted:#6a8fa4;
  --muted2:#9ab4c4;
  --blue:#60a5fa;
  --green:#4ade80;
  --violet:#a78bfa;
  --amber:#fbbf24;
  --rose:#f87171;
  --teal:#2dd4bf;
  --radius:16px;
  --radius-sm:10px;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{
  font-family:"Inter","Segoe UI",system-ui,sans-serif;
  background:
    radial-gradient(ellipse at 10% 15%, rgba(37,99,235,0.18) 0%, transparent 45%),
    radial-gradient(ellipse at 85% 85%, rgba(16,185,129,0.14) 0%, transparent 45%),
    linear-gradient(160deg,#020c14 0%,#04111c 100%);
  min-height:100vh;
  color:var(--text);
  padding:24px 18px;
}
.wrap{max-width:1280px;margin:0 auto;display:flex;flex-direction:column;gap:16px;}
.header{
  background:var(--panel);
  border:1px solid var(--border2);
  border-radius:var(--radius);
  padding:22px 26px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  flex-wrap:wrap;
}
.header-left h1{font-size:1.45rem;font-weight:800;letter-spacing:-0.02em;color:#e8f4ff;}
.header-left p{margin-top:5px;font-size:.85rem;color:var(--muted2);}
.badge-grand{
  background:rgba(16,185,129,0.1);
  border:1px solid rgba(16,185,129,0.28);
  border-radius:999px;
  padding:7px 18px;
  font-size:.82rem;
  font-weight:700;
  color:var(--green);
  white-space:nowrap;
}
.toolbar{
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:14px 18px;
  display:flex;
  flex-wrap:wrap;
  gap:12px;
  align-items:flex-end;
}
.field{display:flex;flex-direction:column;gap:5px;}
.field label{font-size:.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.field select{
  border:1px solid var(--border2);
  border-radius:var(--radius-sm);
  padding:8px 12px;
  background:var(--panel2);
  color:var(--text);
  font-size:.85rem;
  min-width:138px;
  outline:none;
  cursor:pointer;
  transition:border-color .2s;
}
.field select:focus{border-color:var(--blue);}
.toolbar-actions{display:flex;gap:8px;align-items:flex-end;margin-left:auto;flex-wrap:wrap;}
.btn{
  border:1px solid var(--border2);
  border-radius:var(--radius-sm);
  padding:9px 16px;
  font-size:.82rem;
  font-weight:700;
  cursor:pointer;
  text-decoration:none;
  transition:all .2s;
  display:inline-flex;align-items:center;gap:6px;
}
.btn-apply{background:rgba(37,99,235,0.25);color:#93c5fd;border-color:rgba(37,99,235,0.4);}
.btn-apply:hover{background:rgba(37,99,235,0.38);}
.btn-excel{background:rgba(45,212,191,0.08);color:var(--teal);border-color:rgba(45,212,191,0.25);}
.btn-excel:hover{background:rgba(45,212,191,0.15);}
.btn-pdf{background:rgba(248,113,113,0.08);color:var(--rose);border-color:rgba(248,113,113,0.25);}
.btn-pdf:hover{background:rgba(248,113,113,0.15);}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}
.stat{
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:18px 20px;
  position:relative;
  overflow:hidden;
}
.stat::before{
  content:'';position:absolute;top:0;left:0;right:0;height:3px;
  background:var(--accent,var(--blue));
  opacity:.7;
}
.stat .label{font-size:.74rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
.stat .value{margin-top:10px;font-size:1.28rem;font-weight:800;color:var(--accent,var(--blue));}
.stat .sub{margin-top:4px;font-size:.75rem;color:var(--muted);}
.panel{
  background:var(--panel);
  border:1px solid var(--border);
  border-radius:var(--radius);
  overflow:hidden;
}
.panel-head{
  padding:13px 20px;
  border-bottom:1px solid var(--border);
  font-weight:800;
  font-size:.88rem;
  color:#cde8f5;
  background:rgba(8,20,32,0.7);
  display:flex;align-items:center;gap:8px;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.panel-head .dot{
  width:8px;height:8px;border-radius:50%;
  background:var(--accent-dot,var(--blue));
  box-shadow:0 0 8px var(--accent-dot,var(--blue));
}
.insights{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:18px;}
@media(max-width:700px){.insights{grid-template-columns:1fr;}}
.chart-box{
  background:var(--panel2);
  border:1px solid var(--border);
  border-radius:var(--radius-sm);
  padding:18px;
  display:flex;flex-direction:column;gap:10px;
}
.chart-title{font-size:.8rem;font-weight:700;color:var(--muted2);text-transform:uppercase;letter-spacing:.06em;}
.chart-wrap{position:relative;height:220px;}
.cat-table-wrap{padding:0;}
.cat-table{width:100%;border-collapse:collapse;}
.cat-table th{
  padding:11px 18px;
  font-size:.72rem;
  font-weight:700;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:.06em;
  border-bottom:1px solid var(--border);
  text-align:left;
}
.cat-table td{
  padding:12px 18px;
  border-bottom:1px solid rgba(100,160,200,0.08);
  font-size:.88rem;
}
.cat-table tr:last-child td{border-bottom:none;}
.cat-table .bar-cell{padding-right:20px;}
.bar-track{background:rgba(255,255,255,0.05);border-radius:999px;height:6px;overflow:hidden;}
.bar-fill{height:100%;border-radius:999px;background:var(--bar-color,var(--blue));}
.table-wrap{overflow-x:auto;}
table.records{width:100%;border-collapse:collapse;min-width:820px;}
table.records thead th{
  padding:11px 14px;
  font-size:.71rem;
  font-weight:700;
  color:var(--muted);
  text-transform:uppercase;
  letter-spacing:.06em;
  border-bottom:1px solid var(--border);
  text-align:left;
  white-space:nowrap;
}
table.records tbody td{
  padding:11px 14px;
  border-bottom:1px solid rgba(100,160,200,0.07);
  font-size:.86rem;
  vertical-align:middle;
}
table.records tbody tr:hover{background:rgba(37,99,235,0.05);}
table.records tbody tr:last-child td{border-bottom:none;}
.chip{
  display:inline-block;
  border-radius:999px;
  padding:3px 11px;
  font-size:.73rem;
  font-weight:700;
  white-space:nowrap;
}
.amt{font-weight:800;color:var(--green);}
.amt-sub{color:var(--muted2);font-size:.83rem;}
.empty{text-align:center;padding:30px;color:var(--muted);font-size:.88rem;}
.tfoot-row td{
  padding:12px 14px;
  font-size:.84rem;
  font-weight:800;
  background:rgba(37,99,235,0.07);
  border-top:1px solid var(--border2);
}
</style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <div class="header-left">
      <h1>⛪ Church Offerings Dashboard</h1>
      <p><?= htmlspecialchars($periodLabel) ?></p>
    </div>
    <div class="badge-grand">System Total &nbsp;₹<?= number_format($grandTotalAmount, 2) ?></div>
  </div>

  <form class="toolbar" method="GET" action="">
    <div class="field">
      <label for="period">Report Period</label>
      <select id="period" name="period" onchange="toggleMonth(this.value)">
        <option value="monthly" <?= $period==='monthly'?'selected':'' ?>>Monthly</option>
        <option value="yearly"  <?= $period==='yearly' ?'selected':'' ?>>Yearly</option>
      </select>
    </div>
    <div class="field" id="month-wrap">
      <label for="month">Month</label>
      <select id="month" name="month" <?= $period==='yearly'?'disabled':'' ?>>
        <?php for ($m=1; $m<=12; $m++): ?>
          <option value="<?= $m ?>" <?= $m===$selectedMonth?'selected':'' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="field">
      <label for="year">Year</label>
      <select id="year" name="year">
        <?php foreach (array_keys($availableYears) as $yr): ?>
          <option value="<?= $yr ?>" <?= $yr===$selectedYear?'selected':'' ?>><?= $yr ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="toolbar-actions">
      <button type="submit" class="btn btn-apply">&#9654; Apply</button>
      <a class="btn btn-excel" href="?period=<?= urlencode($period) ?>&month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&export=excel">&#8595; Excel</a>
      <a class="btn btn-pdf"   href="?period=<?= urlencode($period) ?>&month=<?= $selectedMonth ?>&year=<?= $selectedYear ?>&export=pdf">&#8601; PDF</a>
    </div>
  </form>

  <div class="stats">
    <div class="stat" style="--accent:var(--blue)">
      <div class="label">Records</div>
      <div class="value"><?= number_format($totalRecords) ?></div>
      <div class="sub">Filtered entries</div>
    </div>
    <div class="stat" style="--accent:var(--green)">
      <div class="label">Total Offerings</div>
      <div class="value">₹<?= number_format($totalAmount, 2) ?></div>
      <div class="sub">Collected this period</div>
    </div>
    <div class="stat" style="--accent:var(--violet)">
      <div class="label">Amount to Send BMC</div>
      <div class="value">₹<?= number_format($totalBmcAmount, 2) ?></div>
      <div class="sub"><?= $totalAmount>0 ? number_format($totalBmcAmount/$totalAmount*100,1).'%' : '—' ?> of total</div>
    </div>
    <div class="stat" style="--accent:var(--amber)">
      <div class="label">Amount for Church</div>
      <div class="value">₹<?= number_format($totalChurchAmount, 2) ?></div>
      <div class="sub"><?= $totalAmount>0 ? number_format($totalChurchAmount/$totalAmount*100,1).'%' : '—' ?> of total</div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head" style="--accent-dot:var(--teal)"><span class="dot"></span>Data Insights</div>
    <div class="insights">
      <div class="chart-box">
        <div class="chart-title">Offering Type Breakdown</div>
        <div class="chart-wrap"><canvas id="donutChart"></canvas></div>
      </div>
      <div class="chart-box">
        <div class="chart-title">BMC vs Church Split</div>
        <div class="chart-wrap"><canvas id="splitChart"></canvas></div>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head" style="--accent-dot:var(--amber)"><span class="dot"></span>Category Totals — <?= htmlspecialchars($periodLabel) ?></div>
    <div class="cat-table-wrap">
      <table class="cat-table">
        <thead>
          <tr>
            <th>Offering Type</th>
            <th>Total Amount</th>
            <th>BMC Share</th>
            <th>Church Share</th>
            <th style="width:160px;">Share of Period</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$categoryTotals): ?>
          <tr><td colspan="5" class="empty">No records for selected period.</td></tr>
        <?php else:
          $chipC = ['Tithe'=>'#6ee7b7','Building Fund'=>'#fcd34d','Vesper Service'=>'#d8b4fe','Poor Fund'=>'#fca5a5','General Offering'=>'#cbd5e1'];
          $barC  = ['Tithe'=>'#10b981','Building Fund'=>'#f59e0b','Vesper Service'=>'#8b5cf6','Poor Fund'=>'#ef4444','General Offering'=>'#64748b'];
          foreach($categoryTotals as $type => $amt):
            $rule   = $allocationRules[$type] ?? ['bmc_pct'=>50,'church_pct'=>50];
            $bmcAmt = $amt * ($rule['bmc_pct']/100);
            $chuAmt = $amt * ($rule['church_pct']/100);
            $pct    = $totalAmount > 0 ? ($amt/$totalAmount*100) : 0;
            $cc     = $chipC[$type] ?? '#94a3b8';
            $bc     = $barC[$type]  ?? '#64748b';
        ?>
          <tr>
            <td>
              <span class="chip" style="background:<?= $bc ?>22;border:1px solid <?= $bc ?>55;color:<?= $cc ?>"><?= htmlspecialchars($type) ?></span>
            </td>
            <td class="amt">₹<?= number_format($amt,2) ?></td>
            <td class="amt-sub">₹<?= number_format($bmcAmt,2) ?></td>
            <td class="amt-sub">₹<?= number_format($chuAmt,2) ?></td>
            <td class="bar-cell">
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="bar-track" style="flex:1;--bar-color:<?= $bc ?>">
                  <div class="bar-fill" style="width:<?= min($pct,100) ?>%;background:<?= $bc ?>"></div>
                </div>
                <span style="font-size:.76rem;color:var(--muted2);min-width:38px;"><?= number_format($pct,1) ?>%</span>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head" style="--accent-dot:var(--blue)"><span class="dot"></span>Detailed Offering Records</div>
    <div class="table-wrap">
      <table class="records">
        <thead>
          <tr>
            <th>#</th>
            <th>Receipt</th>
            <th>Name</th>
            <th>Type</th>
            <th>Date</th>
            <th style="text-align:right">Total</th>
            <th style="text-align:right">BMC</th>
            <th style="text-align:right">Church</th>
          </tr>
        </thead>
        <tbody>
        <?php if(!$rows): ?>
          <tr><td colspan="8" class="empty">No offerings records found for selected period.</td></tr>
        <?php else:
          foreach($rows as $i => $r):
            $type = $r['offering_type'];
            $cc   = ($chipColors[$type]['color'] ?? '#94a3b8');
            $bc   = str_replace('0.15', '0.35', ($chipColors[$type]['bg'] ?? 'rgba(100,116,139,0.35)'));
        ?>
          <tr>
            <td style="color:var(--muted);font-size:.78rem;"><?= $i+1 ?></td>
            <td style="font-weight:700;color:var(--blue);">#<?= htmlspecialchars($r['receipt_no']) ?></td>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td>
              <span class="chip" style="background:<?= $bc ?>;border:1px solid <?= $chipColors[$type]['border'] ?? 'rgba(100,116,139,0.6)' ?>;color:<?= $cc ?>">
                <?= htmlspecialchars($type) ?>
              </span>
            </td>
            <td style="color:var(--muted2);font-size:.83rem;"><?= htmlspecialchars($r['offering_date']) ?></td>
            <td class="amt" style="text-align:right">₹<?= number_format($r['total'],2) ?></td>
            <td class="amt-sub" style="text-align:right">₹<?= number_format($r['bmc_amount'],2) ?></td>
            <td class="amt-sub" style="text-align:right">₹<?= number_format($r['church_amount'],2) ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if($rows): ?>
        <tfoot>
          <tr class="tfoot-row">
            <td colspan="5" style="color:var(--muted2);">Totals for <?= htmlspecialchars($periodLabel) ?></td>
            <td style="text-align:right;color:var(--green)">₹<?= number_format($totalAmount,2) ?></td>
            <td style="text-align:right;color:var(--violet)">₹<?= number_format($totalBmcAmount,2) ?></td>
            <td style="text-align:right;color:var(--amber)">₹<?= number_format($totalChurchAmount,2) ?></td>
          </tr>
        </tfoot>
        <?php endif; ?>
      </table>
    </div>
  </div>

</div>

<script>
const catLabels   = <?= json_encode(array_keys($categoryTotals)) ?>;
const catAmounts  = <?= json_encode(array_values($categoryTotals)) ?>;
const totalBmc    = <?= json_encode($totalBmcAmount) ?>;
const totalChurch = <?= json_encode($totalChurchAmount) ?>;

const colorMap = {
  'Tithe': '#10b981',
  'Building Fund': '#f59e0b',
  'Vesper Service': '#8b5cf6',
  'Poor Fund': '#ef4444',
  'General Offering': '#64748b',
};
const palette = catLabels.map(l => colorMap[l] || '#64748b');
const borderPalette = palette.map(c => c + 'cc');

Chart.defaults.color = '#6a8fa4';
Chart.defaults.font.family = '"Inter","Segoe UI",sans-serif';

new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: catLabels,
    datasets: [{
      data: catAmounts,
      backgroundColor: palette.map(c => c + '33'),
      borderColor: borderPalette,
      borderWidth: 2,
      hoverBackgroundColor: palette.map(c => c + '66'),
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
      legend: { position:'right', labels:{ boxWidth:12, padding:14, font:{size:12} } },
      tooltip: {
        callbacks: {
          label: ctx => ' ₹' + ctx.parsed.toLocaleString('en-IN',{minimumFractionDigits:2})
        }
      }
    }
  }
});

new Chart(document.getElementById('splitChart'), {
  type: 'bar',
  data: {
    labels: ['BMC', 'Church'],
    datasets: [{
      label: 'Amount',
      data: [totalBmc, totalChurch],
      backgroundColor: ['rgba(167,139,250,0.28)', 'rgba(251,191,36,0.28)'],
      borderColor: ['#a78bfa', '#fbbf24'],
      borderWidth: 2,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ' ₹' + ctx.parsed.x.toLocaleString('en-IN',{minimumFractionDigits:2})
        }
      }
    },
    scales: {
      x: {
        grid: { color: 'rgba(100,160,200,0.08)' },
        ticks: {
          callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v)
        }
      },
      y: { grid: { display:false } }
    }
  }
});

function toggleMonth(val) {
  var el = document.getElementById('month');
  if (el) el.disabled = val === 'yearly';
}
</script>
</body>
</html>
