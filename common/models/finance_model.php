<?php
/**
 * @邻售财务模块
 * @author zhoushuai
 * @date 2014-08-04 9:30
 */
class Finance_model extends CI_Model
{

    protected $table = 'bi_finance';
    
    function __construct()
    {
        $this->load->database();
    }
    /**
     * @生成财务记录
     */
    public function insert_finance($data){
        $this->db->insert($this->table, $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    
    /**
     *@修改财务记录
     */
    public function modify_finance($data,$where){
        return $this->db->update($this->table, $data,$where);
    } 
    
    /**
     *@查询财务记录
     */
    public function query_finance($where){
        $this->db->select('*')->from($this->table)->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    } 
    
    /**
     *@查询财务表 
     */
    public function select_finance($where){
        $this->db->select('trade_no,status')->from($this->table)->where($where);
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result[0] : FALSE;
        return $return;
    }
    
    
    /**
     * @我的财务
     */
    public function get_my_finances($uid){
        $return = array();
        $this->db->select('status,SUM(sum) as sum')
            ->from($this->table)
            ->where(array('id_2buser'=>$uid))
            ->group_by('status');
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result : FALSE;
        return $return;
    }
    /**
     * @财务列表
     */
    public function finance_list($where, $order, $page, $offset){
        $this->db->select('f.id_finance AS fid,f.id_2buser AS uid,f.order_no,f.sum,f.status,f.stock_dater AS sdate,f.created AS cdate')
            ->from($this->table . ' as f')
            ->order_by($order ? $order :'f.created DESC')
            ->limit($offset, $offset * ($page - 1));
        $this->db->where($where);
        $result = $this->db->get()->result_array();
        $return = isset($result) ? $result : array();
        //return $this->db->last_query();
        return $return;
    }
    
    /**
     * @财务列表
     * @用途：1，后台分页查询。。。
     */
    public function get_finance_list($where, $limit, $start = 0){
        $this->db->select('f.id_finance AS fid,f.id_2buser AS uid,f.order_no,f.trade_no,f.sum,f.status,f.comment,f.stock_dater AS sdate,f.order_created AS odate,f.created AS cdate,u.username,u.nickname,b.alipay_account,b.alipay_name')
            ->from($this->table . ' as f')
            ->join('bi_2buser as u',"u.id_2buser=f.id_2buser", 'left')
            ->join('bi_2buser_binding AS b','b.id_2buser=f.id_2buser','left')
            ->order_by('f.created DESC');
        if(!empty($where)){
            $this->db->where($where);
        }
        if($limit){
            $this->db->limit($limit,$start);
        }
        $result = $this->db->get()->result_array();
        $return = !empty($result) ? $result:FALSE;
        return $return;
    }
    
    
    
    /**
     * @投诉处理订单审核记录
     */
    public function insert_auditing($data){
        $this->db->insert('bi_auditing', $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }
    
    /**
     * @修改订单审核记录表
     */
    public function modify_auditing($data,$where){
        return $this->db->update('bi_auditing',$data,$where);
    }
    
}
