<?php
/**
 * PRS Products Action - 产品列表页面
 * 文件路径: app/prs/actions/products.php
 * 说明: 产品列表浏览器
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
render_header('PRS · 产品列表');

// API 基础路径 - 使用新的路由方式
$apiBase = '/prs/index.php?action=query_list_products';
?>
<div class="stack" style="gap:16px">
  <div class="toolbar">
    <input id="inpSearch" type="text" placeholder="输入ES名/中文名检索" style="max-width:320px; flex:none;">
    <button class="btn" id="btnSearch" style="max-width:120px; flex:none;">搜索</button>
    <div style="flex:1"></div>
    <div class="muted" id="summary"></div>
  </div>

  <div id="tableWrap" class="table-wrapper" style="max-height:600px">
    <table class="table" id="tbl">
      <thead>
        <tr>
          <th>ID</th>
          <th>ES名称 (SKU)</th>
          <th>基础名称</th>
          <th>中文名称</th>
          <th>类目</th>
          <th>最近观测日</th>
          <th>创建日期</th>
        </tr>
      </thead>
      <tbody>
        </tbody>
    </table>
  </div>

  <div class="toolbar" id="pagination" style="justify-content:flex-end; display:none;">
    <button class="btn secondary" id="btnPrev">上一页</button>
    <div class="muted" id="pageInfo" style="margin: 0 10px;"></div>
    <button class="btn secondary" id="btnNext">下一页</button>
  </div>
</div>

<script>
(() => {
  const $ = s => document.querySelector(s);
  const apiBase = <?= json_encode($apiBase) ?>;
  const PAGE_SIZE = 20;
  let currentPage = 1;

  async function fetchData(page, q = '') {
    const url = `${apiBase}&page=${page}&size=${PAGE_SIZE}&q=${encodeURIComponent(q)}`;

    try {
      const res = await fetch(url);
      const data = await res.json();

      if (!data.ok) {
        toast('查询失败: ' + (data.message || '未知错误'), 'err');
        return { items: [], total: 0 };
      }
      return data;

    } catch (e) {
      toast('请求失败: ' + e.message, 'err');
      return { items: [], total: 0 };
    }
  }

  function fillTable(data) {
    const tb = $('#tbl tbody');
    tb.innerHTML = '';

    if (data.items.length === 0) {
      tb.innerHTML = '<tr><td colspan="7" style="text-align:center;">没有找到产品数据</td></tr>';
      $('#summary').textContent = '总计 0 条记录';
      $('#pagination').style.display = 'none';
      return;
    }

    data.items.forEach(p => {
      const tr = document.createElement('tr');
      const baseName = p.base_name_es || '—';
      tr.innerHTML = `
        <td>${p.id}</td>
        <td>${p.name_es || '—'}</td>
        <td>${baseName}</td>
        <td>${p.name_zh || '—'}</td>
        <td>${p.category || '—'}</td>
        <td>${p.last_observed_date || '未观测'}</td>
        <td>${p.created_at ? p.created_at.substring(0, 10) : '—'}</td>
      `;
      tb.appendChild(tr);
    });

    // 更新总结信息
    const totalPages = Math.ceil(data.total / PAGE_SIZE);
    $('#summary').textContent = `总计 ${data.total} 条记录，当前第 ${data.page} 页 / 共 ${totalPages} 页`;

    // 更新分页控件
    currentPage = data.page;
    $('#pageInfo').textContent = `${currentPage} / ${totalPages}`;
    $('#btnPrev').disabled = currentPage <= 1;
    $('#btnNext').disabled = currentPage >= totalPages;
    $('#pagination').style.display = totalPages > 1 ? 'flex' : 'none';
  }

  async function queryProducts() {
    const q = $('#inpSearch').value.trim();
    const data = await fetchData(currentPage, q);
    fillTable(data);
    toast('查询完成', 'ok', 1200);
  }

  // 首次加载
  (async function() {
    const data = await fetchData(1);
    fillTable(data);
  })();

  // 绑定事件
  $('#btnSearch').addEventListener('click', () => {
    currentPage = 1;
    queryProducts();
  });
  $('#inpSearch').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      currentPage = 1;
      queryProducts();
    }
  });

  $('#btnPrev').addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage--;
      queryProducts();
    }
  });

  $('#btnNext').addEventListener('click', () => {
    currentPage++;
    queryProducts();
  });
})();
</script>
<?php render_footer(); ?>
