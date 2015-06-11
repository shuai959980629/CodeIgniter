<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 
 * 用户核心逻辑
 * 
 * @author jxy
 * @date 2013-10-25
 */
class User_Controller extends Common_Controller
{


    /**
     * 用户登录
     */
    protected function login()
    {

        return $this->{$this->filter_params['method']}();

    }
    
    /**
     * 注销登录
     */
    protected function logout($uid){
        $this->load->model('user_model', 'user');
        $data = array('device_token'=>'','client_number'=>'');
        //更新用户设备
        $this->user->update_device_info($this->token['uid'],$data);
        return true;
    }


    /**
     * 登陆用户数据验证
     */
    protected function validation_login()
    {

        $this->load->library('form_validation');
        $this->lang->load('user');
        if (empty($this->params)) {
            //返回错误
            $this->errors = $this->lang->line('user_login_param_error');
            return false;
        } else {
            //检查登陆方式是否允许
            if (!in_array($this->params['method'], array(
                '2bi',
                'phone',
                'sina',
                'qq',
                'hipigo'))) {
                $this->errors = $this->lang->line('user_login_param_method_error');
                return false;
            } else {
                //验证第三方登陆的数据完整性
                $group = $this->params['method'] == '2bi' ? 'comm_login' : 'auth_login';
                $this->form_validation->set_message('phone_check', $this->lang->line('user_bind_phone'));
                if (false !== $this->form_validation->run($group, $this->params['data'])) {
                    $this->filter_params['method'] = $group;
                    $this->filter_params['data'] = $this->params['data'];
                    return true;
                } else {
                    $this->errors = $this->form_validation->error_string();
                }
            }
        }

        return false;

    }


    /**
     * 普通登陆
     */
    protected function comm_login()
    {

        //直接返回登陆成功与否
        $this->load->model('user_model', 'user');
        $user = $this->user->login($this->filter_params['data']['username'], $this->filter_params['data']['password']);
        if (empty($user)) {
            $this->errors = $this->lang->line('user_login_fail');
            return false;
        } else {
            return $user;
        }

    }
    

    /**
     * 第三方登陆
     */
    protected function auth_login()
    {
        //判断第三方登陆，是否是第一次登陆
        if (!in_array($this->filter_params['data']['type'], array(
            'hipigo',
            'phone',
            'qq',
            'sina'))) {
            $this->errors = $this->lang->line('third_login_type_error');
            return false;
        }
        if($this->filter_params['data']['type']=='phone'){
            //判断验证码
            $userID=0;
            $re = parent::check_captcha($this->filter_params['data']['account'], $userID, $this->filter_params['data']['token']);
            if (!$re) {
                $this->errors = $this->lang->line('user_retrieve_captcha');
                return false;
            }
        }else{
            if (empty($this->filter_params['data']['account']) || empty($this->filter_params['data']['token'])) {
                $this->errors = $this->lang->line('third_login_param_error');
                return false;
            }
        }
        //检查是否绑定
        $this->load->model('user_model', 'user');
        
        $isbind = $this->user->is_binding($this->filter_params['data']['type'],$this->filter_params['data']['account'],$this->filter_params['data']['token']);
        if (false === $isbind) {
            $this->errors = $this->lang->line('third_login_type_error');
            return false;
        } else {
            $uid = 0;
            $new = 0;//是否新用户
            //已绑定
            if ($isbind > 0) {
                $uid = $isbind;
            } else {
                //生成一个新账号然后绑定
                $this->filter_params['data']['object_type'] = $this->platform;
                $uid = $this->user->create_bind_accout($this->filter_params['data']);
                $new=1;
            }

            if ($uid > 0) {
                //return (array )$this->user->get_userinfo($uid);
                return array("uid"=>$uid,"new"=>$new);
            }

        }

        return false;

    }

    /*
    * 注册
    */
    protected function register($data)
    {

        $this->load->model('user_model', 'user');
        $re = $this->user->register($data);
        return $re;
    }

    /*
    * 找回密码 第一步 验证用户 手机
    */
    protected function check_user($username)
    {

        $this->load->model('user_model', 'user');
        $this->load->model('binding_model', 'binding');

        $user = $this->user->username_exit($username);
        if (!$user) {
            $this->errors = $this->lang->line('user_retrieve_name');
            return false;
        }
        //手机是否认证
        $phone = $this->binding->is_binding($user['id_2buser'], 'phone');
        if (!$phone['phone_binding']) {
            $this->errors = $this->lang->line('user_retrieve_phone');
            return false;
        }
        //发送手机短信验证码
        $bind = $this->binding->get_bind($user['id_2buser']);
        $re = parent::send_captcha($bind['phone'], $user['id_2buser']);
        return $re;
    }
    /*
    * 找回密码 第二步 修改密码
    */
    protected function retrieve($data)
    {
        
        $this->load->model('user_model', 'user');
        $this->load->model('binding_model', 'binding');
        
        $user = $this->user->username_exit($this->filter_params['data']['username']);
        if (!$user) {
            $this->errors = $this->lang->line('user_retrieve_name');
            return false;
        }
        $bind = $this->binding->get_bind($user['id_2buser']);
        //判断验证码
        $re = parent::check_captcha($bind['phone'], $user['id_2buser'], $data['valicode']);
        if ($re) {
            //修改密码
            $result = $this->user->modify_user(array('password' => $data['password']), $user['id_2buser']);
            return $result;
        } else {
            $this->errors = $this->lang->line('user_retrieve_captcha');
            return false;
        }

    }

    /*
    * 修改个人资料
    */
    protected function profile($data, $userID)
    {
        $this->load->model('user_model', 'user');
        $profile = array();
        if (!empty($data['nickname'])) {
            $profile['nickname'] = $data['nickname'];
        }
        if (!empty($data['gender'])) {
            $profile['sex'] = $data['gender'];
        }
        if (!empty($data['birthday'])) {
            $profile['birthday'] = $data['birthday'];
        }
        if (!empty($data['sign'])) {
            $profile['sign'] = $data['sign'];
        }
        if (!empty($data['favicon'])) {
            $profile['head_image'] = $data['favicon'];
        }
        $result = $this->user->modify_user($profile, $userID);
        return $result;
    }

    /*
    * 绑定手机
    */
    protected function binding($data, $userID)
    {
        $this->load->model('binding_model', 'bind');
        //手机号是否已绑定
        $is_bind = $this->bind->is_account_binding('phone_number = \'' . $data['phonum'] .'\' AND phone_binding = 1');
        if ($is_bind) {
            $this->errors = $this->lang->line('user_bind_bound');
            return false;
        }
        //判断验证码
        $re = parent::check_captcha($data['phonum'], $userID, $data['valicode']);
        if (!$re) {
            $this->errors = $this->lang->line('user_retrieve_captcha');
            return false;
        }
        //绑定手机
        $info = array('phone_number' => $data['phonum'], 'phone_binding' => 1);
        $bind = $this->bind->get_bind($userID);
        if ($bind)
            $result = $this->bind->modify_bind($info, $userID);
        else {
            $info['id_2buser'] = $userID;
            $result = $this->bind->insert_bind($info);
        }
        return $result;
    }
    
    /**
     * 绑定支付宝
     */
     protected function binding_alipay($data, $userID){
        $this->load->model('binding_model', 'bind');
        $info = array('alipay_account' => $data['alipay'],'alipay_name' => $data['name'],'alipay_binding' => 1);
        $bind = $this->bind->get_bind($userID);
        if ($bind)
            $result = $this->bind->modify_bind($info, $userID);
        else {
            $info['id_2buser'] = $userID;
            $result = $this->bind->insert_bind($info);
        }
        return $result;
     }
    
    

    /*
    * 个人主页
    */
    protected function homepage($userID)
    {
        $this->load->model('user_model', 'user');
        $this->load->model('friend_model', 'friend');
        $this->load->model('cowryowner_model', 'owner');
        $this->load->model('binding_model', 'bind');
        $user = $this->user->get_userinfo($userID);
        if (!empty($user['birthday'])) {
            $user['constellation'] = $this->get_zodiac_sign($user['birthday']);
        } else {
            $user['constellation'] = '';
        }
        $user['nickname'] = !empty($user['nickname']) ? $user['nickname'] : $user['username'];
        if($user['status']!=0){
            $where='o.owner = ' . $userID;
            if($userID!=$this->token['uid']){
                $where='o.owner = ' . $userID.' AND ci.status=\'up\'';
            }
            $cowry = $this->owner->get_cowry($where,'ci.status ASC,o.created DESC', $this->filter_params['page'], 12);
            for($i=0;$i<count($cowry);$i++){
                if($cowry[$i]['num']<0){
                    $cowry[$i]['num'] = 0;
                }elseif($cowry[$i]['num']>=9999){
                    $cowry[$i]['num'] = 9999;
                }
            }
            //获取宝贝种类数量统计
            $num = $this->owner->get_cowry_count($userID,$this->token['uid']);
            $result = array('cowry' => $cowry,'globals' => array('count' => !empty($num) ? $num : 0));
        }
        //获取关系
        $user['relation'] = 0;
        if (isset($this->token['uid']) && $this->token['uid']!='') {
            if ($userID == $this->token['uid']) {
                $user['relation'] = 3;
            } else {
                $relation = $this->friend->get_relation($userID, $this->token['uid']);
                if ($relation!== FALSE) {
                    $user['relation'] = 1;
                }
            }
        }
        //宝贝
        $result['user'] = $user;
        return $result;
    }

    /*
    * 设置密码
    */
    protected function set_password($password, $userID)
    {
        $this->load->model('user_model', 'user');

        $re = $this->user->modify_user(array('password' => $password), $userID);
        return $re;
    }

    /**
     * 获取用户基本信息 昵称 头像
     */
    protected function get_baseinfo($userID)
    {
        $this->load->model('user_model', 'user');
        $re = $this->user->get_userinfo($userID);
        if (!empty($re)) {
            $return = array();
            $return['uid'] = $userID;
            $return['type']=$re['type'];
            $return['nickname'] = !empty($re['nickname']) ? $re['nickname'] : $re['username'];
            $return['favicon'] = $re['favicon'];
            $return['phone'] = $re['phone'];
            $return['binding'] = $re['phonestatus'];
            return $return;
        }
        return false;
    }

    /**
     * 获取用户消息
     */
    protected function get_user_unread($userID)
    {
        $this->load->model('user_model', 'user');
        $this->load->model('cowry_model', 'cowry');

        //聊天室
        $chat = $this->user->get_chat($userID);
        $return = array();
        if (!empty($chat)) {
            foreach ($chat as $k => $li) {
                if ($userID == $li['buyer']) {
                    $chat[$k]['uid'] = $li['vendor'];
                    $chat[$k]['nickname'] = !empty($li['vname']) ? $li['vname'] : $li['vusername'];
                    $chat[$k]['favicon'] = $li['vfavicon'];
                } elseif ($userID == $li['vendor']) {
                    $chat[$k]['uid'] = $li['buyer'];
                    $chat[$k]['nickname'] = !empty($li['bname']) ? $li['bname'] : $li['busername'];
                    $chat[$k]['favicon'] = $li['bfavicon'];
                }
                unset($chat[$k]['att_type']);
                unset($chat[$k]['vendor']);
                unset($chat[$k]['vname']);
                unset($chat[$k]['vusername']);
                unset($chat[$k]['vfavicon']);
                unset($chat[$k]['buyer']);
                unset($chat[$k]['bname']);
                unset($chat[$k]['busername']);
                unset($chat[$k]['bfavicon']);
            }
            $return['chat'] = $chat;
            $this->user->set_chat_read($userID);
        }
        //消息
        $message = $this->user->get_message($userID);
        
        if (!empty($message)) {
          foreach ($message as $key => $val) {
                unset($message[$key]['att_type']);
                unset($message[$key]['mid']);
            }
            $return['message'] = $message; //debug($message);exit;
            $this->user->set_message_read($userID);
        }
        return $return;
    }
    
    /**
     * @获取未读消息（私信、咨询、系统消息）
     */
    protected function get_msg($userID){
        $return = array();
        $this->load->model('user_model', 'user');
        //消息
        $message = $this->user->get_message($userID);
        if (!empty($message)) {
          foreach ($message as $key => $val) {
                unset($message[$key]['att_type']);
                unset($message[$key]['mid']);
            }
            $return = $message;
            $this->user->set_message_read($userID);
        }
        return $return;
        
    }
    
    
    /**
     * 获取自动评价
     * 在个人主页界面显示用户的接单评价和配送评价，
     * 评价基于订单功能中两个阶段消耗的时间来给出评价，
     * 接单时间
     * 平均低于5分钟为极快，
     * 5-10分钟为快，
     * 10-20分钟为普通，
     * 20-30分钟为慢。
     * 配送时间
     * 平均低于10分钟为极快，
     * 10-20分钟为快，
     * 20-40分钟为普通，
     * 40-60分钟为慢
     */
    protected function get_user_appraise($uid)
    {
        $this->load->model('user_model', 'user');
        //获取商家订单id--卖出订单列表
        $this->db->trans_begin();
        $orderList = $this->user->get_order_list($uid);
        //获取商家所有接单消耗时间,配送时间平均值
        if(empty($orderList)){
            $this->errors = '商家没有订单信息。';
            return false;
        }
        $avgtime = $this->user->receive_order_time($orderList);
        $return = array();
        for ($i = 0; $i < count($avgtime); $i++) {
            $time = intval($avgtime[$i]['consuming']);
            switch($avgtime[$i]['process_code']){
                case 4:
                    //接单时间
                    $rs = '';
                    if($time>0 && $time<=5){
                        $rs='极快';
                    }elseif($time>5 && $time<=10){
                        $rs='快';
                    }elseif($time>10 && $time<=20){
                        $rs='普通';
                    }elseif($time>20 && $time<=30){
                        $rs='慢';
                    }
                    $return['order']=$rs;
                    break;
                case 5:
                    //配送时间
                    $rs = '';
                    if($time>0 && $time<=10){
                        $rs='极快';
                    }elseif($time>10 && $time<=20){
                        $rs='快';
                    }elseif($time>20 && $time<=40){
                        $rs='普通';
                    }elseif($time>40 && $time<=60){
                        $rs='慢';
                    }
                    $return['delivery']=$rs;
                    break;  
            }
        }
        if ($this->db->trans_status() === false || empty($return)) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return $return;
        }
    }
    /**
     * @我的财务
     */
    protected function my_finances($userID){
        $return = array('sum'=>0,'payamount'=>0,'unpayamount'=>0,'alipaystatus'=>0);
        $this->load->model('finance_model', 'finance');
        $this->load->model('binding_model', 'bind');
        //是否绑定支付宝帐号
        $where = array('id_2buser' =>$userID, 'alipay_binding' => 1);
        $is_bind = $this->bind->is_account_binding($where);
        if($is_bind){
            $return['alipaystatus']=1;
        }
        //消息
        $finance = $this->finance->get_my_finances($userID);
        if($finance){
            for($i=0;$i<count($finance);$i++){
                if($finance[$i]['status']==3){
                    //已结算
                    $return['payamount'] = $finance[$i]['sum'];
                }else{
                    //未结算
                    $return['unpayamount'] = $return['unpayamount']+$finance[$i]['sum'];
                }
                //$return['sum']=number_format(($return['sum']+$finance[$i]['sum']),2);
                $return['sum']=$return['sum']+$finance[$i]['sum'];
            }
        }
        
        return $return ;   
    }
    
    protected function finance_list($page, $userID,$status){
        $this->load->model('finance_model', 'finance');
        $order = 'f.status ASC,f.created DESC';
        $where = "f.id_2buser = {$userID}";
        $offset = 10;
        if($status===0){
            $order='f.created DESC';
            $where = "f.status !=3 AND f.id_2buser = {$userID}";
        }elseif($status===1){
            $order='f.stock_dater DESC';
            $where = "f.status =3 AND f.id_2buser = {$userID}";
        }
        $result = $this->finance->finance_list($where,$order, $page,$offset);
        for($i=0;$i<count($result);$i++){
            if($result[$i]['status']==3){
                $result[$i]['status']=1;
            }else{
                $result[$i]['status']=0;
            }
        }
        return $result;        
    }
    
    
    /**
     * @邻售首页商铺列表
     */
    protected function shoplist()
    {
        if (in_array($this->params['type'], array('dist', 'search'))) {
            $this->load->model('user_model', 'user');
            $shop = array();
            $this->params['page'] = !empty($this->params['page']) ? intval($this->params['page']) :
                1;
            if ($this->params['type'] == 'dist') {
                //距离，检测经纬度
                if (empty($this->params['lon']) || empty($this->params['lat'])) {
                    $this->params['lon'] = '104.08084106445';
                    $this->params['lat'] = '30.66577911377';
                }
                if (!empty($this->params['lon']) && !empty($this->params['lat'])) {
                    //处理经纬度排行,处理距离
                    $userID = $this->token['uid'] ? $this->token['uid'] : 0;
                    $shop   = $this->user->get_near_shoplist($userID, $this->params['lon'], $this->params['lat'], ($this->params['page'] - 1) * 24, 24);
                    foreach($shop as $Key =>&$vals){
                         if(empty($vals['img'])){
                            $vals['img']= '/attachment/defaultimg/head.jpg';
                         }
                    }
                }
            } elseif ($this->params['type'] == 'search') {
                /*
                //处理搜索
                if (!empty($this->params['query'])) {
                    $shop = $this->order->get_search($this->params['query'], ($this->params['page'] -1) * 24, 24);
                }
                */
            }
            return $shop;

        }
        $this->errors = $this->lang->line('request_param_errors');
        return false;
    }
    

}

/* End of file user_controller.php */