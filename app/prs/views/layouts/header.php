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
      --accent: #2d3446;
    }
    @media (prefers-color-scheme: light){
      :root{
        --bg:#f6f7f9; --card:#ffffff; --text:#0c0d10; --muted:#5b6170; --border:#e5e7eb; --accent:#eef2ff;
      }
    }
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--text);font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;}
    a{color:var(--primary);text-decoration:none}
    .container{max-width:1000px;margin:24px auto;padding:0 16px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.18)}
    .card .hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
    .badge{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--accent);padding:4px 10px;border-radius:999px;color:var(--text);font-weight:600}
    .muted{color:var(--muted)}
    .body{padding:20px}
    .row{display:flex;gap:18px;align-items:stretch;flex-wrap:wrap}
    .col{flex:1 1 360px;min-width:320px}
    textarea, input, select, button{
      width:100%; border:1px solid var(--border); background:transparent; color:var(--text);
      border-radius:12px; padding:12px 14px; outline:none;
    }
    textarea{min-height:260px;resize:vertical}
    button{cursor:pointer; font-weight:700}
    .btn{background:linear-gradient(180deg,#3aa6ff,#1f86f9); border:none; color:#fff}
    .btn.secondary{background:transparent;border:1px solid var(--border);color:var(--text)}
    .btn.ok{background:linear-gradient(180deg,#4cd964,#2bbb49)}
    .btn.err{background:linear-gradient(180deg,#ff5b54,#e23b33)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .stack{display:flex;flex-direction:column;gap:10px}
    .kv{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .kv label{min-width:86px;color:var(--muted)}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:rgba(127,127,127,.08);padding:10px 12px;border-radius:10px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{border-bottom:1px solid var(--border);padding:10px 8px;text-align:left}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:12px}
    .tag.ok{background:rgba(52,199,89,.1);border-color:#3e8e57;color:#3ae374}
    .tag.err{background:rgba(255,69,58,.1);border-color:#b3332d;color:#ff6b6b}
    .tag.warn{background:rgba(245,165,36,.12);border-color:#a67c15;color:#ffb020}
    /* top toast */
    .toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:80%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}
  </style>
</head>
<body>
  <div class="toast" id="toast"><div class="inner"></div></div>
  <div class="container">
    <div class="card">
      <div class="hd">
        <div class="badge">PRS · Price Recording System</div>
        <div style="margin-left:auto" class="muted">
            <a href="/prs/console/ingest_text.php">导入</a>
            <a href="/prs/console/trends.php" style="margin-left:16px">价格趋势</a>
            <a href="/prs/console/products_browser.php" style="margin-left:16px">产品列表</a>
            <a href="/prs/console/stores_browser.php" style="margin-left:16px">门店列表</a>
        </div>
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