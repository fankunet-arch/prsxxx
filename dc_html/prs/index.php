<?php
/**
 * PRS Web Entry Point
 * 文件路径: dc_html/prs/index.php
 * 说明: Web 根目录唯一入口文件（透传请求到 app/prs/index.php）
 */

// 定义系统入口标识
define('PRS_ENTRY', true);

// 定义项目根目录 (dc_html 的上级目录)
define('PROJECT_ROOT', dirname(dirname(__DIR__)));

// 加载后端核心
require_once PROJECT_ROOT . '/app/prs/index.php';
