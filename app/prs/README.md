# PRS 系统 - 价格记录系统

Price Recording System - 多门店商品价格管理与分析平台

## 架构说明

PRS 系统采用 **MRS 风格的中央路由架构**，实现了逻辑与入口的彻底分离。

### 目录结构

```
app/prs/                      # 核心应用目录（不可直接访问）
  ├── index.php               # 中央路由控制器
  ├── bootstrap.php           # 系统引导文件
  ├── actions/                # 页面控制器（HTML 页面）
  │   ├── dashboard.php       # 系统首页
  │   ├── ingest.php          # 批量导入页面
  │   ├── products.php        # 产品列表页面
  │   ├── stores.php          # 门店列表页面
  │   └── trends.php          # 价格趋势分析页面
  ├── api/                    # API 控制器（JSON 接口）
  │   ├── ingest_save.php     # 数据导入接口
  │   ├── query_products_search.php
  │   ├── query_stores_search.php
  │   ├── query_resolve.php
  │   ├── query_list_products.php
  │   ├── query_list_stores.php
  │   ├── query_timeseries.php
  │   ├── query_season.php
  │   └── query_stockouts.php
  ├── lib/                    # 核心库/Model
  │   ├── ingest_controller.php
  │   └── query_controller.php
  ├── views/                  # 视图/模板
  │   └── layouts/
  │       └── header.php      # 布局模板
  └── config_prs/             # 配置文件
      └── env_prs.php         # 环境配置

dc_html/prs/                  # Web 根目录（公开访问）
  ├── index.php               # 唯一入口文件
  ├── css/                    # 静态资源（如果有）
  └── js/                     # 静态资源（如果有）
```

### URL 访问方式

**新的 URL 格式（统一通过参数路由）：**

- 首页：`/prs/index.php` 或 `/prs/index.php?action=dashboard`
- 批量导入：`/prs/index.php?action=ingest`
- 产品列表：`/prs/index.php?action=products`
- 门店列表：`/prs/index.php?action=stores`
- 价格趋势：`/prs/index.php?action=trends`

**API 接口：**

- 导入数据：`/prs/index.php?action=ingest_save`
- 产品搜索：`/prs/index.php?action=query_products_search&q=苹果`
- 门店搜索：`/prs/index.php?action=query_stores_search&q=超市`
- 名称解析：`/prs/index.php?action=query_resolve`
- 产品列表：`/prs/index.php?action=query_list_products&page=1&size=20`
- 门店列表：`/prs/index.php?action=query_list_stores`
- 时序数据：`/prs/index.php?action=query_timeseries&product_id=1&store_id=1`
- 季节性数据：`/prs/index.php?action=query_season&product_id=1&store_id=1`
- 缺货段数据：`/prs/index.php?action=query_stockouts&product_id=1&store_id=1`

## 安全特性

1. **入口控制**：所有核心文件头部都有 `if (!defined('PRS_ENTRY')) die('Access denied');` 检查
2. **路径遍历防护**：使用 `basename()` 清理 action 参数
3. **白名单验证**：中央路由器验证 action 是否在允许的列表中
4. **统一鉴权点**：可以在中央路由器中添加统一的认证逻辑

## 功能模块

### 1. 批量导入 (Ingest)
- 支持文本格式批量导入价格数据
- 试运行校验功能（Dry Run）
- AI 提示词辅助功能
- 自动分隔符识别
- 幂等性保证

### 2. 产品管理 (Products)
- 产品列表浏览（分页）
- 中英文名称搜索
- 产品分类管理
- 观测历史查看

### 3. 门店管理 (Stores)
- 门店列表浏览
- 数据统计（观测天数、记录总数）

### 4. 价格趋势分析 (Trends)
- 价格时序图表（日/周/月聚合）
- 季节性分析（在市月份）
- 缺货段可视化
- 多维度数据查询

## 技术栈

- **后端**：PHP 7.4+
- **数据库**：MySQL 5.7+
- **前端**：原生 JavaScript + Canvas API
- **架构**：MPA (Multi-Page Application) + 中央路由

## 配置

所有配置在 `app/prs/config_prs/env_prs.php` 中定义：

- 数据库连接信息
- 时区设置
- 日志目录
- 图片资源路径

## 数据库表

- `prs_stores` - 门店
- `prs_products` - 产品
- `prs_product_aliases` - 产品别名
- `prs_import_batches` - 导入批次
- `prs_price_observations` - 价格观测
- `prs_season_monthly_v2` - 月度在市视图
- `prs_stockout_segments_v2` - 缺货段视图

## 开发说明

### 添加新的页面 (Action)

1. 在 `app/prs/actions/` 创建新的 PHP 文件，例如 `app/prs/actions/new_page.php`
2. 文件头部添加安全检查：`if (!defined('PRS_ENTRY')) die('Access denied');`
3. 使用 `render_header()` 和 `render_footer()` 包裹页面内容
4. 访问：`/prs/index.php?action=new_page`

### 添加新的 API

1. 在 `app/prs/api/` 创建新的 PHP 文件，例如 `app/prs/api/new_api.php`
2. 文件头部添加安全检查
3. 使用 `prs_json_response()` 返回 JSON 响应
4. 访问：`/prs/index.php?action=new_api`

## 许可证

本项目仅供内部使用。
