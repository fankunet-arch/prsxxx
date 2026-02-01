<?php
/**
 * PRS Trends Action - 价格趋势分析页面
 * 文件路径: app/prs/actions/trends.php
 * 说明: 价格趋势分析和图表展示
 *
 * 支持URL参数预选：
 *   - product_id: 产品ID
 *   - store_id: 门店ID
 *   - product_name: 产品名称（用于显示）
 *   - store_name: 门店名称（用于显示）
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
render_header('PRS · 价格趋势');

// API 基础路径 - 使用新的路由方式
$apiBase = '/prs/index.php?action=query_';
$imgBase = (function(){
    $c = cfg();
    return $c['prs_images_base_url'] ?? '/prs/assets/img/products/';
})();

// 获取URL预选参数
$preselect = [
    'product_id' => isset($_GET['product_id']) ? (int)$_GET['product_id'] : null,
    'store_id' => isset($_GET['store_id']) ? (int)$_GET['store_id'] : null,
    'product_name' => isset($_GET['product_name']) ? trim($_GET['product_name']) : '',
    'store_name' => isset($_GET['store_name']) ? trim($_GET['store_name']) : '',
];
?>
<div class="stack" style="gap:16px">
  <!-- 最近查询历史 -->
  <div id="recentQueries" class="card" style="border-radius:12px; display:none;">
    <div class="body" style="padding:12px 16px;">
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
        <span style="font-weight:500; font-size:14px;">最近查询</span>
        <span class="muted" style="font-size:12px;">点击快速选择</span>
        <div style="flex:1"></div>
        <button class="btn secondary" id="btnClearHistory" style="padding:4px 8px; font-size:12px;">清除</button>
      </div>
      <div id="recentList" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="kv"><label>产品类别 <span class="muted" style="font-size:12px;">(可选)</span></label>
        <select id="selCategory">
          <option value="">-- 全部类别 --</option>
        </select>
      </div>
    </div>
    <div class="col">
      <div class="kv"><label>产品 <span class="muted" style="font-size:12px; color:#ff9f0a;">*必选</span></label>
        <input id="inpProd" list="dlProd" placeholder="输入名称搜索 (支持中文/西班牙文)"><datalist id="dlProd"></datalist>
        <div class="muted" id="prodInfo"></div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="kv"><label>门店 <span class="muted" style="font-size:12px; color:#ff9f0a;">*必选</span></label>
        <input id="inpStore" list="dlStore" placeholder="输入门店名称搜索"><datalist id="dlStore"></datalist>
      </div>
    </div>
    <div class="col">
      <!-- 快速选择门店 -->
      <div class="kv"><label>快速选门店</label>
        <select id="quickStore">
          <option value="">-- 从列表选择 --</option>
          <option value="0">-- 所有门店 --</option>
        </select>
      </div>
    </div>
  </div>

  <!-- 时间范围 - 水平布局 -->
  <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
    <label class="muted" style="font-size:13px; min-width:70px;">时间范围</label>
    <input id="from" type="date" title="起始日期" style="width:150px; flex:none;">
    <span class="muted">至</span>
    <input id="to" type="date" title="截止日期" style="width:150px; flex:none;">
    <select id="quickRange" style="width:110px; flex:none;" title="快速选择">
      <option value="">快速选择</option>
      <option value="7">近7天</option>
      <option value="30">近30天</option>
      <option value="90">近3个月</option>
      <option value="180">近6个月</option>
      <option value="365">近1年</option>
      <option value="all">全部</option>
    </select>
    <div style="flex:1; min-width:20px;"></div>
    <label class="muted" style="font-size:13px;">聚合</label>
    <select id="agg" style="width:90px; flex:none;">
      <option value="day">按日</option>
      <option value="week">按周</option>
      <option value="month">按月</option>
    </select>
    <button class="btn" id="btnQuery" style="width:120px; flex:none;">查询趋势</button>
  </div>

  <div class="card" style="border-radius:16px">
    <div class="body">
      <canvas id="chart" width="960" height="320" style="width:100%;height:auto;max-height:320px"></canvas>
      <div class="muted" id="hint" style="margin-top:6px;font-size:12px">浅绿：当月在市；淡红：缺货段；折线：€/kg</div>
    </div>
  </div>

  <div id="tableWrap" class="table-wrapper" style="max-height:420px;display:none">
    <table class="table" id="tbl">
      <thead><tr id="thead"></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<script>
(() => {
  const $ = s => document.querySelector(s);
  const apiBase = <?= json_encode($apiBase) ?>;

  // URL预选参数
  const preselect = <?= json_encode($preselect) ?>;

  let selectedProd = null, selectedStore = null;
  let allProducts = []; // 缓存所有产品
  let allStores = []; // 缓存所有门店

  // --- 最近查询历史管理 ---
  const HISTORY_KEY = 'prs_trends_history';
  const MAX_HISTORY = 8;

  function getHistory() {
    try {
      return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
    } catch { return []; }
  }

  function saveToHistory(prod, store) {
    if (!prod || !store) return;
    let history = getHistory();
    // 去重：移除相同组合
    history = history.filter(h => !(h.product_id === prod.id && h.store_id === store.id));
    // 添加到开头
    history.unshift({
      product_id: prod.id,
      product_name: prod.name,
      store_id: store.id,
      store_name: store.name,
      timestamp: Date.now()
    });
    // 限制数量
    history = history.slice(0, MAX_HISTORY);
    localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
    renderHistory();
  }

  function renderHistory() {
    const history = getHistory();
    const container = $('#recentQueries');
    const list = $('#recentList');

    if (history.length === 0) {
      container.style.display = 'none';
      return;
    }

    container.style.display = 'block';
    list.innerHTML = '';

    history.forEach(h => {
      const btn = document.createElement('button');
      btn.className = 'btn secondary';
      btn.style.cssText = 'padding:6px 12px; font-size:13px; white-space:nowrap;';
      btn.innerHTML = `<span style="color:#3aa6ff;">${escapeHtml(h.product_name)}</span> @ <span style="color:#30d158;">${escapeHtml(h.store_name)}</span>`;
      btn.title = `点击查询: ${h.product_name} 在 ${h.store_name} 的价格趋势`;
      btn.onclick = () => {
        selectedProd = { id: h.product_id, name: h.product_name };
        selectedStore = { id: h.store_id, name: h.store_name };
        $('#inpProd').value = h.product_name;
        $('#inpStore').value = h.store_name;
        $('#prodInfo').textContent = `#${h.product_id}`;
        // 自动触发查询
        loadStores(h.product_id); // Ensure stores filtered
        loadProducts(h.store_id); // Ensure products filtered
        $('#btnQuery').click();
      };
      list.appendChild(btn);
    });
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // 清除历史按钮
  $('#btnClearHistory').addEventListener('click', () => {
    localStorage.removeItem(HISTORY_KEY);
    renderHistory();
    toast('已清除查询历史', 'ok');
  });

  // --- 快速选择时间范围 ---
  $('#quickRange').addEventListener('change', e => {
    const val = e.target.value;
    if (!val) return;

    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    $('#to').value = todayStr;

    if (val === 'all') {
      $('#from').value = '';
    } else {
      const days = parseInt(val, 10);
      const fromDate = new Date();
      fromDate.setDate(fromDate.getDate() - days);
      $('#from').value = fromDate.toISOString().split('T')[0];
    }
    e.target.value = ''; // 重置选择
  });

  // --- 快速选择门店下拉 ---
  $('#quickStore').addEventListener('change', e => {
    const val = e.target.value;
    if (val === '') return;

    if (val === '0') {
        selectedStore = { id: 0, name: '所有门店' };
        $('#inpStore').value = '所有门店';
        loadProducts(0);
    } else {
        const store = allStores.find(s => s.id == val);
        if (store) {
          selectedStore = { id: store.id, name: store.store_name };
          $('#inpStore').value = store.store_name;
          loadProducts(store.id);
        }
    }
    e.target.value = ''; // 重置
  });

  // --- 数据加载 ---
  async function loadProducts(storeId = 0) {
    try {
      const qs = storeId > 0 ? `&store_id=${storeId}` : '';
      const res = await fetch(`${apiBase}list_products&page=1&size=9999${qs}`);
      const data = await res.json().catch(()=>({}));
      if (data.ok && data.items) {
        allProducts = data.items;
        updateProductDatalist(allProducts);
      }
    } catch (e) { console.error('加载产品失败:', e); }
  }

  async function loadStores(productId = 0) {
    try {
      const qs = productId > 0 ? `&product_id=${productId}` : '';
      const res = await fetch(`${apiBase}list_stores${qs}`);
      const data = await res.json().catch(()=>({}));
      const storeRows = data.rows || (data.data && data.data.rows) || [];
      if (data.ok) {
        allStores = storeRows;
        updateStoreDatalist(allStores);
        updateQuickStoreSelect(allStores);
      }
    } catch (e) { console.error('加载门店失败:', e); }
  }

  function updateQuickStoreSelect(stores) {
    const sel = $('#quickStore');
    sel.innerHTML = '<option value="">-- 从列表选择 --</option><option value="0">-- 所有门店 --</option>';
    stores.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.store_name;
      sel.appendChild(opt);
    });
  }

  // --- 初始化：加载类别和门店 ---
  (async function init() {
    // 设置日期选择的最大值为今天
    const today = new Date().toISOString().split('T')[0];
    $('#from').max = today;
    $('#to').max = today;

    // 设置默认日期：过去30天到今天
    const from30DaysAgo = new Date();
    from30DaysAgo.setDate(from30DaysAgo.getDate() - 30);
    $('#from').value = from30DaysAgo.toISOString().split('T')[0];
    $('#to').value = today;

    // 加载类别
    try {
      const res = await fetch(`${apiBase}categories`);
      const data = await res.json().catch(()=>({}));
      if (data.ok && data.data && data.data.categories) {
        const sel = $('#selCategory');
        data.data.categories.forEach(cat => {
          const opt = document.createElement('option');
          opt.value = cat;
          opt.textContent = cat;
          sel.appendChild(opt);
        });
      }
    } catch (e) { console.error('加载类别失败:', e); }

    // 加载初始数据
    await Promise.all([loadStores(0), loadProducts(0)]);

    // 渲染历史记录
    renderHistory();

    // 处理URL预选参数
    if (preselect.product_id && preselect.product_name) {
      selectedProd = { id: preselect.product_id, name: preselect.product_name };
      $('#inpProd').value = preselect.product_name;
      $('#prodInfo').textContent = `#${preselect.product_id}`;
      // Filter stores based on product
      loadStores(preselect.product_id);
    }
    if (preselect.store_id !== null) {
      if (preselect.store_id === 0) {
          selectedStore = { id: 0, name: '所有门店' };
          $('#inpStore').value = '所有门店';
      } else if (preselect.store_name) {
          selectedStore = { id: preselect.store_id, name: preselect.store_name };
          $('#inpStore').value = preselect.store_name;
      }
      // Filter products based on store
      loadProducts(preselect.store_id);
    }

    // 如果都预选了，自动触发查询
    if (selectedProd && selectedStore) {
      setTimeout(() => $('#btnQuery').click(), 300);
    }
  })();

  // --- 时间选择验证 ---
  $('#from').addEventListener('change', e => {
    const fromDate = e.target.value;
    const toDate = $('#to').value;
    const today = new Date().toISOString().split('T')[0];
    if (fromDate > today) { toast('起始日期不能晚于今天', 'warn'); e.target.value = today; return; }
    if (toDate && fromDate > toDate) { toast('起始日期不能晚于截止日期', 'warn'); e.target.value = toDate; }
    $('#to').min = fromDate;
  });

  $('#to').addEventListener('change', e => {
    const fromDate = $('#from').value;
    const toDate = e.target.value;
    const today = new Date().toISOString().split('T')[0];
    if (toDate > today) { toast('截止日期不能晚于今天', 'warn'); e.target.value = today; return; }
    if (fromDate && toDate < fromDate) { toast('截止日期不能早于起始日期', 'warn'); e.target.value = fromDate; }
  });

  // --- 类别选择改变时，过滤显示的产品（可选功能） ---
  $('#selCategory').addEventListener('change', async e => {
    const category = e.target.value;
    selectedProd = null;
    $('#prodInfo').textContent = '';
    $('#inpProd').value = '';

    if (!category) {
      updateProductDatalist(allProducts);
      return;
    }
    const filtered = allProducts.filter(p => p.category === category);
    updateProductDatalist(filtered);
  });

  // 更新产品 datalist
  function updateProductDatalist(products) {
    const list = $('#dlProd');
    list.innerHTML = '';
    products.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.name_es;
      opt.label = `#${it.id}${it.name_zh ? ' · '+it.name_zh : ''}`;
      opt.dataset.pid = it.id;
      opt.dataset.category = it.category;
      opt.dataset.namezh = it.name_zh || '';
      list.appendChild(opt);
    });
  }

  // --- 产品输入框：点击时显示所有，输入时过滤 ---
  $('#inpProd').addEventListener('focus', e => {
    // Trigger input event logic manually if needed? No, just rely on datalist.
    // But we might want to refresh datalist if category changed?
    // Handled by updateProductDatalist call in category change.
  });

  $('#inpProd').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    selectedProd = null;
    $('#prodInfo').textContent = '';

    if (q === '') {
        // Cleared -> Reload all stores
        loadStores(0);
    }

    // Filter local cache
    const category = $('#selCategory').value;
    let filtered = allProducts;
    if (q) {
        filtered = filtered.filter(p =>
            (p.name_es && p.name_es.toLowerCase().includes(q)) ||
            (p.name_zh && p.name_zh.toLowerCase().includes(q))
        );
    }
    if (category) {
      filtered = filtered.filter(p => p.category === category);
    }
    updateProductDatalist(filtered);
  });

  $('#inpProd').addEventListener('change', e => {
    const v = e.target.value.trim();
    const hit = Array.from($('#dlProd').options).find(o => o.value === v);
    if (hit) {
      selectedProd = {id: parseInt(hit.dataset.pid,10), name: v};
      $('#prodInfo').textContent = `${hit.label} · ${hit.dataset.category}`;
      // Product selected -> Filter stores
      loadStores(selectedProd.id);
    } else {
      selectedProd = null;
      $('#prodInfo').textContent = '';
      loadStores(0);
    }
  });

  // 更新门店 datalist
  function updateStoreDatalist(stores) {
    const list = $('#dlStore');
    list.innerHTML = '';

    // Allow "All Stores" via manual input suggestion
    const optAll = document.createElement('option');
    optAll.value = '所有门店';
    optAll.label = '所有门店';
    optAll.dataset.sid = 0;
    list.appendChild(optAll);

    stores.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.store_name;
      opt.label = `#${it.id}`;
      opt.dataset.sid = it.id;
      list.appendChild(opt);
    });
  }

  // --- 门店输入框 ---
  $('#inpStore').addEventListener('input', e => {
    const q = e.target.value.trim().toLowerCase();
    selectedStore = null;

    if (q === '') {
        // Cleared -> Reload all products
        loadProducts(0);
    }

    if (!q) { updateStoreDatalist(allStores); return; }
    const filtered = allStores.filter(s =>
      s.store_name.toLowerCase().includes(q)
    );
    updateStoreDatalist(filtered);
  });

  $('#inpStore').addEventListener('change', e => {
    const v = e.target.value.trim();

    if (v === '所有门店') {
        selectedStore = {id: 0, name: '所有门店'};
        loadProducts(0);
        return;
    }

    const hit = Array.from($('#dlStore').options).find(o => o.value === v);
    if (hit) {
        const sid = parseInt(hit.dataset.sid, 10);
        selectedStore = {id: sid, name: v};
        loadProducts(sid);
    } else {
        selectedStore = null;
        loadProducts(0);
    }
  });

  // --- 统一：点击查询 → 先请求后端解析名称 → 拿到ID再查 ---
  $('#btnQuery').addEventListener('click', async ()=>{
    const prodText  = $('#inpProd').value.trim();
    const storeText = $('#inpStore').value.trim();

    if (storeText === '所有门店') {
        selectedStore = {id: 0, name: '所有门店'};
    }

    // 若前端未选中，则调用后端解析
    if (!selectedProd || !selectedStore) {
      const body = new URLSearchParams();
      body.set('product_name', prodText);
      if (storeText !== '所有门店') {
          body.set('store_name', storeText);
      }

      const res = await fetch(`${apiBase}resolve`, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body.toString()
      });
      const data = await res.json().catch(()=>({}));

      if (!selectedProd) {
        if (data.product) {
            selectedProd = {id: data.product.id, name: data.product.name_es};
            $('#prodInfo').textContent = `#${data.product.id} · ${data.product.category}${data.product.name_zh ? ' · '+data.product.name_zh : ''}`;
            loadStores(selectedProd.id);
        }
        else if ((data.product_candidates||[]).length) {
          toast('产品不唯一，请从下拉选择', 'warn');
          return;
        }
      }
      if (!selectedStore && storeText !== '所有门店') {
        if (data.store) {
            selectedStore = {id: data.store.id, name: data.store.store_name};
            loadProducts(selectedStore.id);
        }
        else if ((data.store_candidates||[]).length) {
          toast('门店不唯一，请从下拉选择', 'warn');
          return;
        }
      }
    }

    if (!selectedProd || !selectedStore) {
      toast('请先选择"产品"和"门店"', 'warn');
      return;
    }

    const agg  = $('#agg').value;
    const from = $('#from').value || '';
    const to   = $('#to').value   || '';

    const qs = (o)=>Object.entries(o).filter(([,v])=>v!==''&&v!=null).map(([k,v])=>`${k}=${encodeURIComponent(v)}`).join('&');
    const base = `/prs/index.php?action=query_`;
    const uTs  = `${base}timeseries&product_id=${selectedProd.id}&store_id=${selectedStore.id}&${qs({from,to,agg})}`;
    const uSn  = `${base}season&product_id=${selectedProd.id}&store_id=${selectedStore.id}&${qs({from_ym: from?from.slice(0,7):'', to_ym: to?to.slice(0,7):''})}`;
    const uSo  = `${base}stockouts&product_id=${selectedProd.id}&store_id=${selectedStore.id}&${qs({from,to})}`;

    const [tsRes, snRes, soRes] = await Promise.all([fetch(uTs), fetch(uSn), fetch(uSo)]);
    const [ts, sn, so] = await Promise.all([tsRes.json(), snRes.json(), soRes.json()]);

    if (!ts.ok) { toast('查询失败：' + (ts.message || 'timeseries'), 'err'); return; }
    drawChart(ts.rows || [], sn.rows || [], so.rows || [], agg);
    fillTable(ts.rows || [], agg);

    // 保存到查询历史
    saveToHistory(selectedProd, selectedStore);

    toast('查询完成', 'ok');
  });

  // --- 表格 ---
  function fillTable(rows, agg){
    const thead = document.querySelector('#thead'); const tb = document.querySelector('#tbl tbody'); tb.innerHTML = '';
    if (agg==='day'){ thead.innerHTML = `<th>日期</th><th>€/kg</th><th>样本数</th>`; }
    else if (agg==='week'){ thead.innerHTML = `<th>ISO周</th><th>周起始</th><th>€/kg</th><th>样本数</th>`; }
    else { thead.innerHTML = `<th>月份</th><th>月起始日</th><th>€/kg</th><th>样本数</th>`; }

    rows.forEach(r=>{
      const tr = document.createElement('tr');
      if (agg==='day'){
        tr.innerHTML = `<td>${r.date_local}</td><td>${r.price_per_kg ?? ''}</td><td>${r.n ?? ''}</td>`;
      } else if (agg==='week'){
        tr.innerHTML = `<td>${r.iso_year}-W${r.iso_week}</td><td>${r.anchor_date}</td><td>${r.price_per_kg ?? ''}</td><td>${r.n ?? ''}</td>`;
      } else {
        tr.innerHTML = `<td>${r.ym}</td><td>${r.anchor_date}</td><td>${r.price_per_kg ?? ''}</td><td>${r.n ?? ''}</td>`;
      }
      tb.appendChild(tr);
    });
    document.querySelector('#tableWrap').style.display = rows.length ? 'block' : 'none';
  }

  // --- 画图 ---
  function drawChart(rows, seasonRows, stockouts, agg){
    const cvs = document.querySelector('#chart'), ctx = cvs.getContext('2d');
    const DPR = window.devicePixelRatio || 1;
    const Wcss = cvs.clientWidth, Hcss = 320;
    cvs.width = Wcss * DPR; cvs.height = Hcss * DPR; ctx.scale(DPR, DPR);
    ctx.clearRect(0,0,Wcss,Hcss);

    const parseD = s => new Date(s.replace(/-/g,'/')).getTime();
    let points = [];
    if (agg==='day'){
      points = rows.map(r=>({x: parseD(r.date_local), y: +r.price_per_kg}));
    } else if (agg==='week'){
      points = rows.map(r=>({x: parseD(r.anchor_date), y: +r.price_per_kg}));
    } else {
      points = rows.map(r=>({x: parseD(r.anchor_date), y: +r.price_per_kg}));
    }
    if (!points.length){ ctx.fillStyle='#888'; ctx.fillText('无数据', 12, 24); return; }

    const xs = points.map(p=>p.x), ys = points.map(p=>p.y);
    const xmin = Math.min(...xs), xmax = Math.max(...xs);
    const ymin = Math.min(...ys), ymax = Math.max(...ys);
    const padL=48, padR=12, padT=16, padB=28;
    const X = x => padL + (x - xmin) / Math.max(1,(xmax-xmin)) * (Wcss - padL - padR);
    const Y = y => (Hcss - padB) - (y - ymin) / Math.max(0.0001,(ymax-ymin)) * (Hcss - padT - padB);

    // 网格
    ctx.strokeStyle='rgba(127,127,127,.25)'; ctx.lineWidth=1;
    for(let i=0;i<=4;i++){ const y= padT + i*(Hcss-padT-padB)/4; ctx.beginPath(); ctx.moveTo(padL,y); ctx.lineTo(Wcss-padR,y); ctx.stroke(); }

    // 在市月（浅绿）
    seasonRows.forEach(r=>{
      if (r.is_in_market_month!=1) return;
      const start = parseD(r.ym + '-01');
      const end   = new Date(new Date(start).getFullYear(), new Date(start).getMonth()+1, 0).getTime();
      const x1 = X(Math.max(xmin, start));
      const x2 = X(Math.min(xmax, end));
      if (x2>x1){
        ctx.fillStyle='rgba(76,217,100,.10)';
        ctx.fillRect(x1, padT, x2-x1, Hcss-padT-padB);
      }
    });

    // 缺货段（淡红）
    stockouts.forEach(s=>{
      const x1 = X(Math.max(xmin, parseD(s.gap_start)));
      const x2 = X(Math.min(xmax, parseD(s.gap_end)));
      if (x2>x1){
        ctx.fillStyle='rgba(255,69,58,.10)';
        ctx.fillRect(x1, padT, x2-x1, Hcss-padT-padB);
      }
    });

    // 折线
    ctx.beginPath();
    points.forEach((p,i)=>{ const x=X(p.x), y=Y(p.y); if(i===0) ctx.moveTo(x,y); else ctx.lineTo(x,y); });
    ctx.lineWidth = 2;
    ctx.strokeStyle = '#3aa6ff';
    ctx.stroke();

    // y轴标签
    ctx.fillStyle='#9aa0a6'; ctx.font='12px system-ui';
    ctx.fillText(ymin.toFixed(2), 6, Y(ymin));
    ctx.fillText(ymax.toFixed(2), 6, Y(ymax));
  }
})();
</script>
<?php render_footer(); ?>
