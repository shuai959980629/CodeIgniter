<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * 朋友核心逻辑
 *
 * @author rjy
 * @date 13-11-1
 */
class Friend_Controller extends Common_Controller {


    /*
     * 好友列表
     */
    protected function friendlist($userID)
    {

        $this->load->model('friend_model', 'friend');
        $this->load->library ( 'Chinese' );

        //获取列表
        $friends = $this->friend->getfriends($userID, 'good',0,0);
        //分类排序
        $result = array();
        if( !empty($friends) ){
            foreach ($friends as $k=>$val) {
                $friends[$k]['nickname'] = !empty($val['nickname']) ? $val['nickname'] : $val['username'];
                unset($friends[$k]['username']);
                $first = strtoupper($this->chinese->get_first_word($friends[$k]['nickname']));
                $abc = 'abcdefghijklmnopqrstuvwxyz';
                if(in_array($first, str_split(strtoupper($abc), 1))){
                    $result[$first][] = $friends[$k];
                }else{
                    $result['#'] = $friends[$k];
                }
            }
            array_multisort($result,SORT_ASC,SORT_STRING);
        }
        return $result;
    }

    /*
     * 删除好友   移出黑名单
     */
    protected function deletefriend($friend)
    {
        $this->load->model('friend_model', 'friend');

        $re = $this->db->delete('bi_friends', array('id_friends' => $friend));
        return $re;
    }

    /*
     * 添加黑名单
     */
    protected function addblack($userID,$memberID)
    {
        $this->load->model('friend_model', 'friend');

        $fid = $this->friend->is_friend($userID,$memberID,'good');
        if( !empty($fid) && !empty($fid['id_friends']) ){
            $result = $this->friend->update_friend(array('object_type' => 'black'),$fid['id_friends']);
        }else{
            $data = array(
                'id_2buser' => $userID,
                'object_id' => $memberID,
                'object_type' => 'black',
                'created' => date('Y-m-d H:i:s',time())
            );
            $result = $this->friend->add_friend($data);
        }
        return $result;
    }
    /*
    * 黑名单列表
    */
    protected function blacklist($userID)
    {
        $this->load->model('friend_model', 'friend');
        //获取列表
        $friends = $this->friend->getfriends($userID, 'black',$this->filter_params['page'],10);
        if( !empty($friends) ){
            foreach ($friends as $k=>$val) {
                $friends[$k]['nickname'] = !empty($val['nickname']) ? $val['nickname'] : $val['username'];
            }
        }
        return !empty($friends) ? $friends : array();
    }

    /*
     * 感兴趣的人
     */
    protected function interest($userID)
    {
        $this->load->model('friend_model', 'friend');
        $this->load->model('user_model', 'user');
        $this->load->model('cowry_model', 'cowry');

        //获取好友
        $ids = array($userID);
        $friend = $this->friend->getfriends($userID, 'good', 0, 0);
        if( !empty($friend) ){
            foreach( $friend as $row ){
                $ids[] = $row['uid'];
            }
        }
        $interest = $this->friend->get_interest(10,$ids);
        if( !empty($interest) ){
            foreach( $interest as $key=>$val){
                $interest[$key]['nickname'] = !empty($interest[$key]['nickname']) ? $interest[$key]['nickname'] : $interest[$key]['username'];
                unset($interest[$key]['username']);
                if($val['birthday']){
                    $interest[$key]['constellation'] = $this->get_zodiac_sign($val['birthday']);
                }else{
                    $interest[$key]['constellation'] = '';
                }
                $userIDs[] = $val['uid'];
            }
            //获取宝贝数
            $delinum = $this->cowry->get_user_cowry_count($userIDs);
            foreach( $delinum as $num){
                $count[$num['uid']] = $num['count'];
            }
            foreach( $interest as $key=>$val){
                $interest[$key]['cowrynum'] = !empty($count[$val['uid']]) ? $count[$val['uid']] : 0;
            }
        }

        return !empty($interest) ? $interest : array();
    }

    /*
     * 排行榜
     * $data['page']当前页码
     * $data['type'] 0世界排行（默认），1好友排行
     * $type rich/good
     */
    protected function ranklist($data,$type,$userID)
    {
        $this->load->model('ranking_model','rank');
        $this->load->model('friend_model','friend');

        //获取
        if($data['type'] == 1){
            //好友排行
            $friends = $this->friend->getfriends($userID, 'good', 0, 0);
            $ids = array($userID);
            foreach($friends as $val){
                $ids[] = $val['uid'];
            }
            $where = 'r.id_2buser NOT IN ('.implode(',',$ids).') AND r.object_type = \''.$type.'\'';
        }else{
            //世界排行
            $where = 'r.object_type = \''.$type.'\'';
        }
        $list = $this->rank->get_rank_list($where, $data['page'], 20);
        $sort = 1;
        if( !empty($list) ){
            foreach( $list as $key=>$val){
                $cowry_num = $val['delinum'];
                //星座
                if($val['birthday']){
                    $list[$key]['constellation'] = $this->get_zodiac_sign($val['birthday']);
                }else{
                    $list[$key]['constellation'] = '';
                }
                $list[$key]['nickname'] = !empty($list[$key]['nickname']) ? $list[$key]['nickname'] : $list[$key]['username'];
                unset($list[$key]['username']);
                if($data['type'] == 1){
                    //名次
                    if( $val['delinum'] == $cowry_num ){
                        $list[$key]['sort'] = $sort;
                    }else{
                        $list[$key]['sort'] = intval($key+1);
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取关系
     */
    protected  function get_relation($userID)
    {
        $this->load->model('friend_model', 'friend');

        $relationship = 0;
        if( $userID == $this->token['uid'] ){
            $relationship = 3;
        }else{
            $relation = $this->friend->get_relation($userID,$this->token['uid']);
            if( $relation['object_type'] == 'good'){
                $relationship = 1;
            }elseif( $relation['object_type'] == 'black'){
                $relationship = 2;
            }
        }

        return array('relation'=>$relationship);
    }






}
/* End of file friend_controller.php */