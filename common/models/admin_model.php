<?php

class Admin_model Extends CI_Model {
	
	protected $table = 'bi_admin';

    public function __construct()
    {
        $this->load->database();
    }
    /**
     * @查询管理员信息。
     * 
     */
    public function query_admin($where){
        $this->db->select('a.id_admin,a.id_profile,a.realname,a.username,a.password,a.last_time,p.role,p.name AS roleName,')
        ->from('bi_admin AS a')
        ->join('bi_profile AS p','a.id_profile = p.id_profile' ,'left')->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    /**
     * @更新后台管理员信息
     */
    public function update_admin($data,$where){
        $this->db->where($where);
        $re = $this->db->update($this->table, $data);
        return $re;
    }
    
    /**
     * @新增后台管理员
     */
    public function add_admin($data){
        $this->db->insert($this->table, $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    /**
     * @删除管理员
     */
    public function deladmin($where){
        return $this->db->delete($this->table, $where);
    }
    
    /**
     * @查询管理员信息及角色信息
     */
     public function admin_list($where=''){
        $this->db->select('a.*,p.name AS profileName')
             ->from('bi_admin AS a')
             ->join('bi_profile AS p','a.id_profile = p.id_profile' ,'left');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
     }
    
     /**
      * @记录message消息表
      */
     public function insert_msg($data){
        $this->db->insert('bi_message', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
     


}