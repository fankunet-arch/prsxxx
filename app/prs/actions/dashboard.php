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

<div class="stack" style="gap: 20px; max-width: 1180px; margin: 0 auto;">
  <div class="panel" style="background:linear-gradient(135deg,rgba(58,166,255,.14),rgba(76,217,100,.08));border:1px solid var(--border);">
    <div class="section-title">🚀 欢迎使用 PRS 系统</div>
    <div class="muted" style="margin-bottom:10px">价格记录系统（Price Recording System） · 跨端体验与现代化界面已启用</div>
    <div class="chip-row">
      <span class="chip">智能导入 & 试运行校验</span>
      <span class="chip">多门店/多产品趋势分析</span>
      <span class="chip">桌面与移动双端优化</span>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="value" style="color:#3aa6ff;"><?= $productCount ?></div>
      <div class="muted">产品总数</div>
    </div>
    <div class="stat-card">
      <div class="value" style="color:#30d158;"><?= $storeCount ?></div>
      <div class="muted">门店总数</div>
    </div>
    <div class="stat-card">
      <div class="value" style="color:#ff9f0a;"><?= number_format($totalObs) ?></div>
      <div class="muted">价格观测总数</div>
    </div>
  </div>

  <div class="panel">
    <div class="section-title">🧭 快速导航</div>
    <div class="action-grid" style="margin-top:10px">
      <a href="/prs/index.php?action=ingest" class="action-tile">
        <div style="font-size:22px">📥</div>
        <div style="font-weight:700">批量导入</div>
        <div class="muted">粘贴文本→试运行校验→一键入库</div>
      </a>
      <a href="/prs/index.php?action=products" class="action-tile">
        <div style="font-size:22px">🛒</div>
        <div style="font-weight:700">产品列表</div>
        <div class="muted">检索产品、查看类目与观测历史</div>
      </a>
      <a href="/prs/index.php?action=stores" class="action-tile">
        <div style="font-size:22px">🏪</div>
        <div style="font-weight:700">门店列表</div>
        <div class="muted">浏览门店画像与观测概览</div>
      </a>
      <a href="/prs/index.php?action=trends" class="action-tile">
        <div style="font-size:22px">📊</div>
        <div style="font-weight:700">价格趋势</div>
        <div class="muted">快速绘制价格曲线与季节性</div>
      </a>
    </div>
  </div>

  <div class="panel">
    <div class="section-title">💡 功能说明</div>
    <div class="info-grid" style="margin-top:10px">
      <div class="panel" style="background:var(--accent);">
        <div class="panel-title">📥 批量导入</div>
        <div class="muted">支持文本粘贴、AI 提示词辅助以及试运行校验，手机端下也能流畅完成。</div>
      </div>
      <div class="panel" style="background:var(--accent);">
        <div class="panel-title">🛒 产品列表</div>
        <div class="muted">中英文检索、类目梳理与观测时间线，响应式表格便于在小屏浏览。</div>
      </div>
      <div class="panel" style="background:var(--accent);">
        <div class="panel-title">🏪 门店列表</div>
        <div class="muted">快速查看门店活跃度与观测天数，支持滑动阅读。</div>
      </div>
      <div class="panel" style="background:var(--accent);">
        <div class="panel-title">📊 价格趋势</div>
        <div class="muted">产品/门店一键组合，按日周月聚合，图表自适应移动端。</div>
      </div>
    </div>
  </div>

  <?php if ($storeCount > 0): ?>
  <div class="panel">
    <div class="section-title">🏪 门店概览</div>
    <div class="table-wrapper" style="margin-top:12px">
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
    <div style="text-align: center; margin-top: 12px;">
      <a href="/prs/index.php?action=stores" class="muted">查看全部门店 →</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
