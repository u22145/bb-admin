<?php

/**
 * 公共辅助函数
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 网址重定向
 * @param    string    $uri       要跳转的网址
 * @param    string    $method    跳转方法
 * @param    int       $code      状态码
 */
function redirect($uri = '', $method = 'auto', $code = null)
{
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== FALSE) {
        $method = 'refresh';
    } elseif ($method !== 'refresh' && (empty($code) || !is_numeric($code))) {
        if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.1') {
            $code = ($_SERVER['REQUEST_METHOD'] !== 'GET') ? 303 : 307;
        } else {
            $code = 302;
        }
    }
    if ('refresh' == $method) {
        header('Refresh: 0;url=' . $uri);
    } else {
        header('Location: ' . $uri, true, $code);
    }
    exit;
}

/**
 * 生成随机码
 * @param  int      $len     随机码长度
 * @param  string   $type    随机码类型：num-数字，str-小写字母，astr-大写字母，
 *                                       both-小写字母和数字，all-全部字符
 * @return string   $result  返回随机码
 */
function rand_code($len = 6, $type = 'num')
{
    $num = '0123456789';
    $str = 'abcdefghijklmnopqrstuvwxyz';
    $astr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    switch ($type) {
        case 'num':
        case 'str':
        case 'astr':
            $s = $$type;
            break;
        case 'both':
            $s = $str . $num;
            break;
        default:
            $s = $astr . $str . $num;
    }
    $res = '';
    $max = strlen($s) - 1;
    for ($i = 0; $i < $len; $i++) {
        $res .= $s[rand(0, $max)];
    }
    return $res;
}

/**
 * 获取客户端IP地址
 * @param      int     $type   返回类型：0-返回IP地址字符串，1-返回IPV4地址数字
 * @return     mixed           返回结果
 */
function get_client_ip($type = 1)
{
    $ip = '';
    if (isset($_SERVER['HTTP_X_CLIENTIP'])) {
        $ip = $_SERVER['HTTP_X_CLIENTIP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    if ($ip == '::1') $ip = '127.0.0.1';
    if ($type == 1) $ip = sprintf('%u', ip2long($ip));
    return $ip;
}

/**
 * dump                      打印变量
 * @param    mixed  $var     变量
 * @return   void            无返回结果
 */
function dump($var)
{
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    $output = '<pre>' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
    echo ($output);
}

/**
 * 显示断点执行时间
 */
function debug($str = '')
{
    $runtime = intval((microtime(true) - BEGINTIME) * 1000);
    echo '执行时间：';
    if ($str) echo "（ $str ）";
    echo $runtime, ' ms<br />';
}

/** 
 * 模拟http请求
 * @param     string    $url      请求地址 
 * @param     array     $data     POST数据：如果http请求方式为post，
                                  提交数据格式为数组，如没有提交数据，
                                  参数可写为字符串'post'
 * @param     array     $head     HTTP头字段：格式： array('Content-type: text/plain', 'Content-length: 100')
 * @return    string    $res      返回网页内容：无效网址返回false
 */
function http($url, $data = null, $head = null)
{
    $curl = curl_init(); // 初始化一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 设置要访问的地址
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。 设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer 
    if ($data) {
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求 
        if (is_array($data)) {
            if (!empty($data['file'])) {
                $file = $data['file'];
                $path = $file['tmp_name'];
                if (class_exists('\CURLFile')) {
                    curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
                    $data['file'] = new \CURLFile(realpath($path), $file['type']); //>=5.5
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
                    }
                    $data['file'] = '@' . realpath($path); //<=5.5
                }
            }
            $head['Content-Type'] = "multipart/form-data";
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        }
    }
    if ($head) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $head);
    }
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环  
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    $res = curl_exec($curl); // 执行操作
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE); // 获取http状态码
    curl_close($curl); // 关闭CURL会话
    if ($httpcode == 404 || $httpcode == 403) {
        return false;
    } else {
        return $res;
    }
}

/**
 * 日期格式化
 * @param   date    $time       时间 Y-m-d H:i:s 或者时间戳
 * @param   int     $type       返回时间格式：1-显示短日期：8-21，9：00；0-时间差格式：3天前，2小时前
 * @param   string  $result     返回值
 */
function time_format($time = 0, $type = 0)
{
    $result = '';
    if (!$time) {
        $times = time();
    } else {
        //转换为时间戳
        $times = is_numeric($time) ? $time : strtotime($time);
    }
    if ($type == 1) {
        if (date('Y-m-d', $times) == date('Y-m-d')) {
            //今天
            $result = date('H:i', $times);
        } elseif (date('Y', $times) == date('Y')) {
            //今年
            $result = date('m-d', $times);
        } else {
            //其他年份
            $result = date('Y', $times);
        }
    } else {
        if (date('Y-m-d', $times) == date('Y-m-d')) {
            //今天
            $timediff = floor((time() - $times) / 60); //时间差：分钟
            if ($timediff > 59) {
                $result = floor($timediff / 60) . get_tips(9002);
            } elseif ($timediff > 0) {
                $result = $timediff .  get_tips(9003);
            } else {
                $result =  get_tips(9004);
            }
        } elseif (date('Y-m-d', $times + 86400) == date('Y-m-d')) {
            $result = get_tips(9005);
        } elseif (date('Y-m-d', $times + 86400 * 2) == date('Y-m-d')) {
            $result = get_tips(9006);
        } else {
            $timediff = floor((time() - $times) / 86400); //时间差：天
            if ($timediff > 365) {
                $result = floor($timediff / 365) . get_tips(9007);
            } elseif ($timediff > 30) {
                $result = floor($timediff / 30) . get_tips(9008);
            } else {
                $result = $timediff . get_tips(9009);
            }
        }
    }
    return $result;
}

/**
 * 格式化插入数据库的特殊字符串，如用户名，邮箱，时间等
 * @param       string $str     要转换的字符串
 * @return      string $str     返回结果集
 */
function sql_format($str)
{
    //过滤用户输入
    $str = str_format($str);
    //删除非法字符
    $str = str_replace("'", '', $str);
    $str = str_replace('&', '', $str);
    $str = str_replace('=', '', $str);
    $str = str_replace('\"', '', $str);
    $str = str_replace('\\', '', $str);

    return $str;
}

/**
 * 短字符串过滤函数，如过滤文章的标题等单行文本
 * @param   string  $str        要过滤的字符串
 * @return  string  $str        返回结果集
 */
function str_format($str)
{
    //html转义字符
    $str = str_ireplace('&amp;', '&', $str);
    $str = str_ireplace('&nbsp;', ' ', $str);
    $str = str_ireplace('&quot;', '\"', $str);
    $str = str_ireplace('&lt;', '<', $str);
    $str = str_ireplace('&gt;', '>', $str);
    $str = str_ireplace('&#8206;', '', $str);
    //过滤用户输入
    $str = strip_tags($str);
    //删除多余空格
    $str = preg_replace('/\s+/', ' ', $str);
    //删除多余单引号
    $str = str_replace("\\", '', $str);
    $str = preg_replace('/\'+/', "'", $str);
    $str = str_replace("'", "''", $str);
    //过滤字符串首尾空格
    $str = trim($str);

    return $str;
}

/**
 * 文本过滤函数
 * @param   string  $str        要过滤的文本
 * @return  string  $str        返回结果集
 */
function text_format($str)
{
    //兼容不规范换行符
    $str = preg_replace('/<br\s?\/?>/i', PHP_EOL, $str);
    //过滤用户输入
    $str = strip_tags($str);
    //替换回换行符
    $str = str_replace(PHP_EOL, '<br />', $str);
    $str = preg_replace('/\s*<br \/>\s*/', '<br />', $str);
    //删除多余单引号
    $str = str_replace("\\", '', $str);
    $str = preg_replace('/\'+/', "'", $str);
    $str = str_replace("'", "''", $str);
    //过滤字符串首尾空格
    $str = trim($str);

    return $str;
}

/**
 * 自动解析编码读入文件
 * @param   string      $file       文件路径
 * @param   string      $charset    读取文件的目标编码
 * @return  string|false            返回读取内容，文件不存在返回false
 */
function read_file($file, $charset = 'UTF-8')
{
    if (file_exists($file)) {
        $str = file_get_contents($file);
        if ($str) {
            $arr = ['GBK', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1'];
            $enc = mb_detect_encoding($str, $arr, true);
            if ($charset != $enc) {
                $str = mb_convert_encoding($str, $charset, $enc);
            }
        }
        return $str;
    }
    return false;
}

/**
 * 多维数组按某一列的值排序
 * @param   $array  array               要排序的数组
 * @param   $key    string|int          排序的键，如是数值则按数字索引排序
 * @param   $sort   SORT_ASC|SORT_DESC  排序方式，默认升序(4)，降序为(3)
 * @return          array|false         返回排序后的数组，排序失败返回false
 */
function multi_array_sort($array = [], $key = 0, $sort = SORT_ASC)
{
    if (is_array($array)) {
        $arr1 = array_column($array, $key);
        if ($arr1) {
            array_multisort($arr1, $sort, $array);
            return $array;
        }
    }
    return false;
}

/**
 * chk_chinese					检查字符串是否含有中文
 * @param 	string $str 		要检查的字符串
 * @return	bool 				返回true or false
 */
function chk_chinese($str)
{
    if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 校验用户数据格式是否符合要求
 * @param 	string	type		要校验的数据格式
                                (必须是已有的格式目前已有 mobile,password,username,email)  
 *								默认支持的格式  手机号()  用户名  密码  可以自己扩展需要的类型
 * @param 	string	value		要校验的数据的值
 * @param	int		$area_code	国际区号，仅限验证手机号
 * @return 	boolean
 **/
function check_user_data($type = '', $value = '', $area_code = 0)
{
    $result = false;
    switch ($type) {
        case 'mobile':
            //校验手机号
            if ($area_code) {
                //国外手机根据不同国家不同规则进行校验，后期改进
                if (is_numeric($area_code) && is_numeric($value)) {
                    $result = true;
                }
            } else {
                //国内手机
                if (preg_match("/^1[345678]{1}\d{9}$/", $value)) {
                    $result = true;
                }
            }
            break;
        case 'password':
            //校验密码 默认的格式 6-20位的任意字符
            if (mb_strlen($value) >= 6 && mb_strlen($value) <= 20) {
                $result = true;
            }
            break;
        case 'username':
            //校验用户名，不能使用系统关键字
            if (stripos($value, "admin") === false && stripos($value, "password") === false && stripos($value, "user") === false && stripos($value, "code") === false) {
                //默认格式 中英文  下划线  数字   不能是纯数字 长度  2-15
                if (preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]{2,15}$/u", $value) && !is_numeric($value)) {
                    $result = true;
                }
            }
            break;
        case 'email':
            //校验邮箱是否符合标准  使用php原生过滤方法  FILTER_VALIDATE_EMAIL
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $result = true;
            }
            break;
    }
    return $result;
}

//拼接头像URL
function get_pic_url($img, $type = "", $size = "_s", $is_cdn = false)
{
    if (empty($img)) return '';

    // 已是完整的图片URL, 则直接返回
    if (strpos($img, "http://") !== false || strpos($img, "https://") !== false) {
        return $img;
    }

    //去掉之前的 upload 或 /upload 前缀
    $img = mb_eregi_replace("/?upload", "", $img);

    switch ($type) {
            //头像
        case 'avatar':
            if (empty($img)) {
                $img = "/default_avatar.png";
            }
            break;
            //国旗
        case 'country':
            $img = "/country/$img.png";
            break;
        case 'pay_type':
            $img = "/pay_type/{$img}.png";
            break;
        case 'vip_icon':
            $img = "/vip_icon/{$img}.png";
            break;
        case 'symbol':
            // 徽标：anchor, vip, svip
            $img = "/symbol/{$img}.png";
            break;
        default:
            if (empty($img)) return '';
            break;
    }

    if (substr($img, 0, 1) != '/') {
        $img = '/' . $img;
    }

    if ($is_cdn && 'avatar' != $type) {
    // if ($is_cdn ) {
        // cdn缓存
        return PIC_SERVER . $img;
    } else {
        // 本地
        return PIC_LOCAL_SERVER . $img;
    }
}

/**
 * 去除域名
 */
function trim_domain($url)
{
    if (strpos($url, "http://") !== false) {
        $url = explode("/", $url);
        $url = join("/", array_slice($url, 3));
        return $url;
    }
    return $url;
}


/**
 * 获取阿里的访问KEY 和 Secret
 * @return array
 */
function get_ali_access_data()
{
    //TODO load from config.php
    $accessKeyId = "LTAIxwNHMQYGYCRD";
    $accessKeySecret = "7vORuVxWAkvpWOujPw8V2NiYhsc97V";
    return [
        'accessKeyId' => $accessKeyId,
        'accessKeySecret' => $accessKeySecret
    ];
}

/**
 * 获取IM配置信息
 * @return array
 */
function get_im_data()
{
    //TODO load from config.php
    return [
        'key' => '8353c5c226c4ca9454dc10dffe89cbfa',
        'secret' => 'dee71adc2dde'
    ];
}

/**
 * 验证手机号验证码
 * @param $area_code
 * @param $mobile
 * @param $code
 * @return bool
 */
function verify_mobile_code($area_code, $mobile, $code)
{
    /**
     * @var Redis $redis
     */
    global $redis;
    $cache_key = sprintf(RedisKey::MOBILE_CODE, $area_code, $mobile);
    $code_in_cache = $redis->get($cache_key);
    if (empty($code_in_cache)) return false;
    $ok = $code == $code_in_cache;
    if ($ok) {
        // 返回成功时，删除已验证过的key
        $redis->del($cache_key);
    }
    return $ok;
}

/**
 * 验证邮箱验证码
 * @param $email
 * @param $code
 * @return bool
 */
function verify_email_code($email, $code)
{
    /**
     * @var Redis $redis
     */
    global $redis;
    $cache_key = sprintf(RedisKey::EMAIL_CODE, $email);
    $code_in_cache = $redis->get($cache_key);
    if (empty($code_in_cache)) return false;
    $ok = $code == $code_in_cache;
    if ($ok) {
        // 返回成功时，删除已验证过的key
        $redis->del($cache_key);
    }
    return $ok;
}

/**
 * 生成网易的 accid
 */
function generate_accid($uid)
{
    return "xd_" . $uid;
}

/**
 * 获取封面图
 * @param $video string 视频地址
 * @return bool|string
 */
function ffmpeg_get_cover($video, $cover_save_name)
{
    $ffmpeg = "/usr/local/ffmpeg"; // 文件绝对路径
    return exec($ffmpeg . " -i " . $video . " -y -f image2 -t 0.001 -s 200x200 " . $cover_save_name); // 运行命名
}
/**
 * post get,参数过滤函数
 */
function  filterParams($params)
{
    $farr = array(
        "/<(\\/?)(script|i?frame|style|html|body|title|link|meta|object|\\?|\\%)([^>]*?)>/isU",
        "/(<[^>]*)on[a-zA-Z]+\s*=([^>]*>)/isU",
        "/select\b|insert\b|update\b|delete\b|drop\b|;|\"|\'|\/\*|\*|\.\.\/|\.\/|union|into|load_file|outfile|dump/is"
    );
    return  filter_var($params, $farr);
}

/**
 * 把多维数组下的某个键值全部设置为键名 array_index
 *
 * @param array $array
 * @param $name
 * @return void
 */
function array_index(array $array, $name)
{
    $indexedArray = array();
    if (empty($array)) {
        return $indexedArray;
    }
    foreach ($array as $item) {
        if (isset($item[$name])) {
            $indexedArray[$item[$name]] = $item;
            continue;
        }
    }
    return $indexedArray;
}

/**
 * 组合下载路径 get_load_path
 *
 * @param string $file_name
 * @return void
 */
function get_load_path(string $file_name)
{
    $path = 'upload/download/';
    $absolutely_path = BASEPATH . $path;
    if (!file_exists($absolutely_path)) {
        mkdir($absolutely_path, 0777, true);
    }

    return [
        'absolutely_path' => $absolutely_path . $file_name,
        'relative_path' => '/' . $path . $file_name
    ];
}

/**
 * 计算两个时间相差的天/小时/分钟数 time_difference
 *
 * @param string $start_date
 * @param string $end_date
 * @return void
 */
function time_difference(string $start_date, string $end_date)
{
    $start_time = strtotime($start_date);
    $end_time = strtotime($end_date);
    if ($start_time > $end_time) {
        return 0;
        exit;
    }

    $difference = $end_time - $start_time;
    $day = floor($difference / 86400);
    $hour = floor(($difference % 86400) / 3600);
    $minute = floor(($difference % 86400) / 60) - floor($hour ? $hour * 60 : 0);
    $second = floor($difference % 86400 % 60);

    $times = '';
    $times .= $day > 0 ? $day . '天' : '';
    $times .= $hour > 0 ? $hour . '小時' : '';
    $times .= $minute > 0 ? $minute . '分' : '';
    $times .= $second > 0 ? $second . '秒' : '';
    return trim($times, ',');
}

/**
 * @param $title  string 字符串
 * @param $header array() 索引数组
 * @param $data   array(daa）二维数组
 * @param $sheet  string 
 * @return output 浏览器 下载文件
 */
function export($title, $header, $data, $sheet='')
{
    //设置 header，用于浏览器下载
    header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($title) . '"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    $writer = new XLSXWriter();
    //工作簿名称
    $sheet1 = empty($sheet) ? 'sheet1' : $sheet;
    //对每列指定数据类型，对应单元格的数据类型
    foreach ($header as $key => $item) {
        $col_style[] =  'string';
        $widths[] = 20;
    }
    $writer->writeSheetHeader($sheet1, $col_style, ['suppress_row' => true, 'widths' => $widths]);
    $writer->writeSheetHeader($sheet1, $col_style);
    //写入第二行的数据，顺便指定样式
    $writer->writeSheetRow(
        $sheet1,
        [$title],
        ['height' => 32, 'font-size' => 20, 'font-style' => 'bold', 'halign' => 'center', 'valign' => 'center']
    );
    /*设置标题头，指定样式*/
    $styles1 = array(
        'font' => '宋体', 'font-size' => 10, 'font-style' => 'bold', 'fill' => '#eee',
        'halign' => 'center', 'border' => 'left,right,top,bottom'
    );
    $writer->writeSheetRow($sheet1, $header, $styles1);
    $styles2 = ['height' => 20];
    foreach ($data as $row) {
        $writer->writeSheetRow($sheet1, $row, $styles2);
    }
    $writer->markMergedCell($sheet1, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = count($header) - 1);
    //输出文档
    $path = get_load_path($title . date('YmdHis') . '.xlsx');
    $writer->writeToFile($path['absolutely_path']);

    return $path['relative_path'];
}

/**
 * @param $title  string 字符串
 * @param $header array() 索引数组 二维
 * @param $data   array(daa）三维数组
 * @return output 浏览器 下载文件
 */
function export_multi($title, $headers, $datas)
{
    //设置 header，用于浏览器下载
    header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($title) . '"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    $writer = new XLSXWriter();
    //工作簿名称
    $sheet1 = 'sheet1';
    //对每列指定数据类型，对应单元格的数据类型

    foreach ($headers as $k => $header) {
        
        foreach ($header as $key => $item) {
            $col_style[] =  'string';
            $widths[] = 20;
        }
        $writer->writeSheetHeader($sheet1, $col_style, ['suppress_row' => true, 'widths' => $widths]);
        $writer->writeSheetHeader($sheet1, $col_style);
        //写入第二行的数据，顺便指定样式
        $writer->writeSheetRow(
            $sheet1,
            [$title],
            ['height' => 32, 'font-size' => 20, 'font-style' => 'bold', 'halign' => 'center', 'valign' => 'center']
        );
        /*设置标题头，指定样式*/
        $styles1 = array(
            'font' => '宋体', 'font-size' => 10, 'font-style' => 'bold', 'fill' => '#eee',
            'halign' => 'center', 'border' => 'left,right,top,bottom'
        );
        $writer->writeSheetRow($sheet1, $header, $styles1);
        $styles2 = ['height' => 20];
        if( !empty($datas[$k]) )
            foreach ($datas[$k] as $row) {
               $writer->writeSheetRow($sheet1, $row, $styles2);
            }
        $writer->markMergedCell($sheet1, $start_row = 0, $start_col = 0, $end_row = 0, $end_col = count($header) - 1);
    }
    //输出文档
    $path = get_load_path($title . date('YmdHis') . '.xlsx');
    $writer->writeToFile($path['absolutely_path']);

    return $path['relative_path'];
}

/**
 * 发送系统消息
 * @param $_uid
 * @param $msg
 * @return bool
 */
function send_system_msg($_uid, $msg)
{
    if (empty($_uid) || $_uid == -1) return false;

    // 系统用户标识为-1
    $from_uid = -1;

    /**
     * @var Redis $redis
     */
    global $redis;

    $msg_data = [
        'pm_id' => $redis->incr("pm:newid"),
        'times' => time(),
        'from_uid' => $from_uid,
        'to_uid' => $_uid,
        'type' => 2,
        'msg_content' => $msg
    ];
    // 双方的聊天列表
    $redis->rPush("pm:msg:-1:$_uid", json_encode($msg_data, JSON_UNESCAPED_UNICODE));
    // 首页消息
    $redis->zAdd("pm:list:$_uid", time() + 86400 * 365, $from_uid);
    // 对方的新消息数量+1
    $redis->hIncrBy("pm:new:$_uid", $from_uid, 1);

    return true;
}

/**
 * 验证器 validate
 *
 * @param array $data
 * @param array $role
 * @param array $msg
 * @desc 验证器role顺序和data顺序一致时可减少循环次数，提高性能。
 * @return void
 */
function validate(array $data, array $role, array $msg)
{
    $message = '';
    foreach ($role as $roleK => $roleV) {
        $role_arr = explode('|', $roleV);
        $count = intval(count($role_arr));
        $type = ''; // 类型
        $condition = ''; // 条件
        if ($count <= 0) {
            $message =  get_tips(9001);
            return $message;
        }
        if ($count >= 1) {
            $type = $role_arr[0];
        }
        if ($count >= 2) {
            $condition = $role_arr[1];
        }
        if ($type) {
            // 验证类型
            switch ($type) {
                case 'must':
                    if (empty($data[$roleK]) || !isset($data[$roleK])) {
                        $message = $msg[$roleK];
                    }
                    break;
            }
            if ($message) return $message;
        }
        if ($condition) {
            //处理附加条件
            $arr = explode('@', $condition);
            $condition = $arr[0];
            $additional = '';
            if (count($arr) > 1) {
                $additional = $arr[1];
            }
            // 验证条件
            switch ($condition) {
                case 'int':
                    if (is_numeric($data[$roleK]) === false) {
                        $message = $msg[$roleK . '.int'] ?? $msg[$roleK];
                    }
                    break;
                case 'decimal':
                    if (strpos($data[$roleK], '.') === false) {
                        $message = $msg[$roleK . '.decimal'] ?? $msg[$roleK];
                    }
                    break;
                case 'char':
                    if (is_string($data[$roleK]) === false) {
                        $message = $msg[$roleK . '.char'] ?? $msg[$roleK];
                    }
                    // 附加条件处理
                    if ($additional) {
                        $additional_arr = explode(':', $additional);
                        $additional_condition = $additional_arr[0];
                        $additional_value = $additional_arr[1];
                        switch ($additional_condition) {
                            case 'len':
                                if (strlen($data[$roleK]) >= $additional_value) {
                                    $message = $msg[$roleK . '.len'] ?? $msg[$roleK];
                                }
                                break;
                        }
                        if ($message) return $message;
                    }
                    break;
                case 'array':
                    if (is_array($data[$roleK]) === false) {
                        $message = $msg[$roleK . '.array'] ?? $msg[$roleK];
                    }
                    break;
            }
        }
    }

    return $message;
}

/**
 * 获取参数 input
 * @param string $methodAndparam 请求方式.字段 post.name
 * @param string $filter 可执行函数
 * @param string $default 默认值
 * @return void
 */
function input(string $methodAndparam, string $filter = '', $default = null)
{
    $methodAndparam_arr = explode('.', $methodAndparam);
    if (count($methodAndparam_arr) < 2) return $default;
    $method = strtoupper($methodAndparam_arr[0]);
    $param = $methodAndparam_arr[1];

    $data = get_methods($method, $param, $default);
    return $filter ? call_user_func($filter, $data) : $data;
}
/**
 * 获取参数 get_methods
 *
 * @param string $method
 * @return void
 */
function get_methods(string $method, string $param, $default)
{
    switch ($method) {
        case 'POST':
            if (isset($_POST[$param]) && !empty($_POST[$param]) && $_POST[$param] !== false) {
                return $_POST[$param];
            }
            return $default;
            break;
        case 'GET':
            if (isset($_GET[$param]) && !empty($_GET[$param]) && $_POST[$param] !== false) {
                return $_GET[$param];
            }
            return $default;
            break;
    }

    return $default;
}

/**
 * 提示信息
 */
function get_tips($id = 0, $type = 'tips')
{
    global $redis;
    $lang = $redis->get('lang') ?: 'zh';

    $txt = read_file(APPPATH . "/config/lang/$lang/$type" . ".txt");
    if (DIRECTORY_SEPARATOR == '\\') {
        $pat = "/\r\n{$id}\:(.+)\r\n/";
    } else {
        $pat = "/\n{$id}\:(.+)\n/";
    }
    preg_match_all($pat, $txt, $arr);
    if (empty($arr[1][0])) {
        return "Unknown Error";
    }
    if (strpos($arr[1][0], '{file}') === false) {
        return $arr[1][0];
    } else {
        return read_file(APPPATH . "/config/lang/$lang/$type" . "_$id" . ".txt");
    }
}

/**
 * 将时间戳转换为用户当地时间
 */
function time_to_local_string($time, $timezone = 8, $uid = 0)
{
    if (! $timezone && $uid) {
        global $redis;
        $timezone = floatval($redis->hget("user:$uid", 'timezone'));
    }
    // 转换时间为时间戳
    if (strpos($time, ':')) {
        $time = strtotime($time);
    }
    return date('Y-m-d H:i:s', $time + $timezone * 3600);
}
