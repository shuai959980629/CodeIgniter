<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * @零售官网-顶层controller定义一些通用方法
 * @copyright(c) 2014-07-07
 * @author zhou shuai
 * @version Id:home_controller.php
 */
class Home_Controller extends CI_Controller
{
	protected $errors;//错误信息
	protected $params;//初始化页面参数
    protected $url = HOME_PATH; //URL路径，模板中使用


	public function __construct(){
		parent::__construct();
		$this->lang->load('common');
		$this->init();
	}
	
	
	protected function init(){
		//加载页面公用信息
		$this->load_page();	
	}
    
    
    protected function load_page(){
        $this->params['url_prefix']=$this->url;
        $this->params['site_url'] ='http://'.$_SERVER['HTTP_HOST'];
        $this->params['caction'] = $this->uri->rsegments[1];	
	}
    
  
     /**
     * 返回操作结果
     * @param number $status 返回状态 0失败，1成功
     * @param string $data	包含的数据
     * @param string $msg	状态说明 提示信息
     */
    protected function return_status($status = 0,$msg,$data = array())
    {
        global $starttime;
        $result = array(
            'status' => $status,
            'data' => empty($data) ? null : $data,
            'msg' => empty($msg) ? null : $msg,
            'time' => microtime(true) - $starttime);
        if($this->input->is_ajax_request()){
			die(json_encode($result));
		}else{
		   /* $result['url_prefix']=$this->url;
            $this->load->view('tips',$result);*/
            
            header("Content-type:text/html;charset=utf-8");
            $msg = trim(strip_tags($msg));
            echo '<script>alert("'.$msg.'");javascript:history.go(-1);</script>';
            exit;
            
		}
    }
    
    
    /**
     * 返回客户端信息通用函数
     * @param number $status 返回状态
     * @param string $data	包含的数据
     * @param string $msg	状态说明
     */
    protected function return_client($status = 0, $msg = null, $data = null)
    {
        global $starttime;
        $resp = array(
            'status' => $status,
            'data' => empty($data) ? null : $data,
            'msg' => empty($msg) ? null : $msg,
            'time' => microtime(true) - $starttime);
        $json = json_encode($resp);
        die($json);
    }
    
  
	
	/**
	 * 后台加密算法
	 * @param string $pwd 原始密码
	 * @param string $hash
	 * @return string
	 */
	protected function md5pwd($pwd, $hash){
		
		$newpwd =  md5(substr(md5(md5($pwd)), 5, 10).$hash);
		return $newpwd;
		
	}

	/**
	 * 缓存临时数据(session)/后期考虑用内存优化
	 * @param string $key
	 * @param array $data
	 */
	protected function cache_data($key,$data,$replace = false){
		$this->load->driver('cache');
		if($this->cache->memcached->is_supported() === TRUE){
			if(!$replace){
				$this->cache->memcached->save($key,$data,7200);
			}else{
				$this->cache->memcached->replace($key,$data,7200);
			}
			
			return TRUE;
		}
		return FALSE;
		
	}
	
	
	protected function get_session_data($key){
		
		$this->load->driver('cache');
		$return = array();
		if($this->cache->memcached->is_supported() === TRUE){
			$cache = $this->cache->memcached->get($key);
			if(!empty($cache[0])){
				//$return = json_decode($cache[0],TRUE);
				return $cache[0];
			}
		}
		return $return;
		
	}
	
	/**
	 * 生成随机的数字
	 */
	protected function get_random($length=6){
	
		return rand(pow(10, $length-1), pow(10, $length)-1);
	
	}
    /**
     * @param $html
     * @return mixed
     * 替换html标签
     */
    function replace_html($html){
        $return = str_replace('<', '&lt;', $html);
        $return = str_replace('>', '&gt;', $return);
        return $return;
    }



    /**
     * 建立文件夹目录
     * @param string $dirs 文件夹目录路径
     * @param string $mode 权限
     */
    public function makeDir($dirs = '', $mode = 0777)
    {
        $dirs = str_replace ('\\', '/', trim( $dirs ));
        if (! empty ( $dirs ) && ! is_dir ( $dirs )) {
            self::makeDir (dirname($dirs ));
            if(!mkdir ($dirs, $mode )){
                file_put_contents(DOCUMENT_ROOT.'/ac.txt',var_export('权限不足,建立'.$dirs.'目录失败',TRUE));
                return false;
//                exit('权限不足,建立'.$dirs.'目录失败');
            }
        }
        return true;
    }

   

}



/* End of file home_controller.php */