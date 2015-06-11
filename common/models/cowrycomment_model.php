<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zxx
 * Date: 14-12-2
 * Time: 下午15:42
 * To change this template use File | Settings | File Templates.
 */

class Cowrycomment_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * zxx
     * 添加宝贝评论及回复信息
     */
    public function add_cowry_comment($data){
        $this->db->insert('bi_cowry_comment', $data);
        $id = $this->db->insert_id();
        return $id;
    }

    /*
     * zxx
     * 更新宝贝评论及回复信息
     */
    public function update_cowry_comment($data,$where)
    {
        $this->db->where($where);
        $re = $this->db->update('bi_cowry_comment', $data);
        return $re;
    }

    /*
     * zxx
     * 删除宝贝评论及回复信息
     */
    public function del_cowry_comment($where){
        return $this->db->delete('bi_cowry_comment', $where);
    }

    /*
     * zxx
     * 查询宝贝评论及回复信息
     */
    function get_cowry_comment($where='',$option=array(),$fields=''){
        $this->db->select($fields?$fields:'cc.id_comment as commentid,cc.content,cc.created,cc.id_2buser as uid,cc.id_parent as r_commentid,cc.id_replay_2buser as r_uid,u.nickname,u.head_image as favicon')
            ->from('bi_cowry_comment as cc')
            ->join('bi_2buser as u',"cc.id_2buser = u.id_2buser","left");

        if($where){
            $this->db->where($where);
        }
        if($option['offset']){
            $this->db->limit($option['page'],($option['offset']-1)*$option['page']);
        }
        if( $option['order'] ){
            $this->db->order_by($option['order']);
        }
        $result = $this->db->get()->result_array();
//        echo $this->db->last_query();
        return $result;
    }


}