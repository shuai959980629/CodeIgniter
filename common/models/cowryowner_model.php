<?php
/**
 * Created by JetBrains PhpStorm.
 * User: MSI-1
 * Date: 13-11-1
 * Time: 上午10:09
 * To change this template use File | Settings | File Templates.
 */

class Cowryowner_model extends CI_Model
{

    function __construct()
    {
        $this->load->database();
    }

    /*
    * 获取宝贝信息
    */
    public function get_cowry($where, $order, $page, $offset)
    {
        $this->db->select('ci.id_cowry AS cid, o.owner as uid, cover_image AS img, description, quantity as num, price, ci.status')->
            from('bi_cowry_owner as o')->join('bi_cowry_info as ci',
            'o.id_cowry = ci.id_cowry', 'left')->where($where)->where('o.status = 1 AND ci.id_cowry IS NOT NULL')->
            order_by($order)->limit($offset, $offset * ($page - 1));

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    /**
     * 添加
     */
    public function add_owner($data)
    {
        $re = $this->db->insert('bi_cowry_owner', $data);
        return $re;
    }
    /*
    * 删除 逻辑删除 只修改状态status
    */
    public function update_owner($data, $where)
    {
        $this->db->where($where);
        $re = $this->db->update('bi_cowry_owner', $data);
        return $re;
    }

    /**
     * 宝贝是否存在
     * 
     */

    public function is_exist($cowryID,$userID)
    {
        $this->db->select('ci.status,o.quantity AS num')
        ->from('bi_cowry_info as ci')
        ->join('bi_cowry_owner as o','o.id_cowry = ci.id_cowry','left')
        ->where('ci.id_cowry = ' .$cowryID . ' AND o.owner = '.$userID.' AND o.status = 1 AND ci.id_cowry IS NOT NULL');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;

    }

    /**
     * 是否是宝贝所有者
     */
    public function is_onwer($cowryID, $userID)
    {
        $this->db->select('id_cowry_owner,quantity AS num')->from('bi_cowry_owner as o')->
            join('bi_cowry_info as ci', 'o.id_cowry = ci.id_cowry', 'left')->where('o.status = 1 AND o.id_cowry = ' .
            $cowryID . ' AND o.owner = ' . $userID . ' AND ci.id_cowry IS NOT NULL');

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /**
     * 获取宝贝数量
     */
    public function get_cowry_count($userID,$uid)
    {
        $this->db->select('count(ci.id_cowry) as num')->from('bi_cowry_owner as o')
             ->join('bi_cowry_info as ci', 'o.id_cowry = ci.id_cowry', 'left')
             ->where('o.owner',$userID)
             ->where('o.status = 1 AND ci.id_cowry IS NOT NULL');
        if($userID!=$uid){
            $this->db->where('ci.status','up');
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['num'] : 0;
        return $return;
    }

}
