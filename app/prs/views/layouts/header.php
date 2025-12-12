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
      --bg: radial-gradient(circle at 20% 20%, rgba(58,166,255,.08), rgba(34,38,46,.6)), #0b0c10;
      --surface: rgba(19,22,28,.82);
      --muted: #9aa4b5;
      --text: #eef2f8;
      --primary: #4da3ff;
      --ok: #34c759;
      --warn: #f5a524;
      --err: #ff453a;
      --border: #1f2430;
      --accent: rgba(77,163,255,.12);
      --shadow: 0 16px 60px rgba(0,0,0,.35);
    }
    @media (prefers-color-scheme: light){
      :root{ --bg:#f4f6fb; --surface:#ffffff; --text:#0c0d10; --muted:#5b6170; --border:#e5e7eb; --accent:#eef4ff; --shadow:0 12px 36px rgba(15,23,42,.12); }
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    a{color:var(--primary);text-decoration:none}
    .page-shell{min-height:100vh;display:flex;flex-direction:column;backdrop-filter:blur(5px)}
    .container{max-width:1160px;margin:0 auto;padding:0 18px;width:100%}
    .masthead{position:sticky;top:0;z-index:99;background:rgba(12,14,20,.75);border-bottom:1px solid var(--border);box-shadow:0 10px 40px rgba(0,0,0,.18);backdrop-filter:blur(10px)}
    .masthead .inner{display:flex;align-items:center;gap:14px;min-height:64px}
    .brand{display:flex;align-items:center;gap:10px;font-weight:700;letter-spacing:0.2px}
    .brand .dot{width:14px;height:14px;border-radius:50%;background:linear-gradient(135deg,#4da3ff,#7cf3ff);box-shadow:0 0 0 5px rgba(77,163,255,.22)}
    .brand .meta{display:flex;flex-direction:column;gap:2px}
    .brand .title{font-size:16px}
    .brand .sub{font-size:12px;color:var(--muted)}
    .nav-links{display:flex;gap:10px;align-items:center;margin-left:auto;overflow-x:auto;padding:6px 0}
    .nav-links a{padding:8px 12px;border-radius:12px;border:1px solid transparent;color:var(--muted);font-weight:600;white-space:nowrap}
    .nav-links a:hover,.nav-links a:focus-visible{color:var(--text);border-color:var(--border);background:rgba(255,255,255,.03)}
    .page-body{flex:1;padding:28px 0 40px;display:flex}
    .content-grid{display:flex;flex-direction:column;gap:18px;width:100%}
    .panel{background:var(--surface);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow);padding:20px}
    .panel.soft{background:linear-gradient(135deg,rgba(77,163,255,.12),rgba(124,243,255,.08));border-color:rgba(77,163,255,.32)}
    .panel.headered{padding:0}
    .panel .section-header{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .panel .section-body{padding:18px 20px}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;border:1px solid var(--border);background:var(--accent);color:var(--text);font-weight:600;font-size:12px}
    .muted{color:var(--muted)}
    .stack{display:flex;flex-direction:column;gap:12px}
    .row{display:flex;gap:14px;flex-wrap:wrap}
    .col{flex:1 1 320px;min-width:0}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
    .stat-card{background:linear-gradient(135deg,rgba(255,255,255,.04),rgba(77,163,255,.08));border:1px solid var(--border);border-radius:16px;padding:14px 16px;display:flex;flex-direction:column;gap:6px;box-shadow:var(--shadow)}
    .stat-value{font-size:34px;font-weight:800;letter-spacing:0.2px}
    .stat-label{font-size:13px;color:var(--muted)}
    textarea, input, select, button{width:100%;border:1px solid var(--border);background:transparent;color:var(--text);border-radius:12px;padding:12px 14px;outline:none;font-size:14px}
    textarea{min-height:200px;resize:vertical}
    button{cursor:pointer;font-weight:700;min-height:44px;touch-action:manipulation}
    .btn{background:linear-gradient(180deg,#4da3ff,#1f86f9);border:none;color:#fff}
    .btn.secondary{background:transparent;border:1px solid var(--border);color:var(--text)}
    .btn.ok{background:linear-gradient(180deg,#4cd964,#2bbb49)}
    .btn.err{background:linear-gradient(180deg,#ff5b54,#e23b33)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .toolbar.tight{gap:8px}
    .toolbar .spacer{flex:1}
    .kv{display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap}
    .kv label{min-width:92px;color:var(--muted);font-size:13px;line-height:1.4;padding-top:4px}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:rgba(127,127,127,.08);padding:10px 12px;border-radius:10px;font-size:13px;word-break:break-all;border:1px dashed var(--border)}
    .table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:12px;background:rgba(0,0,0,.05)}
    .table{width:100%;border-collapse:collapse;min-width:720px}
    .table th,.table td{border-bottom:1px solid var(--border);padding:11px 10px;text-align:left;font-size:13px}
    .table th{color:var(--muted);font-weight:600}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:11px}
    .tag.ok{background:rgba(52,199,89,.1);border-color:#3e8e57;color:#3ae374}
    .tag.err{background:rgba(255,69,58,.1);border-color:#b3332d;color:#ff6b6b}
    .tag.warn{background:rgba(245,165,36,.12);border-color:#a67c15;color:#ffb020}
    .chip{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:12px;background:rgba(255,255,255,.03);border:1px solid var(--border);font-size:12px;color:var(--muted)}
    .notice{border:1px dashed var(--border);padding:10px 12px;border-radius:12px;background:rgba(255,255,255,.03);color:var(--muted);font-size:13px}
    .nav-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
    .nav-item{display:flex;gap:8px;align-items:flex-start;padding:14px;border-radius:14px;border:1px solid var(--border);background:rgba(255,255,255,.03);color:var(--text);font-weight:700;transition:transform .12s ease, border-color .12s ease}
    .nav-item:hover{transform:translateY(-2px);border-color:rgba(77,163,255,.5)}
    .nav-item .desc{color:var(--muted);font-weight:500;font-size:13px}
    .section-title{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:700;margin:0}
    .result-wrap{max-height:55vh;overflow:auto;border-radius:12px;border:1px solid var(--border);background:rgba(255,255,255,.02)}
    .toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:80%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}

    @media (max-width: 900px) {
      .masthead .inner{flex-wrap:wrap;gap:10px;padding:8px 0}
      .nav-links{width:100%;margin-left:0}
      .page-body{padding:18px 0 28px}
      .panel{border-radius:14px}
      .stat-card{border-radius:12px}
      textarea{min-height:160px;font-size:13px}
      .toolbar{flex-direction:column;align-items:stretch}
      .toolbar .spacer{display:none}
      .kv{flex-direction:column;align-items:flex-start}
      .kv label{min-width:0;width:100%}
      .kv > *{width:100%}
      .table{min-width:620px}
    }

    @media (max-width: 540px) {
      .container{padding:0 12px}
      .brand .title{font-size:14px}
      .brand .sub{font-size:11px}
      button{min-height:42px;font-size:13px;padding:10px 12px}
      .table th,.table td{padding:8px 6px;font-size:12px}
      .table-wrapper{max-height:320px;overflow:auto}
      #resWarnings{max-height:160px;overflow:auto}
    }
  </style>
</head>
<body>
  <div class="toast" id="toast"><div class="inner"></div></div>
  <div class="page-shell">
    <header class="masthead">
      <div class="container inner">
        <div class="brand">
          <span class="dot"></span>
          <div class="meta">
            <span class="title">PRS · Price Recording System</span>
            <span class="sub">轻量、稳定的价格记录台</span>
          </div>
        </div>
        <nav class="nav-links muted">
            <a href="/prs/index.php?action=dashboard">首页</a>
            <a href="/prs/index.php?action=ingest">导入</a>
            <a href="/prs/index.php?action=trends">趋势</a>
            <a href="/prs/index.php?action=products">产品</a>
            <a href="/prs/index.php?action=stores">门店</a>
        </nav>
      </div>
    </header>
    <main class="page-body">
      <div class="container content-grid">
<?php } }

if (!function_exists('render_footer')) {
    function render_footer(): void { ?>
      </div>
    </main>
  </div>
  <script>
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
