<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Church Treasurer Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <style>
    :root {
      --bg: #070c12;
      --panel: rgba(10, 17, 25, 0.78);
      --panel-border: rgba(136, 186, 154, 0.22);
      --text: #f1f6ff;
      --muted: #b7c4d6;
      --primary: #6ddf9f;
      --primary-dark: #43b173;
      --success: #86f7b8;
      --card: rgba(21, 35, 48, 0.58);
      --card-border: rgba(155, 215, 179, 0.2);
      --danger: #ff8e8e;
      --shadow-lg: 0 24px 60px rgba(0, 0, 0, 0.45);
      --shadow-md: 0 14px 34px rgba(0, 0, 0, 0.34);
      --radius-xl: 26px;
      --radius-lg: 18px;
      --radius-md: 14px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: "Inter", "Segoe UI", Roboto, Arial, sans-serif;
    }

    body {
      min-height: 100vh;
      background:
        linear-gradient(to bottom right, rgba(5, 10, 15, 0.88), rgba(3, 8, 12, 0.92)),
        url("church-background.jpg") center / cover no-repeat;
      color: var(--text);
      display: grid;
      place-items: center;
      padding: clamp(16px, 4vw, 36px);
      position: relative;
      overflow-x: hidden;
    }

    body::before,
    body::after {
      content: "";
      position: fixed;
      z-index: 0;
      border-radius: 50%;
      filter: blur(60px);
      pointer-events: none;
    }

    body::before {
      width: 360px;
      height: 360px;
      background: rgba(75, 213, 132, 0.18);
      top: -90px;
      left: -70px;
    }

    body::after {
      width: 320px;
      height: 320px;
      background: rgba(93, 138, 255, 0.14);
      right: -80px;
      bottom: -110px;
    }

    .dashboard {
      width: min(1160px, 100%);
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      backdrop-filter: blur(12px);
      position: relative;
      z-index: 1;
      overflow: hidden;
    }

    .dashboard::before {
      content: "";
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 85% 12%, rgba(102, 233, 157, 0.18), transparent 42%);
      pointer-events: none;
      z-index: -1;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      padding: 24px 28px;
      border-bottom: 1px solid rgba(177, 214, 191, 0.18);
      background: linear-gradient(to right, rgba(19, 31, 42, 0.88), rgba(17, 29, 40, 0.55));
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .brand-logo {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      overflow: hidden;
      border: 1px solid rgba(151, 219, 181, 0.4);
      background: #0f1923;
      box-shadow: 0 0 0 3px rgba(105, 225, 152, 0.12);
      flex-shrink: 0;
    }

    .brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .brand-title h1 {
      font-size: clamp(1.1rem, 2.5vw, 1.48rem);
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    .brand-title p {
      margin-top: 3px;
      color: var(--muted);
      font-size: 0.92rem;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.86rem;
      color: #d6ffe8;
      background: rgba(62, 167, 110, 0.17);
      border: 1px solid rgba(120, 226, 165, 0.35);
      border-radius: 999px;
      padding: 8px 14px;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-pill::before {
      content: "";
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--success);
      box-shadow: 0 0 0 8px rgba(134, 247, 184, 0.16);
    }

    .content {
      padding: 26px;
      display: grid;
      gap: 20px;
    }

    .metrics {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
      gap: 14px;
    }

    .metric {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-md);
      padding: 16px 16px 14px;
      box-shadow: var(--shadow-md);
    }

    .metric-label {
      color: var(--muted);
      font-size: 0.83rem;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      margin-bottom: 10px;
    }

    .metric h3 {
      font-size: 1.35rem;
      font-weight: 700;
    }

    .metric small {
      margin-top: 6px;
      display: block;
      color: #8fd7af;
      font-size: 0.8rem;
    }

    .layout {
      display: grid;
      grid-template-columns: 1.6fr 1fr;
      gap: 16px;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: var(--radius-lg);
      padding: 18px;
      box-shadow: var(--shadow-md);
    }

    .card h2 {
      font-size: 1.03rem;
      margin-bottom: 12px;
      font-weight: 650;
    }

    .actions {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .action-link {
      text-decoration: none;
      color: #f3f8ff;
      border-radius: 13px;
      padding: 14px;
      background: linear-gradient(to right, rgba(90, 162, 126, 0.16), rgba(34, 88, 120, 0.14));
      border: 1px solid rgba(147, 220, 179, 0.28);
      transition: 0.2s ease;
      display: grid;
      gap: 6px;
      min-height: 86px;
    }

    .action-link span {
      color: var(--muted);
      font-size: 0.85rem;
    }

    .action-link:hover {
      transform: translateY(-2px);
      border-color: rgba(180, 244, 210, 0.46);
      background: linear-gradient(to right, rgba(108, 211, 156, 0.2), rgba(43, 112, 151, 0.2));
    }

    .quick-note {
      margin-top: 14px;
      border-radius: 12px;
      padding: 12px;
      border: 1px solid rgba(147, 220, 179, 0.22);
      background: rgba(57, 112, 80, 0.13);
      color: #d5ffe9;
      font-size: 0.9rem;
      line-height: 1.4;
    }

    .activity {
      list-style: none;
      display: grid;
      gap: 10px;
    }

    .activity li {
      display: grid;
      gap: 3px;
      border-bottom: 1px dashed rgba(163, 205, 187, 0.25);
      padding-bottom: 10px;
    }

    .activity li:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .activity strong {
      font-size: 0.92rem;
      font-weight: 600;
    }

    .activity small {
      color: var(--muted);
      font-size: 0.82rem;
    }

    .footer {
      padding: 0 26px 22px;
      color: var(--muted);
      font-size: 0.82rem;
      display: flex;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    @media (max-width: 980px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .actions {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 600px) {
      .topbar {
        padding: 18px;
        flex-direction: column;
        align-items: flex-start;
      }

      .content,
      .footer {
        padding-inline: 18px;
      }
    }
  </style>
</head>

<body>
  <main class="dashboard" role="main" aria-label="Church treasurer dashboard">
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo">
          <img src="church-logo.png" alt="Church logo" />
        </div>
        <div class="brand-title">
          <h1>New Life Bible Church</h1>
          <p>Treasurer Financial Operations Dashboard</p>
        </div>
      </div>
      <div class="status-pill">System Online</div>
    </header>

    <section class="content">
      <section class="metrics" aria-label="dashboard metrics">
        <article class="metric">
          <p class="metric-label">Module Access</p>
          <h3>2 Active</h3>
          <small>Offering + Records</small>
        </article>

        <article class="metric">
          <p class="metric-label">Permission Level</p>
          <h3>Treasurer</h3>
          <small>Finance Management Role</small>
        </article>

        <article class="metric">
          <p class="metric-label">Last Updated</p>
          <h3>Today</h3>
          <small>After Morning Service</small>
        </article>

        <article class="metric">
          <p class="metric-label">Data Health</p>
          <h3>Stable</h3>
          <small>No sync issues detected</small>
        </article>
      </section>

      <section class="layout">
        <article class="card">
          <h2>Quick Actions</h2>
          <div class="actions">
            <a class="action-link" href="add_offering.php">
              <strong>➕ Add Offering</strong>
              <span>Record service offerings and donations.</span>
            </a>

            <a class="action-link" href="view_records.php">
              <strong>📊 View Records</strong>
              <span>Open weekly and monthly giving reports.</span>
            </a>
          </div>

          <p class="quick-note">
            <strong>Tip:</strong> Enter offerings immediately after each service day to keep all records accurate and audit-ready.
          </p>
        </article>

        <article class="card">
          <h2>Recent Activity</h2>
          <ul class="activity">
            <li>
              <strong>Sunday Offering Entry Updated</strong>
              <small>Today • 12:30 PM</small>
            </li>
            <li>
              <strong>March Monthly Report Generated</strong>
              <small>Apr 15 • 08:10 PM</small>
            </li>
            <li>
              <strong>User Access Verified</strong>
              <small>Apr 14 • 09:05 AM</small>
            </li>
          </ul>
        </article>
      </section>
    </section>

    <footer class="footer">
      <span>© 2026 New Life Bible Church • Treasurer Panel</span>
      <span>Secure internal access only</span>
    </footer>
  </main>
</body>
</html>
