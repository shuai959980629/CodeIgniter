<?php
/**
 * Created by JetBrains PhpStorm.
 * User: MSI-1
 * Date: 13-11-1
 * Time: 上午11:23
 * To change this template use File | Settings | File Templates.
 */

class Friend_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * 获取朋友列表
     * $param $type good好友|black黑名单
     * $page==$offset==0 获取所有
     */
    public function getfriends($userID, $type, $page, $offset)
    {
        $this->db->select('bi_2buser.id_2buser AS uid, head_image AS favicon, nickname, username')
            ->from('bi_friends')
            ->join('bi_2buser', 'bi_2buser.id_2buser = bi_friends.object_id', 'left')
            ->where('bi_friends.id_2buser', $userID)
            ->where('bi_2buser.id_2buser IS NOT NULL')
            ->where('bi_friends.object_type = \'' . $type . '\'');

        if($page > 0)
            $this->db->limit($offset, $offset*($page-1));

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }

    /*
     * 是否是好友
     * $userID 自己的ID，
     * $memberID 朋友的ID
     * $type 'good'好友,'black'黑名单
     */
    public function is_friend($userID,$memberID,$type)
    {
        $this->db->select('id_friends')
            ->from('bi_friends')
            ->where('id_2buser', $userID)
            ->where('object_id', $memberID)
            ->where('object_type', $type);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

    /*
     * 添加
     */
    public function add_friend($data){
        $re = $this->db->insert('bi_friends', $data);
        return $re;
    }
    /*
     * 修改
     */
    public function update_friend($data,$id)
    {
        $this->db->where('id_friends', $id);
        $re = $this->db->update('bi_friends', $data);
        return $re;
    }

    /*
     * 获取可能感兴趣的人
     */
    public function get_interest($limit,$ids)
    {
        $friend = implode(',',$ids);
        $this->db->select('id_2buser AS uid, head_image AS favicon, nickname, username, birthday, sign')
            ->from('bi_2buser')
            ->where('id_2buser NOT IN (' . $friend . ')')
            ->order_by('rand()')
            ->limit($limit);

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }

    /**
     * 获取关系
     * $userID, $this->token['uid']
     * 用户编号 id_2buser   朋友编号 object_id
     * (id_2buser = '.$userID.' AND object_id = ' . $memberID.') OR (id_2buser = '.$memberID.' AND object_id = ' . $userID.')
     */
    public function get_relation($userID,$memberID)
    {
        $where = 'id_2buser = '.$memberID.' AND object_id = ' . $userID.'';
        $this->db->select('id_friends,object_type')
            ->from('bi_friends')
            ->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }

}