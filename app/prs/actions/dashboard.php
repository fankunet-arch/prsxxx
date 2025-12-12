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

<div class="stack" style="gap: 18px; max-width: 1200px; margin: 0 auto;">
  <div class="panel">
    <div class="title">欢迎使用 PRS 系统<span class="pill">跨端无缝 · 轻盈导航</span></div>
    <div class="muted">价格记录系统 (Price Recording System) - 多门店商品价格管理与分析平台。</div>
    <div class="pill-row" style="margin-top:10px">
      <span class="pill">实时数据概览</span>
      <span class="pill">移动端友好</span>
      <span class="pill">快速到达常用页面</span>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <h4>产品总数</h4>
      <div class="big" style="color:#6ab7ff;"><?= $productCount ?></div>
      <div class="muted">SKU 规模随时掌握</div>
    </div>
    <div class="stat-card">
      <h4>门店总数</h4>
      <div class="big" style="color:#3ae49f;"><?= $storeCount ?></div>
      <div class="muted">覆盖的线下门店</div>
    </div>
    <div class="stat-card">
      <h4>价格观测总数</h4>
      <div class="big" style="color:#ffb94a;"><?= number_format($totalObs) ?></div>
      <div class="muted">累计入库的记录量</div>
    </div>
  </div>

  <div class="panel">
    <div class="title">快速导航</div>
    <div class="row" style="gap:10px">
      <a href="/prs/index.php?action=ingest" class="btn" style="flex:1;text-align:center;text-decoration:none;padding:14px;">📥 批量导入</a>
      <a href="/prs/index.php?action=products" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:14px;">🛒 产品列表</a>
      <a href="/prs/index.php?action=stores" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:14px;">🏪 门店列表</a>
      <a href="/prs/index.php?action=trends" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:14px;">📊 价格趋势</a>
    </div>
  </div>

  <div class="panel">
    <div class="title">功能说明</div>
    <div class="section">
      <div class="ghost-surface">
        <strong>📥 批量导入</strong><br><span class="muted">支持文本格式导入，配合运行反馈，移动端也能流畅查看结果。</span>
      </div>
      <div class="ghost-surface">
        <strong>🛒 产品列表</strong><br><span class="muted">浏览和搜索所有产品，支持中英文名称检索，查看产品分类和观测历史。</span>
      </div>
      <div class="ghost-surface">
        <strong>🏪 门店列表</strong><br><span class="muted">查看所有门店及其数据统计，包括观测天数和记录总数。</span>
      </div>
      <div class="ghost-surface">
        <strong>📊 价格趋势</strong><br><span class="muted">分析产品在特定门店的走势，日/周/月聚合随时切换。</span>
      </div>
    </div>
  </div>

  <?php if ($storeCount > 0): ?>
  <div class="panel">
    <div class="title">门店概览</div>
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
    <div style="text-align:center; margin-top: 12px;">
      <a href="/prs/index.php?action=stores" class="muted">查看全部门店 →</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
