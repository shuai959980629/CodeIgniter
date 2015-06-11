<?php if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * 订单核心逻辑
 *
 * @author rjy
 * Date: 13-12-17
 * Time: 下午3:33
 */
class Order_Controller extends Common_Controller
{


    /**
     * 邻售首页（附近的宝贝）
     */
    protected function cowrylist()
    {
        if (in_array($this->params['type'], array('dist', 'search','tag','theme'))) {
            $this->load->model('order_model', 'order');
            $cowry = array();
            $this->params['page'] = !empty($this->params['page']) ? intval($this->params['page']) : 1;
            $type = $this->params['type'];
            switch($type){
                case 'dist':
                    //距离，检测经纬度
                    if (empty($this->params['lon']) || empty($this->params['lat'])) {
                        $this->params['lon'] = '104.08084106445';
                        $this->params['lat'] = '30.66577911377';
                    }
                    if (!empty($this->params['lon']) && !empty($this->params['lat'])) {
                        //处理经纬度排行,处理距离
                        $userID = $this->token['uid'] ? $this->token['uid'] : 0;
                        $cowry = $this->order->get_near_cowrylist($userID, $this->params['lon'], $this->params['lat'], ($this->params['page'] - 1) * 24, 24);
                    }
                    break;
                case 'search':
                    //处理搜索
                    if (!empty($this->params['query'])) {
                        $cowry = $this->order->get_search($this->params['query'], ($this->params['page'] -1) * 24, 24);
                    }
                    break;
                case 'tag':
                    if (empty($this->params['lon']) || empty($this->params['lat'])) {
                        $this->params['lon'] = '104.08084106445';
                        $this->params['lat'] = '30.66577911377';
                    }
                    if (!empty($this->params['lon']) && !empty($this->params['lat'])) {
                        $gid = intval($this->params['id'])?intval($this->params['id']):false;
                        if(!$gid){
                            $this->errors = "请传入需要查询的标签id！";
                            return false;
                        }else{
                            $cowry = $this->order->get_cowry_by_tag($gid, $this->params['lon'], $this->params['lat'], ($this->params['page'] - 1) * 24, 24);
                        }
                    }
                   break;
                case 'theme':
                    if (empty($this->params['lon']) || empty($this->params['lat'])) {
                        $this->params['lon'] = '104.08084106445';
                        $this->params['lat'] = '30.66577911377';
                    }
                    if (!empty($this->params['lon']) && !empty($this->params['lat'])) {
                        $tid = intval($this->params['id'])?intval($this->params['id']):false;
                        if(!$tid){
                            $this->errors = "请传入需要查询的专题活动id！";
                            return false;
                        }else{
                            $cowry = $this->order->get_cowry_by_theme($tid, $this->params['lon'], $this->params['lat'], ($this->params['page'] - 1) * 24, 24);
                        }
                    }
                    break;
                
            }
            
            if (empty($cowry)) {
                $this->errors = "暂无宝贝信息。";
                return $cowry;
            } else {
                for ($i = 0; $i < count($cowry); $i++) {
                    $cowry[$i]['img']= get_img_url($cowry[$i]['img'],'cowry');
                    if ($cowry[$i]['num'] < 0) {
                        $cowry[$i]['num'] = 0;
                    } elseif ($cowry[$i]['num'] >= 9999) {
                        $cowry[$i]['num'] = 9999;
                    }
                }
                return $cowry;
            }

        }
        $this->errors = $this->lang->line('request_param_errors');
        return false;
    }

    /**
     * @生成订单
     * @订单状态为零0，未支付订单。
     * @1:虚拟订单, 2:已付款(线下支付订单待确认),3:投诉中,4:投诉完成（仲裁完成）5:已收货(订单完成,线下支付订单已确认),6:取消
     */
    protected function add($data, $userID)
    {
        //产生订单编号
        if($data['uid']==$userID){
            $this->errors = '不能购买的自己宝贝！';
            return false;
        }
        $order_no = create_order_id();
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        if (!in_array($data['payment'], array('online', 'offline'))) {
           $this->errors = '参数错误，请输入支付方式！';
           return false; 
        }
        $payment = $data['payment'];
        $status = 0;//未支付。页面客户端 不显示。支付完成修改状态为2已支付
        if($payment=='offline'){
            $status = 2; //已付款,线下支付订单待确认
        }
        //生成订单
        $this->db->trans_begin();
        $order_data = array(
            'order_no' => $order_no,
            'object_type' => 'retail',
            'total_quantity' => $data['total_num'],
            'total_amount' => $data['total_price'],
            'created' => date('Y-m-d H:i:s', time()),
            'buyer' => $userID,
            'vendor' => $data['uid'],
            'payment' => $payment,
            'id_contact' => 0,
            'status' => $status, 
            );
        $oid = $this->order->add_order($order_data);
        //生成订单明细
        $total_num = 0;
        $total_price = 0;
        foreach ($data['data'] as $item) {
            $item_data = array(
                'id_orders' => $oid,
                'id_cowry' => $item['cid'],
                'owner' => $data['uid'],
                'price' => $item['price'],
                'quantity' => $item['buy_num'],
                'amount' => $item['total'],
                'image' => $item['img'],
                'description' => $item['desc'],
                'name' => $item['name'],
                'phone' => $item['phone'],
                'address' => $item['address'],
                );
            $this->order->add_order_item($item_data);
            $total_num += $item['buy_num'];
            $total_price += $item['price'] * $item['buy_num'];
            if($payment=='online'){
                //线上交易-修改原宝贝数量
                $num = $this->cowry->get_cowry_num($data['uid'], $item['cid']);
                $this->cowry->edit_num($data['uid'], $item['cid'], $num['num'] - $item['buy_num']);
            }
        }
        //判断总数总价是否正确
        $array = array();
        if ($data['total_num'] != $total_num) {
            $array['total_quantity'] = $total_num;
        }
        if ($data['total_price'] != $total_price) {
            $array['total_amount'] = $total_price;
        }
        if (!empty($array)) {
            $this->order->modify_order($array, $oid);
        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->errors = $this->lang->line('add_order_fail');
            return false;
        } else {
            $this->db->trans_commit();
            if($payment=='offline' && $oid && $order_no){
                $log = $this->lang->line('pay_offline');//线下交易，记录支付完成，记录日志
                $dta = array(
                    'order_no' => $order_no,
                    'created' => date('Y-m-d H:i:s', time()),
                    'buyer' => $userID,
                    'vendor' => $data['uid'],
                    'payment' => $payment,
                    'status' => $status, 
                );
                log_pay($log,$dta);
            }
            return array('oid' => $oid, 'order_no' => $order_no);
        }
    }
    /**
     * @生成虚拟订单
     */
    protected function add_v_order($data, $userID)
    {
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        $where = array(
            'v.buyer' => $userID,
            'v.vendor' => $data['uid'],
            'v.id_cowry' => $data['cid'],
            );
        //查询是否存在虚拟订单
        $result = $this->order->v_exists($where);
        if ($result) {
            $data = array('created' => date('Y-m-d H:i:s', time()));
            $this->order->modify_vorder($data, array('id_orders' => $result['oid']));
            return $result;
        }
        //生成虚拟订单
        $this->db->trans_begin();
        $order_data = array(
            'buyer' => $userID,
            'vendor' => $data['uid'],
            'id_cowry' => $data['cid'],
            'created' => date('Y-m-d H:i:s', time()));
        $oid = $this->order->insert_vorder($order_data);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->errors = $this->lang->line('add_order_fail');
            return false;
        } else {
            $this->db->trans_commit();
            return array('oid' => $oid);
        }

    }

    /**
     * @修改订单ModifyOrder
     */
    protected function modify_order()
    {
        return $this->{$this->filter_params['method']}();
    }


    /**
     * @修改真实订单
     * @修改交易完成。支付成功的订单
     */
    protected function modify_real_order()
    {
        $type = $this->filter_params['data']['type'];
        $oid = intval($this->filter_params['data']['oid']);
        $this->load->model('order_model', 'order');
        //修改交易完成。支付成功的订单
        if ($type == 'pay') {
            $order_no = $this->filter_params['data']['out_trade_no'];
            $data = array('status' => 2, 'created' => date('Y-m-d H:i:s', time()));
            $result = $this->order->modify_order($data, $oid);
            if ($result) {
                //线上交易支付成功后
                $log = $this->lang->line('pay_order_syn');//同步线上交易支付完成，记录日志
                $data['order_no'] = $order_no;
                $data['payment']='online';
                log_pay($log,$data);
                return true;
            }
            $this->errors = '订单操作失败！';
            return false;
        }

    }

    /**
     * @修改虚拟订单
     */
    protected function modify_virtual_order()
    {
        $this->errors = '无法修改虚拟订单。该功能已取消！';
        return false;
    }
    
    private function status_analysis($status){
        switch($status){
            case '3':
                $this->errors = "该订单正在投诉处理中！";
                return false;
                break;
            case '4':
                $this->errors = "该订单已完成！";
                return false;
                break;
            case '5':
                $this->errors = "买家已收货或订单完成，无法取消订单！";
                return false;
                break;
            case '6':
                $this->errors = "卖家已取消该订单，无法确认收货！";
                return false;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * @取消订单，修改订单状态
     * 1:虚拟订单, 2:已付款(线下支付订单待确认),3:投诉中,4:投诉完成（仲裁完成）5:已收货(订单完成,线下支付订单已确认),6:取消
     */
    protected function cancel_order($orderID)
    {
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        $order = $this->order->query_order_information($orderID);
        if (!$order) {
            $this->errors = "该订单不存在！";
            return false;
        }
        /**
         * @订单是否已被取消
         */
        if ($order['status'] != 6) {
            /**
             * @判断订单状态：
             * 3:投诉中,4:投诉完成（仲裁完成）5:已收货(订单完成)
             * @订单状态为3，4，5。无法取消订单
             */
            $re = $this->status_analysis($order['status']);
            if(!$re){
                return false;
            }
            $data = array('status' => 6, 'created' => date('Y-m-d H:i:s', time()));
            $result = $this->order->modify_order($data, $orderID);
            if ($result) {
                if($order['payment']=='online'){
                    //线上交易online,生成取消的财务记录
                    $res1=$this->cancel_online_order($order);
                    if($res1){
                        //取消操作记录日志
                        $log = $this->lang->line('pay_cancel_online');
                        $data['order_no'] = $order['order_no'];
                        $data['payment']='online';
                        log_pay($log,$data);
                        return true;
                    }
                }elseif($order['payment']=='offline'){
                    //线下交易offline，不生成财务，取消操作记录日志
                    $log = $this->lang->line('pay_cancel_offline');
                    $data['order_no'] = $order['order_no'];
                    $data['payment']='offline';
                    log_pay($log,$data);
                    return true;
                }
             }
            $this->errors = '取消操作失败！';
            return false;
        }
        $this->errors = '该订单已经被取消，请勿重复操作！';
        return false;
    }
    
    /**
     * @取消订单，生成财务
     * @线上交易online
     */
    private function cancel_online_order($order){
        /**
         * @查询财务中是否已经存在该订单！
         */
        $this->load->model('finance_model', 'finance');
        $where = array('order_no' => $order['order_no']);
        $res = $this->finance->query_finance($where);
        if ($res) {
            //查询财务中已经存在该订单,不做任何处理直接返回
            return true;
        }
        /**
         * @一，卖家取消订单。。应把已支付的金额返还给买家
         * @二，订单未支付。直接取消
         * @生成财务记录。后台确认审核。打款给买家
         */
        $data = array(
            'id_2buser' => $order['buyer'], //买家
            'order_no' => $order['order_no'],
            'sum' => $order['total_price'],
            'status' => 0, //未结算
            'comment' => 2, //取消订单
            'order_created' => $order['created'],
            'created' => date('Y-m-d H:i:s', time()),
            );
        $fid = $this->finance->insert_finance($data);
        if ($fid) {
            /**
             * @退还宝贝库存
             */
            $quantity = $this->cowry->get_cowry_num($order['owner'], $order['cid']); //现在宝贝的库存
            $sum = $quantity['num'] + $order['num']; //现在宝贝的库存加上订单中购买的宝贝数量 总和
            $this->cowry->edit_num($order['owner'], $order['cid'], $sum); //修改库存。
            $content = "你购买的宝贝订单号为：{$order['order_no']}的，已被取消，已付款在2日之内退还到你原付款账 号，请查收！";
            parent::send_msg($order['phone'], $content);
            return true;
        }else{
            return false;
        }
    }          
    

    /**
     * @确认订单。确认收货
     * 1:虚拟订单, 2:已付款(线下支付订单待确认),3:投诉中,4:投诉完成（仲裁完成）5:已收货(订单完成,线下支付订单已确认),6:取消
     */
    protected function confirm_order($oid)
    {
        $this->load->model('order_model', 'order');
        $order = $this->order->query_order_information($oid);
        if (!$order) {
            $this->errors = "该订单不存在！";
            return false;
        }
        /**
         * @订单是否已经确认收货
         */
        if ($order['status'] != 5) {
            /**
             * @判断订单状态：
             * 3:投诉中,4:投诉完成（仲裁完成）6:取消
             * @订单状态为3，4，6。无法确认收货
             */
            $re = $this->status_analysis($order['status']);
            if(!$re){
                return false;
            }
            $data = array('status' => 5, 'created' => date('Y-m-d H:i:s', time()));
            $result = $this->order->modify_order($data, $oid);
            if ($result) {
                if($order['payment']=='online'){
                    //线上交易online,记录日志
                    $res1=$this->confirm_online_order($order);
                    if($res1){
                        $log = $this->lang->line('pay_finished_online');
                        $data['order_no'] = $order['order_no'];
                        $data['payment']='online';
                        log_pay($log,$data);
                        return true;
                    }
                }elseif($order['payment']=='offline'){
                    //线下交易offline，记录日志
                    $res2=$this->confirm_offline_order($order);
                    if($res2){
                        $log = $this->lang->line('pay_finished_offline');
                        $data['order_no'] = $order['order_no'];
                        $data['payment']='offline';
                        log_pay($log,$data);
                        return true;
                    }
                }
            }
            $this->errors = '确认收货操作失败！';
            return false;
        }
        $this->errors = '该订单已经完成，请勿重复操作！';
        return false;

    }
    
    /**
     * @确认订单。确认收货，生成财务
     * @线上交易online
     */
    private function confirm_online_order($order){
        $this->load->model('finance_model', 'finance');
        //查询财务中是否已经存在该订单！
        $where = array('order_no' => $order['order_no']);
        $res = $this->finance->query_finance($where);
        if ($res) {
            //查询财务中已经存在该订单,不做任何处理直接返回
            return true;
        }
        /**
         *@买家确认收货。生成财务记录。打款给卖家
         */
        $data = array(
            'id_2buser' => $order['vendor'], //卖家
            'order_no' => $order['order_no'],
            'sum' => $order['total_price'],
            'status' => 0, //未结算
            'comment' => 1, //1：正常交易
            'order_created' => date('Y-m-d H:i:s', time()), // 确认收货时间
            'created' => date('Y-m-d H:i:s', time()), //财务账单创建时间
            );
        $fid = $this->finance->insert_finance($data);
        if ($fid) {
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * @确认订单。确认收货，不生成财务，库存减少
     * @线下交易online
     */
    private function confirm_offline_order($order){
        $this->load->model('cowry_model', 'cowry');
        //线下交易-修改原宝贝数量
        $quantity = $this->cowry->get_cowry_num($order['owner'], $order['cid']);
        //判断宝贝库存是否充足
        if($quantity['num']<$order['num']){
            $this->errors = '宝贝库存不足，无法确认收货！';
            return false;
        }
        $sum = $quantity['num'] - $order['num']; //现在宝贝的库存减去订单中购买的宝贝数量 
        $res = $this->cowry->edit_num($order['owner'], $order['cid'], $sum);
        if($res){
            return true;
        }else{
            return false;
        }
    }
    
    
    
    
    /**
     * @删除订单，退还宝贝库存
     * @author zhoushuai
     */
    protected function _delete_order($order)
    {
        $this->load->model('order_model', 'order');
        $this->load->model('cowry_model', 'cowry');
        $order = $this->order->query_order_information($orderID);
        if (!$order) {
            return true;
        }
        /**
         * @退还宝贝库存
         */
        $quantity = $this->cowry->get_cowry_num($order['owner'], $order['cid']); //现在宝贝的库存
        $sum = $quantity['num'] + $order['num']; //现在宝贝的库存加上订单中购买的宝贝数量 总和
        $res = $this->cowry->edit_num($order['owner'], $order['cid'], $sum); //修改库存。
        $where = array('id_orders' => $order['oid']);
        if ($res) {
            $this->order->del_order($where);
            $this->order->del_order_item($where);
        }
        return true;
    }


    /**
     * @正在处理的订单列表（统计）
     * 2:已付款(线下支付订单待确认),3:投诉中
     */
    protected function ordering($userID)
    {
        $this->load->model('order_model', 'order');
        $where = "o.status in (2,3) AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        $result = $this->order->ordering($where);
        return $result;
    }

    /**
     * @我的订单列表（v2.0）
     * 1:虚拟订单, 2:已付款(线下支付订单待确认),3:投诉中,4:投诉完成（仲裁完成）5:已收货(订单完成,线下支付订单已确认),6:取消
     */
    protected function orderlist($page, $userID, $status)
    {
        $this->load->model('order_model', 'order');
        //$order = 'o.status ASC,o.created DESC';
        $order = 'o.created DESC,o.status ASC';
        $where = "o.status in (2,3,4,5,6) AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        $offset = 10;
        if ($status == 2) {
            $order = 'o.created DESC';
            $where = "o.status =2 AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        } elseif ($status == 3) {
            $order = 'o.created DESC';
            $where = "o.status =3 AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        } elseif ($status == 4) {
            $order = 'o.created DESC';
            $where = "o.status =4 AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        } elseif ($status == 5) {
            $order = 'o.created DESC';
            $where = "o.status =5 AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        } elseif ($status == 6) {
            $order = 'o.created DESC';
            $where = "o.status =6 AND o.object_type = 'retail' AND (vendor = $userID OR buyer = $userID)";
        }
        $result = $this->order->orderlist($where, $order, $page, $offset);
        for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['buyer'] == $userID) {
                $result[$i]['state'] = 0;
            }
            if ($result[$i]['vendor'] == $userID) {
                $result[$i]['state'] = 1;
            }
        }
        return $result;
    }
    /**
     * @咨询聊天时候的订单列表。。
     * 1:虚拟订单,2:已付款
     */
    protected function get_chat_orderlist($page, $userID, $status, $objectid)
    {
        $this->load->model('order_model', 'order');
        if ($status == 1) {
            $offset = 3;
            $page = 1;
            $order = 'v.created DESC';
            //宝贝被删除。虚拟订单不显示
            $where = "(ci.id_cowry IS NOT NULL AND ci.status='up' AND o.status = 1 AND vendor = $userID AND buyer = $objectid) OR (ci.id_cowry IS NOT NULL AND ci.status='up' AND o.status = 1 AND vendor = $objectid AND buyer = $userID)";
            $result = $this->order->get_vorder($where, $order, $page, $offset);
        } else {
            $order = 'o.created DESC';
            $where = "o.status =2 AND o.object_type = 'retail' AND ((vendor = $userID AND buyer = $objectid) OR (vendor = $objectid AND buyer = $userID))";
            $offset = 10;
            $result = $this->order->orderlist($where, $order, $page, $offset);
        }
        for ($i = 0; $i < count($result); $i++) {
            if ($result[$i]['buyer'] == $userID) {
                $result[$i]['state'] = 0;
            }
            if ($result[$i]['vendor'] == $userID) {
                $result[$i]['state'] = 1;
            }
        }
        return $result;
    }


    /**
     * @订单快照(v2.0)
     *  订单编号 
     */
    protected function get_order_detail($orderID)
    {
        $this->load->model('order_model', 'order');
        $order = $this->order->query_order_information($orderID);
        $return['oid'] = $order['oid'];
        $return['payment']=$order['payment'];//线上交易、线下交易offline:线下支付,cod：货到付款,online:线上支付
        $return['order_no'] = $order['order_no']; //订单编号
        $return['trade_no'] = $order['trade_no']; //交易流水号
        if ($this->token['uid'] == $order['buyer']) {
            $return['uid'] = $order['vendor']; //卖家ID
            $return['state'] = 0; //买入
        } elseif ($this->token['uid'] == $order['vendor']) {
            $return['uid'] = $order['buyer']; //买家id
            $return['state'] = 1; //卖出
        }
        $return['extra'] = $order['extra'];
        $return['status'] = $order['status'];
        $return['total_price'] = $order['total_price']; //总价
        $return['total_num'] = $order['total_num'];
        //获取订单明细
        $items = $this->order->query_order_item($orderID);
        $cowry = array();
        foreach ($items as $k => $li) {
            $cowry[$k]['description'] = $li['description']; //宝贝简介
            $cowry[$k]['uid'] = $li['owner']; //宝贝拥有者id
            $cowry[$k]['cid'] = $li['id_cowry'];
            $cowry[$k]['total'] = $li['amount']; //总价
            $cowry[$k]['price'] = $li['price']; //单价
            $cowry[$k]['buy_num'] = $li['quantity']; //购买数量
            $cowry[$k]['img'] = $li['image']; //宝贝图片
            //宝贝收货信息：电话+收货地址
            $cowry[$k]['address'] = $li['address'];
            $cowry[$k]['phone'] = $li['phone'];
            $cowry[$k]['name'] = $li['name'];
        }
        $return['cowry'] = $cowry;
        return $return;
    }

    /**
     * 订单信息
     */
    protected function get_order_infor($orderID)
    {
        $this->load->model('order_model', 'order');
        $order = $this->order->query_order_information($orderID);
        $return['oid'] = $order['oid'];
        $return['payment']=$order['payment'];//线上交易、线下交易offline:线下支付,cod：货到付款,online:线上支付
        if ($this->token['uid'] == $order['buyer']) {
            $return['uid'] = $order['vendor'];
            $return['state'] = 0; //买入
        } elseif ($this->token['uid'] == $order['vendor']) {
            $return['uid'] = $order['buyer'];
            $return['state'] = 1; //卖出
        }
        $return['extra'] = $order['extra'];
        $return['status'] = $order['status'];
        $return['total_price'] = $order['total_price']; //总价
        $return['total_num'] = $order['total_num'];
        $return['addtime'] = $order['created'];
        //获取订单明细
        $return['cid'] = $order['cid'];
        $return['num'] = $order['num'];
        $return['price'] = $order['price'];
        $return['description'] = $order['description'];
        $return['img'] = $order['image'];
        return $return;

    }
    /**
     * 获取地址列表
     */
    protected function get_addresslist($userID)
    {
        $this->load->model('order_model', 'order');
        return $this->order->get_address(array('id_2buser' => $userID, 'default !=' => '-1'));
    }

    /**
     * 添加地址 编辑
     */
    protected function add_address($data, $userID)
    {
        $this->load->model('order_model', 'order');
        $connect = array(
            'id_2buser' => $userID,
            'name' => $data['name'],
            'cell_phone' => $data['phone'],
            'address' => $data['address'],
            'default' => $data['is_default'],
            'latitude' => $data['lat'],
            'longitude' => $data['lon']);
        $privacy = array(
            'id_2buser' => $userID,
            'object_type' => 'address',
            'level' => $data['show']);
        $this->db->trans_begin();
        if (!empty($data['is_default'])) {
            //将之前的默认地址改为普通地址
            $add = $this->order->get_address(array('id_2buser' => $userID, 'default' => 1));
            if (!empty($add)) {
                /**
                 * @修改默认地址
                 */
                //将之前的默认地址改为普通地址 default设为0
                $this->order->modify_connect(array('default' => 0), $add[0]['aid']);
                //是否可见。保存用户隐私表
                if (!empty($data['show'])) {
                    //查询地址是否已经存在隐私表中
                    $pri = $this->order->get_privacy(array('id_2buser' => $userID, 'object_type' => 'address'));
                    if (!empty($pri)) {
                        $this->order->modify_privacy($privacy, $pri[0]['pid']);
                    } else {
                        $this->order->add_privacy($privacy);
                    }
                }
            } else {
                /**
                 * @没有默认地址。新增并首次添加用户地址是否可见信息
                 */
                //是否可见。保存用户隐私表
                if (!empty($data['show'])) {
                    //查询地址是否已经存在隐私表中
                    $pri = $this->order->get_privacy(array('id_2buser' => $userID, 'object_type' => 'address'));
                    if (!empty($pri)) {
                        $this->order->modify_privacy($privacy, $pri[0]['pid']);
                    } else {
                        $this->order->add_privacy($privacy);
                    }
                }
            }
        }
        if (!empty($data['aid'])) {
            if ($this->order->get_adress_inf($data['aid'])) {
                $this->order->modify_connect($connect, $data['aid']);
                $return = $data['aid'];
            } else {
                $this->errors = '该地址不存在或被删除了！';
                return false;
            }
        } else {
            $return = $this->order->add_connect($connect);

        }
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return array('aid' => $return);
        }
    }

    /**
     * 删除地址
     */
    protected function delete_address($aID, $userID)
    {
        $this->load->model('order_model', 'order');
        $add = $this->order->get_address(array('id_2buser' => $userID, 'id_contact' => $aID));
        $this->db->trans_begin();
        if (!empty($add)) {
            if ($add[0]['is_default'] == 1) {
                $this->order->delete_privacy(array('id_2buser' => $userID, 'object_type' =>'address'));
            }
            $connect = array('default' => -1);
            //不从数据库删除 。只是修改default 改为-1
            $address = $this->order->modify_connect($connect, $aID);
            //return $this->order->delete_connect($aID);
            if ($this->db->trans_status() === false || $address <= 0) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return true;
            }
        }
        $this->errors = $this->lang->line('request_param_errors');
        return false;
    }

}
/* End of file order_Controller.php */
