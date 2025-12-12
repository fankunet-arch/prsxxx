<?php
/**
 * PRS Stores Action - 门店列表页面
 * 文件路径: app/prs/actions/stores.php
 * 说明: 门店列表浏览器
 */

// 防止直接访问
if (!defined('PRS_ENTRY')) {
    die('Access denied');
}



// 加载 header 布局
$header = PRS_VIEW_PATH . '/layouts/header.php';
if (!is_file($header)) {
    http_response_code(500);
    echo "Missing header";
    exit;
}
require_once $header;
render_header('PRS · 门店列表');

// API 基础路径 - 使用新的路由方式
$apiBase = '/prs/index.php?action=query_list_stores';
?>
<div class="stack" style="gap:16px">
  <div class="toolbar">
    <div class="muted" id="summary"></div>
  </div>

  <div id="tableWrap" class="table-wrapper" style="max-height:600px">
    <table class="table" id="tbl">
      <thead>
        <tr>
          <th>ID</th>
          <th>门店名称</th>
          <th>观测天数</th>
          <th>总观测数</th>
          <th>创建日期</th>
        </tr>
      </thead>
      <tbody>
        </tbody>
    </table>
  </div>
</div>

<script>
(() => {
  const $ = s => document.querySelector(s);
  const apiBase = <?= json_encode($apiBase) ?>;

  async function fetchStores() {
    try {
      const res = await fetch(apiBase);
      const data = await res.json();

      if (!data.ok) {
        toast('查询失败: ' + (data.message || '未知错误'), 'err');
        return [];
      }
      return data.rows || [];

    } catch (e) {
      toast('请求失败: ' + e.message, 'err');
      return [];
    }
  }

  function fillTable(rows) {
    const tb = $('#tbl tbody');
    tb.innerHTML = '';

    if (rows.length === 0) {
      tb.innerHTML = '<tr><td colspan="5" style="text-align:center;">没有找到门店数据</td></tr>';
      $('#summary').textContent = '总计 0 家门店';
      return;
    }

    rows.forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${s.id}</td>
        <td>${s.store_name}</td>
        <td>${s.days_observed}</td>
        <td>${s.total_observations}</td>
        <td>${s.created_at ? s.created_at.substring(0, 10) : '—'}</td>
      `;
      tb.appendChild(tr);
    });

    $('#summary').textContent = `总计 ${rows.length} 家门店`;
    toast('查询完成', 'ok', 1200);
  }

  // 首次加载
  (async function() {
    const rows = await fetchStores();
    fillTable(rows);
  })();

})();
</script>
<?php render_footer(); ?>
