<?php
/**
 * 配置文件
 */
if (! defined('BASEPATH')) exit('No direct script access allowed');

//接口状态值
define('ERROR', 0);
define('SUCCESS', 1);
define('UN_LOGIN', 2);
define('AUTH_FAILED', 3);
define('INFO_UN_PERFECT', 4);

//性别
define("GENDER_GIRL", 1);
define("GENDER_BOY", 2);

// 金额小数位数
define('MONEY_DECIMAL_DIGITS', 2);

//金额换算成整数的计算倍数，和上面的對應，千万不要修改
define('MONEY_DECIMAL_MULTIPLE', 100);

//国旗路径
define('COUNTRY_FLAG', '/country');

//定义字符串截取长度 $end: 100
define('STREND', 100);

//设置评论 & 回复 长度截取的字符串
define('SETNAME', 'UTF-8');

// 稳定币名称
define('STABLE_CURRENCY_NAME', 'eurc');
define('STABLE_CURRENCY_TITLE', 'Baby');

//上传图片目录
define('UPLOAD_PATH', BASEPATH . "upload");
define('S3WAIT_PATH', BASEPATH . "s3wait");
define('QNWAIT_PATH', BASEPATH . "qnwait");

// 定义数据库表后缀
define('DATABASESUFFIX', 1);


// 统一设置后台分页的size，默认为200，
// 此外vue下的store.js中的state.adminPageSize也需要设置成相同的值，this.$store.state.adminPageSize
define('ADMIN_PAGE_SIZE', 200);

// 加载路由配置
require APPPATH . 'config/routes.php';