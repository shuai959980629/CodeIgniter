<?php

class Theme_model extends CI_Model
{


    public function __construct()
    {
        $this->load->database();
    }

    /**
     * @新增专题活动
     */
    public function insert_theme($data)
    {
        $this->db->insert('bi_theme', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }

    /**
     * @修改专题活动
     * @param array $where
     * @param array $data
     */
    public function modify_theme($data, $where)
    {
        return $this->db->update('bi_theme', $data, $where);
    }
    
    /**
     * @删除专题活动
     */
    public function delete_theme($where)
    {
        return $this->db->delete('bi_theme', $where);
        //$this->db->last_query();
    }
    
	/**
     * @author zhoushuai
     * @获取专题活动
     */
    public function get_theme($where)
    {
        $this->db->select('*')->from('bi_theme AS t')->where($where);
        $result = $this->db->get()->result_array();
        return !empty($result) ? $result : false ;
    }

    /**
     * @author zhoushuai
     * @获取专题活动
     */
    public function get_theme_by_id($id_theme)
    {
        $this->db->select('*')->from('bi_theme AS t ')->where(array('t.id_theme' => $id_theme));
        $result = $this->db->get()->result_array();
		return !empty($result) ? $result[0] : false ;
    }
	
    /**
     * @author zhoushuai
     * @获取专题活动列表
     * 
     */
    public function get_theme_list($where, $limit, $start = 0,$order='')
    {
        $this->db->select('id_theme AS tid,name,type,logo,content,rule,join,address,latitude AS lat,longitude AS lon,orders,status,valid_begin,valid_end,created')
        ->from('bi_theme as t')->order_by($order ? $order :'orders desc,created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
	
	
	/**
     * 获取最近的获取专题活动列表
     * @param double $userID  //当前的用户
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     */
    public function get_themelist($userID, $mylong, $mylat, $start, $limit)
    {
        $valid_end = date('Y-m-d',time());
        $sql = "SELECT id_theme AS tid,name,type,logo AS img,content,t.rule,t.join,address,latitude AS lat,longitude AS lon,ROUND((6378137 * 2 * ASIN(SQRT(POWER(SIN(($mylat - t.latitude) * PI()/180 / 2), 2) + COS($mylat * PI()/180) *  COS(t.latitude * PI()/180) * POWER(SIN(($mylong - t.longitude) * PI()/180 / 2), 2))))/1000,4) AS distance FROM `bi_theme` AS t where t.`status`=1 AND DATE_FORMAT(t.valid_end,'%Y-%m-%d') >='{$valid_end}' ORDER BY t.orders ASC,distance ASC,t.created DESC LIMIT $start,$limit";
        return $this->db->query($sql)->result_array();
    }
    
    
	
    /**
	 * @author zhoushuai
     * @新增专题关联宝贝
     */
    public function insert_theme_cowry($data)
    {
        $this->db->insert('bi_theme_cowry', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
	
    /**
     * @author zhoushuai
     * @增加专题关联宝贝
     */
    public function insert_batch_theme_cowry($data){
        return $this->db->insert_batch("bi_theme_cowry", $data);
    }
    
    /**
     * @删除专题宝贝
     */
    public function delete_theme_cowry($where)
    {
        return $this->db->delete('bi_theme_cowry', $where);
        //$this->db->last_query();
    }
    
	/**
     * @修改专题宝贝信息
     * @param array $where
     * @param array $data
     */
    public function modify_theme_cowry($data, $where)
    {
        return $this->db->update('bi_theme_cowry', $data, $where);
    }
    
	
	/**
	 *@查询专题宝贝BY ID	
	 */
	public function get_theme_cowry_by_id($id_theme_cowry)
    {
        $this->db->select('*')->from('bi_theme_cowry AS t ')->where(array('t.id_theme_cowry' => $id_theme_cowry));
        $result = $this->db->get()->result_array();
        return $result[0];
    }
	
	
	/**
     * @author zhoushuai
     * @查询专题宝贝
     */
    public function get_theme_cowry_by_where($where)
    {
        $this->db->select('*')->from('bi_theme_cowry AS t')->where($where);
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }
	
    
	/**
     * @author zhoushuai
	 * @后台
     * @获取专题活动中宝贝列表
     */
    public function get_theme_cowry_list($where, $limit, $start = 0,$order=''){
        $this->db->select('tc.id_theme_cowry AS tcid,t.id_theme AS tid,name,u.username,ci.id_cowry AS cid,ci.creator AS uid,ci.cover_image AS img,ci.description,tc.status,tc.created')
        ->from('bi_theme_cowry AS tc')
        ->join('bi_theme as t',"t.id_theme=tc.id_theme", 'left')
        ->join('bi_cowry_info AS ci',"tc.id_cowry=ci.id_cowry", 'left')
        ->join('bi_2buser AS u', 'ci.creator = u.id_2buser', 'left')
        ->order_by($order ? $order :'tc.created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
    /**
     * @获取专题宝贝列表
     */
    public function get_theme_cowry($where,$page,$offset){
        $this->db->select('ci.description,ci.id_cowry AS cid,ci.creator AS uid,ci.price,ci.status,ci.cover_image AS img')
            ->from('bi_theme_cowry as t')
            ->join('bi_cowry_info AS ci',"t.id_cowry=ci.id_cowry", 'left')
			->join('bi_cowry_owner AS o','o.id_cowry=ci.id_cowry','left')
			->join('bi_2buser AS u','u.id_2buser=o.owner','left')
            ->where('ci.status = "up" AND o.status = 1 AND u.status=1 AND o.quantity > 0 AND t.status=1')
            ->limit($offset, $offset * ($page - 1));
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : array();
        return $return;
    }


}
