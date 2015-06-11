<?php

class Right_model Extends CI_Model {


    public function __construct()
    {
        $this->load->database();
    }
    
    /**
     * @新增后台管理员权限
     */
    public function insert_right($data){
        $this->db->insert('bi_right', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    
    /**
     * @修改后台管理员权限
     * @param array $where
     * @param array $data
     */
    public function modify_right($data,$where){
    	return $this->db->update('bi_right',$data,$where);
    }
    
    /**
     * @author zhoushuai
     * @根据角色。获取权限列表
     */
    public function get_right_list($where=''){
        $this->db->select(' r.*,p.id_profile_right,p.id_profile')
            ->from('bi_right as r')
            ->join('bi_profile_right as p',' r.id_right = p.id_right' ,'left')
            ->order_by('id_parent ASC,orders ASC');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        //return $this->db->last_query();
        return $result;
    }
     
    /**
     * @author zhoushuai
     * @获取权限列表
     */
    public function get_right($where){
        $this->db->select('*')->from('bi_right AS r')->where($where);
        $result = $this->db->get()->result_array();
        return empty($result)?FALSE:$result;
    }
    
    /**
     * @author zhoushuai
     * @获取权限信息
     */
    public function get_right_by_id($id_right){
        $this->db->select('*')
             ->from('bi_right AS r ')
             ->where(array('r.id_right'=>$id_right));
        $result = $this->db->get()->result_array();
        return $result[0];
    }

    /**
     * @author zhoushuai
     * @获取管理员角色
     */
    public function get_profile($where=''){
        $this->db->select('*')->from('bi_profile');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        return empty($result)?FALSE:$result;
    }
    
    /**
     * @添加管理员角色
     * @param data 新关注的数据
     * @param 返回插入数据的id
     */
    public function add_profile($data)
    {
        $this->db->insert('bi_profile', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    /**
     * @删除管理员角色
     */
    public function delete_profile($where)
    {
        return $this->db->delete('bi_profile', $where);
        //$this->db->last_query();
    }
    
    /**
     *@增加管理员角色权限
     */
    public function insert_profile_right($data){
        return $this->db->insert_batch("bi_profile_right", $data);
    }
    
    public function insert_pright($data){
        $this->db->insert('bi_profile_right', $data);
        $id = $this->db->insert_id();
        return $id;
    }
    
    /**
     *@删除管理员角色权限
     */
    public function delete_profile_right($where){
        return $this->db->delete("bi_profile_right", $where);
    }
   
    
}