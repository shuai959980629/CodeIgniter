<?php
/**
 * Created by JetBrains PhpStorm.
 * User: MSI-1
 * Date: 13-10-30
 * Time: 上午11:36
 * To change this template use File | Settings | File Templates.
 */

class Binding_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * 是否绑定
     * $param $type 类型(phone,sina,hipigo,qq)
     * $param $userID 用户ID
     */
    public function is_binding($userID, $type){

        $this->db->select($type . '_binding')
            ->from('bi_2buser_binding')
            ->where('id_2buser', $userID);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /*
     * 账号是否被绑定
     */
    public function is_account_binding($where)
    {
        $this->db->select('phone_binding', 'sina_binding', 'qq_binding', 'hipigo_bingding','alipay_binding')
            ->from('bi_2buser_binding')
            ->where($where);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /*
     * 获取用户绑定信息
     */
    public function get_bind($userID)
    {
        $this->db->select('phone_number AS phone')
            ->from('bi_2buser_binding')
            ->where('id_2buser', $userID);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /*
     * 插入
     */
    public function insert_bind($data)
    {
        $re = $this->db->insert('bi_2buser_binding', $data);
        return $re;
    }

    /*
     * 修改
     */
    public function modify_bind($data, $userID)
    {
        $this->db->where('id_2buser', $userID);
        $re = $this->db->update('bi_2buser_binding', $data);
        return $re;
    }

}