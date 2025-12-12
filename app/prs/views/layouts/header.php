<?php
declare(strict_types=1);

/**
 * PRS Layout Header
 * - 提供 render_header(string $title = 'PRS') 与 render_footer()
 * - 内联一份简洁的样式与顶部提示条（自动淡出）
 */

if (!function_exists('render_header')) {
    function render_header(string $title = 'PRS'): void { ?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="color-scheme" content="light dark">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <style>
    :root{
      --bg: #070910;
      --card: #0f121c;
      --muted: #94a3b8;
      --text: #e7edf8;
      --primary: #6ab7ff;
      --ok: #3ae49f;
      --warn: #ffb94a;
      --err: #ff6b6b;
      --border: #1c2233;
      --accent: #11182a;
      --frost: rgba(255,255,255,.04);
    }
    @media (prefers-color-scheme: light){
      :root{ --bg:#f7f9fb; --card:#ffffff; --text:#0e1420; --muted:#6b7280; --border:#e5e7eb; --accent:#eef2ff; --frost:rgba(15,23,42,.04); }
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:radial-gradient(120% 120% at 10% 10%,rgba(106,183,255,.12),transparent 40%),radial-gradient(120% 120% at 90% 10%,rgba(58,226,169,.12),transparent 38%),var(--bg);color:var(--text);font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    a{color:var(--primary);text-decoration:none}
    .container{max-width:1100px;margin:0 auto;padding:0 18px}
    .app-shell{min-height:100vh;display:flex;flex-direction:column;gap:18px;padding:18px 0}
    .masthead{position:sticky;top:0;z-index:9;padding:10px 0 6px;background:linear-gradient(180deg,rgba(7,9,16,.82),rgba(7,9,16,.56));backdrop-filter:blur(12px);border-bottom:1px solid var(--border)}
    .masthead .nav-bar{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
    .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:16px;letter-spacing:0.5px}
    .brand .dot{width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#5ad4ff,#6fd4a3)}
    .brand small{display:block;color:var(--muted);font-weight:500;font-size:12px}
    .nav-links{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-left:auto}
    .nav-links a{padding:8px 10px;border-radius:10px;border:1px solid transparent;color:var(--text);font-weight:600;background:var(--frost)}
    .nav-links a.active{border-color:var(--border);box-shadow:0 0 0 1px rgba(255,255,255,.03) inset;background:linear-gradient(135deg,rgba(106,183,255,.16),rgba(58,226,169,.12));}
    .hero{margin-top:10px;padding:12px 14px;border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.02));display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    .hero .title{font-size:18px;font-weight:700}
    .hero .muted{font-size:13px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:18px;box-shadow:0 10px 36px rgba(0,0,0,.22)}
    .card .hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .badge{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--accent);padding:4px 10px;border-radius:999px;color:var(--text);font-weight:600;font-size:12px}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid var(--border);background:var(--frost);font-weight:600;font-size:12px;color:var(--muted)}
    .muted{color:var(--muted)}
    .body{padding:22px}
    .section{display:flex;flex-direction:column;gap:12px}
    .row{display:flex;gap:18px;align-items:stretch;flex-wrap:wrap}
    .col{flex:1 1 360px;min-width:0}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
    .stat-card{padding:18px;border:1px solid var(--border);border-radius:14px;background:linear-gradient(180deg,rgba(106,183,255,.08),rgba(17,24,42,.08));display:flex;flex-direction:column;gap:6px}
    .stat-card h4{margin:0;font-size:13px;color:var(--muted);letter-spacing:0.2px}
    .stat-card .big{font-size:38px;font-weight:800}
    textarea, input, select, button{
      width:100%; border:1px solid var(--border); background:rgba(255,255,255,.02); color:var(--text);
      border-radius:12px; padding:12px 14px; outline:none; font-size:14px;
    }
    textarea{min-height:200px;resize:vertical}
    button{cursor:pointer; font-weight:700; min-height:44px; touch-action:manipulation}
    .btn{background:linear-gradient(180deg,#6ab7ff,#3384ff); border:none; color:#fff;box-shadow:0 6px 18px rgba(51,132,255,.35)}
    .btn.secondary{background:transparent;border:1px solid var(--border);color:var(--text);box-shadow:none}
    .btn.ok{background:linear-gradient(180deg,#40e3a8,#13b77f)}
    .btn.err{background:linear-gradient(180deg,#ff7b73,#f2453f)}
    .btn.ghost{background:var(--frost);border:1px dashed var(--border);color:var(--muted)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .stack{display:flex;flex-direction:column;gap:10px}
    .kv{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .kv label{min-width:86px;color:var(--muted);font-size:13px}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:rgba(127,127,127,.08);padding:10px 12px;border-radius:10px;font-size:13px;word-break:break-all}
    .table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:12px;background:var(--accent)}
    .table{width:100%;border-collapse:collapse;min-width:600px}
    .table th,.table td{border-bottom:1px solid var(--border);padding:10px 8px;text-align:left;font-size:13px}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:11px}
    .tag.ok{background:rgba(52,199,89,.1);border-color:#3e8e57;color:#3ae374}
    .tag.err{background:rgba(255,69,58,.1);border-color:#b3332d;color:#ff6b6b}
    .tag.warn{background:rgba(245,165,36,.12);border-color:#a67c15;color:#ffb020}
    .callout{padding:12px 14px;border-radius:12px;border:1px dashed var(--border);background:var(--frost);display:flex;gap:10px;align-items:flex-start;font-size:13px}
    .callout strong{color:var(--text)}
    .panel{border:1px solid var(--border);border-radius:14px;padding:16px;background:var(--accent)}
    .panel .title{display:flex;align-items:center;justify-content:space-between;gap:8px;font-weight:700}
    .ghost-surface{background:var(--frost);border:1px solid var(--border);border-radius:12px;padding:10px 12px}
    .pill-row{display:flex;gap:8px;flex-wrap:wrap}
    .toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:80%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}

    /* 移动端优化 */
    @media (max-width: 900px) {
      .container{padding:0 12px}
      .nav-links{width:100%;justify-content:flex-start;gap:8px;margin-left:0}
      .hero{flex-direction:column;align-items:flex-start}
      .card{border-radius:14px}
      .body{padding:18px}
      .row{flex-direction:column;gap:16px}
      .col{flex:1 1 100%;min-width:0}
      textarea{min-height:180px;font-size:13px}
      input, select{font-size:14px}
      button{min-height:44px;font-size:14px}
      .toolbar{flex-direction:column;align-items:stretch}
      .toolbar button{width:100%}
      .kv{flex-direction:column;align-items:flex-start;gap:6px}
      .kv label{min-width:0;width:100%}
      .kv > *{width:100%}
      .code{font-size:12px;padding:8px 10px}
      .table th,.table td{padding:8px 6px;font-size:12px}
      .toast{min-width:90%;max-width:90%}
      .table-wrapper{max-height:360px !important;overflow-x:auto;overflow-y:auto}
    }

    /* 超小屏幕优化 */
    @media (max-width: 520px) {
      .app-shell{gap:12px;padding:12px 0}
      .container{padding:0 8px}
      .card{border-radius:10px}
      .card .hd{padding:12px 14px}
      .badge{font-size:10px}
      .nav-links a{font-size:12px}
      .body{padding:14px}
      textarea{min-height:150px;font-size:12px}
      button{min-height:42px;font-size:13px;padding:10px 12px}
      .table th,.table td{padding:6px 4px;font-size:11px}
      .stat-card .big{font-size:30px}
    }
  </style>
</head>
<body>
  <div class="toast" id="toast"><div class="inner"></div></div>
  <div class="app-shell">
    <header class="masthead">
      <div class="container">
        <div class="nav-bar">
          <div class="brand"><span class="dot"></span>PRS<span class="pill">价格记录系统</span><small>Price Recording System</small></div>
          <?php $curr = $_GET['action'] ?? 'dashboard'; ?>
          <nav class="nav-links muted">
              <a href="/prs/index.php?action=dashboard" class="<?= $curr==='dashboard'?'active':'' ?>">首页</a>
              <a href="/prs/index.php?action=ingest" class="<?= $curr==='ingest'?'active':'' ?>">导入</a>
              <a href="/prs/index.php?action=trends" class="<?= $curr==='trends'?'active':'' ?>">趋势</a>
              <a href="/prs/index.php?action=products" class="<?= $curr==='products'?'active':'' ?>">产品</a>
              <a href="/prs/index.php?action=stores" class="<?= $curr==='stores'?'active':'' ?>">门店</a>
          </nav>
        </div>
        <div class="hero">
          <div class="title">现代化价格记录中心</div>
          <div class="muted">桌面与移动端均可顺畅使用，查看、导入、分析更轻盈。</div>
        </div>
      </div>
    </header>
      <main class="container">
        <div class="card">
          <div class="body">
  <?php } }


if (!function_exists('render_footer')) {
    function render_footer(): void { ?>
        </div></div></main></div><script>
    const toast = (msg,type='ok',ms=2400)=>{
      const t = document.getElementById('toast');
      t.className = 'toast ' + type;
      t.querySelector('.inner').textContent = msg;
      t.style.display = 'block';
      clearTimeout(t._timer);
      t._timer = setTimeout(()=>{ t.style.display='none'; }, ms);
    };
  </script>
</body>
</html>
<?php } }
