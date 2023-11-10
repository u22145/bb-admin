<?php

class Pay extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  提现复核
     */
    public function trade_check()
    {
        $id   = intval($_POST['id']);
        $type = intval($_POST['type']);
        $admin_id   = $this->user['id'];
        $admin_time = date('Y-m-d H:i:s', time());
        (!$id || !$type) && ajax_return(ERROR, get_tips(18001));

        // 查看是不是审核过
        $this->init_db();
        $sql    = "select * from pay_withdraw_1 where id = $id limit 1";
        $result = $this->db->query($sql)->row_array();
        ($result['status'] != 1 ) && ajax_return(ERROR, '运营还没有审核通过哦');

        // 业务逻辑处理
        $sql = "UPDATE pay_withdraw_1 SET pay_status = {$type}, pay_admin_id = $admin_id, pay_admin_time = '$admin_time'  WHERE id = {$id}";
        $res = $this->db->query($sql)->affected_rows();

        $res && ajax_return(1, get_tips(18002));
        ajax_return(ERROR, '网络繁忙');
    }


}