<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * 
 * 顶层controller定义一些通用方法
 * 
 * @author jxy
 * @date 2013-10-25
 */
class Common_Controller extends CI_Controller
{
    protected $headers;
    protected $params; //客户端提交的数据
    protected $filter_params; //验证过得数据
    protected $errors;
    protected $version; //版本
    //token中的值说明：timestamp=请求时间戳,password=登陆密码,device设备码,uid=用户登陆id 当前版本密码为空V2.0以上
    protected $token;
    protected $attach_config; //上传附件的配置
    protected $redis;
    protected $platform ;//平台
    protected $profile;//用户权限
    protected $url = BIBI_PATH; //URL路径，模板中使用

    /**
     * 取得客户端数据
     */
    public function __construct()
    {

        parent::__construct();
		//解密token
        $this->init_token();
		//平台
        $this->init_platform();
        //获取客户端数据
        $this->initClientRequestData();
        $this->init_attach();
        $this->init_version();
        $this->init_profile();
        //$this->init_header();
        $this->lang->load('common');

    }
    
    protected function init_profile(){
        $this->load->model('user_model', 'user');
        $user = $this->user->get_userinfo($this->token['uid']);
        $where = array('profile'=>$user['type']);
        $this->profile = $this->user->query_right($where);
    }
    
    
    
    protected function init_platform(){
        $this->load->library('input');
        $this->platform = $this->input->get_request_header('From');
    }
    
    protected function init_header(){
        $this->load->library('input');
        $this->headers = $this->input->request_headers();
    }

    /**
     * 载入redis
     */
    protected function get_redis()
    {

        return $this->load->driver('cache', array('adapter' => 'redis'));

    }


    /**
     * 取得缓存数据
     * @param unknown $key
     * @return unknown|multitype:
     */
    protected function get_cache_data($key)
    {
        $this->load->driver('cache');
        $return = array();
        if ($this->cache->memcached->is_supported() === true) {
            $cache = $this->cache->memcached->get($key);
            if (!empty($cache[0])) {
                return $cache[0];
            }
        }
        return $return;
    }
    /**
     * 取得缓存数据
     * @param unknown $key
     * @return unknown|multitype:
     */
    protected function get_mem_cache_data($key){
        $this->load->driver('cache');
        $return = array();
        if ($this->cache->memcached->is_supported() === true) {
            $cache = $this->cache->memcached->get($key);
            if (!empty($cache[0])) {
                return $cache[0];
            }
        }
        return $return;
    }

    /**
     * 缓存临时数据(session)/后期考虑用内存优化
     * @param string $key
     * @param array $data
     */
    protected function set_cache_data($key, $data, $replace = false)
    {

        $this->load->driver('cache');
        if ($this->cache->memcached->is_supported() === true) {
            //debug($data);
            if (!$replace) {
                $this->cache->memcached->save($key, $data, 7200);
            } else {
                $this->cache->memcached->replace($key, $data, 7200);
            }

            return true;
        }
        return false;

    }
    
    /**
     * 缓存临时数据(session)/后期考虑用内存优化
     * @param string $key
     * @param array $data
     */
    protected function set_mem_catche_data($key, $data, $replace = false){
        $this->load->driver('cache');
        if ($this->cache->memcached->is_supported() === TRUE) {
            //debug($data);
            if (!$replace) {
                if(!$this->cache->memcached->save($key, $data, 7200)){
                    return false;
                }
            } else {
                if(!$this->cache->memcached->replace( $key, $data, 7200)){
                    return false;
                }
            }
            return true;
        }
        return false;
    }


    /**
     * 初始化上传配置
     */
    private function init_attach()
    {

        $this->attach_config = array(
            'favicon' => array(
                'upload_path' => 'favicon/',
                'allowed_types' => 'jpg|gif|png|jpeg',
                'overwrite' => true,
                'max_size' => 0,
                'range_size'=>array(100,192),
                ),
            'cowry' => array(
                'upload_path' => 'cowry/',
                'allowed_types' => 'jpg|gif|png|jpeg',
                'max_size' => 0,
                'range_size'=>array(100,192,292),
                ),
            'backimg' => array(
                'upload_path' => 'backimg/',
                'allowed_types' => 'jpg|gif|png|jpeg',
                'overwrite' => true,
                'max_size' => 0,
                ),
            'msgimg' => array(
                'upload_path' => 'msgimg/',
                'allowed_types' => 'jpg|gif|png|jpeg',
                'overwrite' => true,
                'max_size' => 0,
                'range_size'=>array(192),
                ),
            'radio' => array(
                'upload_path' => 'radio/',
                'allowed_types' => '*',
                'overwrite' => true,
                'max_size' => 0,
                ),
            );

    }


    /**
     * 解密证书中的数据
     */
    private function init_token()
    {

        $this->load->library('input');
        $cert_encode = $this->input->get_request_header('Token');
        if (!empty($cert_encode)) {

            //检查缓存有无相同证书，有缓存则不继续解密
            //$this->get_redis();
            //$cache_cert = $this->cache->get(md5($cert_encode));
            $cache_cert = $this->get_cache_data(md5($cert_encode));

            if (!empty($cache_cert)) {
                return $this->token = json_decode($cache_cert, true);
            } else {
                //解密证书
                $params = array('key' => $this->config->item('token_key'));
                $this->load->library('des', $params);
                $cert_decode = $this->des->decrypt($cert_encode);
                list($token_encode, $this->token['timestamp']) = explode('+', $cert_decode);
                //验证 证书时间是否有效
                $token_decode = $this->des->decrypt($token_encode);
                list($this->token['uid'], $this->token['password'], $this->token['device']) = explode('+', $token_decode);
                //判断是否登陆
                if (!empty($this->token['uid'])) {
                    //如果登陆，则缓存证书
                    //$this->cache->save(md5($cert_encode),json_encode($this->token),600);
                    $this->set_cache_data(md5($cert_encode), json_encode($this->token), 600);
                }
            }

        }
    }
    
    /**
     * 获取版本。
     */
    private function init_version(){
        $this->load->library('input');
        $this->version = $this->input->get_request_header('Version')? $this->input->get_request_header('Version'):'';
    }


    /**
     * 返回客户端信息通用函数
     * @param number $status 返回状态
     * @param string $data	包含的数据
     * @param string $msg	状态说明
     */
    protected function return_client($status = 0, $data = null, $msg = null)
    {
		header('Content-type: application/json');
        global $starttime;
        $resp = array(
            'version'=>$this->version,
            'status' => $status,
            'data' => empty($data) ? null : $data,
            'msg' => empty($msg) ? null : $msg,
            'time' => date('Y-m-d H:i:s', time()));//microtime(true) - $starttime);
        $json = json_encode($resp);
        $this->error_log($json);
        die($json);

    }


    /**
     * 时间 错误级别 客户端版本 设备品牌 设备型号 调用接 名称 传入参数 接口错误描述
     * example 2013-10-25 15:32:25 error 2bi1.0 HTC 5520 login {"method":"2bi","data":{"username":"jxy","password":"123456"}} method不被允许
     * @param string $client_version 客户端软件版本号
     * @param string $device 设备信息 HTC 5520
     * @param string $func	执行的接口
     * @param string $params  请求的参数(JSON)
     * @param string $msg 接口错误描述 
     */
    protected function error_log($return)
    {

        if (ENVIRONMENT != 'production') {
            $planform = isset($_SERVER['HTTP_PLANFORM']) ? 'web' : '';
            if (strtolower($planform) != 'web') {
                global $starttime;
                $requst = json_encode($this->params);
                $uri = $_SERVER['REQUEST_URI'];
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                $log = $this->lang->line('debug');
                $log = sprintf($log,$this->version, microtime(true)-$starttime,$uri, $useragent, $requst,var_export($this->params, true), $return, var_export(json_decode($return, true), true));
                log_message('error', $log);
            }
        }

    }


    /**
     * 获取客户端的json数据并转成数组
     */
    protected function initClientRequestData(){
        //$params = @file_get_contents('php://input');
		$this->load->library('input');
		$params = $this->input->post('params');
		if (!empty($params)) {
			$this->params = json_decode($params, true);
		}else{
			$this->params = array();
		}
    }
    

    /**
     * 检测手机号码格式
     */
    protected function check_phone_number($mobile)
    {

        if (preg_match('/^(13[0-9]|14[0-9]|15[0-9]|18[0-9]|17[0-9]|18[0-9])\d{8}$/', $mobile)) {
            return true;
        }
        return false;

    }


    /**
     * 生成随机的数字
     */
    protected function get_random($length = 6)
    {

        return rand(pow(10, $length - 1), pow(10, $length) - 1);

    }


    /**
     * 给指定号码发送短信
     * @param str $mobile 手机号码
     * @param str $content 发送内容
     */
    protected function send_sms($mobile, $content)
    {

        $post_data = array();
        $url = 'http://cs.wmlll.com/sms.aspx';

        $post_data = array(
            'action' => 'send',
            'userid' => 168,
            'account' => 'sjlr',
            'password' => '111111',
            'mobile' => $mobile,
            'content' => $content, //('测试短信发送提交'),
            'sendTime' => '',
            );

        $o = '';
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . '&';
        }

        $post_data = substr($o, 0, -1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
        $result = curl_exec($ch);
        // 关闭cURL资源，并且释放系统资源
        curl_close($ch);

        $return = (array )@simplexml_load_string($result);

        if (isset($return['returnstatus']) && strtolower($return['returnstatus']) ==
            'success') {
            return true;
        }

        return false;

    }


    /**
     * 计算星座 '1990-01-01'
     */
    protected function get_zodiac_sign($date)
    {
        $array = explode('-', $date);
        $month = intval($array[1]);
        $day = intval($array[2]);
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31)
            return (false);
        // 星座名称以及开始日期
        $signs = array(
            array("20" => "水瓶座"),
            array("19" => "双鱼座"),
            array("21" => "白羊座"),
            array("20" => "金牛座"),
            array("21" => "双子座"),
            array("22" => "巨蟹座"),
            array("23" => "狮子座"),
            array("23" => "处女座"),
            array("23" => "天秤座"),
            array("24" => "天蝎座"),
            array("22" => "射手座"),
            array("22" => "摩羯座"));
        list($sign_start, $sign_name) = each($signs[(int)$month - 1]);
        if ($day < $sign_start)
            list($sign_start, $sign_name) = each($signs[($month - 2 < 0) ? $month = 11 : $month -=
                2]);
        return $sign_name;
    }



    /**
     * 上传附件的管理
     */
    protected function attachment_upload($type)
    {
        /**
         * @本地测试。打开以下参数
         */
        //$this->token['uid'] = 100;
        if (!empty($this->attach_config["$type"])) { 
            //debug($_FILES);exit;
            $this->attach_config["$type"]['upload_path'] .= $this->token['uid'] . '/';
            $ext = substr($_FILES['attachment']["name"], strpos($_FILES['attachment']["name"],'.'));
            $hight = $this->getHeight($_FILES['attachment']["tmp_name"]);
            $width = $this->getWidth($_FILES['attachment']["tmp_name"]);
            $this->attach_config["$type"]['file_name'] = md5($this->token['uid'] . time()) .$ext;
            $path = $this->attach_config["$type"]['upload_path'];
            $fileName = '/attachment/' . $this->attach_config["$type"]['upload_path'] . $this->attach_config["$type"]['file_name'];
            
            $this->attach_config["$type"]['upload_path'] = ATTACH_PATH . $this->attach_config["$type"]['upload_path'];
            if (!is_dir($this->attach_config["$type"]['upload_path'])) {
                $this->make_dir($this->attach_config["$type"]['upload_path']);
            }
            $this->load->library('upload', $this->attach_config["$type"]);
            if ($this->upload->do_upload('attachment')) {
                
                if($type == 'cowry' || $type == 'msgimg' || $type == 'favicon' ){
                    //判断是否需要裁剪 规定的缩略图
                    /**
                     * @裁剪得到的图片。发到与原图片路径相同的位置。
                     * @数据库没做任何修改
                     * @裁剪后的图片命名：原图片名+"_"+缩略图尺寸+"x"+"缩略图尺寸"+".后缀名"
                     */
                    if (!empty($this->attach_config["$type"]['range_size'])) {
                        $filePath = $this->attach_config["$type"]['upload_path'].$this->attach_config["$type"]['file_name'];
                        if(file_exists($filePath)){
                            //裁剪需要的尺寸
                            $flag = true;
                            $this->load->library('image_lib');
                            $file = explode('.',$this->attach_config["$type"]['file_name']);
                            $range_size = $this->attach_config["$type"]['range_size'];
                            switch($type){
                                case 'msgimg':
                                    $msgImgName = $this->msg_img($width,$hight,$filePath,$type,$path);
                                    if(!$msgImgName){
                                        $flag = false;
                                    }
                                    break;
                                case 'favicon':
                                    $fav = $this->favicon_img($width,$hight,$filePath,$type,$path);
                                    if(!$fav){
                                        $flag = false;
                                    }
                                    break;
                                case 'cowry':
                                    $cowryIMG = $this->cowry_img($width,$hight,$filePath,$type,$path);
                                    if(!$cowryIMG){
                                        $flag = false;
                                    }
                                    break;
                            }
                            if($flag){
                                if($type=='msgimg'){
                                    return $msgImgName;
                                }else{                                    
                                    return $fileName;
                                }
                            }else{
                                $this->errors = $this->image_lib->display_errors();
                            }
                        }else{
                            $this->errors ="原图片丢失或图片上传失败！";
                        }           
                    }else{
                        return $fileName;
                    }
                }else{
                    return $fileName;
                }
            } else {
                $this->errors = $this->upload->display_errors();
            }

        }
        return false;

    }
    
    /**
     * @聊天图片的处理
     */
     private function msg_img($width,$hight,$filePath,$type,$path){
         $flag = true;
         $file = explode('.',$this->attach_config["$type"]['file_name']);
         $range_size = $this->attach_config["$type"]['range_size'];
         $cnt = count($range_size);//需要的尺寸
         for($i=0;$i<$cnt;$i++){
             $reWidth  = $width>$range_size[$i]?$range_size[$i]:$width;
             $reHeight = $hight>$range_size[$i]?$range_size[$i]:$hight;
             $new_image = $file[0]."_{$reWidth}x{$reHeight}.".$file[1];
            if(!$this->image_do($reWidth,$reHeight,$filePath,$new_image,$type)){
                $flag = false;
                break;
            }else{
                $oldPath = $this->attach_config["$type"]['upload_path'].$new_image; 
                $hight = $this->getHeight($oldPath);
                $width = $this->getWidth($oldPath);
                $new_image = $file[0]."_{$width}x{$hight}.".$file[1]; 
                $newPath = $this->attach_config["$type"]['upload_path'].$new_image; 
                rename($oldPath, $newPath);
            }
         }
         if($flag){
            $msgImgName = '/attachment/' . $path . $new_image;
            return $msgImgName;
         }else{
            return false;
         }
         
         
     }
     
     /**
      * @上传头像处理
      */
     private function favicon_img($width,$hight,$filePath,$type,$path){
         $flag = true;
         $file = explode('.',$this->attach_config["$type"]['file_name']);
         $range_size = $this->attach_config["$type"]['range_size'];
         $cnt = count($range_size);//需要的尺寸
         for($i=0;$i<$cnt;$i++){
            $reWidth  = $reHeight = $range_size[$i];
            $new_image = $file[0]."_{$reWidth}x{$reHeight}.".$file[1];
            if(!$this->image_do($reWidth,$reHeight,$filePath,$new_image,$type)){
                $flag = false;
                break;
            }
         }
         if($flag){
            return true;
         }else{
            return false;
         }
     }
     /**
      * @上传商品图片处理
      */
     private function cowry_img($width,$hight,$filePath,$type,$path){
         $flag = true;
         $file = explode('.',$this->attach_config["$type"]['file_name']);
         $range_size = $this->attach_config["$type"]['range_size'];
         //第一步,裁图片
         $thumb_name = $file[0]."_thumb.".$file[1];
         $thumb = $this->crop_img($width,$hight,$filePath,$thumb_name);
         $this->image_lib->clear();
         $thumbPath = $this->attach_config["$type"]['upload_path'].$thumb_name;
         if($thumb){
            if(file_exists($thumbPath)){
                $cnt = count($range_size);//需要的尺寸
                for($i=0;$i<$cnt;$i++){
                    $reWidth  = $reHeight = $range_size[$i];
                    $new_image = $file[0]."_{$reWidth}x{$reHeight}.".$file[1];
                    if(!$this->image_do($reWidth,$reHeight,$thumbPath,$new_image,$type)){
                        $flag = false;
                        break;
                    }
                 }
                 unlink($thumbPath);
             }else{
                $this->errors ="原图片丢失或图片上传失败！";
                $flag = false;
             }
         }else{
            $flag = false;
         }
         if($flag){
            return true;
         }else{
            return false;
         }
     }
     
     /**
      * @图片切割
      */
     private function crop_img($width,$hight,$cource_img,$new_image){
        $config['image_library'] = 'GD2';
        $config['source_image'] = $cource_img;
        $config['new_image'] = $new_image;
        $config['quality'] = '100%' ;
        $config['create_thumb']=false;
        $config['maintain_ratio'] = FALSE;
        if($width >= $hight)
        {
            $config['x_axis'] = floor(($width  - $hight)/2);
            $config['height'] = $config['width']= $hight;
            $config['y_axis'] = 0;
        }else{
            $config['x_axis'] = 0;
            $config['y_axis'] = floor(($hight - $width)/2);
            $config['height'] = $config['width']= $width;
        }
        $this->image_lib->initialize($config);
        if(!$this->image_lib->crop()){
            return false;
        }else{
            return true;
        }
     }
    
    /**
     * @图片压缩
     */
    private function image_do($width,$height,$cource_img,$new_image,$type){ 
        $config['image_library'] = 'GD2';
        $config['source_image'] = $cource_img;
        $config['new_image'] = $new_image;
        $config['create_thumb']=false;
        $config['quality'] = '100%' ;
        $config['master_dim'] = 'width';
        if($type=='msgimg'){
            $config['maintain_ratio'] = TRUE;//使图像保持原始的纵横比例 
            /*
            if($width >= $height)
            {
                $config['master_dim'] = 'height';
            }else{
                $config['master_dim'] = 'width';
            }
            */
        }else{
            $config['maintain_ratio'] = FALSE;
        }
        $config['width'] = $width;
        $config['height'] = $height; 
        $this->image_lib->initialize($config);
        if(!$this->image_lib->resize()){
            return false;
        }else{
            return true;
        }
    }
    
    
    /**
     * 获取图片的高度
     */
    private function getHeight($image)
    {
        $size = getimagesize($image);
        $height = $size[1];
        return $height;
    }
    /**
     * 获取图片的宽度。
     */
    private function getWidth($image)
    {
        $size = getimagesize($image);
        $width = $size[0];
        return $width;
    }

    /**
     * 建立文件夹目录
     * @param string $dirs 文件夹目录路径
     * @param string $mode 权限
     */
    protected function make_dir($dirs = '', $mode = 0777)
    {
        $dirs = str_replace('\\', '/', trim($dirs));
        if (!empty($dirs) && !is_dir($dirs)) {
            self::make_dir(dirname($dirs));
            mkdir($dirs, $mode) or exit('权限不足,建立' . $dirs . '目录失败');
        }
    }


    /**
     * 验证码
     */
    protected function check_captcha($phone, $uid, $captcha)
    {
        //$this->get_redis();
        //$validate_code = $this->cache->get($phone.$uid);
        $validate_code = $this->get_mem_cache_data($phone . $uid);
        if (empty($validate_code)) {
            $this->errors = $this->lang->line('mobile_validate_not_match_or_expirse');
            return false;
        } else {
            if (trim($captcha) == $validate_code) {
                return true;
            }
        }   
    }


    /**
     * 分页，计算初始偏移量
     * @return number
     */
    protected function get_page_start()
    {

        $start = 0;
        if ($this->params['page']) {
            $start = ($this->params['page'] - 1) * $this->config->item('page_of_count') + 1;
        }
        return $start;

    }


    /**
     * 生成宝贝的hash值
     * @param array $imgurl
     * @param string $desc
     * @return string $hash
     */
    protected function create_cawry_hash($imgurl, $desc)
    {

        $hash = '';
        if (is_array($imgurl)) {
            $imgurl = implode(',', $imgurl);
        }
        //直接使用二次md5
        $hash = md5(md5($imgurl) . md5($desc));
        return $hash;

    }


    /**
     *@author zhoushuai
     *@发送验证码
     */
    protected function send_captcha($phone, $userID = 0)
    {
        if ($this->check_phone_number($phone)) {
            $random = $this->get_random(4);
            //$this->get_redis();
            //$this->cache->save($phone.$userID,$random,900);
            if($this->set_mem_catche_data($phone . $userID, $random,$this->get_mem_cache_data($phone . $userID))){
                $content = sprintf($this->lang->line('send_validation_code_by_mobile'), $random);
                $re = $this->send_msg($phone, $content);
                return $re;
            }
        }
        return false;

    }
    
     /**
     * @author zhoushuai
     * 请求地址方法
     * @param $curlPost  请求的参数
     * @param $url  发送的服务地址
     * @return mixed
     */
    protected function Post($curlPost,$url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }
    
    
    /**
     * @author zhoushuai
     * 过滤标签
     * @param $xml 过滤的返回标签
     * @return mixed
     */
    protected function xml_to_array($xml){
        $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
        if(preg_match_all($reg, $xml, $matches)){
            $count = count($matches[0]);
            for($i = 0; $i < $count; $i++){
                $subxml= $matches[2][$i];
                $key = $matches[1][$i];
                if(preg_match( $reg, $subxml )){
                    $arr[$key] = $this->xml_to_array( $subxml );
                }else{
                    $arr[$key] = $subxml;
                }
            }
        }
        return $arr;
    }
    
    
    /**
     * @author zhoushuai
     * 获取随机字符串
     * @param int $length 长度
     * @param int $numeric
     * @param int $type 0：数字字母混搭 1：纯数字 2：纯字母
     * @return string
     */
    protected function random($numeric = 0,$type=0,$length = 6) {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        if($numeric) {
            $hash = sprintf('%0'.$length.'d', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
            if($type == 1)
                $chars = '0123456789';
            elseif($type == 2)
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            $max = strlen($chars) - 1;
            for($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }
    
    /**
     * @author zhoushuai
     * 发送手机短信
     * @param int phone   手机号码
     */
    protected function send_code($phone,$content){
        $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
        $post_data = "account=cf_xrenwu&password=xrenwu123&mobile=".$phone."&content=".rawurlencode($content);
        $gets =  $this->xml_to_array($this->Post($post_data, $target));
        if($gets['SubmitResult']['code']==2){ 
            return TRUE;
        }else{
            return FALSE;
        }
    }
    
    
    /**
     *@author zhoushuai
     *@发送手机短信
     *@测试帐号:it008  密码：VDSF34
     *@正式帐号：SDK-IT008-0051 密码：xrenwuit008
     */
     
     protected function send_msg($phone,$content){
        
        $username = "SDK-IT008-0051";
        $password = "xrenwuit008";
        $url="http://124.173.70.59:8081/SmsAndMms/mt?";
        $curlPost = 'Sn='.$username.'&Pwd='.$password.'&mobile='.$phone.'&content='.rawurlencode($content); 
        $ch = curl_init();//初始化curl
        curl_setopt($ch,CURLOPT_URL,$url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);  //允许curl提交后,网页重定向  
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        if($data==0){
            return TRUE;
        }else{
            return FALSE;
        }

     }
     
    
    
    /**
     * @订单交易完成。支付成功
     * @author zhoushuai
     * @copyright
     */
    protected function  _complete_order($data){
        //商户订单号
        $order_no = $data['out_trade_no'];
        //支付宝交易号
        $trade_no = $data['trade_no'];
        //交易状态
        $trade_status = $data['trade_status'];
        //卖家支付宝账号
        $vendor_email = $data['vendor_email'];
        //买家支付宝账号
        $buyer_email = $data['buyer_email'];
        
        if($data['trade_status'] == 'WAIT_BUYER_PAY'){
            //等待付款。。。。
            return TRUE;
        }elseif($data['trade_status'] == 'TRADE_FINISHED' || $data['trade_status'] == 'TRADE_SUCCESS') {
            $log = $this->lang->line('pay_order');
            $this->load->model('order_model','order');
            $this->load->model('user_model','user');
            $where = array('order_no'=>$order_no);
            $order = $this->order->get_order($where);
            if($order){
                $oid = $order['id_orders'];
                $order_data = array(
                    'trade_no'=>$trade_no,
                    'vendor_email'=>$vendor_email,
                    'buyer_email'=>$buyer_email,
                    'created'=>date('Y-m-d H:i:s', time())
                );
                if($order['status']<=2){
                    $order_data['status'] = 2;
                }
                $result = $this->order->modify_order($order_data,$oid);
                if($result){
                    //支付成功后，需向卖家发送一条提示短信
                    $vendor  = $order['vendor'];//卖家
                    $where   = array('bi_contact.default'=>1,'bi_contact.id_2buser'=>$vendor);
                    $contact = $this->user->query_contact($where);
                    $content = "你在邻售的宝贝已售出，订单号为{$order_no}，请尽快安排送货！";
                    self::send_msg($contact['cell_phone'],$content);
                    log_pay($log,$data);
                    return TRUE;
                } 
            }
        }
        $log = $this->lang->line('pay_failed');
        log_pay($log,$data);
        return FALSE;
    }
    

    
    
    
    
     

}

/* End of file common_controller.php */
/* Location: ./common/controllers/common_controller.php */
