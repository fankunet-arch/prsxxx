<?php
declare(strict_types=1);

/**
 * PRS 导入页（文本粘贴 → 试运行 / 正式入库）
 * - 自动用 text/plain POST 到 API：/prs/api/prs_api_gateway.php?res=ingest&act=bulk&dry_run=...
 * - 顶部有轻量提示条（自动淡出）
 * - 右侧展示解析报告、告警与明细
 */

$header = __DIR__ . '/../../../app/prs/views/layouts/header.php';
if (!is_file($header)) {
    http_response_code(500);
    echo "Missing header layout: {$header}";
    exit;
}
require_once $header;
render_header('PRS · 批量导入');

$apiBase = '/prs/api/prs_api_gateway.php?res=ingest&act=bulk';
?>
<div class="row">
  <div class="col">
    <div class="stack">
      <div class="kv"><label>AI模型（可选）</label><input id="aiModel" placeholder="例如: gpt-ocr / gemini-vision / manual"></div>
      <div class="kv"><label>快速提示</label>
        <div class="muted">首行写“日期 + 店名”，后面按块写明细。分隔符不限，自动识别（#@、||、## 等）。</div>
      </div>
      <textarea id="payload" class="code" placeholder="例：
2025-11-11#@Mercado Central#@

Manzana Golden#@ud:0.38#@udp:190g#@pkg:2#@

pera cinferebcia#@ud:0.43#@udp:166g#@pkg:2.6#@"></textarea>
      <div class="toolbar">
        <button class="btn secondary" id="btnSample">填入示例</button>
        <div style="flex:1"></div>
        <button class="btn" id="btnDry">试运行校验</button>
        <button class="btn ok" id="btnCommit">正式入库</button>
      </div>
    </div>
  </div>

  <div class="col">
    <div class="stack">
      <div class="kv"><label>结果</label><div id="resSummary" class="muted">待运行</div></div>
      <div id="resWarnings" class="code" style="display:none"></div>
      <div id="resTableWrap" style="overflow:auto;max-height:420px;display:none">
        <table class="table" id="resTable">
          <thead>
            <tr>
              <th>#</th><th>ES名</th><th>ZH名</th><th>类目</th><th>€/kg</th><th>€/ud</th><th>g/ud</th><th>状态</th><th>幂等跳过</th><th>设图</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
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

  // 用表单方式提交，避免 WAF 拦截 text/plain
  const callAPI = async (dryRun) => {
    const txt = document.querySelector('#payload').value.trim();
    const ai  = document.querySelector('#aiModel').value.trim();
    if (!txt) { toast('请输入要导入的文本', 'warn'); return; }

    const form = new URLSearchParams();
    form.set('payload', txt);
    form.set('dry_run', dryRun ? 1 : 0);
    if (ai) form.set('ai_model', ai);

    const url = <?= json_encode($apiBase) ?>; // /prs/api/prs_api_gateway.php?res=ingest&act=bulk
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
      return;
    }

    const sum = `门店：${data.store}｜日期：${data.date}｜分隔符：${data.delim} ｜通过 ${data.accepted}，拒绝 ${data.rejected}（${dryRun? '试运行':'已入库'}）`;
    document.querySelector('#resSummary').textContent = sum;
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
      document.querySelector('#resTableWrap').style.display = 'block';
    } else {
      document.querySelector('#resTableWrap').style.display = 'none';
    }
  };


  $('#btnDry').addEventListener('click', () => callAPI(true));
  $('#btnCommit').addEventListener('click', () => callAPI(false));
  $('#btnSample').addEventListener('click', fillSample);
})();
</script>
<?php render_footer(); ?>
