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
      --bg: #0a0f1c;
      --surface: #0f1629;
      --card: rgba(22, 27, 46, 0.92);
      --muted: #9aa5b5;
      --text: #e9edf5;
      --primary: #5aa6ff;
      --primary-strong: #2d7cf5;
      --ok: #4ade80;
      --warn: #fbbf24;
      --err: #fb7185;
      --border: rgba(255,255,255,0.08);
      --accent: rgba(90,166,255,0.08);
      --glass: rgba(255,255,255,0.04);
    }
    @media (prefers-color-scheme: light){
      :root{
        --bg:#eef2f7; --surface:#dde6f7; --card:#ffffff; --text:#0f172a; --muted:#5f6677; --border:#e2e8f0; --accent:#e6f0ff; --glass:rgba(0,0,0,0.02);
      }
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:radial-gradient(circle at 20% 20%, rgba(90,166,255,0.18), transparent 25%),radial-gradient(circle at 80% 0%, rgba(0,255,214,0.12), transparent 32%),var(--bg);color:var(--text);font:14px/1.6 'Inter', system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    a{color:var(--primary);text-decoration:none}
    .container{max-width:1180px;margin:18px auto 32px;padding:0 18px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:18px;box-shadow:0 16px 48px rgba(0,0,0,.28);backdrop-filter:blur(10px)}
    .card .hd{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px;flex-wrap:wrap}
    .badge{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--glass);padding:5px 12px;border-radius:999px;color:var(--text);font-weight:700;font-size:12px;letter-spacing:0.1px}
    .muted{color:var(--muted)}
    .body{padding:22px}
    .row{display:flex;gap:18px;align-items:stretch;flex-wrap:wrap}
    .col{flex:1 1 360px;min-width:0}
    .page-header{display:flex;align-items:flex-start;gap:12px;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px}
    .page-title{margin:0;font-size:26px;font-weight:700;letter-spacing:-0.3px}
    .page-desc{margin:2px 0 0;font-size:14px;color:var(--muted)}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:12px;background:var(--accent);border:1px solid var(--border);font-weight:600;color:var(--text)}
    textarea, input, select, button{
      width:100%; border:1px solid var(--border); background:var(--surface); color:var(--text);
      border-radius:12px; padding:12px 14px; outline:none; font-size:14px; transition:border .18s ease, box-shadow .18s ease;
    }
    textarea{min-height:200px;resize:vertical}
    input:focus, textarea:focus, select:focus{border-color:var(--primary); box-shadow:0 0 0 3px rgba(90,166,255,.18)}
    button{cursor:pointer; font-weight:700; min-height:44px; touch-action:manipulation}
    .btn{background:linear-gradient(135deg,var(--primary),var(--primary-strong)); border:none; color:#fff; box-shadow:0 10px 30px rgba(45,124,245,.35)}
    .btn.secondary{background:var(--glass);border:1px solid var(--border);color:var(--text);box-shadow:none}
    .btn.ok{background:linear-gradient(135deg,#34d399,#16a34a)}
    .btn.err{background:linear-gradient(135deg,#fb7185,#e11d48)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .stack{display:flex;flex-direction:column;gap:10px}
    .kv{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .kv label{min-width:94px;color:var(--muted);font-size:13px;font-weight:600}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:var(--glass);padding:12px;border-radius:12px;font-size:13px;word-break:break-all;border:1px solid var(--border)}
    .table-wrapper{overflow:auto;-webkit-overflow-scrolling:touch;border:1px solid var(--border);border-radius:12px;background:var(--glass);padding:1px;max-height:clamp(260px,48vh,560px)}
    .table{width:100%;border-collapse:collapse;min-width:640px}
    .table th,.table td{border-bottom:1px solid var(--border);padding:10px 8px;text-align:left;font-size:13px}
    .table th{color:var(--muted);font-weight:700;background:var(--glass)}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:11px}
    .tag.ok{background:rgba(52,199,89,.12);border-color:#3e8e57;color:#34d399}
    .tag.err{background:rgba(251,113,133,.14);border-color:#e11d48;color:#fb7185}
    .tag.warn{background:rgba(251,191,36,.16);border-color:#a16207;color:#f59e0b}
    .toast{position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:86%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}
    .nav-links{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:flex-end}
    .nav-links a{padding:8px 10px;border-radius:10px;transition:background .15s ease}
    .nav-links a:hover{background:var(--glass)}
    .hero{display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:12px 0}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
    .stat-card{padding:18px;border-radius:14px;background:linear-gradient(145deg,var(--glass),rgba(255,255,255,0.04));border:1px solid var(--border);box-shadow:0 10px 28px rgba(0,0,0,.16)}
    .stat-card strong{display:block;font-size:30px;line-height:1.2}
    .chip-list{display:flex;gap:8px;flex-wrap:wrap}
    .inline-hint{font-size:12px;color:var(--muted)}

    /* 移动端优化 */
    @media (max-width: 900px) {
      .container{margin:10px auto 22px;padding:0 12px}
      .card{border-radius:14px}
      .card .hd{padding:14px 16px}
      .badge{font-size:11px;padding:4px 10px}
      .nav-links{width:100%;justify-content:flex-start;gap:6px;overflow-x:auto}
      .nav-links a{font-size:13px;white-space:nowrap}
      .body{padding:18px}
      .row{flex-direction:column;gap:14px}
      .col{flex:1 1 100%;min-width:0}
      textarea{min-height:160px;font-size:13px}
      input, select{font-size:14px}
      button{min-height:44px;font-size:14px}
      .toolbar{flex-direction:column;align-items:stretch}
      .toolbar button{width:100%}
      .kv{flex-direction:column;align-items:flex-start;gap:6px}
      .kv label{min-width:0;width:100%}
      .kv > *{width:100%}
      .code{font-size:12px;padding:10px}
      .table th,.table td{padding:8px 6px;font-size:12px}
      .toast{min-width:92%;max-width:92%}
      .table-wrapper{max-height:clamp(220px,42vh,460px)}
      #resWarnings{max-height:140px;overflow-y:auto}
    }

    @media (max-width: 520px) {
      .container{padding:0 8px;margin:6px auto 16px}
      .card{border-radius:12px}
      .card .hd{padding:12px 12px}
      .page-title{font-size:22px}
      .page-desc{font-size:13px}
      .badge{font-size:10px}
      .nav-links a{font-size:12px}
      textarea{min-height:140px;font-size:12px}
      button{min-height:42px;font-size:13px;padding:10px 12px}
      .table th,.table td{padding:6px 4px;font-size:11px}
    }
  </style>
</head>
<body>
  <div class="toast" id="toast"><div class="inner"></div></div>
  <div class="container">
    <div class="card">
      <div class="hd">
        <div class="badge">PRS · Price Recording System</div>
        <nav class="nav-links muted" style="margin-left:auto">
            <a href="/prs/index.php?action=dashboard">首页</a>
            <a href="/prs/index.php?action=ingest">导入</a>
            <a href="/prs/index.php?action=trends">趋势</a>
            <a href="/prs/index.php?action=products">产品</a>
            <a href="/prs/index.php?action=stores">门店</a>
        </nav>
      </div>
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
  </script>
</body>
</html>
<?php } }