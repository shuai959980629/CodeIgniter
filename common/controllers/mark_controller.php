<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * 关注
 * @author zhoushuai
 */
class Mark_Controller extends Common_Controller
{

    /**
     * 加关注
     * @param data array 包含被关注者id的数组
     * @param userID  int 关注者ID  
     */

    protected function mark($data, $userID)
    {
        $this->load->model('mark_model', 'mark');
        $mark_data = array(
            'id_2buser' => $userID,
            'object_type' => 'mark',
            'object_id' => $data['uid'],
            'created' => date('Y-m-d H:i:s', time()));
        $return = $this->mark->add_mark($mark_data);
        if ($return > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取消关注
     * @param data array 包含被关注者id的数组
     * @param userID  int 关注者ID  
     */
    protected function cancel_mark($data, $userID)
    {
        $this->load->model('mark_model', 'mark');
        $cancel_data = array('id_2buser' => $userID, 'object_id' => $data['uid']);
        return $this->mark->cancel_mark($cancel_data);
    }

    /**
     * 获取动态
     * @param uid int 2bi_user用户id
     */
    protected function dynamic($uid,$page)
    {
        $data = array();
        $this->load->model('mark_model', 'mark');
        //获取该2bi_user用户的。关注列表。并得到我关注人的id
        //'object_type' => 'mark'
        $where = array('id_2buser' => $uid);
        $markIdList = $this->mark->get_mark_id_list($where,10,$page);
        if(!$markIdList){
            return $data;
        }
        //根据被关注者的id列表，获取动态表的。有关的动态信息
        $dyn_list = $this->mark->get_dyn_list($markIdList);
        if(!$dyn_list){
            return $data;
        }
        //根据动态信息的。用户id 和宝贝id。分别获取。用户信息和宝贝信息
        for ($i = 0; $i<count($dyn_list); $i++) {
            //$userinfo = $this->mark->get_userinfo($dyn_list[$i]['id_2buser']);
            $cowry = $this->mark->get_cowry($dyn_list[$i]['object_id'], $dyn_list[$i]['id_2buser']);
            if(!$cowry){
                continue;
            }
            //宝贝下架了。。不在显示关注列表
            if($cowry['status']=='down'){
                $where = array('id_dynamic'=>$dyn_list[$i]['id_dynamic']);
                $this->mark->delete_dyn($where);
            }
            
            if($cowry['num']<0){
                $cowry['num'] = 0;
            }elseif($cowry['num']>=9999){
                $cowry['num'] = 9999;
            }
            $data[] = array(
                'uid' => $dyn_list[$i]['id_2buser'],
                'nickname' => $cowry['nickname']?$cowry['nickname']:$cowry['username'],
                'id_cowry' => $cowry['cid'],
                'status'=>$cowry['status'],
                'price'=>$cowry['price'],
                'stock'=>$cowry['num'],
                'favicon' => $cowry['favicon'],
                'cowry_photo'=>explode(',', $cowry['img']),
                'cowry_des'=>$cowry['description'],
                'dyn_des' => $dyn_list[$i]['description'],
                //'cowry'=>$cowry,
                'created' => $dyn_list[$i]['created']
                
            );
        }
        return $data;
    }
    /**
     * @是否有新的动态信息
     */
    protected function new_dyna($uid,$time){
        $data = array('new'=>0,'time'=>date('Y-m-d H:i:s', time()));
        $this->load->model('mark_model', 'mark');
        if($time==''){
            $where = array('f.id_2buser' => $uid);
        }else{
            $where = array('f.id_2buser' => $uid,'d.created >'=>$time);
        }
        $list = $this->mark->query_dyn($where);
        if($list){
            $data['new']=1;
        }
        return $data;
        
    }
    /**
     * @v2.0
     * @获取动态
     */
    protected function dynlist($uid,$page)
    {
        $data = array();
        $this->load->model('mark_model', 'mark');
        $this->load->model('cowry_model', 'cowry');
        $where = array('f.id_2buser' => $uid);
        $list = $this->mark->query_dyn_list($where,$page,10);
        if(!empty($list)){
           for($i=0;$i<count($list);$i++){
                $img = $this->mark->get_dynamic_attachment($list[$i]['cid']);
                $cowryIMG = array();
                for($j=0;$j<count($img);$j++){
                    $cowryIMG[]= $img[$j]['img'];
                }
                $data[] = array(
                    'uid' => $list[$i]['uid'],
                    'nickname' => $list[$i]['nickname']?$list[$i]['nickname']:$list[$i]['username'],
                    'favicon' => $list[$i]['favicon'],
                    'id_cowry' => $list[$i]['cid'],
                    'cowry_photo'=>$cowryIMG,
                    'cowry_des'=>$list[$i]['cowry_des'],
                    'dyn_des' => $list[$i]['dyn_des'],
                    'created' => $list[$i]['created']
                    
                );
                
            } 
        }
        return $data;
    }
    
    /**
     * @v2.0
     * @获取关注列表
     */
   protected function marklist($uid,$page){
        $data = array();
        $this->load->model('mark_model', 'mark');
        $where = array('f.id_2buser' => $uid);
        $list = $this->mark->query_mark_list($where,$page,10);
        if(!empty($list)){
            for($i=0;$i<count($list);$i++){
                $data[]=array(
                    'uid'=>$list[$i]['uid'],
                    'type'=>$list[$i]['type'],
                    'username'=>$list[$i]['username'],
                    'nickname' => $list[$i]['nickname']?$list[$i]['nickname']:$list[$i]['username'],
                    'favicon' => get_img_url($list[$i]['favicon'],'favicon','favicon'),
                    'format'=>format_date(strtotime($list[$i]['created']))
                );
            }
        }
        return $data;
   }
    
    
    
    


}
/* End of file mark_Controller.php */
/* Location: ./shared/core/mark_Controller.php */
