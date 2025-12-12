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
      --bg: #0b0c10;
      --card: #12151a;
      --muted: #97a0af;
      --text: #e6e9ef;
      --primary: #3aa6ff;
      --ok: #34c759;
      --warn: #f5a524;
      --err: #ff453a;
      --border: #1f2430;
      --accent: #161a24;
      --glass: rgba(255,255,255,.04);
    }
    @media (prefers-color-scheme: light){
      :root{
        --bg:#f6f7f9; --card:#ffffff; --text:#0c0d10; --muted:#5b6170; --border:#e5e7eb; --accent:#eef2ff; --glass: rgba(255,255,255,.68);
      }
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:radial-gradient(120% 120% at 20% 20%, rgba(58,166,255,.10), transparent),radial-gradient(90% 90% at 80% 0%, rgba(52,199,89,.08), transparent),var(--bg);color:var(--text);font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;min-height:100vh;}
    a{color:var(--primary);text-decoration:none}
    .container{max-width:1180px;margin:18px auto;padding:0 16px;display:flex;flex-direction:column;gap:14px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 16px 40px rgba(0,0,0,.18);position:relative;overflow:hidden}
    .surface{background:linear-gradient(180deg,rgba(255,255,255,.02),rgba(255,255,255,.00));backdrop-filter:blur(6px)}
    .body{padding:20px}
    .app-header{display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid var(--border);background:linear-gradient(135deg,rgba(58,166,255,.12),rgba(58,166,255,.06));backdrop-filter:blur(8px)}
    .brand{display:flex;align-items:center;gap:12px}
    .brand .logo{width:42px;height:42px;border-radius:12px;border:1px solid var(--border);background:linear-gradient(180deg,#3aa6ff,#1f86f9);display:inline-flex;align-items:center;justify-content:center;font-weight:800;color:#fff;letter-spacing:.5px;box-shadow:0 10px 24px rgba(58,166,255,.35)}
    .brand .meta{display:flex;flex-direction:column;gap:2px}
    .brand strong{font-size:16px}
    .brand small{color:var(--muted);font-size:12px}
    .nav-links{display:flex;gap:10px;align-items:center;margin-left:auto;padding:4px 6px;border:1px solid var(--border);background:var(--glass);border-radius:999px;backdrop-filter:blur(10px)}
    .nav-links a{padding:8px 12px;border-radius:10px;color:var(--text);font-weight:600;white-space:nowrap}
    .nav-links a:hover,.nav-links a:focus-visible{background:rgba(58,166,255,.16);outline:none}
    .nav-toggle{display:none;border:1px solid var(--border);background:var(--card);color:var(--text);border-radius:12px;width:42px;height:42px;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 8px 16px rgba(0,0,0,.18)}
    .stack{display:flex;flex-direction:column;gap:10px}
    .row{display:flex;gap:18px;align-items:stretch;flex-wrap:wrap}
    .col{flex:1 1 360px;min-width:0}
    textarea, input, select, button{
      width:100%; border:1px solid var(--border); background:transparent; color:var(--text);
      border-radius:12px; padding:12px 14px; outline:none; font-size:14px;
    }
    textarea{min-height:200px;resize:vertical}
    button{cursor:pointer; font-weight:700; min-height:44px; touch-action:manipulation}
    .btn{background:linear-gradient(180deg,#3aa6ff,#1f86f9); border:none; color:#fff;transition:transform .12s ease, box-shadow .12s ease}
    .btn.secondary{background:transparent;border:1px solid var(--border);color:var(--text)}
    .btn.ok{background:linear-gradient(180deg,#4cd964,#2bbb49)}
    .btn.err{background:linear-gradient(180deg,#ff5b54,#e23b33)}
    .btn:active{transform:translateY(1px)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .kv{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .kv label{min-width:96px;color:var(--muted);font-size:13px;font-weight:600}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:rgba(127,127,127,.08);padding:10px 12px;border-radius:10px;font-size:13px;word-break:break-all}
    .table-wrapper{overflow:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:12px;background:var(--accent)}
    .table{width:100%;border-collapse:collapse;min-width:600px}
    .table th,.table td{border-bottom:1px solid var(--border);padding:10px 8px;text-align:left;font-size:13px}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:11px}
    .tag.ok{background:rgba(52,199,89,.1);border-color:#3e8e57;color:#3ae374}
    .tag.err{background:rgba(255,69,58,.1);border-color:#b3332d;color:#ff6b6b}
    .tag.warn{background:rgba(245,165,36,.12);border-color:#a67c15;color:#ffb020}
    .toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:88%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}
    .section-title{display:flex;align-items:center;gap:8px;font-size:18px;font-weight:700;margin:0}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid var(--border);background:var(--accent);color:var(--muted);font-size:12px}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
    .stat-card{padding:16px;border:1px solid var(--border);border-radius:14px;background:var(--card);box-shadow:0 10px 28px rgba(0,0,0,.14)}
    .stat-card .value{font-size:36px;font-weight:800;margin-bottom:6px}
    .action-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
    .action-tile{border:1px solid var(--border);border-radius:14px;padding:14px;text-decoration:none;background:var(--accent);color:var(--text);display:flex;flex-direction:column;gap:6px;transition:transform .12s ease, box-shadow .12s ease}
    .action-tile:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(0,0,0,.16)}
    .panel{border:1px solid var(--border);border-radius:14px;padding:16px;background:var(--card);box-shadow:0 10px 24px rgba(0,0,0,.12)}
    .panel .panel-title{display:flex;align-items:center;gap:8px;font-weight:700;margin:0 0 6px 0}
    .chip-row{display:flex;gap:8px;flex-wrap:wrap}
    .chip{padding:4px 10px;border-radius:12px;background:var(--accent);border:1px solid var(--border);font-size:12px;color:var(--muted)}
    .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}
    .floating-hint{position:absolute;bottom:12px;right:16px;font-size:12px;color:var(--muted)}
    .muted{color:var(--muted)}

    /* 移动端优化 */
    @media (max-width: 860px) {
      .container{margin:12px auto;padding:0 10px}
      .app-header{flex-wrap:wrap}
      .nav-links{width:100%;justify-content:flex-start;gap:8px;flex-wrap:wrap;border-radius:12px}
      .nav-toggle{display:inline-flex;margin-left:auto}
      .nav-links.is-collapsed{display:none}
      .nav-links.open{display:flex}
      .body{padding:16px}
      .row{flex-direction:column;gap:16px}
      .col{flex:1 1 100%;min-width:0}
      textarea{min-height:160px;font-size:13px}
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
      .table-wrapper{max-height:320px !important}
    }

    /* 超小屏幕优化 */
    @media (max-width: 520px) {
      .container{padding:0 6px;margin:8px auto}
      .card{border-radius:12px}
      .app-header{padding:12px 12px}
      .brand .logo{width:36px;height:36px;border-radius:10px}
      .body{padding:12px}
      textarea{min-height:140px;font-size:12px}
      button{min-height:42px;font-size:13px;padding:10px 12px}
      .table th,.table td{padding:6px 4px;font-size:11px}
    }
  </style>
</head>
<body>
  <div class="toast" id="toast"><div class="inner"></div></div>
  <div class="container">
    <div class="card app-header">
      <div class="brand">
        <div class="logo">PRS</div>
        <div class="meta">
          <strong>Price Recording System</strong>
          <small>跨端现代化界面</small>
        </div>
      </div>
      <button class="nav-toggle" id="navToggle" aria-label="切换导航">☰</button>
      <nav class="nav-links is-collapsed" id="navLinks">
          <a href="/prs/index.php?action=dashboard">首页</a>
          <a href="/prs/index.php?action=ingest">导入</a>
          <a href="/prs/index.php?action=trends">趋势</a>
          <a href="/prs/index.php?action=products">产品</a>
          <a href="/prs/index.php?action=stores">门店</a>
      </nav>
    </div>
    <div class="card surface">
      <div class="body">
<?php } }

if (!function_exists('render_footer')) {
    function render_footer(): void { ?>
      </div></div></div><script>
    const toast = (msg,type='ok',ms=2400)=>{
      const t = document.getElementById('toast');
      t.className = 'toast ' + type;
      t.querySelector('.inner').textContent = msg;
      t.style.display = 'block';
      clearTimeout(t._timer);
      t._timer = setTimeout(()=>{ t.style.display='none'; }, ms);
    };
    (()=>{
      const nav = document.getElementById('navLinks');
      const btn = document.getElementById('navToggle');
      if(nav && btn){
        const sync = ()=>{ if(window.innerWidth>860){ nav.classList.remove('is-collapsed','open'); } else { nav.classList.add('is-collapsed'); nav.classList.remove('open'); } };
        sync();
        btn.addEventListener('click',()=>{ nav.classList.toggle('open'); nav.classList.toggle('is-collapsed'); });
        window.addEventListener('resize', sync);
      }
    })();
  </script>
</body>
</html>
<?php } }
