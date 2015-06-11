<?php

class Tag_model extends CI_Model
{


    public function __construct()
    {
        $this->load->database();
    }

    /**
     * @新增宝贝标签
     */
    public function insert_tag($data)
    {
        $this->db->insert('bi_tag', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }

    /**
     * @修改宝贝标签
     * @param array $where
     * @param array $data
     */
    public function modify_tag($data, $where)
    {
        return $this->db->update('bi_tag', $data, $where);
    }
    
    /**
     * @删除宝贝标签
     */
    public function delete_tag($where)
    {
        return $this->db->delete('bi_tag', $where);
    }
    
    /**
     * @author zhoushuai
     * @根据角色。获取宝贝标签列表
     * 
     */
    public function get_tag_list($where='')
    {
        $this->db->select('id_tag,id_parent,name')->from('bi_tag AS t')->order_by('orders DESC,id_tag DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }

    /**
     * @author zhoushuai
     * @获取宝贝标签
     */
    public function get_tag($where='')
    {
        
        $this->db->select('*')->from('bi_tag AS t')->order_by('orders DESC,id_tag DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }
    
    /**
     * @author zhoushuai
     * @获取宝贝标签名称
     */
    public function get_tag_name($where='')
    {
        $this->db->select('name')->from('bi_tag AS t');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return empty($result) ? false : $result;
    }
    
    
    /**
     * @author zhoushuai
     * @获取宝贝标签
     */
    public function get_tag_by_id($id_tag)
    {
        $this->db->select('*')->from('bi_tag AS t ')->where(array('t.id_tag' => $id_tag));
        $result = $this->db->get()->result_array();
        return $result[0];
    }
    
    /**
     * @author zhoushuai
     * @增加标签关联的宝贝
     */
    public function insert_tag_cowry($data){
        return $this->db->insert_batch("bi_tag_cowry", $data);
    }
    
    /**
     * @删除标签关联的宝贝
     */
    public function delete_tag_cowry($where)
    {
        return $this->db->delete('bi_tag_cowry', $where);
    }
    
    /**
     * @查询宝贝拥有的标签
     */
    public function get_cowry_tag($cid,$uid){
        $this->db->select('t.id_tag,id_cowry AS cid,owner AS uid,g.name')
            ->from('bi_tag_cowry AS t')
            ->join('bi_tag AS g','t.id_tag = g.id_tag','left')
            ->where("id_cowry = {$cid} AND owner = {$uid}");
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : false;
        return $return;
    }
    
    
       
    /**
     * @获取标签关联的宝贝列表
     */
    public function get_tag_cowry($where,$page,$offset){
        $this->db->select('ci.description,ci.id_cowry AS cid,ci.creator AS uid,ci.price,ci.status,ci.cover_image AS img')
            ->from('bi_tag_cowry as t')
            ->join('bi_cowry_info AS ci',"t.id_cowry=ci.id_cowry", 'left')
            ->where('t.id_cowry IS NOT NULL')
            ->limit($offset, $offset * ($page - 1));
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : array();
        return $return;
    }


}
