<?php
/**
 * PRS Dashboard Action - 系统首页
 * 文件路径: app/prs/actions/dashboard.php
 * 说明: 系统首页，显示快速导航和系统概览
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
render_header('PRS · 控制台');

// 获取统计数据
try {
    require_once PRS_LIB_PATH . '/query_controller.php';
    $controller = new PRS_Query_Controller();

    // 获取门店列表
    $stores = $controller->list_stores();
    $storeCount = count($stores);
    $totalObs = array_sum(array_column($stores, 'total_observations'));

    // 获取产品数量
    $productData = $controller->list_products(1, 1, '');
    $productCount = $productData['total'] ?? 0;
} catch (Exception $e) {
    prs_log("Dashboard stats error: " . $e->getMessage(), 'ERROR');
    $storeCount = 0;
    $productCount = 0;
    $totalObs = 0;
}
?>

<div class="panel soft">
  <div class="stack">
    <div class="pill">控制台</div>
    <div class="row" style="align-items:flex-end">
      <div class="col">
        <h1 style="margin:0;font-size:26px;line-height:1.2">欢迎回来，PRS 控制台已焕新</h1>
        <p class="muted" style="margin:6px 0 0 0">现代化界面，桌面与移动端一致友好。核心功能保持简洁，随时可用。</p>
      </div>
      <div class="col" style="text-align:right;min-width:200px">
        <div class="chip">稳定运行 · <?= date('Y-m-d') ?></div>
      </div>
    </div>
  </div>
</div>

<div class="panel headered">
  <div class="section-header">
    <h3 class="section-title">系统概览</h3>
    <span class="chip">实时统计</span>
  </div>
  <div class="section-body">
    <div class="stat-grid">
      <div class="stat-card">
        <div class="muted">产品总数</div>
        <div class="stat-value" style="color:#4da3ff;"><?= $productCount ?></div>
      </div>
      <div class="stat-card">
        <div class="muted">门店总数</div>
        <div class="stat-value" style="color:#34c759;"><?= $storeCount ?></div>
      </div>
      <div class="stat-card">
        <div class="muted">价格观测总数</div>
        <div class="stat-value" style="color:#ff9f0a;"><?= number_format($totalObs) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="panel headered">
  <div class="section-header">
    <h3 class="section-title">快速导航</h3>
    <span class="muted">常用入口一屏展示</span>
  </div>
  <div class="section-body">
    <div class="nav-grid">
      <a class="nav-item" href="/prs/index.php?action=ingest">
        <span class="icon">📥</span>
        <div class="stack" style="gap:4px">
          <span>批量导入</span>
          <span class="desc">遵循模板粘贴数据，可先试运行校验</span>
        </div>
      </a>
      <a class="nav-item" href="/prs/index.php?action=products">
        <span class="icon">🛒</span>
        <div class="stack" style="gap:4px">
          <span>产品列表</span>
          <span class="desc">检索中西文名，查看基础属性</span>
        </div>
      </a>
      <a class="nav-item" href="/prs/index.php?action=stores">
        <span class="icon">🏪</span>
        <div class="stack" style="gap:4px">
          <span>门店列表</span>
          <span class="desc">关注覆盖度与观测频次</span>
        </div>
      </a>
      <a class="nav-item" href="/prs/index.php?action=trends">
        <span class="icon">📊</span>
        <div class="stack" style="gap:4px">
          <span>价格趋势</span>
          <span class="desc">按日/周/月聚合，查看在市与缺货段</span>
        </div>
      </a>
    </div>
  </div>
</div>

<div class="panel headered">
  <div class="section-header">
    <h3 class="section-title">功能说明</h3>
    <span class="chip">必读</span>
  </div>
  <div class="section-body">
    <div class="stack" style="gap:12px">
      <div>
        <strong>📥 批量导入</strong><br>
        <span class="muted">支持文本格式批量导入价格数据，包含试运行校验与 AI 提示词辅助。</span>
      </div>
      <div>
        <strong>🛒 产品列表</strong><br>
        <span class="muted">浏览和搜索所有产品，支持中英文名称检索，查看产品分类和观测历史。</span>
      </div>
      <div>
        <strong>🏪 门店列表</strong><br>
        <span class="muted">查看所有门店及其数据统计，包括观测天数和记录总数。</span>
      </div>
      <div>
        <strong>📊 价格趋势</strong><br>
        <span class="muted">分析特定产品在特定门店的价格走势，支持日/周/月聚合，可视化展示季节性和缺货段。</span>
      </div>
    </div>
  </div>
</div>

<?php if ($storeCount > 0): ?>
<div class="panel headered">
  <div class="section-header">
    <h3 class="section-title">门店概览</h3>
    <span class="chip">近期开通</span>
  </div>
  <div class="section-body">
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>门店名称</th>
            <th>观测天数</th>
            <th>总观测数</th>
            <th>创建时间</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($stores, 0, 5) as $store): ?>
          <tr>
            <td><?= htmlspecialchars($store['store_name']) ?></td>
            <td><?= $store['days_observed'] ?></td>
            <td><?= number_format($store['total_observations']) ?></td>
            <td><?= substr($store['created_at'], 0, 10) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($storeCount > 5): ?>
    <div style="text-align:right;margin-top:10px">
      <a href="/prs/index.php?action=stores" class="muted">查看全部门店 →</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php render_footer(); ?>
