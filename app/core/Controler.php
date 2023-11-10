<?php
/**
 * 主控制器
 */
if (! defined('BASEPATH')) exit('No direct script access allowed');

class Controller 
{
    /**
     * mysql数据库
     * @var Database
     */
    protected $db;
    /**
     * redis数据库
     * @var Redis
     */
    protected $redis;

    protected $user;        // 用户信息
    protected $tpl_data;    // 模板数据
    protected $req_data;    // 请求数据
    
    public function __construct()
    {
        // 默认加载公共函数
        $this->load_helper('common');
        // 初始化redis
        $this->init_redis(1);
        //加载数据库
        $this->init_db();

        global $controller, $method;
        //过滤登录请求
        if ('login' != $controller && 'index' != $controller) {
            //检测登录
            $this->check_user();
        }
    }

    /**
     * 检测权限
     * @author :kobe
     * @date 2019-8-18
     */
    protected function check_user()
    {
        $appid = DATABASESUFFIX;
        $usercode = sql_format($_POST['usercode'] ?? '');
        if (! $usercode) {
            ajax_return(ERROR, '您尚未登錄');
        }


        // 查询用户信息
        $sql = "select id, username, status, is_super_admin from admin_user_$appid where usercode = '$usercode'";
        $userinfo = $this->db->query($sql)->row_array();
        if (! $userinfo) {
            ajax_return(ERROR, '您的登錄已過期');
        }
        if ($userinfo['status'] != 1) {
            ajax_return(ERROR, '您的账号已被禁用，请联系管理员！');
        }

        // 用戶信息賦值
        $this->user = [
            'id' => $userinfo['id'],
            'username' => $userinfo['username'],
        ];

        // 檢查權限
        global $controller, $method;
        if (! $userinfo['is_super_admin']) {
            // 查询用户权限
            $sql = "select r.id from admin_user_role_$appid r inner join admin_role_power_$appid p on r.role_id = p.role_id inner join admin_power_$appid c on p.power_id = c.power_id where r.admin_id = {$userinfo['id']} and c.controller = '$controller' and c.method = '$method' limit 1";
            $res = $this->db->query($sql)->row_array();
            if (! $res) {
                ajax_return(ERROR, "你没有权限进行此操作");
            }
        }

        //记录访问日志
        $arr = [
            'time' => date('Y-m-d H:i:s'),
            'admin' => $userinfo['id'],
            'url' => $controller . '/' . $method,
            'post' => array_filter(array_diff_key($_POST, ['usercode' => ''])),
        ];
        @file_put_contents(ERRLOG_PATH . '/api_' . date("Ymd") . '.log', json_encode($arr) . "\n", FILE_APPEND);
    }
    
    /**
     * 加载配置文件
     * @param   string  $confs     配置文件名称，全部小写，可接受多个参数
     */
    protected function load_config(...$confs)
    {
        global $config;
        foreach ($confs as $conf) {
            $conf = strtolower($conf);
            // 配置文件名必须全部小写
            if (file_exists(APPPATH . 'config/' . $conf . '.php')) {
                include_once APPPATH . 'config/' . $conf . '.php';
            } else {
                show_error(994, ['title' => "The configure file <i>$conf</i> does not exist."]);
            }
        }
        return $config;
    }

    /**
     * 加载辅助函数
     * @param   string  $load_helper     辅助函数文件名称，全部小写
     */
    protected function load_helper($helper = '')
    {
        if (! $helper) {
            $helper = 'common';
        } else {
            $helper = strtolower($helper);
        }
        // 辅助函数名必须全部小写
        if (file_exists(APPPATH . 'helper/' . $helper . '.php')) {
            include_once APPPATH . 'helper/' . $helper . '.php';
        } else {
            show_error(995, ['title' => "The helper <i>$helper</i> does not exist."]);
        }
    }

    /**
     * 加载模型
     * @param   string $model 模型名称，全部小写
     * @param string $appid 应用ID
     */
    protected function load_model($model = '')
    {
        global $controller;
        if (! $model) {
            // 参数为空则默认加载与当前控制器同名model
            $model = $controller . '_model';
        } else {
            $model = strtolower($model);
        }
        // 模型文件名必须首字母大写、其他小写
        $model_name = ucwords($model);
        if (file_exists(APPPATH . 'model/' . $model_name . '.php')) {
            include_once APPPATH . 'model/' . $model_name . '.php';
            $this->$model = new $model_name();

            // 在模型中继承控制器的属性
            foreach (array_keys(get_object_vars($this)) as $var) {
                $this->$model->$var = $this->$var;
            }
        } else {
            show_error(996, ['title' => "The model <i>$model_name</i> does not exist."]);
        }
    }
    
    /**
     * 初始化数据库连接
     * @param    int     $server_id    服务器id    1-主服务器；2-从服务器
     */
    protected function init_db($server_id = 1)
    {
        if ($server_id > 1) {
            global $config;
            $conf = $config['db' . $server_id];
            $obj = new Database();
            $obj->connect($conf['host'], $conf['user'], $conf['pwd'], $conf['db'], $conf['port'], $conf['charset']);
            return $obj;
        }
        if (! $this->db) {
            global $config;
            $conf = $config['db' . $server_id];
            $this->db = new Database();
            $this->db->connect($conf['host'], $conf['user'], $conf['pwd'], $conf['db'], $conf['port'], $conf['charset']);
            $GLOBALS['db'] = $this->db;
            return $this->db;
        }
    }
    
    /**
     * 初始化redis连接
     * @param    int     $server_id    服务器id    1-主服务器；2-从服务器
     */
    protected function init_redis($server_id = 1)
    {
        if (! $this->redis) {
            global $config;
            $conf = $config['redis' . $server_id];
            //创建相同的新连接时，redis会默认使用已打开的连接
            $obj = new Redis();
            $obj->connect($conf['host'], $conf['port']);
            if ($conf['db']) {
                $obj->select($conf['db']);
            }
            $this->redis = $obj;
            $GLOBALS['redis'] = $obj;
        }
    }
    
    /**
     * 初始化mcache缓存
     */
    protected function init_mcache()
    {
        $obj = new Mcache($this->redis);
        return $obj;
    }
    
    /**
     * 分配变量
     * @param string      $key     标签名
     * @param string         $val     变量值
     */
    protected function assign($key, $val)
    {
        if ($key && preg_match('/^[A-z_][A-z0-9_]*$/', $key)) {
            $this->tpl_data[$key] = $val;
        } else {
            exit('变量名不合法：' . $key);
        }
    }

    /**
     * 页面输出
     */
    protected function display($html = '')
    {
        global $controller, $method;
        $this->tpl_data['_controller'] = $controller;
        $this->tpl_data['_method'] = $method;
        //视图文件
        if (! $html) {
            $html = APPPATH . 'view/' . $controller . '/' . $method . '.html';
        } else {
            $html = APPPATH . 'view/' . $html . '.html';
        }
        //加载模板
        $tpl = new Template($html);
        $tpl->display($this->tpl_data);
    }

    /**
    * 输出错误提示
    */
    protected function prt_error($msg = '')
    {
        $this->assign('msg', $msg);
        $this->display('public/error');
    }
}
