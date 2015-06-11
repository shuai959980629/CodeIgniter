<?php

class Bb_info_model Extends CI_Model {


	protected $table = 'bi_bb_info';
	
   public function __construct()
   {
        $this->load->database();
    }

    
    /**
     * 添加哔哔
     * @param array $data
     */
    public function add_bibi($data){
    	
    	$this->db->insert($this->table,$data);
    	$member['id_bb'] = $this->db->insert_id();
    	$member['id_2buser'] = $data['creator'];
    	$this->db->insert('bi_bb_member',$member);
    	return $member['id_bb'];
    	
    }


    /**
     * 添加哔哔音频附件
     */
    public function add_attachment($data)
    {
        $this->db->insert('bi_attachment_item',$data);
        $attach = $this->db->insert_id();
        return $attach;
    }

    /**
     * 修改哔哔音频附件
     */
    public function modify_attachment($data,$aid)
    {
        return $this->db->update('bi_attachment_item',$data,array('id_attachment'=>$aid));
    }

    /**
     * 修改哔哔
     * @param int $bid
     * @param int $uid
     * @param array $data
     */
    public function modify_bibi($bid,$uid,$data){
    	
    	return $this->db->update($this->table,$data,array('id_bb'=>$bid,'creator'=>$uid));
    	
    }
    
    
    /**
     * 获取详情
     * @param int $bid
     * @return array $return;
     */
    public function get_detail($bid){
    	
    	$return = array();
    	$this->db->select("b.nickname,b.head_image as favicon,b.id_2buser as uid,b.sign,a.id_bb as bid,a.title as content,a.object_type as type,a.activity_time as bbtime,a.activity_place as address,a.description,a.created as addtime,a.member_count as usernum,a.cowry_room_count as cownum,ai.view_time as seconds")
    	->from($this->table." as a")
    	->where('a.id_bb',$bid)
    	->join('bi_2buser as b',"a.creator=b.id_2buser",'left')
        ->join('bi_attachment_item as ai',"ai.id_attachment=a.id_attachment",'left');
    	
    	$result = $this->db->get()->result_array();
    	$return = isset($result[0]) ? $result[0] : array();
    	return $return;
    	
    }
    
    
    /**
     * 获取热门列表
     * @param int $start
     * @param int $limit
     * @return array $return
     */
    public function get_hostlist($start,$limit){
    	
    	$return = array();
    	$this->db->select("b.nickname,b.head_image as favicon,b.id_2buser as uid,b.sign,a.id_bb as bid,a.title as content,
    	a.object_type as type,a.activity_time as bbtime,a.activity_place as address,a.description,a.created as addtime,
    	a.member_count as usernum,a.cowry_room_count as cownum,ai.view_time as seconds")
    	->from($this->table." as a")
    	->order_by('a.member_count','desc')
    	->limit($limit,$start)
    	->join('bi_2buser as b',"a.creator=b.id_2buser",'left')
        ->join('bi_attachment_item as ai',"ai.id_attachment=a.id_attachment",'left');
    	 
    	$result = $this->db->get()->result_array();
    	$return = isset($result[0]) ? $result[0] : array();
    	return $return;
    	
    }
    
    
    /**
     * 获取搜索结果
     * @param string $key
     * @param int $start
     * @param int $limit
     * @return array $return
     */
    public function get_search($key,$start,$limit){
    	
    	$return = array();
    	$this->db->select("b.nickname,b.head_image as favicon,b.id_2buser as uid,b.sign,a.id_bb as bid,a.title as content,a.object_type as type,a.activity_time as bbtime,a.activity_place as address,a.description,a.created as addtime,a.member_count as usernum,a.cowry_room_count as cownum")
    	->from($this->table." as a")
    	->where("a.object_type",'text')
    	->like("a.title",$key)
    	->order_by('a.member_count','desc')
    	->limit($limit,$start)
    	->join('bi_2buser as b',"a.creator=b.id_2buser",'left');
    	
    	$result = $this->db->get()->result_array();
    	$return = isset($result[0]) ? $result[0] : '';
    	return $return;
    	
    }

    
    /**
     * 检查哔哔是否有效
     * @param int $bid
     * @return number
     */
    public function is_enable($bid){
    	
    	$return = 0;
    	$this->db->select('status')
    	->from($this->table)
    	->where("id_bb",$bid)
    	->limit(1);
    	
    	$result = $this->db->get()->result_array();
    	$return = isset($result[0]['status']) ? $result[0]['status'] : 0;
    	return $return;
    	
    }
    
    
    
    /**
     * 加入成员
     */
    public function join_member($insert_data,$bid){
    	
    	$this->db->insert('bi_bb_member',$insert_data);
    	$this->db->set('member_count','`member_count`+1',FALSE);
    	$this->db->update('bi_bb_info',array('id_bb'=>$bid));
    	return TRUE;
    	
    }
    
    
    /**
     * 检测是否已经是某个哔哔的成员
     * @param int $uid
     * @param int $bid
     * @return boolean TRUE已加入/FALSE没有加入
     */
    public function is_member($uid,$bid){
    	
    	$this->db->select("count(1) as cnt")
    	->from('bi_bb_member')
    	->where('id_bb',$bid)
    	->where('id_2buser',$uid)
    	->limit(1);
    	
    	$result = $this->db->get()->result_array();
    	$return = $result[0]['cnt'] == 1 ? TRUE : FALSE;
    	return $return;
    	
    }


    /*
     * 获取我参与的哔哔
     */
    public function join_list($where,$order,$page,$offset)
    {
        $this->db->select('u.id_2buser AS uid,u.head_image AS favicon,
        b.title as content,b.id_bb as bid,b.object_type AS type,
        b.cowry_room_count AS cownum,b.member_count AS usernum')
            ->from('bi_bb_member AS m')
            ->join('bi_bb_info as b',"m.id_bb=b.id_bb",'left')
            ->join('bi_2buser as u',"b.creator=u.id_2buser",'left')
            ->where($where)
            ->where('m.status = 1')
            ->where('b.id_bb IS NOT NULL')
            ->order_by($order)
            ->limit($offset,$offset*($page-1));

        $result = $this->db->get()->result_array();
        return $result;
    }
    
    /**
     * 成员退出
     * @param int $uid
     * @param int $bid
     */
    public function out_member($uid,$bid){
    	
    	$this->db->delete('bi_bb_member',array('id_bb'=>$bid,'id_2buser'=>$uid,'status !='=>4));
    	$this->db->set('member_count','`member_count`-1',FALSE);
    	$this->db->update('bi_bb_info',array('id_bb'=>$bid));
    	return TRUE;
    	
    }
    
    
    /**
     * 验证是否是BB创建者
     * @param int $authorid //创建者id
     * @param int $bid //哔哔id
     * @return bool TRUE是创建者/FALSE不是创建者
     */
    public function is_author($authorid,$bid){
    	
    	$this->db->select("count(1) as cnt")
    	->from('bi_bb_info')
    	->where('id_bb',$bid)
    	->where('creator',$authorid)
    	->limit(1);
    	 
    	$result = $this->db->get()->result_array();
    	$return = $result[0]['cnt'] == 1 ? TRUE : FALSE;
    	return $return;
    	
    }
    
    
    /**
     * 禁言/解禁言某个成员
     * @param int $uid
     * @param int $bid
     */
    public function shut_member($uid,$bid){
    	
    	$this->db->select('status')
    	->from('bi_bb_member')
    	->where(array('id_bb'=>$bid,'id_2buser'=>$uid))
    	->limit(1);
    	
    	$result = $this->db->get()->result_array();
    	
    	//查不到用户，说明是非成员，禁言，新增一条数据
    	if(empty($result[0]['status'])){
    		$data = array('id_2buser'=>$uid,'id_bb'=>$bid,'status'=>3);
    		$this->db->insert('bi_bb_member',$data);
    		return TRUE;
    	}elseif ($result[0]['status'] == 1){
    		//正常成员，禁言
    		$this->db->update('bi_bb_member',array('status'=>2),array('id_bb'=>$bid,'id_2buser'=>$uid));
    	}elseif ($result[0]['status'] == 2){
    		//正常成员，解禁言
    		$this->db->update('bi_bb_member',array('status'=>1),array('id_bb'=>$bid,'id_2buser'=>$uid));
    	}elseif ($result[0]['status'] == 3){
    		//非成员解禁言，直接删除
    		$this->db->delete('bi_bb_member',array('id_bb'=>$bid,'id_2buser'=>$uid,'status'=>3));
    	}
    	
    	return TRUE;
    	
    }
    
    
    /**
     * 关闭哔哔
     * @param int $bid
     */
    public function close_bibi($bid){
    	
    	return $this->db->update($this->table,array('status'=>2),array('id_bb'=>$bid));
    	
    }
    
    
    
    /**
     * 获取宝贝房
     */
    public function get_cowrylist($bid){
    	
    	$this->db->select("a.id_bb as bid,a.id_cowry as cid,a.tribute_people as uid,b.cover_image as attaurl,c.head_image as favicon")
    	->from('bi_cowry_room as a')
    	->where(array('a.id_bb'=>$bid))
    	->join('bi_cowry_info as b','a.id_cowry=b.id_cowry','left')
    	->join('bi_2buser as c','a.tribute_people=c.id_2buser');
    	
    	$result = $this->db->get()->result_array();
    	return $result;
    	
    }
    
    
    
    public function get_memberlist($status,$bid){
    	
    	$return = array();
    	$where = array();
    	if($status == 1){
    		//成员列表
    		$where = array(1,2);
    	}else{
    		//禁言列表
    		$where = array(2,3);
    	}
    	
    	$this->db->select('a.id_2buser as uid,a.id_bb as bid,b.nickname,b.head_image as favicon')
    	->from('bi_bb_member as a')
    	->where(array('a.id_bb'=>$bid))
    	->where_in('a.status',$where)
    	->join('bi_2buser as b','a.id_2buser=b.id_2buser');
    	
    	$result = $this->db->get()->result_array();
    	if(!empty($result)){
    		$return = $result;
    	}
    	return $return;
    	
    }



    /*
     * 历史聊天消息
     */
    public function get_history($where,$order,$page,$offset)
    {
        $this->db->select('content AS msg,c.object_type AS type,c.created AS addtime,u.id_2buser AS uid,u.head_image AS favicon')
            ->from('bi_bb_chat AS c')
            ->join('bi_2buser AS u','u.id_2buser = c.id_2buser','left')
            ->where($where)
            ->order_by($order)
            ->limit($offset,$offset*($page-1));

        $result = $this->db->get()->result_array();
        return $result;
    }
    
    

    /**
     * 获取最近的哔哔列表
     * @param double $mylong  //当前的经度
     * @param double $mylat   //当前的纬度
     * @param int $start      //分页的偏移量
     * @param int $limit      //每页显示的条数
     * @param int $dist       //取多少公里范围内的数据
     */
    public function get_near_bblist($mylong,$mylat,$start,$limit,$dist){
    	
    	$sql = "CALL get_nearbb($mylong,$mylat,$start,$limit,$dist)";
    	return $this->db->query($sql)->result_array();
    	
    }


    /**
     * 是否有附件
     */
    public function is_attachment($bid)
    {
        $this->db->select("id_attachment AS aid")
            ->from($this->table)
            ->where('id_bb',$bid);

        $result = $this->db->get()->result_array();
        $return = isset($result[0]) ? $result[0] : array();
        return $return;
    }
    
    
}