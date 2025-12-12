<?php
/**
 * PRS Trends Action - 价格趋势分析页面
 * 文件路径: app/prs/actions/trends.php
 * 说明: 价格趋势分析和图表展示
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
?>
<div class="stack" style="gap:16px">
  <div class="row">
    <div class="col">
      <div class="kv"><label>产品</label>
        <input id="inpProd" list="dlProd" placeholder="输入ES名/中文检索"><datalist id="dlProd"></datalist>
        <div class="muted" id="prodInfo"></div>
      </div>
    </div>
    <div class="col">
      <div class="kv"><label>门店</label>
        <input id="inpStore" list="dlStore" placeholder="输入门店名检索"><datalist id="dlStore"></datalist>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="kv"><label>时间</label>
        <input id="from" type="date"> 至 <input id="to" type="date" style="max-width:200px">
      </div>
    </div>
    <div class="col">
      <div class="kv"><label>聚合</label>
        <select id="agg">
          <option value="day">按日</option>
          <option value="week">按周</option>
          <option value="month">按月</option>
        </select>
        <div style="flex:1"></div>
        <button class="btn" id="btnQuery" style="max-width:160px">查询</button>
      </div>
    </div>
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

  let selectedProd = null, selectedStore = null;

  // --- 联想（保持） ---
  const debounce = (fn, ms=250) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms);}};

  $('#inpProd').addEventListener('input', debounce(async e => {
    const q = e.target.value.trim();
    const list = $('#dlProd'); list.innerHTML = '';
    selectedProd = null; $('#prodInfo').textContent = '';
    if (!q) return;
    const res = await fetch(`${apiBase}products_search&q=${encodeURIComponent(q)}`);
    const data = await res.json().catch(()=>({}));
    (data.items||[]).forEach(it => {
      const opt = document.createElement('option');
      opt.value = `${it.name_es}`;
      opt.label = `#${it.id} · ${it.category}${it.name_zh ? ' · '+it.name_zh : ''}`;
      opt.dataset.pid = it.id;
      list.appendChild(opt);
    });
  }, 250));

  $('#inpProd').addEventListener('change', e=>{
    const v = e.target.value.trim();
    const hit = Array.from($('#dlProd').options).find(o => o.value === v);
    selectedProd = hit ? {id: parseInt(hit.dataset.pid,10), name: v} : null;
    $('#prodInfo').textContent = hit ? hit.label : '';
  });

  $('#inpStore').addEventListener('input', debounce(async e=>{
    const q = e.target.value.trim();
    const list = $('#dlStore'); list.innerHTML = '';
    selectedStore = null;
    if (!q) return;
    const res = await fetch(`${apiBase}stores_search&q=${encodeURIComponent(q)}`);
    const data = await res.json().catch(()=>({}));
    (data.items||[]).forEach(it=>{
      const opt = document.createElement('option');
      opt.value = it.store_name;
      opt.label = `#${it.id}`;
      opt.dataset.sid = it.id;
      list.appendChild(opt);
    });
  }, 250));

  $('#inpStore').addEventListener('change', e=>{
    const v = e.target.value.trim();
    const hit = Array.from($('#dlStore').options).find(o => o.value === v);
    selectedStore = hit ? {id: parseInt(hit.dataset.sid,10), name: v} : null;
  });

  // --- 统一：点击查询 → 先请求后端解析名称 → 拿到ID再查 ---
  $('#btnQuery').addEventListener('click', async ()=>{
    const prodText  = $('#inpProd').value.trim();
    const storeText = $('#inpStore').value.trim();

    // 若前端未选中，则调用后端解析
    if (!selectedProd || !selectedStore) {
      const body = new URLSearchParams();
      body.set('product_name', prodText);
      body.set('store_name', storeText);

      const res = await fetch(`${apiBase}resolve`, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body.toString()
      });
      const data = await res.json().catch(()=>({}));

      if (!selectedProd) {
        if (data.product) { selectedProd = {id: data.product.id, name: data.product.name_es}; $('#prodInfo').textContent = `#${data.product.id} · ${data.product.category}${data.product.name_zh ? ' · '+data.product.name_zh : ''}`; }
        else if ((data.product_candidates||[]).length) {
          toast('产品不唯一，请从下拉选择', 'warn');
          return;
        }
      }
      if (!selectedStore) {
        if (data.store) { selectedStore = {id: data.store.id, name: data.store.store_name}; }
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
