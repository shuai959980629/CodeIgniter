<?php

class App_version_model Extends CI_Model {
	
	protected $table = 'bi_app_version';

    public function __construct()
    {
        $this->load->database();
    }

    public function check_update($where){
    	
    	$this->db->select('version,url,content AS info')->from($this->table)->where($where)->order_by('created','desc')->limit(1);
    	$result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
    	return $return;
    	
    }
    
    /**
     *@查询所有版本信息
     *@author ZHOUSHUAI
     */
    public function query_version($where=''){
        $this->db->select('*')->from($this->table)->order_by('created','desc');
        if(!empty($where)){
            $this->db->where($where);
        }
        $result = $this->db->get()->result_array();
        //echo $this->db->last_query();
        $return = !empty($result) ? $result : FALSE;
    	return $return;
    }
    
    /**
     * @删除版本信息
     */
    public function del_version($where){
        return $this->db->delete($this->table, $where);
    }
    
    /**
     * @增加新的版本信息
     */
    public function add_version($data){
        $this->db->insert($this->table, $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }


}