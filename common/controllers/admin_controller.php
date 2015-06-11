<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * @后台管理顶层controller定义一些通用方法
 * @copyright(c) 2014-06-18
 * @author zhou shuai
 * @version Id:admin_controller.php
 */
class Admin_Controller extends CI_Controller
{
	
	protected $users;//用户登陆信息
	protected $errors;//错误信息
	protected $params;//初始化页面参数
	protected $page_data;//初始化页面数据
	protected $current;
	protected $session_id;//session_id
    protected $url = ADMIN_PATH; //URL路径，模板中使用
    //分页参数
    static $offset = 0;

	public function __construct(){
		parent::__construct();
		$this->lang->load('common');
		$this->init();
	}
	
	
	/**
	 * 初始化后端服务
	 */
	protected function init(){
	    $this->ini_user();
	    //初始化页面菜单
    	$this->init_menu();
        
        $this->init_current();
		//加载页面公用信息
		$this->load_page();
		
        $this->form_hash();
	}
    protected function init_current(){
        $con = $this->router->class;
        if($con == 'home'){
            $this->page_data['curentID'] = 0;
        }elseif($con == 'super'){
            $this->page_data['curentID'] = 'S01';
        }else{
            $method = $this->router->method;
            $where =  "r.menu_url like '%{$con}%'";
            $this->load->model('right_model','right');
            $curRight= $this->right->get_right($where);
            $this->page_data['curentID'] = $curRight[0]['id_parent'];
        }  
    }
    
    protected function init_menu(){
        if(!empty($this->users)){
           if($this->users['id_admin'] > 0){
    			$this->page_data['menu'] = $this->get_menu($this->users['id_profile']);
    		} 
        }
    }
    
    
    protected function ini_user(){
    	$this->load->library('session');
        $this->session_id = $this->session->userdata('session_id');
    	$this->users = $this->get_session_data($this->session_id);
    	$this->page_data['user'] = $this->users;
    }
    
    protected function load_page(){
        $this->page_data['url_prefix']=$this->url;
        $this->page_data['site_url'] ='http://'.$_SERVER['HTTP_HOST'];
        $this->page_data['caction'] = $this->uri->rsegments[1];	
	}
    
    private function form_hash(){
		$this->load->library('session');
		$hash = $this->session->userdata('formhash');
		if(empty($hash)){
			$hash = $this->get_random();
			$this->session->set_userdata('formhash', $hash);
		}
	}
    
    
    /**
	 * 获取用户菜单、权限
	 * @param $id_profile 用户角色ID
	 */
	public function get_menu($id_profile)
	{
		$tree = array();
		if(empty($id_profile)){
			return $tree;			
		}
		$this->load->model('right_model','right');
        //角色权限关系profile_right 角色权限right
        $where = 'id_profile = '.$id_profile;
        $right = $this->right->get_right_list($where);
        if(empty($right)){
            return $tree;
        }
        $where = array("r.id_parent"=>0);
        $parent = $this->right->get_right($where);
        $tree =$this->list_to_tree($right,$parent);
        return $tree;
	}
    
    /**
	 * 把返回的数据转换成Tree
	 * @author zhoushuai
	 * @param array $right,$parent 要转换的数据
	 * @param array $pk 数据的id
	 * @param string $pid parent标记字段
	 * @return array
	 */
	protected  function list_to_tree($right,$parent,$pk='id_right',$pid = 'id_parent',$child = 'children') {
		// 创建Tree
		$tree = array();
        //创建基于主键的数组引用
		$rightTree = array();
        $parentTree = array();
		foreach ($right as $key => $data) {
			$rightTree[$data["$pid"]][] =& $right["$key"];
		}
        foreach ($parent as $key => $data) {
			$parentTree[$data["$pk"]] =& $parent["$key"];
		}
        foreach($parentTree as $key=>$data){
            if(array_key_exists($key,$rightTree)){
                $parentTree["$key"]["$child"]=$rightTree["$key"];
            }
        }
        foreach($parent as $key=>$data){
            if(isset($parent[$key]["$child"])){
                $tree[] = $parent[$key];
            }
        }
		return $tree;
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
	 * 
     * 后台管理员登陆
	 * @param string $username
	 * @param string $password
	 */
    protected function admin_login($username,$password){
        $this->load->model('admin_model','admin');
        $where= array(
            'username'=>$username,
            'password'=>$this->md5pwd($password)
        );
        $user = $this->admin->query_admin($where);
        if(!$user){
            //登录失败！
            $this->errors = $this->lang->line('user_login_fail');
            return FALSE;
        }
        $admin = array_merge($user);
		$this->cache_data($this->session_id, $admin);
		return $admin;
        
    }
  
	
	/**
	 * 后台加密算法
	 * @param string $pwd 原始密码
	 * @return string
	 */
	protected function md5pwd($pwd){
		$hash = hash("sha512",APPNAME);
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
				$this->cache->memcached->save($key,$data,0);
			}else{
				$this->cache->memcached->replace($key,$data,0);
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
     * 获取页码
     * @static
     * @param int $total 总数量
     * @param int $offset 当前页码
     * @param string $function_name 方法名
     * @param string $click_type 传来的$function_name 类型。 url :链接地址  method ： 方法名
     * @param int $pager 分页数量
     * @return string
     */
    function get_page($total, $offset = 0, $function_name='',$click_type='method', $pager=0)
    {
        //当前页码
        $offset = $offset ? $offset : self::$offset;
        //分页数量
        $pager = $pager ? $pager : ($this->config->item('page_of_count'));
        //链接文字显示
        $text_ary = array('<<', '>>');
        //页码左右显示数量
        $show_number = 2;

        //计算总页数开始
        if( $total < $pager )
            return '';
        else if( $total % $pager )
            $page_count = (int)($total / $pager) + 1;
        else
            $page_count = $total / $pager;
        //计算总页数结束

        //判断输入的分页码在有效分页范围内
        if ( $offset <= 0 )
            $offset = 1;
        else if ( $offset >= $page_count)
            $offset = $page_count;

        //计算上一页
        $page_str = '';
        $page_str.= '<li style="width:auto;text-align:center;border:0px;margin-right:10px;"><a href="javascript:void(0);">总共&nbsp;'.$page_count.'&nbsp;页</a></li>';
        if ( $offset <= 1 )
            $page_str .= '<li><a href="javascript:void(0);" class="shop" title="上一页">'.$text_ary[0].'</a></li>';
        else{
            if($click_type == 'url')
                $page_str .= '<li><a href="'.$function_name.'/'.($offset - 1).'" class="up" title="上一页">'.$text_ary[0].'</a></li>';
            else
                $page_str .= '<li><a onclick="'.$function_name.'('.($offset - 1).')" class="up" title="上一页">'.$text_ary[0].'</a></li>';
        }

        //计算页码数字
        if ( $offset <= ($show_number + 1) ) {
            for ($i = 1; $i <= ($page_count < ($show_number * 2 + 1) ? $page_count : ($show_number * 2 + 1)); $i++ )
            {
                if ( $i == $offset )
                    $page_str .= '<li class="active"><a href="javascript:void(0);"><b>'.$i.'</b></a></li>';
                else{
                    if($click_type == 'url')
                        $page_str .= '<li><a href="'.$function_name.'/'.$i.'">'.$i.'</a></li>';
                    else
                        $page_str .= '<li><a onclick="'.$function_name.'('.$i.')">'.$i.'</a></li>';
                }
            }
        } else if ( $offset >= ($page_count - $show_number) ) {
            for ($i = ($page_count - ($show_number * 2)); $i <= $page_count; $i++ )
            {
                if($i != 0){
                    if ( $i == $offset )
                        $page_str .= '<li class="active"><a href="javascript:void(0);"><b>'.$i.'</b></a></li>';
                    else{
                        if($click_type == 'url')
                            $page_str .= '<li><a href="'.$function_name.'/'.$i.'">'.$i.'</a></li>';
                        else
                            $page_str .= '<li><a onclick="'.$function_name.'('.$i.')">'.$i.'</a></li>';
                    }
                }
            }
        } else {
            for ($i = ($offset - $show_number); $i <= ($offset + $show_number); $i++ )
            {
                if ( $i == $offset )
                    $page_str .= '<li class="active"><a href="javascript:void(0);"><b>'.$i.'</b></a></li>';
                else{
                    if($click_type == 'url')
                        $page_str .= '<li><a href="'.$function_name.'/'.$i.'">'.$i.'</a></li>';
                    else
                        $page_str .= '<li><a onclick="'.$function_name.'('.$i.')">'.$i.'</a></li>';
                }
            }
        }

        //计算下一页
        if( $offset >= $page_count )
            $page_str .= '<li><a href="javascript:void(0);" class="shop" title="下一页">'.$text_ary[1].'</a></li>';
        else{
            if($click_type == 'url')
                $page_str .= '<li><a href="'.$function_name.'/'.($offset + 1).'" class="next" title="下一页">'.$text_ary[1].'</a></li>';
            else
                $page_str .= '<li><a onclick="'.$function_name.'('.($offset + 1).')" class="next" title="下一页">'.$text_ary[1].'</a></li>';
        }
        return $page_str;
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

    //获取远程文件大小
    function getFileSize($url){
        $url = parse_url($url);
        if($fp = @fsockopen($url['host'],empty($url['port'])?80:$url['port'],$error)){
            fputs($fp,"GET ".(empty($url['path'])?'/':$url['path'])." HTTP/1.1\r\n");
            fputs($fp,"Host:$url[host]\r\n\r\n");
            while(!feof($fp)){
                $tmp = fgets($fp);
                if(trim($tmp) == ''){
                    break;
                }else if(preg_match('/Content-Length:(.*)/si',$tmp,$arr)){
                    return sprintf("%.1f", (trim($arr[1])/1024));
                }
            }
            return null;
        }else{
            return null;
        }
    }

    //获取图片文件的本地路径+名字
    function file_url_name($path,$filename,$type=1){
        if($type == 0){
            $path = '/attachment/business/'.$path.'/';
        }else{
            $path = BASE_PATH. '/../attachment/business/'.$path.'/';
        }
        $cdir = str_split(strtolower($filename),1);
        $tmp = array_chunk($cdir, 4);
        if($tmp[0]){
            $dir = implode('/', $tmp[0]);
        }
        $path .= $dir.'/'.$filename;
        return $path;
    }

    
    /*
     * zxx
     * 二维数组排序
     * */
    function array_sort($arr,$keys,$type='asc'){
        $key_value = $new_array = array();
        foreach ($arr as $k=>$v){
            $key_value[$k] = $v[$keys];
        }
        if($type == 'asc'){
            asort($key_value);
        }else{
            arsort($key_value);
        }
        reset($key_value);
        foreach ($key_value as $k=>$v){
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
    
    
    protected function send_msg_to_client($url){
        $result = request_curl($url);
        $rsArray = json_decode($result,true);
        return $rsArray;
    }
 

}



/* End of file admin_controller.php */