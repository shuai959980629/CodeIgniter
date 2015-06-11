<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * @专题活动
 * @author zhoushuai
 * @date 2014-11-07 16:30
 */
class Theme_Controller extends Common_Controller
{
    
    /**
     * @author zhoushuai
     * @获取专题活动列表
     */
    protected function get_theme_list()
    {
        if (in_array($this->params['type'], array('dist', 'search'))) {
            $this->load->model('theme_model', 'theme');
            $themes = array();
            $this->params['page'] = !empty($this->params['page']) ? intval($this->params['page']) :1;
            if ($this->params['type'] == 'dist') {
                //距离，检测经纬度
                if (empty($this->params['lon']) || empty($this->params['lat'])) {
                    $this->params['lon'] = '104.08084106445';
                    $this->params['lat'] = '30.66577911377';
                }
                if (!empty($this->params['lon']) && !empty($this->params['lat'])) {
                    //处理经纬度排行,处理距离
                    $userID = $this->token['uid'] ? $this->token['uid'] : 0;
                    $themes = $this->theme->get_themelist($userID, $this->params['lon'], $this->params['lat'], ($this->params['page'] - 1) * 24, 24);
                }
            } elseif ($this->params['type'] == 'search') {
                /*
                //处理搜索
                if (!empty($this->params['query'])) {
                    $shop = $this->order->get_search($this->params['query'], ($this->params['page'] -1) * 24, 24);
                }
                */
            }
            return $themes;

        }
        $this->errors = $this->lang->line('request_param_errors');
        return false;
    }
    
    /**
     * @author zhoushuai
     * @获取专题活动列表
     */
    protected function get_theme_cowry($id_theme,$page){
        $this->load->model('theme_model', 'theme');
        $where = array('id_theme'=>$id_theme);
        $offset = 10;
        $cowry = $this->theme->get_theme_cowry($where,$page,$offset);
        return $cowry;
    }
	
    /**
     * @author zhoushuai
     * @专题活动--推荐自己宝贝，待后台审核 0未审核
     */
	protected function recom_self_cowry($id_theme,$cowry){
		$this->load->model('theme_model', 'theme');
		//判断专题活动是否存在。是否关闭，是否允许自荐
		$theme = $this->theme->get_theme_by_id($id_theme);
		if(!$theme){
			$this->errors = '该专题已删除，无法推荐自己宝贝！';
		}else{
			if($theme['type']=='normal'){
				if($theme['status']==0){
					$this->errors = '该专题已关闭，无法推荐自己宝贝！';
				}else{
					if($theme['join']==0){
						$this->errors = '该专题不允许用户推荐自己宝贝！';
					}else{
						for($i=0;$i<count($cowry);$i++){
							$thcowry[]=array('id_theme'=>$id_theme,'id_cowry'=>$cowry[$i],'status'=>0,'created'=>date('Y-m-d H:i:s', time()));
						}
						$res =$this->theme->insert_batch_theme_cowry($thcowry);
						if($res){
							return TRUE;
						}
						$this->errors = '推荐失败！';
					}
				}
			}
			$this->errors = '该类专题不接收用户推荐宝贝，请重新选择！';
		}
		return FALSE;
	}
	
	

}

/* End of file Theme_Controller.php */
