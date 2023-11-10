<?php

/**
 * 合作商数据统计
 */
class Advert extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  广告列表
     */
    public function advert_list()
    {
        $table = 'advert_' . DATABASESUFFIX;
        $where = '1';
        // 参数处理
        $page = input('post.page', 'intval', 1);
        $page_size = input('post.page_size', 'intval', ADMIN_PAGE_SIZE);
        $limit = (($page - 1) * $page_size);
        $id = input('post.id', 'intval', 0);
        $name = sql_format(input('post.name', '', 0));
        $status = input('post.status', '', 'all');
        if ($status == 'off') {
            $where .= " and status = 0";
        } else if ($status == 'on') {
            $where .= " and status = 1";
        }
        if ($id) {
            $where .= " AND `id` = {$id}";
        }
        if ($name) {
            $where .= " AND `name` LIKE '%{$name}%'";
        }

        // 数据处理
        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT {$page_size} OFFSET {$limit}";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as &$val) {
            $val['status_txt'] = ($val['status'] == 1) ? get_tips(10012) : get_tips(10013);
            $val['uptime'] = time_to_local_string($val['uptime']);
            $val['url'] = SHARE_SERVER_URL.'/h5/download?num='.$val['id'];

        }
        $count_sql = "SELECT COUNT(id) as num FROM {$table} WHERE {$where}";
        $total = $this->db->query($count_sql)->affected_rows();

        // 返回数据
        ajax_return(SUCCESS, '', array(
            'list' => $list,
            'page' => $page,
            'total' => (int) $total
        ));
    }

    /**
     * 修改广告渠道状态 partner_status
     *
     * @return void
     */
    public function advert_status()
    {
        $id = input('post.id', 'intval', 0);
        $status = input('post.status', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));
        $table = 'advert_' . DATABASESUFFIX;

        $sql = "UPDATE {$table} SET `status` = {$status} WHERE `id` = {$id}";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 创建广告渠道 
     *
     * @return void
     */
    public function create_advert()
    {
        $table = 'advert_' . DATABASESUFFIX;
        $data = $_POST['data'];
        $name = sql_format($data['name']);
        $url = sql_format($data['url'] ?? '');
        $desc = sql_format($data['desc'] ?? '');
        $id = intval($data['id'] ?? 0);
        $code = str_format($data['code'] ?? '');
        if ($id) {
            $sql = "UPDATE {$table} SET `name` = '{$name}', `desc` = '{$desc}', `code`='{$code}' WHERE `id` = {$id}";
        } else {
            $sql = "INSERT INTO {$table}(`name`, `code`, `desc`, `status`) VALUE('{$name}', '{$code}', '{$desc}', 1)";
        }
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 删除广告渠道
     *
     * @return void
     */
    public function del_advert()
    {
        $id = input('post.id', 'intval', 0);
        if (!$id) ajax_return(ERROR, get_tips(1006));

        $table = 'advert_' . DATABASESUFFIX;
        $sql = "DELETE FROM {$table} WHERE id = {$id}";
        $result = $this->db->query($sql)->affected_rows();

        $result_status = ERROR;
        $result_msg = get_tips(1004);
        if ($result) {
            $result_status = SUCCESS;
            $result_msg = get_tips(1005);
        }

        ajax_return($result_status, $result_msg);
    }

    /**
     * 广告商数据总计
     */
    public function advert_total()
    {
        //接收参数
        $page_size = ADMIN_PAGE_SIZE; //每页显示个数
        $page_no = intval($_POST['page_no'] ?? 1);    //页码
        $appid = DATABASESUFFIX;
        $where = 1;
        $id = intval($_POST['advert_id'] ?? 0);       // 渠道id
        $name = sql_format($_POST['advert_name'] ?? '');
        $offset = ($page_no - 1) * $page_size;
        if ($id) {
            $where .= " and id = $id";
        }
        if ($name) {
            $where .= " and name like %$name%";
        }
        $this->init_db();
        $sql = "select * from advert_$appid where $where limit {$page_size} offset {$offset}";
        $res = $this->db->query($sql)->result_array(); 
        $yes = date('Ymd',strtotime("-1 day"));           // 昨天
        $before_yes = date('Ymd',strtotime("-2 day"));    // 前天
        foreach ($res as &$value) {
            $android = $this->redis->zrevrange('download_android_'.$value['id'], 0, -1, true);
            $ios = $this->redis->zrevrange('download_ios_'.$value['id'], 0, -1, true);
            $value['android'] = array_sum($android);
            $value['ios'] = array_sum($ios);
            $value['android_rate'] = '0%';
            $value['ios_rate'] = '0%';
            if ($android) {
                $yes_num = intval($android[$yes] ?? 0);
                $before_yes_num = intval($android[$before_yes] ?? 0);
                $value['android_rate'] = !$before_yes_num ? '0%' : number_format(($yes_num - $before_yes_num) / $before_yes_num * 100, MONEY_DECIMAL_DIGITS, '.', '') . '%';
            }
            if ($ios) {
                $yes_num = intval($ios[$yes] ?? 0);
                $before_yes_num = intval($ios[$before_yes] ?? 0);
                $value['ios_rate'] = !$before_yes_num ? '0%' : number_format(($yes_num - $before_yes_num) / $before_yes_num * 100, MONEY_DECIMAL_DIGITS, '.', '') . '%';
            }
        }
        $count_sql = "select count(id) as num from advert_$appid";
        $total = $this->db->query($count_sql)->row_array()['num'];
        ajax_return(SUCCESS, '', ['list' => $res, 'total' => intval($total), 'page' => $page_no]);
    }

    /**
     * 广告数据每日統計
     */
    public function advert_day()
    {
        $page_size  = 5;                                                //每页显示日期个数
        $page_no    = intval($_POST['page_no'] ?? 1);                   //页码
        $appid      = DATABASESUFFIX;
        $id         = intval($_POST['advert_id'] ?? 1);                  //廣告渠道id
        $page_size  = isset($_POST['advert_id']) ? ADMIN_PAGE_SIZE : 5;
        $offset     = ($page_no - 1) * $page_size;

        $id         = intval($_POST['advert_id'] ?? 1);                  //廣告渠道id
        $where      = '1';
        $where      .= isset($_POST['advert_id']) ? " AND advert_out.id={$id}" : '';

        $this->init_db();

        $startDate  = date_create('2020-08-20');
        $today      = date_create(date('Y-m-d H:i:s', time()));
        $dateDiff   = date_diff($startDate, $today);
        $diff       = $dateDiff->format("%a");

        $sql    = "SELECT count(advert_out.id) AS total FROM advert_$appid advert_out WHERE $where";
        $rtn    = $this->db->query($sql)->row_array()['total'];
        $total  = $diff * $rtn;

        $data       = [];
        $start      = $offset;
        for ($i = $offset; $i < $diff && $i - $start < $page_size; $i++ ) {

            $searchDate = date("Y-m-d",strtotime("-$i day", time()));

            $sql = "SELECT advert_out.id, advert_out.name, advert_out.status,
                            (SELECT COUNT(`userIos`.platform) 
                                FROM user_" . $appid . "_0 userIos
                                WHERE userIos.advert_id=advert_out.id AND (userIos.platform=2 OR userIos.platform=5) AND date(DATE_ADD(userIos.join_date, INTERVAL 8 HOUR))='{$searchDate}') as iosRegTotal,
                           
                            (SELECT COUNT(`userAndroid`.platform) 
                                FROM user_".$appid."_0 userAndroid 
                                WHERE userAndroid.advert_id=advert_out.id AND (userAndroid.platform=1 OR userAndroid.platform=4) AND date(DATE_ADD(userAndroid.join_date, INTERVAL 8 HOUR))='{$searchDate}')  as androidRegTotal,

                            (SELECT COUNT(`userWeb`.platform) 
                                FROM user_".$appid."_0 userWeb
                                WHERE userWeb.advert_id=advert_out.id AND  userWeb.platform=10  AND date(DATE_ADD(userWeb.join_date, INTERVAL 8 HOUR))='{$searchDate}') as webRegTotal,
                            (SELECT COUNT(`userPc`.platform)     
                                FROM user_".$appid."_0 userPc 
                                WHERE userPc.advert_id=advert_out.id AND  userPc.platform=20  AND date(DATE_ADD(userPc.join_date, INTERVAL 8 HOUR))='{$searchDate}') as pcRegTotal,

                            (SELECT COUNT(`logAndroid`.id)
                                FROM advert_download_log logAndroid 
                                WHERE logAndroid.advert_id=advert_out.id AND  logAndroid.device_type='ANDROID'  AND date(DATE_ADD(logAndroid.create_at, INTERVAL 8 HOUR))='{$searchDate}') as androidDLTotal,
                            (SELECT COUNT(`logIos`.id)
                                FROM advert_download_log logIos 
                                WHERE logIos.advert_id=advert_out.id AND  logIos.device_type='IOS' AND date(DATE_ADD(logIos.create_at, INTERVAL 8 HOUR))='{$searchDate}') as iosDLTotal,
                            (SELECT COUNT(`logWeb`.id)
                                FROM advert_download_log logWeb 
                                WHERE logWeb.advert_id=advert_out.id AND  logWeb.device_type='WEB' AND date(DATE_ADD(logWeb.create_at, INTERVAL 8 HOUR))='{$searchDate}') as webDLTotal,
                            (SELECT COUNT(`logPc`.id)
                                FROM advert_download_log logPc 
                                WHERE logPc.advert_id=advert_out.id AND logPc.device_type='PC' AND date(DATE_ADD(logPc.create_at, INTERVAL 8 HOUR))='{$searchDate}') as pcDLTotal
                            
                        FROM advert_$appid advert_out
                        WHERE $where
                        ORDER BY advert_out.id DESC";

            $res = $this->db->query($sql)->result_array();

            // @file_put_contents(ERRLOG_PATH . '/advert_logs_' . date("Ymd") . '.log',
            //         date("Y-m-d H:i:s") . "\n $sql \n",
            //         FILE_APPEND);
            
            foreach ($res as $value) {
                $data[] = [
                    'date'       => $searchDate,
                    'id'         => $value['id'],
                    'name'       => $value['name'],
                    'dl_ios'        => $value['iosDLTotal'],
                    'dl_android'    => $value['androidDLTotal'],
                    'dl_web'        => $value['webDLTotal'] ,
                    'dl_pc'         => $value['pcDLTotal'] ,
                    'status_txt' => $value['status'] ? '啟用' : '禁用',
                    'reg_android'=> $value['androidRegTotal'],
                    'reg_ios'    => $value['iosRegTotal'],
                    'reg_web'    => $value['webRegTotal'],
                    'reg_pc'     => $value['pcRegTotal'],
                ];
            }
                
        }
        
        // @file_put_contents(ERRLOG_PATH . '/advert_logs_' . date("Ymd") . '.log',
        //             date("Y-m-d H:i:s") . " $total - $page_no - $diff -  \n",
        //             FILE_APPEND);
        ajax_return(SUCCESS, '', ['list' => $data, 'total' => $total, 'page' => $page_no, 'limit' => ($rtn * $page_size) ?? 0]);
    }
    
}
