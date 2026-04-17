<?php
include 'config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = mysqli_prepare($conn, 'SELECT * FROM offerings WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    http_response_code(404);
    echo '<h2>Receipt not found.</h2>';
    exit;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Receipt #<?php echo e($row['receipt_no']); ?></title>
    <style>
        :root {
            --bg: #070a0d;
            --panel: rgba(11, 18, 19, 0.82);
            --panel-soft: rgba(16, 26, 28, 0.74);
            --line: rgba(136, 255, 179, 0.12);
            --line-strong: rgba(136, 255, 179, 0.22);
            --txt: #e7f5ec;
            --muted: #8aa394;
            --accent: #9bff72;
            --accent-dark: #2f5f3a;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Inter, "Segoe UI", Roboto, Arial, sans-serif;
            color: var(--txt);
            background:
                radial-gradient(circle at 15% 18%, rgba(104, 255, 137, 0.3), transparent 32%),
                radial-gradient(circle at 84% 86%, rgba(123, 255, 187, 0.2), transparent 28%),
                linear-gradient(135deg, #050709 0%, #0a1112 55%, #070a0d 100%);
            padding: 20px;
        }

        .app-shell {
            width: min(1100px, 100%);
            border-radius: 22px;
            border: 1px solid rgba(176, 255, 210, 0.14);
            background: linear-gradient(155deg, rgba(61, 126, 77, 0.16), rgba(3, 8, 10, 0.8));
            box-shadow:
                0 35px 60px rgba(0, 0, 0, 0.55),
                inset 0 1px 0 rgba(206, 255, 228, 0.08);
            padding: 20px;
            backdrop-filter: blur(6px);
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: 1.55fr 1fr;
            gap: 18px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
        }

        .header {
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--line);
        }

        .heading {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        .sub {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .pill {
            border: 1px solid var(--line-strong);
            color: #d9ffe6;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(141, 255, 174, 0.08);
        }

        .left-body {
            padding: 18px;
        }

        .portfolio {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            background: linear-gradient(130deg, rgba(120, 255, 166, 0.08), rgba(20, 34, 35, 0.72));
            margin-bottom: 14px;
        }

        .label {
            color: var(--muted);
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .total {
            margin: 8px 0 5px;
            font-size: clamp(1.7rem, 4vw, 2.2rem);
            font-weight: 700;
        }

        .trend {
            color: #77ff95;
            font-size: 0.8rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .meta {
            border: 1px solid var(--line);
            background: var(--panel-soft);
            border-radius: 12px;
            padding: 10px;
        }

        .meta .value {
            margin-top: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            word-break: break-word;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--line);
        }

        th, td {
            padding: 11px 12px;
            border-bottom: 1px solid var(--line);
            font-size: 0.9rem;
        }

        th {
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #acd2bb;
            font-size: 0.74rem;
            text-align: left;
            background: rgba(43, 67, 53, 0.28);
        }

        td:last-child,
        th:last-child {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        tr:last-child td { border-bottom: none; }

        .total-row td {
            color: #c8ffd6;
            background: rgba(117, 255, 147, 0.09);
            font-weight: 700;
        }

        .side {
            padding: 18px;
            display: grid;
            gap: 12px;
            align-content: start;
        }

        .swap-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--panel-soft);
            padding: 12px;
        }

        .swap-title {
            margin: 0 0 10px;
            font-size: 0.9rem;
            color: #d8fce5;
        }

        .swap-block {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px;
            margin-bottom: 10px;
            background: rgba(7, 13, 14, 0.55);
        }

        .swap-label {
            color: var(--muted);
            font-size: 0.7rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .swap-value {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 8px;
            font-size: 0.92rem;
        }

        .swap-value strong {
            font-size: 1.25rem;
            color: #e6ffe8;
        }

        .btn {
            width: 100%;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            color: #0d1f12;
            background: linear-gradient(180deg, #b0ff83, #90f65c);
            cursor: pointer;
        }

        .footer {
            margin-top: 2px;
            color: var(--muted);
            font-size: 0.78rem;
            display: flex;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 950px) {
            .receipt-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .app-shell { padding: 12px; }
            .meta-grid { grid-template-columns: 1fr; }
            th, td { padding: 9px; }
        }

        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .app-shell, .card { box-shadow: none; border: 1px solid #bbb; background: #fff; }
            .btn { display: none; }
        }
    </style>
</head>
<body>
<main class="app-shell" role="document" aria-label="Church donation receipt">
    <section class="receipt-grid">
        <article class="card">
            <header class="header">
                <div>
                    <h1 class="heading">Church Offering Receipt</h1>
                    <p class="sub">Thank you for your faithful giving</p>
                </div>
                <span class="pill">#<?php echo e($row['receipt_no']); ?></span>
            </header>

            <div class="left-body">
                <section class="portfolio" aria-label="Total offering value">
                    <div class="label">Total offering</div>
                    <div class="total">₹<?php echo number_format((float) $row['total'], 2); ?></div>
                    <div class="trend">Official church contribution record</div>
                </section>

                <div class="meta-grid">
                    <div class="meta"><div class="label">Name</div><div class="value"><?php echo e($row['name']); ?></div></div>
                    <div class="meta"><div class="label">Place</div><div class="value"><?php echo e($row['place']); ?></div></div>
                    <div class="meta"><div class="label">Date</div><div class="value"><?php echo e($row['date']); ?></div></div>
                    <div class="meta"><div class="label">Receipt ID</div><div class="value"><?php echo e($id); ?></div></div>
                </div>

                <table aria-label="Offering breakdown">
                    <thead>
                    <tr>
                        <th>Offering Type</th>
                        <th>Amount (₹)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td>Tithe</td><td><?php echo number_format((float) $row['tithe'], 2); ?></td></tr>
                    <tr><td>Church Expense</td><td><?php echo number_format((float) $row['expense'], 2); ?></td></tr>
                    <tr><td>Building Fund</td><td><?php echo number_format((float) $row['building'], 2); ?></td></tr>
                    <tr><td>Thanks Offering</td><td><?php echo number_format((float) $row['thanks'], 2); ?></td></tr>
                    <tr class="total-row"><td>Total</td><td><?php echo number_format((float) $row['total'], 2); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </article>

        <aside class="card side" aria-label="Receipt summary panel">
            <div class="swap-card">
                <h2 class="swap-title">Receipt Summary</h2>

                <div class="swap-block">
                    <div class="swap-label">From</div>
                    <div class="swap-value"><span>Donor</span><strong><?php echo e($row['name']); ?></strong></div>
                </div>

                <div class="swap-block">
                    <div class="swap-label">To</div>
                    <div class="swap-value"><span>Church Account</span><strong>₹<?php echo number_format((float) $row['total'], 2); ?></strong></div>
                </div>

                <button class="btn" onclick="window.print()">Print Receipt</button>
            </div>

            <div class="footer">
                <span>Generated on <?php echo date('F j, Y'); ?></span>
                <span>Official Church Record</span>
            </div>
        </aside>
    </section>
</main>
</body>
</html>
