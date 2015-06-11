<?php
/**
 * Created by JetBrains PhpStorm.
 * User: MSI-1
 * Date: 13-10-31
 * Time: 下午5:24
 * To change this template use File | Settings | File Templates.
 */

class Cowry_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * 添加
     */
    public function add_cowry($data){
        $this->db->insert('bi_cowry_info', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    
    /**
     * @上架宝贝。添加信息到用户动态表
     */
    public function add_cowry_dynamic($data){
       $where = array(
        'object_id' => $data['object_id'],
        'id_2buser' => $data['id_2buser'],
        'object_type' => 'up'
        );
        $res = $this->query_dynamic($where);
        if(!$res){
             $this->db->insert('bi_dynamic', $data);
             $lastid = $this->db->insert_id();
             return $lastid;
        }else{
            return true;
        }
       
    }
    
    /**
     * @删除动态
     */
    public function del_dynamic($where){
        return $this->db->delete('bi_dynamic', $where);
    }
    
    
    /**
     * 查询用户动态表。查看上架宝贝是否已经存在
     */
     public function query_dynamic($where){
        $this->db->select('*')->from('bi_dynamic')->where($where)->limit(1);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
     }
    
    
    /*
     * 修改
     */
    public function update_cowry($data,$id)
    {
        $this->db->where('id_cowry', $id);
        $re = $this->db->update('bi_cowry_info', $data);
        return $re;
    }

    /*
     * 获取宝贝详情
     * @version 1.1
     */
    public function get_cowry($cid,$uid)
    {
        $this->db->select('u.id_2buser AS uid,u.head_image AS favicon,u.nickname,u.username,u.type,description,price,ci.like AS zan,ci.status,o.status as valid,ci.id_cowry AS cid,ci.id_contact as aid,d.name,d.cell_phone,d.address,ci.latitude as lat,ci.longitude as lon,o.quantity AS num,GROUP_CONCAT(CONCAT(a.att_img)) AS img')
            ->from('bi_cowry_info AS ci')
            ->join('bi_cowry_owner AS o', 'o.id_cowry = ci.id_cowry', 'left')
            ->join('bi_2buser AS u', 'o.owner = u.id_2buser', 'left')
            ->join('bi_cowry_attachment AS a', 'a.id_cowry = ci.id_cowry', 'left')
            ->join('bi_contact as d',"d.id_contact = ci.id_contact", 'left')
            ->where('ci.id_cowry = '.$cid . ' AND o.owner = ' . $uid . ' AND o.status = 1')
            ->limit(1);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /**
     * 获取宝贝基本信息
     */
    public function get_cowry_baseinfo($cid,$uid)
    {
        $this->db->select('description,ci.id_cowry AS cid,o.owner AS uid,ci.price,ci.status,o.quantity AS num,ci.cover_image AS img,ci.address')
            ->from('bi_cowry_info AS ci')
            ->join('bi_cowry_owner AS o', 'o.id_cowry = ci.id_cowry', 'left')
            ->where('ci.id_cowry = '.$cid . ' AND o.owner = ' . $uid . ' AND o.status = 1')
            ->limit(1);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }
    
    /**
     * @查询宝贝所有图片
     */
    public function get_cowry_attachment($cid){
        $this->db->select('att_img as img')->from('bi_cowry_attachment')->where('id_cowry = '.$cid)->order_by('id_attachment DESC');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : array();
        return $return;
    }
    /**
     * 检查宝贝是否可以编辑
     * @version 1.1
     */
    public function check_cowry_edit($uid,$cid){
    	
    	$this->db->select('a.id_cowry_owner,a.id_cowry as cid,a.owner,a.quantity as num,b.description,b.cover_image as faceimg')
    	->from('bi_cowry_owner as a')
    	->where(array('a.owner'=>$uid,'a.id_cowry'=>$cid,'a.status'=>1))
    	->join('bi_cowry_info as b','a.id_cowry=b.id_cowry')
    	->limit(1);
    	
    	$result = $this->db->get()->result_array();
    	if(!empty($result[0])){
    		return $result[0];
    	}
    	return FALSE;
    	
    }
    
    
    /**
     * 宝贝是否被交易
     * false代表没有被交易
     * @version 1.1
     */
    public function is_trade($cid){
    	
    	$query = $this->db->get_where('bi_ta_orders_item',array('id_cowry'=>$cid));
    	$result = $query->result_array();
    	if(!empty($result)){
    		return $result;
    	}
    	return FALSE;
    	
    }
    
    
    /**
     * 修改原宝贝
     * @param array $where
     * @param array $data
     */
    public function edit_cowry($where,$data){
    	
    	return $this->db->update('bi_cowry_info',$data,$where);
    	
    }


    /**
     * 获取宝贝库存
     * @param int $uid
     * @param int $cid
     */
    public function get_cowry_num($uid,$cid)
    {
        $this->db->select('quantity as num')
            ->from('bi_cowry_owner')
            ->where(array('owner'=>$uid,'id_cowry'=>$cid,'status'=>1))
            ->limit(1);

        $result = $this->db->get()->result_array();
        if(!empty($result[0])){
            return $result[0];
        }
        return FALSE;
    }
    /**
     * 编辑宝贝库存
     * @param int $uid
     * @param int $cid
     */
    public function edit_num($uid,$cid,$num){

        return $this->db->update('bi_cowry_owner',array('quantity'=>$num),array('id_cowry'=>$cid,'owner'=>$uid));

    }


    /**
     * 宝贝房
     */
    public function cowry_room($data)
    {
        $re = $this->db->insert('bi_cowry_room',$data);
        return $re;
    }

    /**
     * 获取用户上贡宝贝数量
     */
    public function get_tribute_count($userIDs)
    {
        $this->db->select('vendor AS uid,SUM(quantity) AS count')
            ->from('bi_cowry_transaction')
            ->where('vendor IN ('.implode(',', $userIDs).')')
            ->where('object_type','tribute')
            ->order_by('uid');

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }

    /**
     * 宝贝是否存在
     * @version 1.1
     */
    public function is_cowry($cowryID)
    {
        $this->db->select('id_cowry,creator')
            ->from('bi_cowry_info')
            ->where('id_cowry = ' . $cowryID);
        $result =  $this->db->get()->result_array();
        if(!empty($result)){
            return $result[0];
        }
        return FALSE;
    }
    
    /**
     * @author zhoushuai
     * 宝贝是否存在
     * @version 2.0
     */
    
    public function exist_in_cowrys($cid,$uid){
        $this->db->select('id_cowry AS cid,creator AS uid')
            ->from('bi_cowry_info')
            ->where(array('id_cowry'=>$cid,'creator'=>$uid));
        $result =  $this->db->get()->result_array();
        if(!empty($result)){
            return $result[0];
        }
        return FALSE;
    }
    
    
    /**
     * 获取宝贝的全部所有者
     * @version 1.1
     */
    public function get_all_owner($cowryID)
    {
        $this->db->select('id_cowry_owner,id_cowry,owner,quantity')
            ->from('bi_cowry_owner')
            ->where(array('id_cowry' =>$cowryID, 'status'=>1));

        $result =  $this->db->get()->result_array();
        if(!empty($result)){
            return $result;
        }
        return FALSE;
    }

    /**
     * 获取商家宝贝 上架 随机 5个
     * @version 1.1
     */
    public function get_rand_cowry($userID)
    {
        $this->db->select('ci.id_cowry AS cid, ci.description,cover_image AS img')
            ->from('bi_cowry_owner as o')
            ->join('bi_cowry_info as ci', 'o.id_cowry = ci.id_cowry', 'left')
            ->where(array('o.owner' => $userID, 'o.status' => 1, 'ci.status' =>'up'))
            ->where('ci.id_cowry > 0')
            ->order_by('rand()')
            ->limit(5);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }

    /**
     * @author zhoushuai
     * @查询商家宝贝信息。列表
     * @获取商家宝贝
     * @version 2.0
     */
    public function get_user_cowry($where, $limit, $start = 0)
    {
        $this->db->select('ci.id_cowry AS cid,ci.description,ci.price, cover_image AS img,o.owner as uid,o.quantity as num')
            ->from('bi_cowry_owner as o')
            ->join('bi_cowry_info as ci', 'o.id_cowry = ci.id_cowry', 'left')
            ->join('bi_2buser AS u', 'o.owner = u.id_2buser', 'left')
            ->join('bi_contact as d',"d.id_contact = ci.id_contact", 'left')
            ->where(array('o.status' => 1))
            ->where('ci.id_cowry > 0 AND o.quantity >0')
            ->order_by('o.created DESC');            
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        //$str = $this->db->last_query();debug($str);
        $return = !empty($result) ? $result : array();
        return $return;
    }
    
    

    /**
     * 获取用户宝贝数量
     */
    public function get_user_cowry_count($userIDs)
    {
        $this->db->select('owner AS uid,SUM(quantity) AS count')
            ->from('bi_cowry_owner')
            ->where('owner IN ('.implode(',', $userIDs).')')
            ->where('status',1)
            ->order_by('uid');

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }

    /**
     * 获取用户可售宝贝数量
     */
    public function get_up_cowry_num($userID)
    {
        $this->db->select('o.owner AS uid,count(ci.id_cowry) AS count')
            ->from('bi_cowry_owner as o')
            ->join('bi_cowry_info as ci', 'o.id_cowry = ci.id_cowry', 'left')
            ->where('owner', $userID)
            ->where('o.status',1)
            ->where('ci.status','up');

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }
	
	/**
	 *@author zhoushuai
	 *@赞
	 *@显示点赞总数，当点击后总数加1，按钮变成实心，再次点击减1按钮变成空心，可反复点击
	 */
	public function zan($cid){
		$mysql = 'update `bi_cowry_info` set `like` = `like`+1 WHERE `id_cowry` ='.$cid;
		$this->db->query($mysql);
		return $this->db->affected_rows();
	}
	
	/**
	 *@author zhoushuai
	 *@取消赞
	 *@显示点赞总数，当点击后总数加1，按钮变成实心，再次点击减1按钮变成空心，可反复点击
	 */
	public function deny($cid){
		$mysql = 'update `bi_cowry_info` set `like` = IF(`like`<1, 0, `like`-1) WHERE `id_cowry` ='.$cid;
		$this->db->query($mysql);
		return $this->db->affected_rows();
	
	}
	
	
	/*
     * 添加宝贝-点赞
     */
    public function insert_zan($data){
        $this->db->insert('bi_cowry_zan', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    
    /**
     * @宝贝-点赞
     */
    public function del_zan($where){
        return $this->db->delete('bi_cowry_zan', $where);
    }
    
    /*
     * 修改宝贝-点赞
     */
    public function update_zan($data, $where)
    {
        return $this->db->update('bi_cowry_zan', $data,$where);
    }
	
	
	/**
	 *@判断是否点赞
	 */
	public function is_zan($uid,$cid){
		$this->db->select('id_cowry AS cid,id_2buser AS uid,id_zan AS zid')
            ->from('bi_cowry_zan')
            ->where(array('id_cowry'=>$cid,'id_2buser'=>$uid));
        $result =  $this->db->get()->result_array();
        if(!empty($result)){
            return $result[0];
        }
        return FALSE;
	}


}