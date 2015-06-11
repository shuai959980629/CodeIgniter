<?php
/**
 * Created by JetBrains PhpStorm.
 * User: MSI-1
 * Date: 13-10-29
 * Time: 下午4:52
 * To change this template use File | Settings | File Templates.
 */

class User_model extends CI_Model
{


    function __construct()
    {
        $this->load->database();
    }

    /*
    * 账号是否已经存在
    */
    public function username_exit($name)
    {

        $this->db->select('id_2buser')->from('bi_2buser')->where('username', $name);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    
    /**
     * @昵称是否已经存在
     */
    public function nickname_exit($name)
    {

        $this->db->select('id_2buser')->from('bi_2buser')->where('nickname', $name);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    
    
    /*
    * 注册
    */
    public function register($data)
    {
        $register = array(
            'username' => $data['username'],
            'password' => $data['password'],
            'created' => date('Y-m-d H:i:s', time()),
            'nickname' => $data['username']);
        $re = $this->db->insert('bi_2buser', $register);
        $return = array('uid' => $this->db->insert_id());
        return $return;
    }

    /*
    * 修改用户表（password,last_time,nickname,gender,birthday,sign）
    */
    public function modify_user($data, $userID)
    {
        $this->db->where('id_2buser', $userID);
        $re = $this->db->update('bi_2buser', $data);
        return $re;
    }
    
    /**
     * @获取个人信息
     */
    public function get_profile($uid){
        $this->db->select('bi_2buser.id_2buser as uid,username,nickname,type,status,sex AS gender, birthday, sign, head_image AS favicon, background_image AS backimg,phone_number as phone, phone_binding AS phonestatus , alipay_binding AS alipaystatus,alipay_account AS alipay')
        ->from('bi_2buser')
        ->where('bi_2buser.id_2buser', $uid)
        ->join('bi_2buser_binding','bi_2buser_binding.id_2buser = bi_2buser.id_2buser', 'left');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        if($return){ 
           if(empty($return['favicon'])){
                $return['favicon'] = '/attachment/defaultimg/head.jpg';
            }
            /**
             * @判断用户类型。。。shop：店铺。normal：普通
             */
            if($return['type']=='shop'){
                $shopinf= $this->query_shop_info($return['uid']);
                $return['address'] =$shopinf['address']; 
                $return['contact']=$shopinf['shopphone'];
                $return['description'] = $shopinf['description'];
                $return['show'] =1;
            }else{
                //获取默认的地址
                $contact = $this->get_address($uid);
                if($contact){
                    $return['address'] = $contact['address'];
                    if(!$return['phonestatus']){
                        $return['phone'] = $contact['phone'];
                    }
                    //是否可见
                    $show = $this->is_show_privacy($uid,'address');
                    if($show){
                        $return['show'] = $show;
                    }                
                }  
           } 
        }
        return $return;
    }
    
    
    
    /**
     * 获取用户信息
     */
    public function get_userinfo($uid)
    {
        $this->db->select('bi_2buser.id_2buser as uid,username,nickname,type, status,sex AS gender, birthday, sign, head_image AS favicon, background_image AS backimg,phone_number as phone, phone_binding AS phonestatus , alipay_binding AS alipaystatus,alipay_account AS alipay')
        ->from('bi_2buser')
        ->where('bi_2buser.id_2buser', $uid)
        ->join('bi_2buser_binding','bi_2buser_binding.id_2buser = bi_2buser.id_2buser', 'left');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        if($return){ 
            if(empty($return['favicon'])){
                $return['favicon'] = '/attachment/defaultimg/head.jpg';
            }
            /**
             * @判断用户类型。。。shop：店铺。normal：普通
             */
            if($return['type']=='shop'){
                $shopinf= $this->query_shop_info($return['uid']);
                $return['address'] =$shopinf['address']; 
                $return['contact']=$shopinf['shopphone'];
                $return['description'] = $shopinf['description'];
                $return['show'] =1;
            }else{
                //获取默认的地址
                $contact = $this->get_address($uid);
                if($contact){
                    $return['address'] = $contact['address'];
                    if(!$return['phonestatus']){
                        $return['phone'] = $contact['phone'];
                    }
                    //是否可见
                    $show = $this->is_show_privacy($uid,'address');
                    if($show){
                        $return['show'] = $show;
                    }                
                }  
            }
        }
        return $return;
    }
    
    
    /**
     * 获取用户信息
     * @AND c.default=1 AND c.default!=-1
     */
    public function get_user_list($where, $limit, $start = 0)
    {
        $this->db->select('u.id_2buser AS uid,u.username,u.nickname,u.type,u.created,u.status,u.head_image AS favicon,u.background_image AS backimg,b.phone_number as phone,b.phone_binding as phonestatus,c.id_contact AS aid,c.address')
        ->from('bi_2buser as u')
        ->join('bi_2buser_binding as b','b.id_2buser=u.id_2buser','left')
        ->join('bi_contact as c','u.id_2buser = c.id_2buser','left')
        ->order_by('u.created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $this->db->group_by('u.id_2buser');
        $result = $this->db->get()->result_array();
        //echo $this->db->last_query();
        $return = !empty($result) ? $result : false;
        if($return){
            //获取默认的地址
            for($i=0;$i<count($return);$i++){
                if(empty($return[$i]['favicon'])){
                    $return[$i]['favicon'] = '/attachment/defaultimg/head.jpg';
                }
                /**
                 * @判断用户类型。。。shop：店铺。normal：普通
                 */
                if($return[$i]['type']=='shop'){
                    $shopinf= $this->query_shop_info($return[$i]['uid']);
                    $return[$i]['address'] =$shopinf['address']; 
                    $return[$i]['phone']=$shopinf['shopphone'];
                    $return[$i]['description'] = $shopinf['description'];
                    $return[$i]['sid']=$shopinf['sid'];
                    $return[$i]['show'] =1;
                }else{
                    //获取默认的地址
                    $contact = $this->get_address($return[$i]['uid']);
                    if($contact){
                        $return[$i]['address'] = $contact['address'];
                        if(!$return[$i]['phonestatus']){
                            $return[$i]['phone'] = $contact['phone'];
                        }
                        //是否可见
                        $show = $this->is_show_privacy($return[$i]['uid'],'address');
                        if($show){
                            $return[$i]['show'] = $show;
                        }                
                    }
                }
            }            
        }
        return $return;
    }
    
    /**
     * @获取具体某个商铺的信息
     */
    public function query_shop_info($uid){
        $this->db->select('id_shop AS sid,address,description,name AS shopname,phone AS shopphone')->from('bi_shop')->where('bi_shop.id_2buser', $uid);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    
    /**
     * 获取用户的联系信息
     */
     public function query_contact($where){
       $this->db->select('*') ->from('bi_contact')->where($where);
       $result = $this->db->get()->result_array();
       $return = !empty($result) ? $result[0] : false;
       return $return;
     }
    
    
    
    /**
     * 获取默认的地址
     */
     public function get_address($uid){
       $this->db->select('bi_contact.address,bi_contact.cell_phone AS phone') ->from('bi_contact')->where('bi_contact.id_2buser', $uid)->where('bi_contact.default',1);
       $result = $this->db->get()->result_array();
       $return = !empty($result) ? $result[0] : false;
       if(!$return){
            return false;
       }
       return $return;
     }
    
    /**
     *  查看个人隐私，是否可见
     */
     public function is_show_privacy($uid,$type){
        $this->db->select('bi_2buser_privacy.level') 
        ->from('bi_2buser_privacy')
        ->where('bi_2buser_privacy.id_2buser', $uid)
        ->where('bi_2buser_privacy.object_type',$type);
       $result = $this->db->get()->result_array();
       $return = !empty($result) ? $result[0]['level'] : array();
       return $return;
     }
    
    

    /**
     * 更新用户设备
     * @param int $uid
     * @param array $data
     */
    public function update_device_info($uid, $data)
    {

        return $this->db->update('bi_2buser', $data, array('id_2buser' => $uid));

    }
    /**
     * @查询设备码,删除已经存在的设备码。确保设备码唯一
     */
     public function delete_device_token($where){
        $this->db->select('id_2buser')->from('bi_2buser')->where($where);
        $result = $this->db->get()->result_array();
        if(!empty($result)){
            $dta = array('device_token'=>'','client_number'=>'');
            for($i=0;$i<count($result);$i++){
                $this->update_device_info($result[$i]['id_2buser'],$dta);
            }
        }
     }

    public function validation_password($where)
    {

        $result = $this->db->get_where('bi_2buser', $where)->result_array();
        if (!empty($result)) {
            return true;
        }
        return false;

    }


    /**
     * 普通登陆
     * @param string $username
     * @param string $password
     * @return Ambigous <boolean, array>
     */
    public function login($username, $password)
    {

        $this->db->select('id_2buser as uid,nickname,head_image as favicon')->from('bi_2buser')->
            where(array('username' => $username, 'password' => $password))->limit(1);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : array();
        return $return;

    }


    /**
     * 查询账号是否绑定，并取出uid
     */
    public function is_binding($type, $accout,$token)
    {

        $where = array();
        if ($type == 'sina') {
            $where['sina_binding'] = 1;
            //$where['sina_account'] = $accout;
            $where['sina_token'] = $token;
        } elseif ($type == 'qq') {
            $where['qq_binding'] = 1;
            //$where['qq_account'] = $accout;
            $where['qq_token'] = $token;
        } elseif ($type == 'phone') {
            $where['phone_binding'] = 1;
            $where['phone_number'] = $accout;
        } elseif ($type == 'hipigo') {
            $where['hipigo_bingding'] = 1;
            //$where['hipigo_account'] = $accout;
            $where['hipigo_token'] = $token;
        }
        if (empty($where)) {
            return false;
        }
        $this->db->select('id_2buser as uid')->from('bi_2buser_binding')->where($where)->limit(1);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['uid'] : 0;
        return $return;

    }


    /**
     * 绑定登陆账号
     * @param int $uid
     * @param string $type
     * @param string $accout
     * @return boolean
     */
    public function create_bind_accout($data)
    {

        $insert_data = array();
        $user_data = array();
        if ($data['type'] == 'sina') {
            $insert_data['sina_binding'] = 1;
            $insert_data['sina_account'] = $data['account'];
            $insert_data['sina_token'] = $data['token'];
        } elseif ($data['type'] == 'qq') {
            $insert_data['qq_binding'] = 1;
            $insert_data['qq_account'] = $data['account'];
            $insert_data['qq_token'] = $data['token'];
        } elseif ($data['type'] == 'phone') {
            $insert_data['phone_binding'] = 1;
            $insert_data['phone_number'] = $data['account'];
        } elseif ($data['type'] == 'hipigo') {
            $insert_data['hipigo_bingding'] = 1;
            $insert_data['hipigo_account'] = $data['account'];
            $insert_data['hipigo_token'] = $data['token'];
        }
        if (empty($insert_data)) {
            return false;
        }
        if($data['type']=='phone'){
            $random = getRandom(5);
            $user_data['username'] = $data['account'];//手机号码。。帐号
            $user_data['nickname'] = "邻售".$random; //昵称
        }else{
            $random = getRandom(8);
            $user_data['username'] = "LS".$random;// 帐号
            $user_data['nickname'] = $data['account'];//昵称
        }
        $user_data['object_type']= $data['object_type'];
        $this->db->insert('bi_2buser', $user_data);
        $insert_data['id_2buser'] = $this->db->insert_id();

        $this->db->insert('bi_2buser_binding', $insert_data);

        return $insert_data['id_2buser'];

    }

    /**
     * 用户是否存在
     */
    public function user_exit($userID)
    {
        $this->db->select('id_2buser as uid')->from('bi_2buser')->where('id_2buser', $userID)->
            limit(1);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['uid'] : false;
        return $return;
    }


    /**
     * 获取聊天室
     */
    public function get_chat($userID)
    {
        $this->db->select('c.id_orders as oid,operation,c.created as time,c.object_type as contype,a.object_type as att_type,view_url as path,view_time as viewtime,c.content,buyer,buyer.nickname as bname,buyer.username as busername,buyer.head_image as bfavicon,vendor,vendor.nickname as vname,vendor.username as vusername,vendor.head_image as vfavicon')->
            from('bi_ta_chat as c')->join('bi_attachment_item as a',
            'a.id_attachment = c.id_attachment', 'left')->join('bi_ta_orders as o',
            'o.id_orders = c.id_orders', 'left')->join('bi_2buser as buyer',
            'buyer.id_2buser = o.buyer', 'left')->join('bi_2buser as vendor',
            'vendor.id_2buser = o.vendor', 'left')->where('c.id_2buser', $userID)->where('is_read',
            0);
        $result = $this->db->get()->result_array();
        $return = array();
        if(!empty($result)){
            for($i=0;$i<count($result);$i++){
                if($result[$i]['contype']=='img'||$result[$i]['contype']=='audio'||$result[$i]['att_type']=='img'|| $result[$i]['att_type']=='audio'){
                    $result[$i]['contype'] = !empty($result[$i]['contype'])?$result[$i]['contype']:$result[$i]['att_type'];
                    $result[$i]['content'] = $result[$i]['path'];
                }
            }
            $return =  $result;
        }
        return $return;
    }

    /**
     * 聊天室 修改为已读
     */
    public function set_chat_read($userID)
    {
        return $this->db->update('bi_ta_chat', array('is_read' => 1), array('id_2buser' =>$userID));
    }

    /**
     * 获取消息
     */
    public function get_message($userID)
    {
        $this->db->select('s.id_message AS mid,s.sender as uid,u.nickname,u.head_image as favicon,s.object_type as type,s.id_object as cid,s.content_type as contype,s.content,s.created as time,a.object_type as att_type,a.view_url as path,a.view_time as viewtime')->
            from('bi_message as s')->join('bi_attachment_item as a','a.id_attachment = s.id_attachment', 'left')
            ->join('bi_2buser as u','u.id_2buser = s.sender', 'left')
            ->where('s.receiver', $userID)->where('is_read',0);
        $this->db->order_by('s.created DESC');
        $result = $this->db->get()->result_array();
        $return = array();
        if(!empty($result)){
            for($i=0;$i<count($result);$i++){
                if($result[$i]['contype']=='img'||$result[$i]['contype']=='audio'||$result[$i]['att_type']=='img'|| $result[$i]['att_type']=='audio'){
                    $result[$i]['contype'] = !empty($result[$i]['contype'])?$result[$i]['contype']:$result[$i]['att_type'];
                    $result[$i]['content'] = $result[$i]['path'];
                }
            }
            $return =  $result;
        }
        return $return;
    }
    /**
     * 私信 修改为已读
     */
    public function set_message_read($userID)
    {
        return $this->db->update('bi_message', array('is_read' => 1), array('receiver'=>$userID));
    }

    /**
     *获取商家所有接单消耗时间,配送时间平均值
     * SELECT `process_code`,AVG(consuming) as  t_avg FROM `bi_ta_order_process` where id_orders
     * in (select id_orders from bi_ta_orders where vendor =57 and object_type ='retail')
     * and (process_code=4 or process_code =5)  
     * GROUP BY process_code;
     */

    public function receive_order_time($data)
    {
        //$where = array('process_code' => 4, 'process_code' => 5);
        $where = '(process_code = 4 OR process_code = 5)';
        $this->db->select('process_code')->select_avg('consuming')->from('bi_ta_order_process')->
            where_in('id_orders', $data)->where($where)->group_by('process_code');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : array();
        return $return;

    }


    /**
     * 获取订单列表
     * select id_orders from bi_ta_orders where vendor =57 and object_type ='retail'
     */

    public function get_order_list($uid)
    {
        $this->db->select("id_orders")->from('bi_ta_orders')->where(array("vendor" => $uid,
                "object_type" => 'retail'));
        $result = $this->db->get()->result_array();
        $return = array();
        if (!empty($result)) {
            for ($i = 0; $i < count($result); $i++) {
                $return[$i] = $result[$i]['id_orders'];
            }
        }
        return $return;
    }
    
    /**
     * @用户登录手机验证记录
     * @param data 数据
     * @param 返回插入数据的id
     */
     public function insert_record($data){
        $this->db->insert('bi_record', $data);
        $id = $this->db->insert_id();
        return $id;
     }
    
    /**
     * 修改用户登录手机验证记录
     * @param array $where
     * @param array $data
     */
    public function modify_record($where,$data){
    	
    	return $this->db->update('bi_record',$data,$where);
    	
    }
    
    
    public function query_record($where){
        $this->db->select('count(*) as num')->from('bi_record')->where("date(created)=curdate()");
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result[0]) ? $result[0]['num'] : false;
        return $return;
    }
    
    
    
    /**
     * @用户店铺验证
     * @param data 数据
     * @param 返回插入数据的id
     */
     public function insert_shop($data){
        $this->db->insert('bi_shop', $data);
        $id = $this->db->insert_id();
        return $id;
     }
    
    /**
     * @修改用户店铺验证
     * @param array $where
     * @param array $data
     */
    public function modify_shop($where,$data){
    	
    	return $this->db->update('bi_shop',$data,$where);
    	
    }
    
    /**
     * @删除用户店铺验证
     * @param array $where
     */
    public function del_shop($where){
        return $this->db->delete('bi_shop', $where);
    }
    
    /**
     * @查询店铺验证信息
     */
    public function query_shop($where){
       $this->db->select('*') ->from('bi_shop')->where($where);
       $result = $this->db->get()->result_array();
       $return = !empty($result) ? $result[0] : false;
       return $return;
    }
    
    
    
    /**
     * 获取最近的用户店铺列表
     * @param double $userID  //当前的用户
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     */
    public function get_near_shoplist($userID, $mylong, $mylat, $start, $limit)
    {
        $sql = "SELECT s.id_2buser AS uid,s.id_shop AS sid,name,s.phone,s.address,s.description,u.head_image AS img,ROUND((6378137 * 2 * ASIN(SQRT(POWER(SIN(($mylat - s.latitude) * PI()/180 / 2), 2) + COS($mylat * PI()/180) *  COS(s.latitude * PI()/180) * POWER(SIN(($mylong - s.longitude) * PI()/180 / 2), 2))))/1000,4) AS distance FROM bi_2buser AS u LEFT JOIN bi_shop AS s ON u.id_2buser = s.id_2buser WHERE  u.`status`=1 AND u.`type`='shop' AND s.id_2buser!='' ORDER BY distance ASC,u.created DESC LIMIT $start,$limit";
        return $this->db->query($sql)->result_array();
    }
    
    
    /**
     * @获取用户商铺权限
     */
    public function query_right($where){
       $this->db->select('max') ->from('bi_2buser_right')->where($where);
       $result = $this->db->get()->result_array();
       $return = !empty($result) ? $result[0] : false;
       return $return;
    }
    
    /**
     * @获取用户商铺权限列表
     */
    public function get_permissions_list($where, $limit, $start = 0)
    {
        $this->db->select('id_right AS rid,profile AS role,max')->from('bi_2buser_right as r');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        //echo $this->db->last_query();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
    
    /**
     * @修改用户商铺权限
     * @param array $where
     * @param array $data
     */
    public function modify_right($where,$data){
    	
    	return $this->db->update('bi_2buser_right',$data,$where);
    	
    }


    /**
     * zxx
     * 获取当前登录用户信息
     */
    public function get_user_info($where){
        $this->db->select('id_2buser as uid,username,nickname,type,status,sex AS gender, birthday, sign, head_image')
            ->from('bi_2buser');

        if($where){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return $result;
    }


}
