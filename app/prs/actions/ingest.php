<?php
/**
 * PRS Ingest Action - 数据导入页面
 * 文件路径: app/prs/actions/ingest.php
 * 说明: 批量导入界面（文本粘贴 → 试运行 / 正式入库）
 */

// 防止直接访问
if (!defined('PRS_ENTRY')) {
    die('Access denied');
}



// 加载 header 布局
$header = PRS_VIEW_PATH . '/layouts/header.php';
if (!is_file($header)) {
    http_response_code(500);
    echo "Missing header layout: {$header}";
    exit;
}
require_once $header;
render_header('PRS · 批量导入');

// API 基础路径 - 使用新的路由方式
$apiBase = '/prs/index.php?action=ingest_save';

// AI 提示词
$aiPrompt = "你是一个价格记录系统的数据解析助手。\n请根据我提供的图片内容，提取商品的价格信息，并严格按照我指定的格式输出。\n\n**输出格式要求 (使用 \"#@\" 作为分隔符):**\n\n1.  **首行 (Header):** 必须以日期和店名开头。格式为：\n    `[YYYY-MM-DD]#@[店名]#@`\n    例如: `2025-11-11#@Mercado Central#@`\n\n2.  **后续行 (Detail Blocks):** 每个商品信息占据一个空行分隔的块。块的第一个非键值对内容必须是商品的西班牙语名称 (name_es)。\n    - **必填信息**: 商品西班牙语名 (name_es)。\n    - **可选键值对**:\n        - `ud`: 单价 (€/ud)，例: `ud:0.38`\n        - `udp`: 单位重量 (克/ud)，例: `udp:190g` (注意：单位必须是 g)\n        - `pkg`: 公斤价 (€/kg)，例: `pkg:2.6`\n        - `zh`: 中文名，例: `zh:苹果金`\n        - `cat`: 类目 (fruit/seafood/dairy/unknown)，例: `cat:fruit`\n\n**请严格遵守格式，不要添加任何解释性文字或 markdown 块。**\n\n**示例输出格式:**\n2025-11-11#@Mercado Central#@\n\nManzana Golden#@ud:0.38#@udp:190g#@pkg:2#@\n\nPera Conferencia#@ud:0.43#@udp:166g#@pkg:2.6#@";

?>
<div class="stack" style="gap:16px">
  <div class="callout"><strong>提醒：</strong>当前 AI 提示词与试运行校验暂不可用，请直接粘贴文本并走正式入库；移动端会自动收起明细，避免运行结果撑爆屏幕。</div>
  <div class="row">
    <div class="col">
      <div class="panel" style="gap:12px;display:flex;flex-direction:column;">
        <div class="title">数据输入</div>
        <div class="kv"><label>AI模型</label><input id="aiModel" placeholder="暂不可用" disabled></div>
        <div class="kv"><label>快速提示</label>
          <div class="muted">首行写"日期 + 店名"，后面按块写明细。分隔符不限，自动识别（#@、||、## 等）。</div>
        </div>
        <textarea id="payload" class="code" placeholder="例：
2025-11-11#@Mercado Central#@

Manzana Golden#@ud:0.38#@udp:190g#@pkg:2#@

pera cinferebcia#@ud:0.43#@udp:166g#@pkg:2.6#@"></textarea>
        <div class="toolbar">
          <button class="btn secondary" id="btnSample">填入示例</button>
          <div style="flex:1"></div>
          <button class="btn secondary" id="btnDry" data-disabled="1" title="试运行校验维护中">试运行校验</button>
          <button class="btn ok" id="btnCommit">正式入库</button>
        </div>
      </div>

      <div class="panel" style="margin-top:10px;gap:12px;display:flex;flex-direction:column;">
        <div class="title">AI 提示词<span class="pill">暂不可用</span></div>
        <div class="muted">提示词目前停用，仅供查看。复制按钮被锁定以防误用。</div>
        <textarea id="aiPromptHelper" class="code" rows="8" readonly style="max-height:180px;overflow:auto;opacity:.65;"><?= htmlspecialchars($aiPrompt) ?></textarea>
        <div class="toolbar" style="justify-content:flex-end">
          <button class="btn ghost" id="btnCopyPrompt" data-disabled="1" style="max-width:140px;">复制提示词</button>
        </div>
      </div>
    </div>

    <div class="col">
      <div class="panel" style="gap:12px;display:flex;flex-direction:column;">
        <div class="title">运行结果<span class="pill">实时</span></div>
        <div class="ghost-surface" id="resSummary" style="font-size:13px">待运行</div>
        <div id="resWarnings" class="code" style="display:none;word-break:break-word"></div>
        <div class="toolbar" style="justify-content:flex-end">
          <button class="btn ghost" id="btnToggleTable" data-open="0" style="max-width:140px;">展开明细</button>
        </div>
        <div id="resTableWrap" class="table-wrapper" style="max-height:360px;display:none">
          <div class="muted" style="font-size:11px;margin-bottom:4px;padding:0 4px">👆 向右滑动查看更多列</div>
          <table class="table" id="resTable">
            <thead>
              <tr>
                <th>#</th><th>ES名</th><th>ZH名</th><th>类目</th><th>€/kg</th><th>€/ud</th><th>g/ud</th><th>状态</th><th>幂等</th><th>图</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const $ = sel => document.querySelector(sel);
  const apiBase = <?= json_encode($apiBase) ?>;

  const fillSample = () => {
    $('#payload').value =
`2025-11-11#@Mercado Central#@

Manzana Golden#@ud:0.38#@udp:190g#@pkg:2#@

pera cinferebcia#@ud:0.43#@udp:166g#@pkg:2.6#@`;
  };

  // 暂停特性：AI 提示词、试运行
  const guardDisabled = (el, msg) => {
    if (!el) return;
    el.addEventListener('click', (e) => {
      if (el.dataset.disabled === '1') {
        e.preventDefault();
        toast(msg, 'warn');
      }
    });
  };
  guardDisabled($('#btnCopyPrompt'), 'AI 提示词暂不可用');
  guardDisabled($('#btnDry'), '试运行校验暂不可用');

  // 用表单方式提交，避免 WAF 拦截 text/plain
  const callAPI = async (dryRun) => {
    const txt = document.querySelector('#payload').value.trim();
    const ai  = document.querySelector('#aiModel').value.trim();
    if (!txt) { toast('请输入要导入的文本', 'warn'); return; }

    const form = new URLSearchParams();
    form.set('payload', txt);
    form.set('dry_run', dryRun ? 1 : 0);
    if (ai) form.set('ai_model', ai);

    const url = apiBase;
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With':'XMLHttpRequest'
      },
      body: form.toString(),
      credentials: 'same-origin'
    });

    let data, raw;
    try { raw = await res.text(); data = JSON.parse(raw); }
    catch { toast('导入失败：返回不是JSON', 'err', 3800); console.error(raw); return; }

    if (!data.ok) {
      toast('导入失败：' + (data.message || '未知错误'), 'err', 3800);
      document.querySelector('#resSummary').textContent = '失败：' + (data.message || '未知错误');
      // 有 stderr（比如 Warning）则展示出来，便于快速定位
      if (data.stderr) {
        const w = document.querySelector('#resWarnings');
        w.style.display = 'block';
        w.textContent = data.stderr;
      }
      document.querySelector('#resTableWrap').style.display = 'none';
      const toggleBtn = document.querySelector('#btnToggleTable');
      toggleBtn.dataset.open = '0';
      toggleBtn.textContent = '展开明细';
      return;
    }

    const sum = `门店：${data.store}｜日期：${data.date}｜分隔符：${data.delim} ｜通过 ${data.accepted}，拒绝 ${data.rejected}（${dryRun? '试运行':'已入库'}）`;
    const resSummary = document.querySelector('#resSummary');
    resSummary.textContent = sum;
    toast(dryRun ? '试运行完成' : '已成功入库', 'ok');

    const warnBox = document.querySelector('#resWarnings');
    if ((data.stderr && data.stderr.length) || (data.warnings && data.warnings.length)) {
      warnBox.style.display = 'block';
      warnBox.textContent = [
        ...(data.stderr ? [data.stderr] : []),
        ...(data.warnings || []).map(w => '• ' + w)
      ].join('\n');
    } else { warnBox.style.display = 'none'; }

    const tb = document.querySelector('#resTable tbody');
    tb.innerHTML = '';
    const resTableWrap = document.querySelector('#resTableWrap');
    const toggleBtn = document.querySelector('#btnToggleTable');
    if (data.details && data.details.length) {
      data.details.forEach(d => {
        const tr = document.createElement('tr');
        const tag = (v,klass='') => `<span class="tag ${klass}">${v}</span>`;
        tr.innerHTML = `
          <td>${d.line ?? ''}</td>
          <td>${d.name_es ?? ''}</td>
          <td>${d.name_zh ?? ''}</td>
          <td>${d.category ?? ''}</td>
          <td>${d.price_per_kg ?? ''}</td>
          <td>${d.price_per_ud ?? ''}</td>
          <td>${d.unit_weight_g ?? ''}</td>
          <td>${tag(d.status || 'listed')}</td>
          <td>${d.idem_skipped ? tag('是','warn') : tag('否','ok')}</td>
          <td>${d.image_filename_set ? tag('已设','ok') : tag('—')}</td>
        `;
        tb.appendChild(tr);
      });
      if (toggleBtn.dataset.open === '1') {
        resTableWrap.style.display = 'block';
      } else {
        resTableWrap.style.display = 'none';
      }
      // 移动端：滚动到结果区域
      setTimeout(() => {
        if (window.innerWidth <= 768) {
          resSummary.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 100);
    } else {
      resTableWrap.style.display = 'none';
      toggleBtn.dataset.open = '0';
      toggleBtn.textContent = '展开明细';
    }
  };


  $('#btnDry').addEventListener('click', (e) => { if (e.currentTarget.dataset.disabled !== '1') callAPI(true); });
  $('#btnCommit').addEventListener('click', () => callAPI(false));
  $('#btnSample').addEventListener('click', fillSample);
  $('#btnToggleTable').addEventListener('click', () => {
    const btn = $('#btnToggleTable');
    const wrap = $('#resTableWrap');
    const open = btn.dataset.open === '1';
    btn.dataset.open = open ? '0' : '1';
    btn.textContent = open ? '展开明细' : '收起明细';
    wrap.style.display = open ? 'none' : 'block';
  });
})();
</script>
<?php render_footer(); ?>
