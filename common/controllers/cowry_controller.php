<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * 宝贝核心逻辑
 *
 * @author rjy
 * Date: 13-11-5
 * Time: 下午3:33
 */
class Cowry_Controller extends Common_Controller
{

    /**
     * 添加宝贝
     */
    protected function addcowry($data, $userID)
    {
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowry_attach_model', 'attach');
        $this->load->model('cowryowner_model', 'owner');
        if (empty($data['lon']) || empty($data['lat'])) {
            $data['lon'] = '104.08084106445';
            $data['lat'] = '30.66577911377';
        }
        if(empty($data['cowimg'])){
            $this->errors = '请上传宝贝图片！';
            return false;
        }        
        $cowry = array(
            'description' => $data['desc'],
            'cover_image' => $data['cowimg'][0],
            'creator' => $userID,
            'created' => date('Y-m-d H:i:s', time()),
            'price' => !empty($data['price']) ? $data['price'] : 0,
            'status' => $data['status'] ? $data['status'] : 'up',
            'latitude' => $data['lat'],
            'longitude' => $data['lon'],
            'address' => !empty($data['address']) ? $data['address'] : '',
            'id_contact'=>$data['aid']
            );
        $this->db->trans_begin();
        $cowryID = $this->cowry->add_cowry($cowry);
        //附件
        foreach ($data['cowimg'] as $val) {
            $this->attach->add_attach(array('id_cowry' => $cowryID, 'att_img' => $val));
        }
        //所有者
        $owner = array(
            'id_cowry' => $cowryID,
            'owner' => $userID,
            'quantity' => $data['number'],
            'created' => date('Y-m-d H:i:s', time()),
            );
        $this->owner->add_owner($owner);

        //上架宝贝。添加信息到用户动态表
        if ($cowry['status'] == 'up') {
            $dynamic = array(
                'object_id' => $cowryID,
                'id_2buser' => $userID,
                'cowrydesc'=>$data['desc'],
                'object_type' => 'up',
                'description' => '上架了一个新产品',
                'created' => date('Y-m-d H:i:s', time()),
            );
            foreach ($data['cowimg'] as $val) {
                $this->attach->add_dynamic_attach(array('id_cowry' => $cowryID, 'att_img' => $val));
            }
            $this->cowry->add_cowry_dynamic($dynamic);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return array('cid' => $cowryID);
        }
    }


    /**
     * 
     * @编辑宝贝
     * @上架宝贝。添加信息到用户动态表
     * @只要交易过，就是新的宝贝(以前的逻辑。当前不生成新宝贝)
     */
    protected function edit_cowry()
    {
        $this->load->model('cowry_attach_model', 'attach');
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowryowner_model', 'owner');
        //检查是否有权限编辑、宝贝是否有效
        $effect = $this->cowry->check_cowry_edit($this->token['uid'], $this->filter_params['cid']);
        if (false === $effect) {
            $this->errors = $this->lang->line('you_has_not_edit_accout');
            return false;
        } else {
            //查看宝贝原来的信息
            $cowryInf = $this->cowry->get_cowry_baseinfo($this->filter_params['cid'],$this->token['uid']);
            //debug($cowryInf);
            $desCowry = $cowryInf['description'];
            $imgCowry = $cowryInf['img'];
            $hashCowry = hash("md5",trim($desCowry).trim($cowryInf['img']));
            //检查宝贝是否交易
            $isbuy = $this->cowry->is_trade($this->filter_params['cid']);
            $where_update = array('id_cowry' => $this->filter_params['cid']);
            $data = array(
                'description' => $this->filter_params['desc'],
                'price' => $this->filter_params['price'],
                'latitude' => $this->filter_params['lat'],
                'longitude' => $this->filter_params['lon'],
                'address' => $this->filter_params['address'],
                'status' => $this->filter_params['status'],
                'cover_image' => $this->filter_params['cover_image'],
                'id_contact'=>$this->filter_params['aid'],
                'created'=>date('Y-m-d H:i:s', time())
                );
            $hash = hash("md5",trim($this->filter_params['desc']).trim($this->filter_params['cover_image']));
            $this->db->trans_begin();
            if (FALSE !== $isbuy && $hashCowry !== $hash) {
                /**
                 * @宝贝有交易，删除旧宝贝的所有权，生成新的宝贝新所有权 
                //$this->db->delete('bi_cowry_owner', array('id_cowry_owner' => $effect['id_cowry_owner']));
                $data['creator']=$this->token['uid'];
                $data['created']= date('Y-m-d H:i:s', time());
                $data['status']='down';
                //新宝贝id
                $cowryID = $this->cowry->add_cowry($data);
                //所有者
                $owner = array(
                    'id_cowry' => $cowryID,
                    'owner' => $this->token['uid'],
                    'quantity' => $this->filter_params['number'],
                    'created' => date('Y-m-d H:i:s', time()),
                    );
                //添加新的所有权
                $this->owner->add_owner($owner);
                */
            } else {
                /**
                 * @没有交易，直接修改
                 */
            }
            
            //原宝贝状态是上架。则直接修改。原宝贝状态是下架。重新上架则需要判断商家已宝贝上架数量不能超过6,20
            if( $this->params['status'] == 'up' && $cowryInf['status']!= 'up' ){
                $count = $this->cowry->get_up_cowry_num($this->token['uid']);
                if( $count['count'] >= $this->profile['max']){
                    $string = $this->lang->line('cowry_add_up');
                    $pattern = '/\d+/i';
                    $this->errors = preg_replace($pattern, $this->profile['max'], $string);
                    return FALSE;
                }               
            }
            $this->cowry->edit_cowry($where_update, $data);
            if ($this->filter_params['number'] != $effect['num']) {
                $this->cowry->edit_num($this->token['uid'], $this->filter_params['cid'], $this->filter_params['number']);
            }
            //原来宝贝id
            $cowryID = $this->filter_params['cid'];
            //删除原来宝贝的附件信息（图片）
            $this->db->delete('bi_cowry_attachment', array('id_cowry' => $this->filter_params['cid']));
            
            if (is_array($this->filter_params['cowimg'])) {
                //附件
                foreach ($this->filter_params['cowimg'] as $val) {
                    $this->attach->add_attach(array('id_cowry' => $cowryID, 'att_img' => $val));
                }
            }
            
            //上架宝贝。添加信息到用户动态表
            if ($this->params['status'] == 'up') {
                $dynamic = array(
                    'object_id' => $cowryID,
                    'id_2buser' => $this->token['uid'],
                    'cowrydesc'=>$this->filter_params['desc'],
                    'object_type' => 'up',
                    'description' => '上架了一个新产品',
                    'created' => date('Y-m-d H:i:s', time()),
                    );
                foreach ($this->filter_params['cowimg'] as $val) {
                    $this->attach->add_dynamic_attach(array('id_cowry' => $cowryID, 'att_img' => $val));
                }
                $this->cowry->add_cowry_dynamic($dynamic);
            }else{
                //下架宝贝删除 动态信息
                $this->db->delete('bi_dynamic', array('object_id' => $this->filter_params['cid']));
                //删除动态宝贝的附件信息（图片）
                $this->db->delete('bi_dynamic_attachment', array('id_cowry' => $this->filter_params['cid']));
            }
            
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return array('cid' => $cowryID);
                //return true;
            }
        }
    }

    /*
    * 删除宝贝
    */
    protected function delete_cowry($data, $userID)
    {
        $this->load->model('cowryowner_model', 'owner');
        //所有者 逻辑删除 只修改状态
        $where = 'id_cowry = ' . $data['cid'] . ' AND owner = ' . $userID;
        $res = $this->owner->update_owner(array('status' => 0), $where);
        if($res){
            //删除宝贝 动态信息
            $this->db->delete('bi_dynamic', array('object_id' => $data['cid']));
            //删除动态宝贝的附件信息（图片）
            $this->db->delete('bi_dynamic_attachment', array('id_cowry' => $data['cid']));
            return true;
        }else{
            $this->errors=$this->lang->line('cowry_delete_fail');
            return false;
        }
    }


    /*
    * 宝贝详细
    */
    protected function detail_cowry($cid, $uid)
    {
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowry_attach_model', 'attach');
        $cowry = $this->cowry->get_cowry($cid, $uid);
        if(!$cowry['valid']){
            $this->errors = $this->lang->line('cowry_deleted');
            return false;
        }
        //获取该商家其他宝贝 5个 上架
        $where = 'o.owner = ' . $uid . ' AND ci.status = \'up\' AND ci.id_cowry <> ' . $cid;
        $other = $this->cowry->get_user_cowry($where, 5);
        if($cowry['num']<0){
            $cowry['num'] = 0;
        }elseif($cowry['num']>=9999){
            $cowry['num'] = 9999;
        }
		
		//查询当前用户($this->token['uid'])对该宝贝是否点赞
		$like = 0;//没有点赞
		$isZan = $this->cowry->is_zan($this->token['uid'],$cowry['cid']);
		if($isZan){
			$like = 1;
		}
		
        //获取宝贝的最近两条评论
        $data_['cid'] = $cid;
        $data_['offset'] = 1;
        $page = 2;
        $comment = $this->get_comment($data_,$page,'method');
        $result = array(
            'user' => array(
                'uid' => $cowry['uid'],
                'type'=>$cowry['type'],
                'favicon' => $cowry['favicon'],
                'nickname' => !empty($cowry['nickname']) ? $cowry['nickname'] : $cowry['username'],
                ),
            'cowry' => array(
                'description' => $cowry['description'],
                'cid' => $cowry['cid'],
                'num' => $cowry['num'],
				'zan'=>$cowry['zan'],
				'like'=>$like,
                'status' => $cowry['status'],
                'price' => $cowry['price'] ? $cowry['price'] : 0,
                'aid' => $cowry['aid'],
                'lat'=>$cowry['lat'],
                'lon'=>$cowry['lon'],
                'address' => $cowry['address'],
                'name' => $cowry['name'],
                'phone' => $cowry['cell_phone'],
                'img' => explode(',', $cowry['img']),
                ),
            'other' => $other,
            'total' => $comment['total'],
        'comment' => $comment['comment']
        );
        return $result;
    }


    /**
     * 进贡宝贝 v_1.1已删除
     */
    protected function delicate_cowry($data, $userID)
    {
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowryowner_model', 'owner');

        $this->db->trans_begin();
        //交易记录
        $cowry_data = array(
            'id_cowry' => $data['cid'],
            'object_type' => 'tribute',
            'quantity' => $data['number'],
            'buyer' => $data['bid'],
            'vendor' => $userID,
            'created' => date('Y-m-d H:i:s'));
        $this->cowry->transaction($cowry_data);
        //宝贝房
        $cowry_room = array(
            'id_bb' => $data['bid'],
            'id_cowry' => $data['cid'],
            'quantity' => $data['number'],
            'created' => date('Y-m-d H:i:s'),
            'tribute_people' => $userID,
            );
        $this->cowry->cowry_room($cowry_room);
        //所有数减少
        $num = $this->owner->is_onwer($data['cid'], $userID);
        $this->owner->update_owner(array('quantity' => ($num['num'] - $data['number'])),
            array('id_cowry_owner' => $num['id_cowry_owner']));

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }


    /**
     * 赠送宝贝
     */
    protected function donate_cowry($data, $userID)
    {
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowryowner_model', 'owner');

        $this->db->trans_begin();
        //交易记录
        $order_data = array(
            'object_type' => 'give',
            'total_quantity' => $data['number'],
            'total_amount' => 0,
            'created' => date('Y-m-d H:i:s', time()),
            'buyer' => $userID,
            'vendor' => $data['uid'],
            'payment' => '',
            'id_contact' => 0,
            'status' => 3,
            );
        $oid = $this->order->add_order($order_data);
        //生成交易明细
        $item_data = array(
            'id_orders' => $oid,
            'id_cowry' => $data['cid'],
            'price' => 0,
            'quantity' => $data['number'],
            );
        $this->order->add_order_item($item_data);
        //赠送人所有数减少
        //$num = $this->owner->is_onwer($data['cid'],$userID);
        //$this->owner->update_owner(array('quantity'=>($num['num']-$data['number'])),array('id_cowry_owner'=>$num['id_cowry_owner']));
        //接受方 增加
        $recipient = $this->owner->is_onwer($data['cid'], $data['uid']);
        if ($recipient) {
            //$this->owner->update_owner(array('quantity'=>($recipient['num']+$data['number'])),array('id_cowry_owner'=>$recipient['id_cowry_owner']));
        } else {
            $owner = array(
                'id_cowry' => $data['cid'],
                'owner' => $data['uid'],
                'quantity' => 0, //$data['number'],
                'created' => date('Y-m-d H:i:s', time()),
                );
            $this->owner->add_owner($owner);
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    /**
     * 宝贝转换
     * @param $oid
     * @param $userID
     */
    protected function convert($oid, $userID)
    {
        $this->load->model('cowryowner_model', 'owner');
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowry_attach_model', 'attach');

        $this->db->trans_begin();
        $order = $this->order->get_all_order_cowry_item($oid);
        foreach ($order as $li) {
            //新宝贝
            $cowry = array(
                'description' => $li['description'],
                'cover_image' => $li['cover_image'],
                'creator' => $userID,
                'created' => date('Y-m-d H:i:s', time()),
                'price' => $li['price'],
                'status' => 'down',
                'latitude' => $li['latitude'],
                'longitude' => $li['longitude'],
                'address' => $li['address'],
                );
            $cowryID = $this->cowry->add_cowry($cowry);
            //附件
            $cowimg = explode(',', $li['img_url']);
            foreach ($cowimg as $val) {
                $this->attach->add_attach(array('id_cowry' => $cowryID, 'att_img' => $val));
            }
            //所有者
            $owner = array(
                'id_cowry' => $cowryID,
                'owner' => $userID,
                'quantity' => 0,
                'created' => date('Y-m-d H:i:s', time()),
                );
            $this->owner->add_owner($owner);
        }
        //修改订单状态
        $this->order->modify_order(array('status' => 8), $oid);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }

    /**
     * 获取宝贝基本信息
     */
    protected function baseinfo($cid, $uid)
    {
        $this->load->model('cowry_model', 'cowry');
        $this->load->model('cowry_attach_model', 'attach');
        $cowry = $this->cowry->get_cowry($cid, $uid);
        if($cowry===FALSE){
            return FALSE;
        }
        if($cowry['num']<0){
            $cowry['num'] = 0;
        }elseif($cowry['num']>=9999){
            $cowry['num'] = 9999;
        }
        $result = array(
            'description' => $cowry['description'],
            'cid' => $cowry['cid'],
            'num' => $cowry['num'],
            'status' => $cowry['status'],
            'aid'=>$cowry['aid'],
            'address' => $cowry['address'],
            'lon'=>$cowry['lon'],
            'lat'=>$cowry['lat'],
            'price' => $cowry['price'] ? $cowry['price'] : 0,
            'img' => explode(',', $cowry['img']),
            );
        return $result;
    }
	
	
	/**
	 *@宝贝列表
	 */
    protected function get_cowrylist($page, $userID, $status)
    {
        $this->load->model('cowry_model', 'cowry');
        $where = 'o.owner = ' . $userID . ' AND ci.status = \'' . $status . '\'';
        $this->db->trans_begin();
        $cowry = $this->cowry->get_user_cowry($where, 12, 12 * ($page - 1));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            for($i=0;$i<count($cowry);$i++){
                if($cowry[$i]['num']<0){
                    $cowry[$i]['num'] = 0;
                }elseif($cowry[$i]['num']>=9999){
                    $cowry[$i]['num'] = 9999;
                }
            }
            return $cowry;
        }
    }
    
	/**
	 *@用户自荐专题宝贝列表（上架）
	 */
	protected function get_recom_cowry($page, $userID,$id_theme,$status){
		$this->load->model('cowry_model', 'cowry');
		$this->load->model('theme_model','theme');
        $where = 'o.owner = ' . $userID . ' AND ci.status = \'' . $status . '\'';
        $this->db->trans_begin();
        $cowry = $this->cowry->get_user_cowry($where, 12, 12 * ($page - 1));
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            for($i=0;$i<count($cowry);$i++){
                if($cowry[$i]['num']<0){
                    $cowry[$i]['num'] = 0;
                }elseif($cowry[$i]['num']>=9999){
                    $cowry[$i]['num'] = 9999;
                }
				$where = array('id_theme'=>$id_theme,'id_cowry'=>$cowry[$i]['cid']);
				$theCowry = $this->theme->get_theme_cowry_by_where($where);
				if($theCowry){
					$cowry[$i]['status'] = $theCowry[0]['status'];
				}else{
					$cowry[$i]['status']=3;
				}
            }
            return $cowry;
		}
	}
	
	
    /**
     * @获取宝贝标签
     */
    protected function get_tag(){
        $this->load->model('tag_model', 'tag');
        $tag = $this->tag->get_tag_list();
        return _list_to_tree($tag);
        
    }
    
    /**
     * @根据标签获得宝贝列表
     */
    protected function get_cowry_by_tag($id_tag,$page){
        $this->load->model('tag_model', 'tag');
        $where = array('id_tag'=>$id_tag);
        $offset = 10;
        $cowry = $this->tag->get_tag_cowry($where,$page,$offset);
        return $cowry;
    }

    /**
     * 获取宝贝评论
     * $id_cowry  宝贝id
     * $offset 页码
     * $page 信息条数
     * zxx
     */
    protected function get_comment($data,$page=10,$fun=''){
        $this->load->model('cowrycomment_model','comment');
        $this->load->model('user_model','user');
        $where = 'cc.id_cowry = ' . $data['cid'];
        $option['offset']= $data['offset'];
        $option['page'] = $page;
        $option['order'] = 'cc.created desc';
        $cowry_comment = $this->comment->get_cowry_comment($where,$option);
        if($cowry_comment){
            foreach($cowry_comment as $k=>$v){
                $cowry_comment[$k]['r_nickname'] = '';
                if($v['r_uid']){
                    $where_u = 'id_2buser = ' . $v['r_uid'];
                    $user_info = $this->user->get_user_info($where_u);
                    if($user_info){
                        $cowry_comment[$k]['r_nickname'] = $user_info[0]['nickname'];
                    }
                }
            }
        }
        $option['offset']= 0;
        $comment_total = $this->comment->get_cowry_comment($where,$option,'count(cc.id_comment) as total');

        $return['comment'] = $cowry_comment;
        $return['total'] =  $comment_total[0]['total'];
        if(!$fun)
            return $cowry_comment;
        else
            return $return;
    }

    /**
     * zxx
     * 添加宝贝评论
     */
    protected function add_comment($data) {
        $this->load->model('cowrycomment_model','comment');
//        if (empty($data['lon']) || empty($data['lat'])) {
//            $data['lon'] = '104.08084106445';
//            $data['lat'] = '30.66577911377';
//        }
        $content = $data['content'];
        if(empty($content)){
            $this->errors = '怎么没有评论内容！？';
            return false;
        }
        $created = date('Y-m-d H:i:s', time());
        $cowry_comment = array(
            'id_cowry' => $data['cid'],
            'id_2buser' => $this->token['uid'],
            'content' => $content,
            'created' => $created
        );

        $cowry_comment['id_replay_2buser'] = 0;
        $cowry_comment['id_parent'] = 0;
        if(!empty($data['r_uid'])){
            $cowry_comment['id_replay_2buser'] = $data['r_uid'];
            $cowry_comment['id_parent'] = $data['r_commentid'];
        }
        $this->db->trans_begin();
        //添加评论（或回复）信息
        $commentID = $this->comment->add_cowry_comment($cowry_comment);
//        //读取评论或回复信息
//        $data_['cid'] = $data['cid'];
//        $data_['offset'] = 1;
//        $comment_info = $this->get_comment($data_);
        //获取评论用户信息
        $this->load->model('user_model','user');
        $where_u = 'id_2buser = ' . $this->token['uid'];
        $user_info = $this->user->get_user_info($where_u);

        $return['commentid'] = $commentID;
        $return['created'] = $created;
        $return['nickname'] = '';
        $return['fav'] = '';
        if($user_info){
            $return['nickname'] = $user_info[0]['nickname'];
            $return['fav'] = $user_info[0]['head_image'];
        }else{
            $this->errors = '怎么没有用户信息呢！';
            return false;
        }

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
//            'commentid' => $commentID,'comment'=>$comment_info['comment']
            return $return;
        }
    }
	
	/**
	 *@author zhoushuai
	 *@新增点赞功能
	 *@显示点赞总数，当点击后总数加1，按钮变成实心，再次点击减1按钮变成空心，可反复点击
	 */
	protected function zan($cid){
		$uid =  $this->token['uid'];
		$this->load->model('cowry_model','cowry');
		$this->db->trans_begin();
		$res = $this->cowry->zan($cid);
		if($res){
			$data=array(
				'id_cowry'=>$cid,
				'id_2buser'=>$uid,
				'created'=>date('Y-m-d H:i:s', time())
			);
			$this->cowry->insert_zan($data);
			if ($this->db->trans_status() === TRUE) {
				$this->db->trans_commit();
				return TRUE;
			}
		}
		$this->db->trans_rollback();
		return FALSE;
	}
	
	/**
	 *@author zhoushuai
	 *@取消赞
	 *@显示点赞总数，当点击后总数加1，按钮变成实心，再次点击减1按钮变成空心，可反复点击
	 */
	protected function deny($cid){
		$uid =  $this->token['uid'];
		$this->load->model('cowry_model','cowry');
		$this->db->trans_begin();
		$res = $this->cowry->deny($cid);
		if($res){
			$where = array('id_cowry'=>$cid,'id_2buser'=>$uid);
			$this->cowry->del_zan($where);
			if ($this->db->trans_status() === TRUE) {
				$this->db->trans_commit();
				return TRUE;
			}
		}
		$this->db->trans_rollback();
		return FALSE;
	}



}
/* End of file cowry_Controller.php */
/* Location: ./shared/core/cowry_Controller.php */
