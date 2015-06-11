<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rjy
 * Date: 13-11-5
 * Time: 下午5:00
 * To change this template use File | Settings | File Templates.
 */

class Cowry_attach_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * 添加宝贝附件
     */
    public function add_attach($data){
        //查询图片是否已经存在
        $this->db->select('id_cowry')->from('bi_cowry_attachment')->where($data);
        $result = $this->db->get()->result_array();
        if(empty($result)){
            $re = $this->db->insert('bi_cowry_attachment', $data);
            return $re;
        }        
    }

    /*
     * 删除
     */
    public function del_attach($where){
        $this->db->where($where);
        $re = $this->db->delete('bi_cowry_attachment');
        return $re;
    }
    
    /*
     * 添加动态附件
     */
    public function add_dynamic_attach($data){
        //查询图片是否已经存在
        $this->db->select('id_cowry')->from('bi_dynamic_attachment')->where($data);
        $result = $this->db->get()->result_array();
        if(empty($result)){
            $re = $this->db->insert('bi_dynamic_attachment', $data);
            return $re;
        }        
    }
    
    /*
     * 删除动态附件
     */
    public function del_dynamic__attach($where){
        $this->db->where($where);
        $re = $this->db->delete('bi_dynamic_attachment');
        return $re;
    }
    
    
    
}