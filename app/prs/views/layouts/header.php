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
    .card .hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap}
    .badge{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:var(--accent);padding:4px 10px;border-radius:999px;color:var(--text);font-weight:600;font-size:12px}
    .muted{color:var(--muted)}
    .body{padding:20px}
    .row{display:flex;gap:18px;align-items:stretch;flex-wrap:wrap}
    .col{flex:1 1 360px;min-width:0}
    textarea, input, select, button{
      width:100%; border:1px solid var(--border); background:transparent; color:var(--text);
      border-radius:12px; padding:12px 14px; outline:none; font-size:14px;
    }
    textarea{min-height:200px;resize:vertical}
    button{cursor:pointer; font-weight:700; min-height:44px; touch-action:manipulation}
    .btn{background:linear-gradient(180deg,#3aa6ff,#1f86f9); border:none; color:#fff}
    .btn.secondary{background:transparent;border:1px solid var(--border);color:var(--text)}
    .btn.ok{background:linear-gradient(180deg,#4cd964,#2bbb49)}
    .btn.err{background:linear-gradient(180deg,#ff5b54,#e23b33)}
    .toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .stack{display:flex;flex-direction:column;gap:10px}
    .kv{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    .kv label{min-width:86px;color:var(--muted);font-size:13px}
    .code{font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;background:rgba(127,127,127,.08);padding:10px 12px;border-radius:10px;font-size:13px;word-break:break-all}
    .table-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch}
    .table{width:100%;border-collapse:collapse;min-width:600px}
    .table th,.table td{border-bottom:1px solid var(--border);padding:10px 8px;text-align:left;font-size:13px}
    .tag{display:inline-block;padding:2px 8px;border-radius:8px;border:1px solid var(--border);background:var(--accent);font-size:11px}
    .tag.ok{background:rgba(52,199,89,.1);border-color:#3e8e57;color:#3ae374}
    .tag.err{background:rgba(255,69,58,.1);border-color:#b3332d;color:#ff6b6b}
    .tag.warn{background:rgba(245,165,36,.12);border-color:#a67c15;color:#ffb020}
    .toast{position:fixed;top:18px;left:50%;transform:translateX(-50%);z-index:9999;display:none;min-width:280px;max-width:80%}
    .toast .inner{padding:12px 16px;border-radius:12px;backdrop-filter:blur(8px);border:1px solid var(--border)}
    .toast.ok .inner{background:rgba(76,217,100,.16);color:#eaffea}
    .toast.err .inner{background:rgba(255,69,58,.16);color:#ffeaea}
    .toast.warn .inner{background:rgba(245,165,36,.16);color:#fff4d8}
    .nav-links{display:flex;gap:12px;flex-wrap:wrap;align-items:center}

    /* 移动端优化 */
    @media (max-width: 768px) {
      .container{margin:12px auto;padding:0 8px}
      .card{border-radius:12px}
      .card .hd{padding:12px 16px;flex-direction:column;align-items:flex-start;gap:10px}
      .badge{font-size:11px;padding:3px 8px}
      .nav-links{width:100%;justify-content:flex-start;gap:8px}
      .nav-links a{font-size:13px}
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
      /* 移动端结果表格优化 */
      .table-wrapper{max-height:300px !important;overflow-x:auto;overflow-y:auto;border:1px solid var(--border);border-radius:8px}
      #resSummary{font-size:13px;line-height:1.5;padding:8px 0}
      #resWarnings{max-height:150px;overflow-y:auto}
      #resTableWrap{margin-top:8px}
    }

    /* 超小屏幕优化 */
    @media (max-width: 480px) {
      .container{padding:0 4px;margin:8px auto}
      .card{border-radius:8px}
      .card .hd{padding:10px 12px}
      .badge{font-size:10px}
      .nav-links a{font-size:12px}
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