<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 
 * 哔哔的核心逻辑
 * 
 * @author jxy
 * @date 2013-11-05 13:47
 */
class Bibi_Controller extends Common_Controller {

	
	/**
	 * 添加或修改哔哔
	 * @param int $bid
	 * @return boolean
	 */
	protected function add_modify_bibi($bid=0){
		
		$this->filter_params['object_type'] = $this->params['type'];
		//$this->filter_params['address'] = $this->params['bbtime'] = $this->params['desc'] = '';
		$this->filter_params['last_time'] = time();

		if(empty($this->params['content'])){
			$this->errors = $this->lang->line('request_param_errors');
			return FALSE;
		}
		if($this->filter_params['object_type'] == 'text'){
			if(strlen($this->params['content']) < 6 || strlen($this->params['content']) > 24){
				$this->errors = $this->lang->line('request_content_length_error');
				return FALSE;
			}else{
				$this->filter_params['title'] = $this->params['content'];
			}
		}else{
            //音频附件
            $attach = array(
                'object_type' => 'audio',
                'view_url' => $this->params['content'],
                'view_time' => intval($this->params['seconds']),
            );
            $this->filter_params['title'] = $this->params['content'];
        }
		//默认取天府广场的坐标
		if(empty($bid)){
			if(empty($this->params['lon']) || empty($this->params['lat'])){
				$this->filter_params['create_longitude'] = '104.072277';
				$this->filter_params['create_latitude'] = '30.663333';
			}
			$this->filter_params['creator'] = $this->token['uid'];
		}
		if(!empty($this->params['bbtime']) && !empty($this->params['address']) && !empty($this->params['desc'])){
			if(strlen($this->params['desc']) < 240){
				$this->filter_params['activity_time'] = $this->params['bbtime'];
				$this->filter_params['activity_place'] = $this->params['address'];
				$this->filter_params['description'] = $this->params['desc'];
			}else{
				$this->errors = $this->lang->line('request_desc_length_error');
				return FALSE;
			}
		}
		$this->load->model('bb_info_model','bb_info');
		//编辑
		if($bid){
			$this->bb_info->modify_bibi($bid,$this->token['uid'],$this->filter_params);
            $attach = $this->bb_info->is_attachment($bid);
            if($attach['aid']>0){
                $this->bb_info->modify_attachment($attach,$attach['aid']);
            }else{
                $attachID = $this->bb_info->add_attachment($attach);
                $this->bb_info->modify_bibi($bid,$this->token['uid'],array('id_attachment'=>$attachID));
            }
			return TRUE;
		}else{//新增
			$bid = $this->bb_info->add_bibi($this->filter_params);
            $attachID = $this->bb_info->add_attachment($attach);
            $this->bb_info->modify_bibi($bid,$this->token['uid'],array('id_attachment'=>$attachID));
		}
	}
	
	
	/**
	 * 取得哔哔详情
	 * @return boolean
	 */
	protected function bibi_dettail(){
		
		$this->load->model('bb_info_model','bb_info');
		if($this->params['bid']){
			return $this->bb_info->get_detail($this->params['bid']);
		}else{
			$this->errors = $this->lang->line('request_param_errors');
			return FALSE;
		}
		
	}
	
	
	/**
	 * 取得哔哔列表
	 */
	protected function bblist(){
		
		$start = $this->get_page_start();
		if(in_array($this->params['type'], array('dist','hot','search'))){
			$this->load->model('bb_info_model','bb_info');
			if($this->params['type'] == 'dist'){
				//距离，检测经纬度
                if(empty($this->params['lon']) || empty($this->params['lat'])){
                    $this->params['lon'] = '104.072277';
                    $this->params['lat'] = '30.663333';
                }
                if(!empty($this->params['lon']) && !empty($this->params['lat'])){
					//处理经纬度排行,处理距离
					return $this->bb_info->get_near_bblist($this->params['lon'],$this->params['lat'],$start,$this->config->item('page_of_count'),$this->config->item('get_list_range'));
				}
			}elseif ($this->params['type'] == 'hot'){
				//处理热门榜,根据哔哔成员数
				$result = $this->bb_info->get_hostlist($start,$this->config->item('page_of_count'));
				return $result;
			}elseif ($this->params['type'] == 'search'){
				//处理搜索
				if(!empty($this->params['query'])){
					$result = $this->bb_info->get_search($this->params['query'],$start,$this->config->item('page_of_count'));
				}
			}
		}
		$this->errors = $this->lang->line('request_param_errors');
		return FALSE;
		
	}
	
	
	/**
	 * 哔哔成员管理
	 * @param string $opt
	 * 	join 添加
	 *  out 退出
	 *  shut 禁言/解禁言
	 */
	protected function member_management($opt){
		
		$this->load->model('bb_info_model','bb_info');
		$bid = $this->params['bid'];
		if(!empty($bid)){
			//检查BB是否有效
			$bb = $this->bb_info->is_enable($this->params['bid']);
			//检查是否已经加入
			$ismember = $this->bb_info->is_member($this->token['uid'],$this->params['bid']);
			if($bb){
				if($opt == 'join'){
					if(!$ismember){
						$insert_data = array(
							'id_bb' => intval($this->params['bid']),
							'id_2buser' => $this->token['uid']
						);
						$this->bb_info->join_member($insert_data,$this->params['bid']);
						return TRUE;
					}else{
						$this->errors = $this->lang->line('current_member_is_bb_member');
						return FALSE;
					}
					
				}elseif ($opt == 'out'){
					$isauthor = $this->bb_info->is_author($this->token['uid'],$this->params['bid']);
					if(!$isauthor){
					//检查是否已经加入
					if($ismember){
						$this->bb_info->out_member($this->token['uid'],$this->params['bid']);
						return TRUE;
					}else{
						$this->errors = $this->lang->line('current_member_is_not_bb_member');
						return FALSE;
					}
					}else{
						$this->errors = $this->lang->line('is_bibi_author_not_opt');
						return  FALSE;
					}
				
				}elseif ($opt == 'shut'){
					//创建者不能退出
					if($this->params['uid'] == $this->token['uid']){
						$this->errors = $this->lang->line('is_bibi_author_not_opt');
						return FALSE;
					}
					//检查当前账户，是否是创建者
					$isauthor = $this->bb_info->is_author($this->token['uid'],$this->params['bid']);
					if($isauthor){
						$this->bb_info->shut_member($this->params['uid'],$this->params['bid']);
						return TRUE;
					}else{
						$this->errors = $this->lang->line('is_not_bibi_author_not_opt');
						return  FALSE;
					}
				
				}
			}else{
				$this->errors = $this->lang->line('bb_status_is_not_opt');
				return FALSE;
			}
			
		}
		$this->errors = $this->lang->line('request_param_errors');
		return FALSE;
		
	}

    /*
     * 获取我参与的哔哔
     */
    protected function joinlist($userID,$page)
    {
    	
        $this->load->model('bb_info_model','bb');

        $list = $this->bb->join_list('m.id_2buser = '.$userID,'m.created DESC',$page,24);
        if( empty($list) ){
            $this->errors = $this->lang->line['bb_joinlist_empty'];
        }
        return $list;
        
    }
    
    
    /**
     * 关闭哔哔
     */
    protected function close_bibi(){
    	
    	//检查是否是创建者
    	$this->load->model('bb_info_model','bb_info');
    	$isauthor = $this->bb_info->is_author($this->token['uid'],$this->params['bid']);
    	if($isauthor){
    		return $this->bb_info->close_bibi($this->params['bid']);
    	}else{
    		$this->errors = $this->lang->line('is_not_bibi_author_not_opt');
    		return FALSE;
    	}
    }
    
    
    /**
     * 获取宝贝房
     */
    protected function get_cowrylist(){
    	
    	$this->load->model('bb_info_model','bb_info');
    	$cowry = $this->bb_info->get_cowrylist($this->params['bid']);
    	return $cowry;
    	
    }
    
    
    /**
     * 获取成员列表
     * @param int $status //1表示成员列表，0表示禁言列表
     */
    protected function get_memberlist($status = 1){
    	
    	$this->load->model('bb_info_model','bb_info');
    	$member = $this->bb_info->get_memberlist($status,$this->params['bid']);
    	if(empty($member)){
    		$this->errors = $this->lang->line('no_data');
    		return FALSE;
    	}else{
    		return $member;
    	}
    	
    }
    

    /*
     * 历史聊天消息
     */
    protected function historymsg($data)
    {
        $this->load->model('bb_info_model','bb');

        $where = 'c.id_bb = '.$data['bid'].' AND c.created <='.$data['timestamp'];
        $msg = $this->bb->get_history($where,'c.created DESC',$data['page'],24);

        return $msg;
    }



}

/* End of file Bibi_Controller.php */
/* Location: ./shared/core/bibi_controller.php */