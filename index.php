<?php
/**
 * entry
 */
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// tmp remove later
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");

// 设置时区
ini_set('date.timezone', 'Asia/Shanghai');

// 记录页面开始执行时间
define('BEGINTIME', microtime(true));

// 网站根路径
define('BASEPATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
// 应用程序路径
define('APPPATH', BASEPATH . 'app' . DIRECTORY_SEPARATOR);

// 错误日志目录
define('ERRLOG_PATH', BASEPATH . 'data/logs');
// 会话信息保存目录
define('SESSION_SAVE_PATHS', BASEPATH . 'data/session');
// 缓存目录
define('CACHE_PATH', BASEPATH . 'data/cache');

// 模板引擎编译文件输出目录
define('TPL_COMPILE_PATH', BASEPATH . 'data/runtime');
// 模板变量左侧界定符
define('TPL_LEFT_SEPERATOR', '<{');
// 模板变量右侧界定符
define('TPL_RIGHT_SEPERATOR', '}>');

// 定义AJAX请求标志
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
define('IS_POST', strtolower($_SERVER['REQUEST_METHOD']) == 'post');
define('IS_GET', strtolower($_SERVER['REQUEST_METHOD']) == 'get');

// 设置会话保存路径
ini_set('session.save_path', SESSION_SAVE_PATHS);
if (! session_id()) {
    @session_start();
}

// 加载环境配置
require '../.env';

// 错误报告方式
if (DEVELOPMENT_ENVIRONMENT == true) {
    // 开发环境打印所有错误
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    $sql_debug = [];
} else {
    // 正式环境不报告错误，但记录错误到日志文件
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 'On');
    ini_set('log_errors', 'On');
    ini_set('error_log', ERRLOG_PATH . '/error.log');
}

// 忽略图片处理错误
ini_set('gd.jpeg_ignore_warning', 1);

// 加载配置文件
require APPPATH . 'config/config.php';

// 授权域名访问
// header('Access-Control-Allow-Origin:' . ADMIN_SERVER_URL);
// 加载核心文件
require APPPATH . 'core/kernel.php';
call_hook();
