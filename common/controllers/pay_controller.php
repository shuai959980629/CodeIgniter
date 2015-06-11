<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * @邻售支付-接口
 * @author zhoushuai
 * @date 2014-08-04 9:30
 */
class Pay_Controller extends Common_Controller
{
    
    /**
     * @支付完成
     */
    protected function  _complete_pay($data){
        return parent::_complete_order($data);  
    } 
    
    /**
     * @支付日志记录
     * @author zhoushuai
     * @param log 日志格式
     * @param data 记录需要的数据
     */
    protected function log_pay($log,$data){
        
        global $starttime;
        $uri = $_SERVER['REQUEST_URI'];
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $log = sprintf($log,microtime(true)-$starttime,$uri, $useragent,var_export($data, true));
        log_message('error', $log);
        
    }
     
    
    

}

/* End of file pay_controller.php */
