<?php
/**
 * Created by JetBrains PhpStorm.
 * User: rjy
 * Date: 13-11-5
 * Time: 下午2:31
 * To change this template use File | Settings | File Templates.
 */

class Ranking_model Extends CI_Model {

    function __construct()
    {
        $this->load->database();
    }

    /*
     * 获取排行榜
     */
    public function get_rank_list($where, $limit, $offset)
    {
        $this->db->select('bi_2buser.id_2buser,ranking AS sort,cowry_quantity AS delinum,nickname,head_image AS favicon,birthday')
            ->from('bi_2buser_ranking_list AS r')
            ->join('bi_2buser', 'bi_2buser.id_2buser = r.id_2buser', 'left')
            ->where($where)
            ->limit($offset, $offset*($limit-1));

        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }


}