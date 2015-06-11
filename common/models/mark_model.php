<?php
/**
 * @author  zhoushuai
 * Date: 14-03-26
 * Time: am 9:30
 */
class Mark_model extends CI_Model
{

    protected $table = 'bi_friends';

    function __construct()
    {
        $this->load->database();
    }
    /**
     * 添加关注
     * @param data 新关注的数据
     * @param 返回插入数据的id
     */
    public function add_mark($data)
    {
        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        return $id;
    }
    /**
     * 取消关注
     */
    public function cancel_mark($where)
    {
        return $this->db->delete($this->table, $where);
        //$this->db->last_query();
    }
    /**
     * 验证关注是否存在
     */
    public function check_marked($where)
    {
        $this->db->select("id_friends as fid")->from($this->table)->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0]['fid'] : false;
        return $return;
    }

    /**
     * 获取被关注人的id
     * @param uid int 用户id
     * SELECT b.* 
     * FROM  bi_dynamic b  
     * WHERE b.id_2buser  
     * IN (SELECT a.object_id FROM bi_friends a WHERE a.id_2buser=57) 
     * ORDER BY created DESC
     */

    public function get_mark_id_list($where,$offset,$page)
    {

        $this->db->select('a.object_id as id')->from('bi_friends as a')->where($where)->order_by('created', 'DESC')->limit($offset, $offset * ($page - 1));
        $result = $this->db->get()->result_array();
        if (!empty($result)) {
            $data = array();
            for ($i = 0; $i < count($result); $i++) {
                $data[$i] = $result[$i]['id'];
            }
            return $data;
        } else {
            return false;
        }
    }


    public function get_dyn_list($where)
    {
        $this->db->select("*")->from('bi_dynamic as b')->where_in('b.id_2buser', $where)->order_by('created', 'DESC');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
    /**
     * @删除动态
     */
    public function delete_dyn($where){
        return $this->db->delete('bi_dynamic', $where);
    }
    
    /**
     * @删除动态附件
     */
    public function del_dyn_attachment($where){
        return $this->db->delete('bi_dynamic_attachment', $where);
    }
    
    /*
    * 获取用户信息
    */
    public function get_userinfo($uid)
    {
        $this->db->select('nickname ,head_image AS photo,')->from('bi_2buser')->where('bi_2buser.id_2buser',$uid);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }


    /**
     * 获取宝贝详情
     */
    public function get_cowry($cid, $uid)
    {
        $this->db->select('u.id_2buser AS uid,u.head_image AS favicon,u.nickname,u.username,description,price,ci.status,ci.id_cowry AS cid,ci.address,o.quantity AS num ,GROUP_CONCAT(CONCAT(a.att_img)) AS img')
            ->from('bi_cowry_info AS ci')->join('bi_cowry_owner AS o','o.id_cowry = ci.id_cowry', 'left')
            ->join('bi_2buser AS u','o.owner = u.id_2buser', 'left')
            ->join('bi_cowry_attachment AS a', 'a.id_cowry = ci.id_cowry', 'left')
            ->where('ci.id_cowry = ' . $cid .' AND o.owner = ' . $uid . ' AND o.status = 1')
            ->limit(1);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : false;
        return $return;
    }
    
    /**
     * @获取动态并且分页每页显示10条记录
     */
     
    public function query_dyn_list($where,$page, $offset)
    {
        $this->db->select('d.id_dynamic AS did, u.id_2buser AS uid,u.head_image AS favicon,u.nickname,u.username,d.cowrydesc AS cowry_des,d.description AS dyn_des,d.object_id AS cid,d.created')
            ->from('bi_dynamic AS d')
            ->join('bi_friends AS f','d.id_2buser = f.object_id','left')
            ->join('bi_2buser AS u','d.id_2buser = u.id_2buser', 'left')
            ->order_by('d.created', 'DESC');      
        $this->db->where($where);
        $this->db->limit($offset, $offset * ($page - 1));
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
    /**
     * @查询宝贝所有图片
     */
    public function get_dynamic_attachment($cid){
        $this->db->select('att_img as img')->from('bi_dynamic_attachment')->where('id_cowry = '.$cid)->order_by('id_attachment DESC');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : array();
        return $return;
    }
    
    
    /**
     * @获取关注列表
     */
    public function query_mark_list($where,$page, $offset){
       $this->db->select('u.id_2buser AS uid,u.head_image AS favicon,u.nickname,u.username,u.type,f.created')
            ->from('bi_2buser AS u')
            ->join('bi_friends AS f','u.id_2buser = f.object_id','left')
            ->where($where)
            ->order_by('f.created', 'DESC');
        $this->db->limit($offset, $offset * ($page - 1));
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
        
    }
    
    /**
     * @查询动态
     */
    public function query_dyn($where){
        $this->db->select('d.*')->from('bi_dynamic AS d')
        ->join('bi_friends AS f','d.id_2buser = f.object_id','left')
        ->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : false;
        return $return;
    }
    
     

}
