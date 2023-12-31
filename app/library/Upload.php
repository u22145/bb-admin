<?php
/**
 * 文件上传类
 *
 * 文件上传：支持图片，视频，音频，其他文件格式
 *
 */
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Upload
{

    /**
     *  作用：文件上传
     * @param string $field
     * @param string $file_name
     * @param string $media_tye
     * @return mixed
     */
    public function run_upload($field = 'file', $file_name = '', $media_tye = 'image')
    {
        $_file = $_FILES[$field];
        $result['status'] = 0;
        $result['msg'] = '';
        if (! $_file) {
            $result['msg'] = '上传文件出错';
            return $result;
        } else {
            //判断文件
            if (! is_uploaded_file($_file['tmp_name'])) {
                $error = isset($_file['error']) ? $_file['error'] : 4;
                switch ($error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $msg = '您上传的文件太大了';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $msg = '您上传的文件太大了';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $msg = '文件只上传了一部分';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $msg = '您没有选择要上传的文件';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $msg = '找不到临时文件夹';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $msg = '无法将文件写入磁盘';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $msg = '文件上传被扩展停止';
                        break;
                    default:
                        $msg = '您没有选择要上传的文件';
                        break;
                }
                $result['msg'] = $msg;
                return $result;
            } 
        }
        $mimes = array(
            'image' => array('image/gif',    'image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg', 'image/png',  'image/x-png', 'image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'),
            'media' => array('video/mj2', 'video/mp4', 'video/wmv'),
            'flash' => array('video/flv'),
        );
        //校验文件格式
        if ($media_tye != 'file') {
            if ($media_tye == 'image') {
                $img_info = getimagesize($_file['tmp_name']);
                list($width, $height) = $img_info;
                if (! in_array($img_info['mime'], $mimes[$media_tye], TRUE)) {
                    $result['msg'] = '您上传的文件格式不正确';
                    return $result;
                }
            } else {
                if (! in_array($_file['type'], $mimes[$media_tye], TRUE)) {
                    $result['msg'] = '您上传的文件格式不正确';
                    return $result;
                }
            }
        }
    
        //解析文件路径
        if (! $file_name) {
            $file_info = pathinfo($_file['name']);//解析文件路径
            $ext = '.' . strtolower($file_info['extension']);//文件后缀
            $file_name = date('His') . $ext;//保存文件名
            $file_path = UPLOAD_PATH . '/' . date('Ymd') . '/';//存储路径
        }  else {
            $file_info = pathinfo(UPLOAD_PATH . $file_name);//解析文件路径
            $file_path = strtolower($file_info['dirname']) . '/';//存储路径
        }

        //判断目录是否存在，如果不存在则自动创建
        if (! file_exists($file_path)) {
            if (! @mkdir($file_path, 0775, true)) {
                $result['msg'] = '上传的目录无法写入' . $file_path;
                return $result;
            }
        }
      
        //执行上传
        if (! @copy($_file['tmp_name'], UPLOAD_PATH . $file_name)) {
            if (! @move_uploaded_file($_file['tmp_name'], UPLOAD_PATH . $file_name )) {
                $result['msg'] = '上传的目录不存在' . UPLOAD_PATH . $file_name;
                return $result;
            }
        }

        // s3文件备份 shell脚本移动文件
        $s3_file_path = S3WAIT_PATH . $file_name;
        if (!file_exists(dirname($s3_file_path))) {
            @mkdir(dirname($s3_file_path), 0775, true);
        }
        @copy(UPLOAD_PATH . $file_name, $s3_file_path);
        
        // qiniu文件備份
        /*$s3_file_path = QNWAIT_PATH . $file_name;
        if (!file_exists(dirname($s3_file_path))) {
            @mkdir(dirname($s3_file_path), 0775, true);
        }
        @copy(UPLOAD_PATH . $file_name, $s3_file_path);*/
        
        //组装结果集
        $result = array(
            'file_name'        => $file_name,
            'status'        => 1,
            'msg'            => '上传成功',
        );
        
        //返回上传结果
        return $result;
    }

    /**
     *  作用：检查文件是否包含木马
     * @param $file
     * @return int
     */
    private function check_file($file)
    {
        $return = 9;
        if (file_exists($file)) {
            $resource = fopen($file, 'rb');
            $fileSize = filesize($file);
            fseek($resource, 0);
            if ($fileSize > 512) { // 取头和尾
                $hexCode = bin2hex(fread($resource, 512));
                fseek($resource, $fileSize - 512);
                $hexCode .= bin2hex(fread($resource, 512));
            } else { // 取全部
                $hexCode = bin2hex(fread($resource, $fileSize));
            }
            fclose($resource);
            if (preg_match('/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is', $hexCode)){ 
                $return = 5; //木马
            } else {
                $return = 0;
            }
        }
        return $return;
    }
}
