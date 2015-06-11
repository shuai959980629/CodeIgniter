<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rjy
 * Date: 13-12-17
 * Time: 下午2:12
 * Version 1.1
 * To change this template use File | Settings | File Templates.
 */
class Order_model extends CI_Model
{

    protected $table = 'bi_ta_orders';

    function __construct()
    {
        $this->load->database();
    }

    /**
     * @获取最近的宝贝列表
     * @param double $userID  //当前的用户
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     */
    public function get_near_cowrylist($userID, $mylong, $mylat, $start, $limit)
    {
        $sql = "CALL get_near_cowry($userID,$mylong,$mylat,$start,$limit)";
        return $this->db->query($sql)->result_array();
    }
    
    
    /**
     * @获取标签关联的宝贝列表
     * @param double $id_tag  //标签id
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     */
    public function get_cowry_by_tag($id_tag,$mylong, $mylat, $start, $limit){
        $sql = "CALL get_cowry_by_tag($id_tag,$mylong,$mylat,$start,$limit)";
        return $this->db->query($sql)->result_array();
    }
    

    /**
     * @获取专题活动列表
     * @param double $id_theme//专题id
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     */
    public function get_cowry_by_theme($id_theme,$mylong, $mylat, $start, $limit){
        $sql = "CALL get_cowry_by_theme($id_theme,$mylong,$mylat,$start,$limit)";
        return $this->db->query($sql)->result_array();
    }

    /**
     * 获取搜索结果
     * @param string $key
     * @param int $start
     * @param int $limit
     * @return array $return
     */
    public function get_search($key, $start, $limit)
    {

        $return = array();
        $this->db->select("ci.id_cowry as cid,cover_image AS img,description,o.quantity as num,price,ci.created as addtime,owner as uid,u.nickname")
             ->from("bi_cowry_info as ci")
             ->like("ci.description", $key)
             ->where('ci.status','up')
             ->where('o.quantity !=', 0)
             ->where('o.status', 1)
             ->where('u.status', 1)
             ->order_by('ci.created', 'desc')
             ->limit($limit, $start)
             ->join('bi_cowry_owner as o', "ci.id_cowry=o.id_cowry", 'left')
             ->join('bi_2buser as u','u.id_2buser=o.owner', 'left');
        $result = $this->db->get()->result_array();
        //echo $this->db->last_query();
        $return = !empty($result) ? $result : '';
        return $return;
    }

    /**
     * 添加订单
     */
    public function add_order($data)
    {
        $this->db->insert($this->table, $data);
        $oid = $this->db->insert_id();
        return $oid;
    }

    /**
     * 修改订单
     */
    public function modify_order($data, $orderID)
    {
        return $this->db->update($this->table, $data, array('id_orders' => $orderID));
    }
    
    public function modify_batch_order($data,$where){
        return $this->db->update_batch($this->table,$data,$where);
    }
    
    
    /**
     * @删除订单
     */
    public function del_order($where){
        return $this->db->delete($this->table, $where);
    }
    
    /**
     * @查询订单
     */
    public function query_order($orderID){
        $this->db->select("*")->from($this->table)->where('id_orders',$orderID);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    /**
     * @查询订单id
     */
    public function get_order($where){
        $this->db->select("*")->from($this->table)->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
     
    /**
     * @查询订单基本信息
     */
    public function query_order_information($oid){
        
        $this->db->select("o.id_orders as oid,o.payment,o.order_no,o.trade_no,o.extra,o.created,o.status,total_amount as total_price,total_quantity as total_num,buyer,vendor,t.id_cowry AS cid,t.owner,t.image,t.description,t.price,t.quantity AS num,t.amount,t.name,t.phone,t.address,o.created")
        ->from($this->table . ' as o')
        ->join('bi_ta_orders_item AS t','t.id_orders = o.id_orders','left')
        ->where('o.id_orders', $oid);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
        
    }
    
    /**
     * @根据条件查询基本信息
     */
    public function query_order_list($where){
        $this->db->select("o.id_orders as oid,o.payment,o.order_no,o.trade_no,o.extra,o.created,o.status,total_amount as total_price,total_quantity as total_num,buyer,vendor,t.id_cowry AS cid,t.owner,t.image,t.description,t.price,t.quantity AS num,t.amount,t.name,t.phone,t.address,o.created")
        ->from($this->table . ' as o')
        ->join('bi_ta_orders_item AS t','t.id_orders = o.id_orders','left')
        ->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
    
    /**
     * @生成虚拟订单
     */
    public function insert_vorder($data){
        $this->db->insert('bi_ta_vorder', $data);
        $oid = $this->db->insert_id();
        return $oid;
    }
    
    /**
     * @修改虚拟订单
     */
    public function modify_vorder($data,$where){
        return $this->db->update('bi_ta_vorder', $data,$where);
    }
    
    /**
     * @查询是否存在虚拟订单
     */
    public function v_exists($where){
        $this->db->select("v.id_orders as oid")->from('bi_ta_vorder AS v')->where($where)->order_by('v.created desc')->limit(1);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]: false;
        return $return;
    }
    
    /**
     * @查询虚拟订单。和宝贝信息
     */
    public function get_vorder($where, $order, $page, $offset){
        $this->db->select('v.id_orders AS oid,v.id_cowry AS cid,v.status,vendor,buyer,ci.price,ci.description,ci.cover_image AS img,ci.creator AS uid')
            ->from('bi_ta_vorder AS v')
            ->join('bi_cowry_info as ci',"v.id_cowry = ci.id_cowry", 'left')
            ->join('bi_cowry_owner as o','o.id_cowry = v.id_cowry','left')
            ->order_by($order ? $order :'v.created DESC')
            ->limit($offset, $offset * ($page - 1));
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : array();
        //echo $this->db->last_query();
        return $return;
    }

    /**
     * 获取订单状态
     */
    public function get_order_status($orderID)
    {
        $this->db->select("id_orders as oid,status")->from($this->table)->where('id_orders',$orderID);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['status'] : false;
        return $return;
    }
    
    /**
     * @查询订单明细
     */
    public function query_order_item($orederID){
        $this->db->select("*")->from('bi_ta_orders_item')->where(array('id_orders' => $orederID));
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    /**
     * 添加订单明细
     */
    public function add_order_item($data)
    {
        $this->db->insert('bi_ta_orders_item', $data);
    }

    /**
     * 修改订单明细
     */
    public function modify_order_item($data, $orederID, $cowryID)
    {
        return $this->db->update('bi_ta_orders_item', $data, array('id_orders' => $orederID,'id_cowry' => $cowryID));
    }
    
    /**
     * 删除订单明细
     */
    public function del_order_item($where)
    {
        $this->db->delete('bi_ta_orders_item', $where);
    }
    
    
    /**
     * @获取订单列表，
     * @用途：1，后台分页查询。。。
     */
    public function get_order_list($where, $limit, $start = 0){
        $this->db->select("id_orders as oid,o.order_no,o.payment,o.trade_no,o.extra,o.created,o.status,o.cpt_result,o.cpt_comment,total_amount as total_price,total_quantity as total_num,vendor,vendor_email,vendor.nickname as vendor_nickname,vendor.username as vendor_username,vendor.head_image as vendor_head_image,buyer,buyer_email,buyer.nickname as buyer_nickname,buyer.username as buyer_username,buyer.head_image as buyer_head_image")
            ->from($this->table . ' as o')
            ->join('bi_2buser as buyer',"buyer.id_2buser=o.buyer", 'left')
            ->join('bi_2buser as vendor',"vendor.id_2buser=o.vendor", 'left')
            ->order_by('o.created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result:FALSE;
        return $return;
    }
    
    /**
     * 
     */
    public function get_review_list($where, $limit, $start = 0){
        $this->db->select("*")->from('bi_auditing')->order_by('status ASC,created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result:FALSE;
        return $return;
    }
    

    /**
     * 获取订单卖家买家
     */
    public function get_member($orderID)
    {
        $this->db->select("id_orders as oid,buyer,vendor")->from($this->table)->where('id_orders',
            $orderID);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    
    /**
     * @正在处理的订单列表（统计）
     * 2:已付款,3:投诉中,
     */
    public function ordering($where){
        $this->db->select('count(o.id_orders) as total')
            ->from($this->table . ' as o')
            ->join('bi_ta_orders_item as t',"t.id_orders=o.id_orders", 'left')
            ->where('t.id_cowry IS NOT NULL');
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result[0] : 0;
        //return $this->db->last_query();
        return $return;
        
    }
    
    /**
     * @获取卖出,买入,投诉中的订单列表
     */
    public function orderlist($where, $order, $page, $offset){
        $this->db->select('o.id_orders AS oid,o.payment,t.id_cowry AS cid,vendor,buyer,total_amount as total_price,t.price,t.quantity AS num,o.status,t.description,t.image AS img,t.owner AS uid')
            ->from($this->table . ' as o')
            ->join('bi_ta_orders_item as t',"t.id_orders=o.id_orders", 'left')
            ->where('t.id_cowry IS NOT NULL')
            ->order_by($order ? $order :'o.created DESC')
            ->limit($offset, $offset * ($page - 1));
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : array();
        //return $this->db->last_query();
        return $return;
    }
    
    /**
     * 添加地址
     */
    public function add_connect($data)
    {
        $this->db->insert('bi_contact', $data);
        $aid = $this->db->insert_id();
        return $aid;
    }
    /**
     * 查找地址
     */
     public function get_adress_inf($aid){
        $this->db->select("id_contact as aid,name,latitude as lat,longitude as lon ,cell_phone as phone,address,default as is_default")->from('bi_contact')->where('id_contact',$aid);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
        
     }
     
    /**
     * 添加默认地址是否可见
     */
    public function add_privacy($data)
    {
        $this->db->insert('bi_2buser_privacy', $data);
        $aid = $this->db->insert_id();
        return $aid;
    }
    /**
     * 查询地址可见信息是否已经保存
     */
    public function get_privacy($where)
    {
        $this->db->select("level,id_privacy as pid")->from('bi_2buser_privacy')->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : array();
        return $return;
    }
    /**
     * 修改地址是否可见。
     */
    public function modify_privacy($data,$pid){
        return $this->db->update('bi_2buser_privacy', $data, array('id_privacy' => $pid));
    }
    /**
     * 修改地址
     */
    public function modify_connect($data, $aid)
    {
        return $this->db->update('bi_contact', $data, array('id_contact' => $aid));
    }

    /**
     * 删除地址
     */
    public function delete_connect($aid)
    {
        return $this->db->delete('bi_contact', array('id_contact' => $aid));
    }
    /**
     * 隐私中地址信息
     */
    public function delete_privacy($where){
        return $this->db->delete('bi_2buser_privacy',$where);
    }

    /**
     * 获取地址数量
     */
    public function get_connect_num($userID)
    {
        $this->db->select("count(id_contact) as count")->from('bi_contact')->where('id_2buser',$userID);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['count'] : false;
        return $return;
    }
    /**
     * 获取地址数量
     */
     public function get_address_num($where){
        $this->db->select("count(id_contact) as count")->from('bi_contact')->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['count'] : false;
        return $return;
     }

    /**
     * 获取地址列表
     */
    public function get_address($where)
    {
        $this->db->select("id_contact as aid,name,latitude as lat,longitude as lon ,cell_phone as phone,address,default as is_default")
        ->from('bi_contact')->where($where)->order_by('default DESC,aid desc');
        $result = $this->db->get()->result_array();
        if(!empty($result)){
           for($i=0;$i<count($result);$i++){
                if($result[$i]['is_default']==1){
                    $pri = $this->get_privacy(array('id_2buser' => $where['id_2buser'], 'object_type' =>'address'));
                    if(!empty($pri)){
                        $result[$i]['show'] = $pri[0]['level'];
                    }
                }            
           }            
        }
        //echo $this->db->last_query();
        $return = !empty($result) ? $result : array();
        return $return;
    }
    /**
     * 获取默认地址id
     */
     public function  get_default_address($uid){
        $this->db->select("id_contact as aid,name,latitude as lat,longitude as lon ,cell_phone as phone,address,default as is_default")
        ->from('bi_contact')->where('default',1)->where('id_2buser',$uid);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result :false;
        return $return;
        
     }
    

    /**
     * 获取订单中 宝贝 的所有内容
     */
    public function get_all_order_cowry_item($orderID)
    {
        $this->db->select("id_orders as oid,t.id_cowry as cid,c.price,c.latitude,c.longitude,c.address,c.description,c.cover_image,GROUP_CONCAT(CONCAT(a.att_img)) AS img_url")->
            from('bi_ta_orders_item as t')->join('bi_cowry_info as c',
            "c.id_cowry=t.id_cowry", 'left')->join('bi_cowry_attachment as a',
            "c.id_cowry=a.id_cowry", 'left')->where('id_orders', $orderID);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    /**
     * 根据时间（id_chat）获取chat最新。的记录
     */
     public function get_chat_time($where){
        
        $this->db->select("created")->from("bi_ta_chat")->where($where)->order_by("id_chat DESC")->limit(1,0);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['created']: 0;
        //return $this->db->last_query();
        return $return;
     }
     /**
      * 获取未读订单消息
      */
      public function get_unread_chat($where){
        $this->db->select("count(*) as num")->from("bi_ta_chat")->where($where)->where('is_read',0);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['num']: 0;
        //return $this->db->last_query();
        return $return;
      }
    

}
