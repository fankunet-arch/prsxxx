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
    <input id="inpSearch" type="text" placeholder="搜索门店名称..." style="max-width:300px; flex:none;">
    <button class="btn secondary" id="btnSearch" style="max-width:80px; flex:none;">搜索</button>
    <button class="btn secondary" id="btnClear" style="max-width:80px; flex:none;">清除</button>
    <div style="flex:1"></div>
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
          <th style="width:100px; text-align:center;">操作</th>
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

  let allStores = []; // 缓存所有门店数据

  // HTML转义函数
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

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
      tb.innerHTML = '<tr><td colspan="6" style="text-align:center;">没有找到门店数据</td></tr>';
      $('#summary').textContent = '总计 0 家门店';
      return;
    }

    rows.forEach(s => {
      const tr = document.createElement('tr');
      const storeName = s.store_name || '';
      const encodedName = encodeURIComponent(storeName);
      tr.innerHTML = `
        <td>${s.id}</td>
        <td>${escapeHtml(storeName)}</td>
        <td>${s.days_observed}</td>
        <td>${s.total_observations}</td>
        <td>${s.created_at ? s.created_at.substring(0, 10) : '—'}</td>
        <td style="text-align:center;">
          <a href="/prs/index.php?action=trends&store_id=${s.id}&store_name=${encodedName}"
             class="btn secondary"
             style="padding:4px 10px; font-size:12px; text-decoration:none;"
             title="查看 ${storeName} 的价格趋势">
            趋势
          </a>
        </td>
      `;
      tb.appendChild(tr);
    });

    const searchVal = $('#inpSearch').value.trim();
    if (searchVal) {
      $('#summary').textContent = `找到 ${rows.length} 家门店（共 ${allStores.length} 家）`;
    } else {
      $('#summary').textContent = `总计 ${rows.length} 家门店`;
    }
  }

  // 搜索过滤
  function filterStores(query) {
    if (!query) {
      fillTable(allStores);
      return;
    }
    const q = query.toLowerCase();
    const filtered = allStores.filter(s =>
      s.store_name && s.store_name.toLowerCase().includes(q)
    );
    fillTable(filtered);
  }

  // 搜索事件
  $('#btnSearch').addEventListener('click', () => {
    filterStores($('#inpSearch').value.trim());
  });

  $('#inpSearch').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      filterStores($('#inpSearch').value.trim());
    }
  });

  // 实时搜索（输入时即时过滤）
  $('#inpSearch').addEventListener('input', (e) => {
    filterStores(e.target.value.trim());
  });

  // 清除搜索
  $('#btnClear').addEventListener('click', () => {
    $('#inpSearch').value = '';
    fillTable(allStores);
  });

  // 首次加载
  (async function() {
    allStores = await fetchStores();
    fillTable(allStores);
    toast('加载完成', 'ok', 1200);
  })();

})();
</script>
<?php render_footer(); ?>
