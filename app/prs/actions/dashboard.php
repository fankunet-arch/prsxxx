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

<div class="stack" style="gap: 24px; max-width: 1200px; margin: 0 auto;">
  <!-- 欢迎区 -->
  <div class="card">
    <div class="body">
      <h2 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 600;">欢迎使用 PRS 系统</h2>
      <p class="muted" style="margin: 0;">价格记录系统 (Price Recording System) - 多门店商品价格管理与分析平台</p>
    </div>
  </div>

  <!-- 统计卡片 -->
  <div class="row" style="gap: 16px;">
    <div class="col" style="flex: 1;">
      <div class="card" style="text-align: center; padding: 24px;">
        <div style="font-size: 48px; font-weight: 700; color: #3aa6ff; margin-bottom: 8px;">
          <?= $productCount ?>
        </div>
        <div class="muted">产品总数</div>
      </div>
    </div>
    <div class="col" style="flex: 1;">
      <div class="card" style="text-align: center; padding: 24px;">
        <div style="font-size: 48px; font-weight: 700; color: #30d158; margin-bottom: 8px;">
          <?= $storeCount ?>
        </div>
        <div class="muted">门店总数</div>
      </div>
    </div>
    <div class="col" style="flex: 1;">
      <div class="card" style="text-align: center; padding: 24px;">
        <div style="font-size: 48px; font-weight: 700; color: #ff9f0a; margin-bottom: 8px;">
          <?= number_format($totalObs) ?>
        </div>
        <div class="muted">价格观测总数</div>
      </div>
    </div>
  </div>

  <!-- 快速导航 -->
  <div class="card">
    <div class="body">
      <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">快速导航</h3>
      <div class="row" style="gap: 12px;">
        <a href="/prs/index.php?action=ingest" class="btn" style="flex: 1; text-align: center; text-decoration: none; padding: 16px;">
          <div style="font-size: 24px; margin-bottom: 4px;">📥</div>
          <div>批量导入</div>
        </a>
        <a href="/prs/index.php?action=products" class="btn" style="flex: 1; text-align: center; text-decoration: none; padding: 16px;">
          <div style="font-size: 24px; margin-bottom: 4px;">🛒</div>
          <div>产品列表</div>
        </a>
        <a href="/prs/index.php?action=stores" class="btn" style="flex: 1; text-align: center; text-decoration: none; padding: 16px;">
          <div style="font-size: 24px; margin-bottom: 4px;">🏪</div>
          <div>门店列表</div>
        </a>
        <a href="/prs/index.php?action=trends" class="btn" style="flex: 1; text-align: center; text-decoration: none; padding: 16px;">
          <div style="font-size: 24px; margin-bottom: 4px;">📊</div>
          <div>价格趋势</div>
        </a>
      </div>
    </div>
  </div>

  <!-- 功能说明 -->
  <div class="card">
    <div class="body">
      <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">功能说明</h3>
      <div class="stack" style="gap: 12px;">
        <div>
          <strong>📥 批量导入</strong><br>
          <span class="muted">支持文本格式批量导入价格数据，包含试运行校验和 AI 提示词辅助功能</span>
        </div>
        <div>
          <strong>🛒 产品列表</strong><br>
          <span class="muted">浏览和搜索所有产品，支持中英文名称检索，查看产品分类和观测历史</span>
        </div>
        <div>
          <strong>🏪 门店列表</strong><br>
          <span class="muted">查看所有门店及其数据统计，包括观测天数和记录总数</span>
        </div>
        <div>
          <strong>📊 价格趋势</strong><br>
          <span class="muted">分析特定产品在特定门店的价格走势，支持日/周/月聚合，可视化展示季节性和缺货段</span>
        </div>
      </div>
    </div>
  </div>

  <!-- 最近活跃门店 -->
  <?php if ($storeCount > 0): ?>
  <div class="card">
    <div class="body">
      <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600;">门店概览</h3>
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
      <div style="text-align: center; margin-top: 12px;">
        <a href="/prs/index.php?action=stores" class="muted">查看全部门店 →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php render_footer(); ?>
