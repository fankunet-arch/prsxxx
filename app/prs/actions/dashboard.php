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

<div class="stack" style="gap:20px; max-width:1220px; margin:0 auto;">
  <div class="page-header">
    <div>
      <h2 class="page-title">欢迎使用 PRS 系统</h2>
      <p class="page-desc">价格记录系统 (Price Recording System) · 统一的多端界面，桌面与移动端均可流畅操作。</p>
    </div>
    <div class="pill">✨ 现代化布局 · 自适应</div>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <strong><?= $productCount ?></strong>
      <div class="muted">产品总数</div>
    </div>
    <div class="stat-card">
      <strong><?= $storeCount ?></strong>
      <div class="muted">门店总数</div>
    </div>
    <div class="stat-card">
      <strong><?= number_format($totalObs) ?></strong>
      <div class="muted">价格观测总数</div>
    </div>
  </div>

  <div class="card">
    <div class="body">
      <div class="page-header" style="margin-bottom:10px">
        <div>
          <h3 class="page-title" style="font-size:20px;margin-bottom:4px">快速入口</h3>
          <p class="page-desc">常用操作集中呈现，移动端横向滚动同样易用。</p>
        </div>
        <span class="inline-hint">轻触卡片即可进入</span>
      </div>
      <div class="row" style="gap:12px">
        <a href="/prs/index.php?action=ingest" class="btn" style="flex:1;text-align:center;text-decoration:none;padding:16px 14px">📥 批量导入</a>
        <a href="/prs/index.php?action=products" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:16px 14px">🛒 产品列表</a>
        <a href="/prs/index.php?action=stores" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:16px 14px">🏪 门店列表</a>
        <a href="/prs/index.php?action=trends" class="btn secondary" style="flex:1;text-align:center;text-decoration:none;padding:16px 14px">📊 价格趋势</a>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="body">
      <div class="page-header" style="margin-bottom:8px">
        <div>
          <h3 class="page-title" style="font-size:20px;margin:0 0 4px">功能概览</h3>
          <p class="page-desc">核心功能在桌面与手机端保持一致体验，试运行结果会在可滚动区域中展示，避免撑爆屏幕。</p>
        </div>
      </div>
      <div class="stack" style="gap:10px">
        <div class="hero">
          <div class="pill">📥 批量导入</div>
          <div class="muted">文本导入 + 试运行校验，AI 提示词仅作为辅助可选项。</div>
        </div>
        <div class="hero">
          <div class="pill">🛒 产品列表</div>
          <div class="muted">快速检索产品与类目，支持中英文名称。</div>
        </div>
        <div class="hero">
          <div class="pill">🏪 门店列表</div>
          <div class="muted">查看门店覆盖度与观测规模。</div>
        </div>
        <div class="hero">
          <div class="pill">📊 价格趋势</div>
          <div class="muted">聚合维度灵活的价格趋势图，移动端支持滑动查看。</div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($storeCount > 0): ?>
  <div class="card">
    <div class="body">
      <div class="page-header" style="margin-bottom:8px">
        <div>
          <h3 class="page-title" style="font-size:20px;margin:0 0 4px">门店概览</h3>
          <p class="page-desc">近期活跃门店，列表可横向滚动，移动端不会挤压内容。</p>
        </div>
      </div>
      <div class="table-wrapper" style="max-height:360px">
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
      <div style="text-align:center; margin-top:10px;">
        <a href="/prs/index.php?action=stores" class="muted">查看全部门店 →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
