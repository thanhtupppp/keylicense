<style>
    :root {
        color-scheme: dark;
        --bg: #050816;
        --bg2: #0b1220;
        --card: rgba(15, 23, 42, .76);
        --border: rgba(148, 163, 184, .16);
        --text: #e5eefc;
        --muted: #94a3b8;
        --accent: #7c3aed;
        --accent2: #2563eb;
        --accent3: #22c55e;
        --danger: #ef4444;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        min-height: 100vh;
        font-family: 'Instrument Sans', system-ui, sans-serif;
        color: var(--text);
        background:
            radial-gradient(circle at top left, rgba(124, 58, 237, .22), transparent 34%),
            radial-gradient(circle at 80% 20%, rgba(56, 189, 248, .12), transparent 28%),
            linear-gradient(180deg, var(--bg), var(--bg2));
    }
    a { color: inherit; text-decoration: none; }
    .wrap { width: min(1200px, calc(100% - 32px)); margin: 0 auto; padding: 28px 0 40px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom: 18px; }
    .brand { display:flex; align-items:center; gap:12px; }
    .mark { width: 42px; height: 42px; border-radius: 14px; background: linear-gradient(135deg, var(--accent), var(--accent2)); }
    .title { margin: 0; font-size: 1.35rem; }
    .muted { color: var(--muted); }
    .card {
        padding: 20px; border-radius: 24px; border: 1px solid var(--border);
        background: var(--card); backdrop-filter: blur(18px); box-shadow: 0 30px 90px rgba(2, 6, 23, .38);
    }
    .grid { display:grid; gap: 16px; }
    .grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
    .grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
    .pill, .button {
        display:inline-flex; align-items:center; justify-content:center; border-radius: 14px; padding: 12px 16px; font-weight:700;
    }
    .pill { border: 1px solid var(--border); background: rgba(15, 23, 42, .36); }
    .button { border: none; cursor: pointer; color: white; background: linear-gradient(135deg, var(--accent), var(--accent2)); }
    .button.alt { background: linear-gradient(135deg, #0f766e, #22c55e); }
    .button.danger { background: linear-gradient(135deg, #dc2626, #ef4444); }
    .notice { margin-bottom: 16px; padding: 12px 14px; border-radius: 16px; }
    .stat { padding: 16px; border-radius: 18px; background: rgba(2, 6, 23, .36); border: 1px solid rgba(148, 163, 184, .12); }
    .stat strong { display:block; font-size: 1.35rem; margin-bottom: 6px; }
    .field { display:grid; gap: 8px; }
    label { color: #cbd5e1; font-size: .92rem; }
    input, textarea, select {
        width: 100%; border-radius: 14px; padding: 13px 14px; outline:none;
        border: 1px solid rgba(148,163,184,.18); background: rgba(2,6,23,.55); color: var(--text);
    }
    textarea { min-height: 120px; resize: vertical; }
    .tabs { display:flex; gap:10px; flex-wrap: wrap; margin-bottom: 18px; }
    .tab { padding:10px 14px; border-radius:999px; border:1px solid var(--border); background: rgba(15,23,42,.36); }
    .tab.active { border-color: rgba(124,58,237,.45); background: rgba(124,58,237,.18); }
    .codebox { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .86rem; line-height: 1.7; word-break: break-all; background: rgba(2,6,23,.6); border: 1px solid rgba(148,163,184,.16); border-radius: 16px; padding: 14px; }
    .badge { display:inline-flex; align-items:center; padding: 6px 10px; border-radius: 999px; font-size: .8rem; font-weight: 700; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .84rem; word-break: break-all; }
    .stack { display:grid; gap: 16px; }
    .stats { display:grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 14px; }
    .table-wrap { overflow:auto; border-radius: 18px; }
    table { width:100%; border-collapse: collapse; }
    th, td { padding: 12px 10px; border-bottom: 1px solid rgba(148, 163, 184, .12); text-align: left; vertical-align: top; }
    th { color: #cbd5e1; font-size: .88rem; font-weight: 600; }
    @media (max-width: 900px) { .grid.cols-2, .grid.cols-3, .grid, .stats { grid-template-columns: 1fr; } }
</style>
