<?php
include 'config.php';

$error = '';

if (isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $place = trim($_POST['place'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = $_POST['date'] ?? '';

    $tithe = (float)($_POST['tithe'] ?? 0);
    $expense = 0.0;
    $building = (float)($_POST['building'] ?? 0);
    $thanks = (float)($_POST['thanks'] ?? 0);

    if ($name === '' || $place === '' || $phone === '' || $date === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $total = $tithe + $expense + $building + $thanks;

        $res = mysqli_query($conn, "SELECT COALESCE(MAX(receipt_no), 0) as max_id FROM offerings");
        $row = mysqli_fetch_assoc($res);
        $receipt_no = ((int)$row['max_id']) + 1;

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO offerings (receipt_no, name, place, phone, tithe, expense, building, thanks, total, date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                'isssddddds',
                $receipt_no,
                $name,
                $place,
                $phone,
                $tithe,
                $expense,
                $building,
                $thanks,
                $total,
                $date
            );

            if (mysqli_stmt_execute($stmt)) {
                $last_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                header('Location: receipt.php?id=' . $last_id);
                exit;
            }

            $error = 'Could not save offering. Please try again.';
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Something went wrong while preparing your request.';
        }
    }
}

$previewTotal = (float)($_POST['tithe'] ?? 0) + (float)($_POST['building'] ?? 0) + (float)($_POST['thanks'] ?? 0);
$places = [
    'Nandini Layout',
    'Sumanahalli',
    'Pattegarapalya',
    'Govindraj Nagar',
    'Vijaya Nagar',
    'Bapuji Nagar',
    'Attiguppe',
    'Girinagar',
    'Shanti Nagar',
    'JC Road',
    'Anjanappa Garden',
    'CRP Quarters',
    'Kumaraswamy Layout',
    'RR Nagar (Rajarajeshwari Nagar)',
    'Mailasandra',
    'TR Mill (Tannery Road Mills / TR Mills area)',
    'Old Guddadahalli',
    'Kasturba Nagar (Hosaguddadahalli)',
    'Goripalya South',
    'Goripalya North',
    'KP Agrahara',
    'New Binnypet',
    'Ambedkar Bhavan',
    'VS Garden'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Offering</title>
    <style>
        :root {
            --bg: #05090c;
            --panel: #0b1319;
            --panel-soft: #0f191f;
            --text: #e7f2ef;
            --muted: #8ba3a0;
            --line: #1f2c34;
            --green: #9efc7f;
            --green-dark: #5acb57;
            --danger: #ff7272;
            --chip: #152126;
            --shadow: 0 28px 60px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 24% 18%, rgba(77, 179, 87, 0.35) 0%, rgba(77, 179, 87, 0.03) 30%, transparent 55%),
                radial-gradient(circle at 78% 78%, rgba(34, 76, 255, 0.15) 0%, transparent 42%),
                var(--bg);
            color: var(--text);
            font-family: Inter, Segoe UI, Arial, sans-serif;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .app {
            width: min(1120px, 100%);
            border-radius: 18px;
            background: rgba(5, 11, 16, 0.88);
            border: 1px solid #16222a;
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            gap: 10px;
        }

        .brand {
            font-weight: 700;
            letter-spacing: .2px;
        }

        .chip {
            background: var(--chip);
            color: var(--muted);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 13px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.2fr .85fr;
            gap: 16px;
        }

        .panel {
            background: linear-gradient(165deg, rgba(17, 27, 34, 0.96), rgba(9, 15, 20, 0.98));
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 18px;
        }

        .panel h1,
        .panel h2,
        .panel p {
            margin: 0;
        }

        .title {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 10px;
            margin-bottom: 14px;
        }

        .title h1 {
            font-size: 1.4rem;
        }

        .subtitle {
            color: var(--muted);
            margin-top: 6px;
            font-size: .92rem;
        }

        .alert {
            background: rgba(255, 114, 114, .12);
            color: #ffb2b2;
            border: 1px solid rgba(255, 114, 114, .4);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: .92rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-size: .83rem;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        input,
        select {
            width: 100%;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #24343d;
            background: #0d171d;
            color: var(--text);
            padding: 0 12px;
            font-size: .95rem;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #7cd877;
            box-shadow: 0 0 0 2px rgba(158, 252, 127, .18);
        }

        .section {
            color: #dbe8e5;
            font-weight: 600;
            padding-top: 8px;
            border-top: 1px dashed #26343b;
            margin-top: 2px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        button {
            height: 44px;
            border: none;
            border-radius: 11px;
            padding: 0 18px;
            font-weight: 700;
            cursor: pointer;
        }

        .primary {
            background: linear-gradient(180deg, #b2ff8f, #89ef6f);
            color: #132210;
            min-width: 190px;
        }

        .secondary {
            background: #17232b;
            color: #b7cbc9;
            border: 1px solid #24343d;
        }

        .card {
            background: var(--panel-soft);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .stat-title {
            color: var(--muted);
            font-size: .82rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .stat-value {
            font-size: 2rem;
            color: #c5fdb6;
            margin-bottom: 6px;
        }

        .mini {
            color: var(--muted);
            font-size: .85rem;
        }

        .list {
            display: grid;
            gap: 8px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #1c2a32;
            font-size: .93rem;
        }

        .item:last-child { border-bottom: none; }

        .note {
            color: var(--muted);
            font-size: .82rem;
            margin-top: 6px;
        }

        @media (max-width: 920px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <div class="topbar">
            <div class="brand">Seventh Day Adventist Central Telugu Church, Chamarajpet</div>
        </div>

        <div class="layout">
            <section class="panel">
                <div class="title">
                    <div>
                        <h1>Offering Entry</h1>
                        <p class="subtitle">Record member offerings and generate receipts instantly.</p>
                    </div>
                    <span class="chip">Auto Receipt</span>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="grid">
                        <div>
                            <label for="name">Name *</label>
                            <input id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="place">Place *</label>
                            <select id="place" name="place" required>
                                <option value="">Select place</option>
                                <?php foreach ($places as $placeOption): ?>
                                    <option value="<?php echo htmlspecialchars($placeOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($_POST['place'] ?? '') === $placeOption) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($placeOption, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="phone">Phone *</label>
                            <input id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="date">Date *</label>
                            <input id="date" type="date" name="date" required value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="full section">Offering Breakdown</div>

                        <div>
                            <label for="tithe">Tithe</label>
                            <input id="tithe" type="number" step="0.01" min="0" name="tithe" value="<?php echo htmlspecialchars($_POST['tithe'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="building">Building Fund</label>
                            <input id="building" type="number" step="0.01" min="0" name="building" value="<?php echo htmlspecialchars($_POST['building'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div>
                            <label for="thanks">Thanks Offering</label>
                            <input id="thanks" type="number" step="0.01" min="0" name="thanks" value="<?php echo htmlspecialchars($_POST['thanks'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="actions">
                        <button class="primary" name="submit" type="submit">Save & Generate Receipt</button>
                        <button class="secondary" type="reset">Reset Form</button>
                    </div>
                    <p class="note">* Required fields. Receipt number is generated automatically.</p>
                </form>
            </section>

            <aside class="panel">
                <div class="card">
                    <div class="stat-title">Offering Summary</div>
                    <div class="stat-value">₹<?php echo number_format($previewTotal, 2); ?></div>
                    <div class="mini">Live preview from entered values</div>
                </div>

                <div class="card">
                    <div class="stat-title">Top Buckets</div>
                    <div class="list">
                        <div class="item"><span>Tithe</span><strong>₹<?php echo number_format((float)($_POST['tithe'] ?? 0), 2); ?></strong></div>
                        <div class="item"><span>Building Fund</span><strong>₹<?php echo number_format((float)($_POST['building'] ?? 0), 2); ?></strong></div>
                        <div class="item"><span>Thanks Offering</span><strong>₹<?php echo number_format((float)($_POST['thanks'] ?? 0), 2); ?></strong></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
