<?php
/**
 * 数据库连接类
 */
defined('BASEPATH') or exit('No direct script access allowed');

class Database
{
    /**
     * @var mysqli
     */
    private $_mysqli;
    private $_result;
    private $_debug;
    
    // 连接数据库
    public function connect($address, $username, $password, $db_name, $port, $charset = 'utf8mb4')
    {
        $this->_mysqli = new mysqli($address, $username, $password, $db_name, $port);
        $this->_mysqli->set_charset($charset);
    }
    
    // 自定义SQL查询语句
    public function query($sql)
    {
        $btime = microtime(true);
        $this->_result = $this->_mysqli->query($sql);
        if (! $this->_result) {
            show_error(3, ['message' => $this->_mysqli->error . " ($sql)"]);
        }
        if (DEVELOPMENT_ENVIRONMENT == true) {
            global $sql_debug;
            $query_time = intval((microtime(true) - $btime) * 1000);
            $sql_debug[] = array('sql' => $sql, 'time' => $query_time);
        }
        return $this;
    }
    
    // 返回单条数据
    public function row_array()
    {
        if (false !== $this->_result) {
            $res = $this->_result->fetch_assoc();
            $this->_result->free();
        } else {
            $res = array();
        }
        return $res;
    }
    
    // 返回多条数据
    public function result_array()
    {
        $res = array();
        if (false !== $this->_result) {
            while ($row = $this->_result->fetch_assoc()) {
                $res[] = $row;
            }
            $this->_result->free();
        }
        return $res;
    }
    
    // 返回最后插入id
    public function insert_id()
    {
        return $this->_mysqli->insert_id;
    }
    
    // 返回影响函数
    public function affected_rows() {
        return $this->_mysqli->affected_rows;
    }
    
    // 批量查询语句，并返回多个结果集
    public function multi_query($sql)
    {
        $this->_result = $this->_mysqli->multi_query($sql);
        if (! $this->_result) {
            show_error(3, ['message' => $this->_mysqli->error]);
        }
        $res = array();
        $i = 0;
        if (false !== $this->_result) {
            do {
                if ($result = $this->_mysqli->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $res[$i][] = $row;
                    }
                    $result->free();
                }
                $i++;
            } while ($this->_mysqli->more_results() && $this->_mysqli->next_result());
        }
        return $res;
    }
    
    // 开启事务
    public function trans_begin()
    {
        // 设置为不自动提交，因为MYSQL默认立即执行
        $this->_mysqli->autocommit(false);
        // 开始事务定义
        $this->_mysqli->begin_transaction();
    }
    
    // 回滚事务
    public function trans_rollback()
    {
        // 判断当执行失败时回滚
        $this->_mysqli->rollback();
    }
    
    // 提交事务
    public function trans_commit()
    {
        // 执行事务
        $this->_mysqli->commit();
    }
    
    // 关闭数据库连接
    public function close()
    {
        if ($this->_mysqli) {
            $this->_mysqli->close();
        }
    }
}
