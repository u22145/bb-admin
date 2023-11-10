<?php

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/8/9
 * Time: 17:39
 */
class Resource extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load_model('common_model');
    }

    /**
     * 礼物列表
     */
    public function gift_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $gift = sql_format($_POST['gift'] ?? '');
        $status = intval($_POST['status'] == '' ? 3 : $_POST['status']);
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        $where = '1';
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($gift) {
            $where .= " AND gift LIKE '%$gift%'";
        }
        if ($status < 3) {
            $where .= ' AND status = ' . $status;
        }

        $field = "id, gift, fee, gift_pic, status, location, effect";
        $sql = "SELECT $field FROM cat_gift_" . DATABASESUFFIX . ' WHERE ' . $where . ' ORDER BY location asc,uptime desc LIMIT ' . $offset . ',' . $page_size;
        $list = $this->db->query($sql)->result_array();
        $sql = "SELECT COUNT(id) AS total FROM cat_gift_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();
        foreach ($list as &$gift) {
            $gift['gift_url'] = get_pic_url($gift['gift_pic']);
            $gift['fee']      = number_format($gift['fee'], MONEY_DECIMAL_DIGITS, '.', '');
        }

        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 导出礼物列表
     */
    public function export_gift_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $gift = sql_format($_POST['gift'] ?? '');
        $status = intval($_POST['status'] ?? 0);
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        $where = '1';
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($gift) {
            $where .= " AND gift LIKE '%$gift%'";
        }
        if ($status) {
            $where .= ' AND status=' . $status;
        }

        $field = "id,gift_pic,gift,fee,location,status";
        $sql = "SELECT $field FROM cat_gift_" . DATABASESUFFIX . ' WHERE ' . $where . ' ORDER BY id DESC LIMIT ' . $offset . ',' . $page_size;
        $list = $this->db->query($sql)->result_array();

        foreach ($list as &$v) {
            $v['gift_pic'] = get_pic_url($v['gift_pic']);
            switch ($v['status']) {
                case 0:
                    $v['status'] = get_tips(1020);
                    break;
                case 1:
                    $v['status'] = get_tips(1019);
                    break;
            }
        }

        $title = [
            get_tips(6001),
            get_tips(6002),
            get_tips(6003),
            get_tips(6004),
            get_tips(6005),
            get_tips(6006),
        ];
        $this->load_model('common_model');

        $path = $this->common_model->export_excel('gift_list', $title, $list);
        ajax_return(SUCCESS, get_tips(1002), ['path' => '/' . $path]);
    }

    /**
     * 礼物添加
     */
    public function gift_add()
    {
        $gift = sql_format($_POST['gift'] ?? '');
        $fee = floatval($_POST['fee'] ?? 0);
        $location = intval($_POST['location'] ?? 1);
        $effect = strval($_POST['effect'] ?? '');
        $gift_pic = sql_format($_POST['gift_pic'] ?? '');

        if (!$gift) {
            ajax_return(ERROR, get_tips(6007));
        }

        if ($fee < 0) {
            ajax_return(ERROR, get_tips(6008));
        }

        if (!$gift_pic) {
            ajax_return(ERROR, get_tips(6009));
        }

        $sql = "INSERT INTO cat_gift_" . DATABASESUFFIX . "(gift, fee, location, gift_pic, effect) VALUES ('$gift', $fee, $location, '$gift_pic', '$effect')";
        $insert_id = $this->db->query($sql)->insert_id();

        //更新礼物对应金额
        $this->redis->hset("cat:gift", $insert_id, $fee);

        //删除礼物缓存
        $this->init_mcache()->delete("gift");

        //全部下线
        $sql = "update cat_gift_" . DATABASESUFFIX . " set status = 0 where location = $location";
        $this->db->query($sql);
        //当前上线
        $sql = "update cat_gift_" . DATABASESUFFIX . " set status = 1 where id = $insert_id";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(1005));
    }

    public function add_video()
    {
        ajax_return(1, 1, 1);
        if (!isset($_FILES['file'])) {
            ajax_return(ERROR, get_tips(6010));
        }

        $type_list = [
            'banner',
            'gift',
            'ad_pic',
            'ad_video',
            'ad_owner_logo',
        ];

        $type = sql_format($_POST['type'] ?? '');
        if (!in_array($type, $type_list)) {
            ajax_return(ERROR, get_tips(6011));
        }

        $media_type = ($type == 'ad_video') ? 'file' : 'image';
        $file_info = pathinfo($_FILES['file']['name']);
        $ext = '.' . strtolower($file_info['extension']);
        $file_name = date('His') . $ext;
        $file_path = '/' . $type . '/' . date('Ymd') . '/';

        $upload = new Upload();
        $res = $upload->run_upload('file', $file_path . $file_name, 'file');
        if (!$res['status']) {
            ajax_return(ERROR, $res['msg']);
        }
        ajax_return(SUCCESS, "", ['path' => $res['file_name'], 'msg' => $res['msg']]);
    }

    /**
     * 图片、视频、音频单文件上传
     */
    public function upload_pic()
    {
        $uid = intval($_POST['uid'] ?? 0);
        if (!isset($_FILES['file'])) {
            ajax_return(ERROR, get_tips(6010));
        }

        $type_list = [
            'banner',
            'gift',
            'ad_pic',
            'ad_video',
            'ad_owner_logo',
            'avatar',
            'qrcode',
            'voucher',
            'channel'
        ];

        $type = sql_format($_POST['type'] ?? '');
        if (!in_array($type, $type_list)) {
            ajax_return(ERROR, get_tips(6011));
        }

        $media_type = ($type == 'ad_video') ? 'file' : 'image';
        $file_info = pathinfo($_FILES['file']['name']);
        $ext = '.' . strtolower($file_info['extension']);

        if ($uid > 0) {
            $file_name = $uid . $ext;
        } else {
            $file_name = date('His') . $ext;
        }
        $file_path = '/' . $type . '/';

        $upload = new Upload();
        $res = $upload->run_upload('file', $file_path . $file_name, $media_type);
        if (!$res['status']) {
            ajax_return(ERROR, $res['msg']);
        }
        ajax_return(SUCCESS, "", ['path' => $res['file_name'], 'msg' => $res['msg']]);
    }

    /**
     * 礼物素材上传
     */
    public function gift_upload()
    {
        $this->upload_pic();
    }

    /**
     * banner素材上传
     */
    public function banner_upload()
    {
        $this->upload_pic();
    }

    /**
     * 广告素材上传
     */
    public function ad_upload()
    {
        $this->upload_pic();
    }

    /**
     * 重置头像
     */
    public function reset_upload()
    {
        $this->upload_pic();
    }

    /**
     * 上传二维码
     */
    public function qrcode_upload()
    {
        $this->upload_pic();
    }

    /**
     * 广告商素材上传
     */
    public function ad_owner_upload()
    {
        $this->upload_pic();
    }

    /**
     * 订单凭证上传
     */
    public function voucher_upload()
    {
        $this->upload_pic();
    }

    /**
     * 礼物修改
     */
    public function gift_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $gift = sql_format($_POST['gift'] ?? '');
        $fee = floatval($_POST['fee'] ?? 0);
        $location = intval($_POST['location'] ?? 0);
        $effect = strval($_POST['effect'] ?? '');
        $gift_pic = sql_format($_POST['gift_pic'] ?? '');

        if (!$gift) {
            ajax_return(ERROR, get_tips(6007));
        }

        if ($fee < 0) {
            ajax_return(ERROR, get_tips(6008));
        }

        $set = "gift = '{$gift}', fee = $fee, location = $location, effect = '$effect'";
        if ($gift_pic) {
            $set .= " ,gift_pic = '{$gift_pic}'";
        }

        $sql = "UPDATE cat_gift_" . DATABASESUFFIX . " SET {$set} WHERE id= {$id}";
        $this->db->query($sql);

        //全部下线
        $sql = "update cat_gift_" . DATABASESUFFIX . " set status = 0 where location = $location";
        $this->db->query($sql);
        //当前上线
        $sql = "update cat_gift_" . DATABASESUFFIX . " set status = 1 where id = $id";
        $this->db->query($sql);

        //更新礼物对应金额
        $this->redis->hset("cat:gift", $id, $fee);

        //删除礼物缓存
        $this->init_mcache()->delete("gift");
        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * 删除礼物
     */
    public function gift_del()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $sql = "delete from cat_gift_" . DATABASESUFFIX . " where id = $id";
            $this->db->query($sql);

            //删除礼物缓存
            $this->init_mcache()->delete("gift");

            //删除礼物对应金额
            $this->redis->hdel("cat:gift", $id);

            ajax_return(SUCCESS, get_tips(1005));
        }
        ajax_return(0, get_tips(1004));
    }

    /**
     * 礼物状态更新
     */
    public function gift_update_status()
    {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $sql = "select status,fee from cat_gift_" . DATABASESUFFIX . " where id = $id";
            $res = $this->db->query($sql)->row_array();
            if (!$res) {
                ajax_return(0, get_tips(1004));
            }
            if (1 == $res['status']) {
                $status = 0;
                $desc = get_tips(1020);

                //删除礼物对应金额
                $this->redis->hdel("cat:gift", $id);
            } else {
                $status = 1;
                $desc = get_tips(1019);

                //更新礼物对应金额
                $this->redis->hset("cat:gift", $id, $res['fee']);
            }

            $sql = "UPDATE cat_gift_" . DATABASESUFFIX . " SET status = $status WHERE id = $id";
            $this->db->query($sql);

            //删除礼物缓存
            $this->init_mcache()->delete("gift");

            ajax_return(SUCCESS, get_tips(1005));
        }
        ajax_return(0, get_tips(1004));
    }

    /**
     * 定价列表
     */
    public function priced_list()
    {
        $list = [
            ['coin' => 'EURC', 'full_name' => 'EURC', 'coin_type' => '稳定币', 'price' => ''],
            ['coin' => 'MSQ', 'full_name' => 'MSQ TOKEN', 'coin_type' => '涨幅币', 'price' => ''],
        ];
        $field = "eurc_exchange_rate,msq_exchange_rate";
        $sql = "SELECT $field FROM cat_currency_" . DATABASESUFFIX . " WHERE currency='EUR'";
        $res = $this->db->query($sql)->row_array();

        $list[0]['price'] = round($res['eurc_exchange_rate']);
        $list[1]['price'] = round($res['msq_exchange_rate']);

        ajax_return(SUCCESS, "", $list);
    }

    /**
     * 定价编辑
     */
    public function priced_edit()
    {
        $price = floatval($_POST['price'] ?? 0);
        $coin = sql_format($_POST['coin'] ?? '');

        switch ($coin) {
            case 'EURC':
                $set = 'eurc_exchange_rate=' . $price;
                break;
            case 'MSQ':
                $set = 'msq_exchange_rate=' . $price;
                break;
            default:
                break;
        }
        $sql = "UPDATE cat_currency_" . DATABASESUFFIX . " SET $set WHERE currency='EUR'";
        $this->db->query($sql);
        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * 推送列表
     */
    public function push_list()
    {
        $receiver = intval($_POST['receiver'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $start_time = trim($_POST['start_time'] ?? '');
        $end_time = trim($_POST['end_time'] ?? '');
        $redirect = intval($_POST['redirect'] ?? 0);

        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        $where = '1';
        if (isset($_POST['receiver']) && is_numeric($_POST['receiver'])) {
            $where .= ' AND receiver=' . $receiver;
        }

        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }

        if ($start_time) {
            if ($end_time) {
                $where .= ' AND push_time between "' . $start_time . '" AND "' . $end_time . '"';
            } else {
                $where .= ' AND push_time > "' . $start_time . '"';
            }
        } else {
            if ($end_time) {
                $where .= ' AND push_time < "' . $end_time . '"';
            }
        }

        if (isset($_POST['redirect']) && is_numeric($_POST['redirect'])) {
            $where .= ' AND redirect=' . $redirect;
        }
        $field = "id,msg,receiver,active,redirect,push_time,status";
        $sql = "SELECT $field FROM app_message_push_" . DATABASESUFFIX . " WHERE " . $where . " ORDER BY id DESC LIMIT " . $offset . "," . $page_size;

        $list = $this->db->query($sql)->result_array();
        $sql = "SELECT COUNT(id) AS total FROM app_message_push_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();

        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 推送添加
     */
    public function push_add()
    {
        $msg = sql_format($_POST['msg'] ?? '');
        $receiver = intval($_POST['receiver'] ?? 1);
        $active = intval($_POST['active'] ?? 1);
        $redirect = intval($_POST['redirect'] ?? 1);
        $push_time = sql_format($_POST['push_time'] ?? date('Y-m-d H:i:s'));

        $sql = "INSERT INTO app_message_push_" . DATABASESUFFIX . " (msg,receiver,active,redirect,push_time) VALUES ('$msg',$receiver,$active,$redirect,'$push_time')";
        $insert_id = $this->db->query($sql)->insert_id();

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 推送编辑
     */
    public function push_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $msg = sql_format($_POST['msg'] ?? '');
        $receiver = intval($_POST['receiver'] ?? 1);
        $active = intval($_POST['active'] ?? 1);
        $redirect = intval($_POST['redirect'] ?? 1);
        $push_time = sql_format($_POST['push_time'] ?? date('Y-m-d H:i:s'));

        $sql = "UPDATE app_message_push_" . DATABASESUFFIX . " SET msg='$msg',receiver=$receiver,active=$active,redirect=$redirect,push_time='$push_time' WHERE id=$id";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * banner列表
     */
    public function banner_list()
    {
        // 接受参数
        $id = intval($_POST['id'] ?? 0);
        $redirect = intval($_POST['redirect'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $banner_type = intval($_POST['banner_type'] ?? 0);
        $location = intval($_POST['location'] ?? 0);
        $show_crowd = intval($_POST['show_crowd'] ?? 0);
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;
        $where = '1';

        // 条件处理
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($redirect) {
            $where .= ' AND redirect=' . $redirect;
        }
        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }
        if ($banner_type) {
            $where .= ' AND banner_type=' . $banner_type;
        }
        if ($location) {
            $where .= ' AND location=' . $location;
        }
        if ($show_crowd) {
            $where .= ' AND show_crowd=' . $show_crowd;
        }

        // 查询数据
        $field = "id, pic, banner_type, location, ctime, redirect, url, show_crowd, status, banner_desc";
        $sql = "SELECT $field FROM ad_banner_" . DATABASESUFFIX . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as &$banner) {
            $banner['banner_pic'] = get_pic_url($banner['pic']);
            if ($banner['redirect'] == 4) {
                $banner['redirect'] = "2";
            } else if ($banner['redirect'] == 3 || $banner['redirect'] == 2 || $banner['redirect'] == 1) {
                $banner['redirect'] = "1";
            }
        }
        $sql = "SELECT COUNT(id) AS total FROM ad_banner_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();

        // 返回数据
        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 导出banner列表
     */
    public function export_banner_list()
    {
        // 接受参数
        $id = intval($_POST['id'] ?? 0);
        $redirect = intval($_POST['redirect'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $banner_type = intval($_POST['banner_type'] ?? 0);
        $location = intval($_POST['sort'] ?? 0);
        $show_crowd = intval($_POST['show_crowd'] ?? 0);
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;
        $where = '1';

        //处理条件
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($redirect) {
            $where .= ' AND redirect=' . $redirect;
        }
        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }
        if ($banner_type) {
            $where .= ' AND banner_type=' . $banner_type;
        }
        if ($location) {
            $where .= ' AND location=' . $location;
        }
        if ($show_crowd) {
            $where .= ' AND show_crowd=' . $show_crowd;
        }

        // 查询数据
        $field = "id,pic,banner_type,location,ctime,redirect,url,show_crowd,banner_desc,status";
        $sql = "SELECT $field FROM ad_banner_" . DATABASESUFFIX . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();

        foreach ($list as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $v['status'] = get_tips(1019);
                    break;
                case 1:
                    $v['status'] = get_tips(1020);
                    break;
                case 2:
                    $v['status'] = get_tips(1021);
                    break;
                default:
                    break;
            }
            switch ($v['banner_type']) {
                case 1:
                    $v['banner_type'] = get_tips(6012);
                    break;
                case 2:
                    $v['banner_type'] = get_tips(6013);
                    break;
                default:
                    break;
            }
            switch ($v['redirect']) {
                case 1:
                    $v['redirect'] = get_tips(6014);
                    break;
                case 2:
                    $v['redirect'] = get_tips(6015);
                    break;
                default:
                    break;
            }
            switch ($v['show_crowd']) {
                case 0:
                    $v['show_crowd'] = get_tips(6016);
                    break;
                case 1:
                    $v['show_crowd'] = get_tips(6017);
                    break;
                case 2:
                    $v['show_crowd'] = get_tips(6018);
                    break;
                default:
                    break;
            }
            $list[$k] = $v;
        }

        $title = [
            get_tips(6019),
            get_tips(6020),
            get_tips(6021),
            get_tips(6022),
            get_tips(1001),
            get_tips(6023),
            get_tips(6024),
            get_tips(6025),
            get_tips(6026),
            get_tips(5023),
        ];

        $this->load_model('common_model');
        $path = $this->common_model->export_excel('banner_list', $title, $list);
        ajax_return(SUCCESS, get_tips(1002), ['path' => '/' . $path]);
    }

    /**
     * banner添加
     */
    public function banner_add()
    {
        $pic = sql_format($_POST['pic'] ?? '');
        $redirect = intval($_POST['redirect'] ?? 1);
        if ($redirect == 1) {
            $url = intval($_POST['url'] ?? 1);
            $redirect = $url;
        } else {
            $redirect = 4;
            $url = sql_format($_POST['url'] ?? '');
        }
        $show_crowd = intval($_POST['show_crowd'] ?? 0);
        $banner_desc = sql_format($_POST['banner_desc'] ?? '');
        $banner_type = intval($_POST['banner_type'] ?? 1);
        $location = intval($_POST['location'] ?? 1);

        if (!$pic) {
            ajax_return(ERROR, get_tips(6009));
        }

        $sql = "SELECT id FROM ad_banner_" . DATABASESUFFIX . " WHERE location=$location AND status = 0";
        $res = $this->db->query($sql)->row_array();

        $sql = "INSERT INTO ad_banner_" . DATABASESUFFIX . " (pic,redirect,url,show_crowd,banner_desc,banner_type,location,ctime) VALUES
        ('$pic',$redirect,'$url',$show_crowd,'$banner_desc',$banner_type,$location,'" . date('Y-m-d H:i:s') . "')";
        $insert_id = $this->db->query($sql)->insert_id();

        if ($res) {
            //所有banner改为下线
            $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET status = 1 WHERE location = $location";
            $this->db->query($sql);

            //当前banner启用
            $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET status = 0 WHERE id = $insert_id";
            $this->db->query($sql);
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * banner编辑
     */
    public function banner_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $pic = sql_format($_POST['pic'] ?? '');
        $redirect = intval($_POST['redirect'] ?? 1);
        if ($redirect == 1) {
            $url = intval($_POST['url'] ?? 1);
            $redirect = $url;
        } else {
            $redirect = 4;
            $url = sql_format($_POST['url'] ?? '');
        }
        $show_crowd = intval($_POST['show_crowd'] ?? 0);
        $banner_desc = sql_format($_POST['banner_desc'] ?? '');
        $banner_type = intval($_POST['banner_type'] ?? 1);
        $location = intval($_POST['location'] ?? 1);

        if (!$pic) {
            ajax_return(ERROR, get_tips(6009));
        }

        $sql = "SELECT id FROM ad_banner_" . DATABASESUFFIX . " WHERE location = $location AND status = 0";
        $res = $this->db->query($sql)->row_array();

        $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET pic = '$pic', redirect = $redirect, url = '$url', show_crowd = $show_crowd, banner_desc = '$banner_desc', banner_type = $banner_type, location = $location WHERE id = $id";
        $this->db->query($sql);

        //查询是否有相同banner位置的
        if ($res && $res['id'] != $id) {
            //所有banner改为下线
            $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET status = 1 WHERE location = $location";
            $this->db->query($sql);
            //当前banner启用
            $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET status = 0 WHERE id = $id";
            $this->db->query($sql);
        }

        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * banner更改状态
     */
    public function banner_update_status()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        if ($status == 2) {
            $sql = "delete from ad_banner_" . DATABASESUFFIX . " where id={$id}";
        } else {
            $sql = "UPDATE ad_banner_" . DATABASESUFFIX . " SET status=$status WHERE id=$id";
        }
        $this->db->query($sql);

        switch ($status) {
            case 0:
                $desc = get_tips(1019);
                break;
            case 1:
                $desc = get_tips(1020);
                break;
            case 2:
                $desc = get_tips(1021);
                break;
            default:
                $desc = get_tips(6027);
                break;
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 广告列表
     */
    public function ad_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $ad_title = sql_format($_POST['ad_title'] ?? '');
        $ad_owner_name = sql_format($_POST['ad_owner_name'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $ctime_start = sql_format($_POST['ctime_start'] ?? '');
        $ctime_end = sql_format($_POST['ctime_end'] ?? '');
        $expire_time_start = sql_format($_POST['expire_time_start'] ?? '');
        $expire_time_end = sql_format($_POST['expire_time_end'] ?? '');
        $where = '1';

        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($ad_title) {
            $where .= ' AND ad_title LIKE "%' . $ad_title . '%"';
        }

        if ($ad_owner_name) {
            $where .= " AND owner_name like '%$ad_owner_name%'";
        }

        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }
        if ($ctime_start) {
            if ($ctime_end) {
                $where .= ' AND ctime BETWEEN "' . $ctime_end . '" AND "' . $ctime_end . '"';
            } else {
                $where .= ' AND ctime > "' . $ctime_start . '"';
            }
        } else {
            if ($ctime_end) {
                $where .= ' AND ctime < "' . $ctime_end . '"';
            }
        }
        if ($expire_time_start) {
            if ($expire_time_end) {
                $where .= ' AND expire_time BETWEEN "' . $expire_time_end . '" AND "' . $expire_time_end . '"';
            } else {
                $where .= ' AND expire_time > ' . $expire_time_start;
            }
        } else {
            if ($expire_time_end) {
                $where .= ' AND expire_time < ' . $expire_time_end;
            }
        }
        $field = "id, ad_title, ad_pic pic, ad_url, ad_video, ctime, expire_time, click_count, status, owner_name, ad_owner_id, type, location";
        $sql = "SELECT $field FROM ad_content_" . DATABASESUFFIX  . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as $k => &$item) {
            $list[$k]['ad_pic'][0] = get_pic_url($item['pic']);
            $item['ad_video'] = get_pic_url($item['ad_video']);
        }
        $sql = "SELECT COUNT(id) AS total FROM ad_content_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();

        $sql = "select ad_owner_name, id, logo from ad_owner_" . DATABASESUFFIX . " where status = 0";
        $owner_res = $this->db->query($sql)->result_array();

        $ret = [
            'data' => $list,
            'owner_res' => $owner_res,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 导出广告列表
     */
    public function export_ad_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $ad_title = sql_format($_POST['ad_title'] ?? '');
        $ad_owner_id = intval($_POST['ad_owner_id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        $ctime_start = sql_format($_POST['ctime_start'] ?? '');
        $ctime_end = sql_format($_POST['ctime_end'] ?? '');
        $expire_time_start = sql_format($_POST['expire_time_start'] ?? '');
        $expire_time_end = sql_format($_POST['expire_time_end'] ?? '');
        $where = '1';

        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;

        if ($id) {
            $where .= ' AND c.id=' . $id;
        }
        if ($ad_title) {
            $where .= ' AND c.ad_title LIKE "%' . $ad_owner_id . '%"';
        }
        if ($ad_owner_id) {
            $where .= ' AND c.ad_owner_id=' . $ad_owner_id;
        }
        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND c.status=' . $status;
        }
        if ($ctime_start) {
            if ($ctime_end) {
                $where .= ' AND c.ctime BETWEEN "' . $ctime_end . '" AND "' . $ctime_end . '"';
            } else {
                $where .= ' AND c.ctime > "' . $ctime_start . '"';
            }
        } else {
            if ($ctime_end) {
                $where .= ' AND c.ctime < "' . $ctime_end . '"';
            }
        }
        if ($expire_time_start) {
            if ($expire_time_end) {
                $where .= ' AND c.expire_time BETWEEN "' . $expire_time_end . '" AND "' . $expire_time_end . '"';
            } else {
                $where .= ' AND c.expire_time > ' . $expire_time_start;
            }
        } else {
            if ($expire_time_end) {
                $where .= ' AND c.expire_time < ' . $expire_time_end;
            }
        }
        $field = "c.id,c.ad_title,c.ad_pic,c.ad_url,c.ctime,c.expire_time,c.click_count,o.ad_owner_name,c.status";
        $sql = "SELECT $field FROM ad_content_" . DATABASESUFFIX . " AS c LEFT JOIN ad_owner_" . DATABASESUFFIX . " AS o ON c.ad_owner_id=o.id WHERE $where ORDER BY c.id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();

        foreach ($list as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $v['status'] = get_tips(1019);
                    break;
                case 1:
                    $v['status'] = get_tips(1020);
                    break;
                case 2:
                    $v['status'] = get_tips(1021);
                    break;
                default:
                    break;
            }
            $list[$k] = $v;
        }
        $title = [
            get_tips(6028),
            get_tips(6029),
            get_tips(6030),
            get_tips(6031),
            get_tips(1001),
            get_tips(6032),
            get_tips(6033),
            get_tips(6034),
            get_tips(5023),
        ];

        $this->load_model('common_model');
        $path = $this->common_model->export_excel('ad_list', $title, $list);
        ajax_return(SUCCESS, get_tips(1002), ['path' => '/' . $path]);
    }

    /**
     * 广告添加
     */
    public function ad_add()
    {
        $ad_title = sql_format($_POST['ad_title'] ?? '');
        $ad_pic = trim_domain(sql_format($_POST['ad_pic'] ?? ''));
        $ad_url = sql_format($_POST['ad_url'] ?? '');
        $ad_video = trim_domain(sql_format($_POST['ad_video'] ?? ''));
        $expire_time = sql_format($_POST['expire_time'] ?? date('Y-m-d H:i:s'));
        $ad_owner_id = intval($_POST['ad_owner_id'] ?? 0);
        $location = intval($_POST['location'] ?? 0);
        $ctime = date('Y-m-d H:i:s');
        $ad_logo = sql_format($_POST['ad_logo'] ?? '');
        $owner_name = sql_format($_POST['ad_owner_name'] ?? '');
        $type = intval($_POST['ad_type'] ?? 1);

        if (!$ad_title) {
            ajax_return(ERROR, get_tips(6035));
        }
        if (!$ad_pic) {
            ajax_return(ERROR, get_tips(6036));
        }
        if (!$ad_url) {
            ajax_return(ERROR, get_tips(6037));
        }
        if (!$ad_owner_id) {
            ajax_return(ERROR, get_tips(6038));
        }
        if ($ad_video) {
            if (!$ad_pic) {
                ajax_return(ERROR, get_tips(6039));
            }
        }

        $sql = "INSERT INTO ad_content_" . DATABASESUFFIX . " (ad_title, ad_pic, ad_url, expire_time, ad_owner_id, location, ctime, ad_video, ad_logo, owner_name, type) VALUES 
        ('$ad_title','$ad_pic','$ad_url','$expire_time',$ad_owner_id,$location,'$ctime','$ad_video', '$ad_logo', '$owner_name', $type)";
        $insert_id = $this->db->query($sql)->insert_id();

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 广告编辑
     */
    public function ad_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $ad_title = sql_format($_POST['ad_title'] ?? '');
        $ad_pic = trim_domain(sql_format($_POST['ad_pic'] ?? ''));
        $ad_video = trim_domain(sql_format($_POST['ad_video'] ?? ''));
        $ad_url = sql_format($_POST['ad_url'] ?? '');
        $expire_time = sql_format($_POST['expire_time'] ?? date('Y-m-d H:i:s'));
        $ad_owner_id = intval($_POST['ad_owner_id'] ?? 0);
        $location = intval($_POST['location'] ?? 0);
        $ad_logo = sql_format($_POST['ad_logo'] ?? '');
        $owner_name = sql_format($_POST['ad_owner_name'] ?? '');
        $type = intval($_POST['ad_type'] ?? 1);

        if (!$ad_title) {
            ajax_return(ERROR, get_tips(6035));
        }
        if (!$ad_pic) {
            ajax_return(ERROR, get_tips(6036));
        }
        if (!$ad_url) {
            ajax_return(ERROR, get_tips(6037));
        }
        if (!$ad_owner_id) {
            ajax_return(ERROR, get_tips(6038));
        }

        $sql = "UPDATE ad_content_" . DATABASESUFFIX . " SET ad_video = '$ad_video', ad_logo = '$ad_logo',owner_name = '$owner_name', type = '$type', ad_title='$ad_title',ad_pic='$ad_pic',ad_url='$ad_url',expire_time='$expire_time',ad_owner_id=$ad_owner_id,location=$location
         WHERE id=$id";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * 广告状态更新
     */
    public function ad_update_status()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        $sql = "UPDATE ad_content_" . DATABASESUFFIX . " SET status=$status WHERE id=$id";
        $this->db->query($sql);

        switch ($status) {
            case 0:
                $desc = get_tips(1019);
                break;
            case 1:
                $desc = get_tips(1020);
                break;
            case 2:
                $desc = get_tips(1021);
                break;
            default:
                $desc = get_tips(6027);
                break;
        }

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 广告删除
     */
    public function ad_del()
    {
        $id = intval($_POST['id'] ?? 0);

        $sql = "delete from ad_content_" . DATABASESUFFIX . " WHERE id=$id";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 广告商列表
     */
    public function ad_owner_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $ad_owner_name = sql_format($_POST['ad_owner_name'] ?? '');
        $status = intval($_POST['status'] ?? 0);
        $ctime_start = sql_format($_POST['ctime_start'] ?? '');
        $ctime_end = sql_format($_POST['ctime_end'] ?? '');
        $where = '1';
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($ad_owner_name) {
            $where .= ' AND ad_owner_name LIKE "%' . $ad_owner_name . '%"';
        }
        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }
        if ($ctime_start) {
            if ($ctime_end) {
                $where .= ' AND ctime BETWEEN "' . $ctime_end . '" AND "' . $ctime_end . '"';
            } else {
                $where .= ' AND ctime > "' . $ctime_start . '"';
            }
        } else {
            if ($ctime_end) {
                $where .= ' AND ctime < "' . $ctime_end . '"';
            }
        }
        $field = "id,ad_owner_name,logo,ctime,status";
        $sql = "SELECT $field FROM ad_owner_" . DATABASESUFFIX . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();
        foreach ($list as &$item) {
            $item['logo'] = get_pic_url($item['logo']);
        }
        $sql = "SELECT COUNT(id) AS total FROM ad_owner_" . DATABASESUFFIX . " WHERE $where";
        $res = $this->db->query($sql)->row_array();

        $ret = [
            'data' => $list,
            'page_no' => $page_no,
            'page_size' => $page_size,
            'page_count' => floor($res['total'] / $page_size) + 1,
            'total' => (int) $res['total'],
        ];

        ajax_return(SUCCESS, "", $ret);
    }

    /**
     * 导出广告商列表
     */
    public function export_ad_owner_list()
    {
        $id = intval($_POST['id'] ?? 0);
        $ad_owner_name = sql_format($_POST['ad_owner_name'] ?? '');
        $status = intval($_POST['status'] ?? 0);
        $ctime_start = sql_format($_POST['ctime_start'] ?? '');
        $ctime_end = sql_format($_POST['ctime_end'] ?? '');
        $where = '1';
        $page_no = intval($_POST['page_no'] ?? 1) ?: 1;
        $page_size = intval($_POST['page_size'] ?? ADMIN_PAGE_SIZE) ?: ADMIN_PAGE_SIZE;
        $offset = ($page_no - 1) * $page_size;
        if ($id) {
            $where .= ' AND id=' . $id;
        }
        if ($ad_owner_name) {
            $where .= ' AND ad_owner_name LIKE "%' . $ad_owner_name . '%"';
        }
        if (isset($_POST['status']) && is_numeric($_POST['status'])) {
            $where .= ' AND status=' . $status;
        }
        if ($ctime_start) {
            if ($ctime_end) {
                $where .= ' AND ctime BETWEEN "' . $ctime_end . '" AND "' . $ctime_end . '"';
            } else {
                $where .= ' AND ctime > "' . $ctime_start . '"';
            }
        } else {
            if ($ctime_end) {
                $where .= ' AND ctime < "' . $ctime_end . '"';
            }
        }
        $field = "id,ad_owner_name,logo,ctime,status";
        $sql = "SELECT $field FROM ad_owner_" . DATABASESUFFIX . " WHERE $where ORDER BY id DESC LIMIT $offset,$page_size";
        $list = $this->db->query($sql)->result_array();

        foreach ($list as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $v['status'] = get_tips(1019);
                    break;
                case 1:
                    $v['status'] = get_tips(1020);
                    break;
                case 2:
                    $v['status'] = get_tips(1021);
                    break;
                default:
                    break;
            }
            $list[$k] = $v;
        }
        $title = [
            get_tips(6040),
            get_tips(6041),
            get_tips(6042),
            get_tips(1001),
            get_tips(5023),
        ];
        $this->load_model('common_model');

        $path = $this->common_model->export_excel('ad_owner_list', $title, $list);
        ajax_return(SUCCESS, get_tips(1002), ['path' => '/' . $path]);
    }

    /**
     * 广告商添加
     */
    public function ad_owner_add()
    {
        $logo = sql_format($_POST['logo'] ?? '');
        $ad_owner_name = sql_format($_POST['ad_owner_name'] ?? '');
        $ctime = date('Y-m-d H:i:s');

        if (!$logo) {
            ajax_return(ERROR, get_tips(6043));
        }
        if (!$ad_owner_name) {
            ajax_return(ERROR, get_tips(6044));
        }
        $sql = "INSERT INTO ad_owner_" . DATABASESUFFIX . " (logo,ad_owner_name,ctime) VALUES ('$logo','$ad_owner_name','$ctime')";
        $insert_id = $this->db->query($sql)->insert_id();

        ajax_return(SUCCESS, get_tips(1005));
    }

    /**
     * 广告商编辑
     */
    public function ad_owner_edit()
    {
        $id = intval($_POST['id'] ?? 0);
        $logo = sql_format($_POST['logo'] ?? '');
        $ad_owner_name = sql_format($_POST['ad_owner_name'] ?? '');

        if (!$logo) {
            ajax_return(ERROR, get_tips(6043));
        }
        if (!$ad_owner_name) {
            ajax_return(ERROR, get_tips(6044));
        }
        $sql = "UPDATE ad_owner_" . DATABASESUFFIX . " SET logo='$logo',ad_owner_name = '$ad_owner_name' WHERE id = $id";
        $this->db->query($sql);
        ajax_return(SUCCESS, get_tips(1014));
    }

    /**
     * 广告商状态更新
     */
    public function ad_owner_update_status()
    {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        $sql = "UPDATE ad_owner_" . DATABASESUFFIX . " SET status = $status WHERE id = $id";
        $this->db->query($sql);

        switch ($status) {
            case 0:
                $desc = get_tips(1019);
                break;
            case 1:
                $desc = get_tips(1020);
                break;
            case 2:
                $desc = get_tips(1021);
                break;
            default:
                $desc = get_tips(6045);
                break;
        }

        ajax_return(SUCCESS);
    }

    /**
     * 删除广告商
     */
    public function ad_owner_del_status()
    {
        $id = intval($_POST['id'] ?? 0);

        if (empty($id)) {
            ajax_return(ERROR, get_tips(6046));
        }
        $sql = "delete from ad_owner_" . DATABASESUFFIX . "  WHERE id = $id";
        $this->db->query($sql);

        ajax_return(SUCCESS, get_tips(6046));
    }


    /**
     * 分销提成配置
     */
    public function pyra_list()
    { }

    /**
     * 分销提成编辑
     */
    public function pyra_edit()
    { }
}
