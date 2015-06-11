<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * 
 * @copyright(c) 2013-11-19
 * @author zhou shuai
 * @version Id:funcs.php
 */


if (!function_exists('debug')) {

    function debug($obj)
    {
        header('Content-Type:text/html;charset=utf-8');
        echo '<pre />';
        print_r($obj);
    }

}

function is_url($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 拼装图片的显示路径
 * @param string $type cowry/favicon/msg
 * @param string $filename 文件名称根据MD5计算路径
 * @param bool  $isimg 是否返回完整图片地址
 * @param string $default_img_name 默认图片的名字
 */
function get_img_url($filename, $type, $default_img_name = 'bibi')
{
    switch ($type) {
        case 'favicon':
            $ext = 'png';
            break;
        default:
            $ext = 'jpg';
            break;
    }
    if (!empty($type) && !empty($filename)) {
        if (is_url($filename)) {
            return $filename;
        }
        if (file_exists(DOCUMENT_ROOT . $filename)) {
            return $filename;
        } else {
            return '/attachment/defaultimg/' . $default_img_name . '.' . $ext; //返回个默认图片的路径
        }
    } else {
        return '/attachment/defaultimg/' . $default_img_name . '.' . $ext; //返回个默认图片的路径
    }
}


//过滤返回匹配表情
function replace_emoticons($content)
{
    $content = str_replace('/::)',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/0.gif" border="0" />',
        $content);
    $content = str_replace('/::~',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/1.gif" border="0" />',
        $content);
    $content = str_replace('/::B',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/2.gif" border="0" />',
        $content);
    $content = str_replace('/::|',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/3.gif" border="0" />',
        $content);
    $content = str_replace('/:8-)',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/4.gif" border="0" />',
        $content);
    $content = str_replace('/::<',
        '<img src="/biz/media/kindeditor/plugins/emoticons/images/5.gif" border="0" />',
        $content);
    return $content;
}


/**
 * 处理页面上手机号码的显示
 * @param string $str
 */
function view_phone($str)
{

    if (!$str || strlen($str) != 11) {
        return $str;
    } else {
        return substr($str, 0, 3) . '*****' . substr($str, 7);
    }
}


/**
 * 截取编辑器中文等字符串
 * @param unknown $str
 */
function truncate_utf8($str, $length = 20, $type = 1)
{
    $return = '';
    $str = html_entity_decode($str, ENT_COMPAT, 'utf-8');
    if ($type == 2)
        $str = strip_tags($str, '<img/>');
    else
        $str = strip_tags($str);

    if (function_exists('iconv')) {
        $return = mb_substr($str, 0, $length, 'utf-8');
    }
    return $return;

}


function csubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{

    if (function_exists("mb_substr")) {

        if (mb_strlen($str, $charset) <= $length)
            return $str;

        $slice = mb_substr($str, $start, $length, $charset);

    } else {

        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";

        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";

        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";

        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";

        preg_match_all($re[$charset], $str, $match);

        if (count($match[0]) <= $length)
            return $str;

        $slice = join("", array_slice($match[0], $start, $length));

    }

    if ($suffix)
        return $slice . "…";

    return $slice;

}

/**
 * @utf8符串翻转 
 * @param string $str 需要翻转的字符串
 * @param bool $reverse_numbers 是否翻转字符串中的数字
 * @return string
 */
function utf8_strrev($str, $reverse_numbers) {
   preg_match_all('/./us', $str, $ar);
   if ($reverse_numbers)
     return join('',array_reverse($ar[0]));
   else {
       $temp = array();
       foreach ($ar[0] as $value) {
          if (is_numeric($value) && !empty($temp[0]) && is_numeric($temp[0])) {
             foreach ($temp as $key => $value2) {
                if (is_numeric($value2))
                  $pos = ($key + 1);
                else
                   break;
             }
             $temp2 = array_splice($temp, $pos);
             $temp = array_merge($temp, array($value), $temp2);
          } else
             array_unshift($temp, $value);
       }
       return implode('', $temp);
   }
 }

/**
 * @符串翻转 
 * @param string $str 需要翻转的字符串
 * @param string $charset 中文字符编码utf-8 或 gbk
 * @return string
 */
function str_strrev($str, $charset='utf-8'){

     if(!is_string($str)||!mb_check_encoding($str, $charset)){
         return $str;
     }
     $array=array();
     $len=mb_strlen($str, $charset);
     for($i=0;$i<$len;$i++){
         $array[]=mb_substr($str,$i,1,$charset);
     }
     $array = array_reverse($array);
     $string=implode($array);
     return $string;
 }


/**
 * 生成卡号
 * @param number $min
 * @param number $max
 * @param number $count
 * @return string
 */
function get_member_card($min = 5, $max = 8)
{
    $length = rand($min, $max);
    $card = '';
    for ($i = 0; $i < $length; $i++) {
        $card .= rand(0, 9);
    }
    return $card;
}


function sprint_member_card($str, $length = 8)
{
    return str_pad($str, $length, '0', STR_PAD_LEFT);
}


function request_curl($url, $jsonData = '', $force = 0)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    $ssl = substr($url, 0, 8) == "https://" ? true : false;
    if ($ssl && $force) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); // 检查证书中是否设置域名
    } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //不输出内容
    if (!empty($jsonData)) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


function print_excel($filename, $data = '')
{
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/vnd.ms-execl; charset=UTF-8");
    header("Content-Type: application/force-download");
    header("Content-Type: application/download");
    header("Content-Disposition: attachment; filename=" . iconv('utf-8', 'gbk', $filename) .".xls");
    header("Content-Transfer-Encoding: binary");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo iconv('utf-8', 'gbk', $data);
}


/**
 * 返回客户端信息通用函数
 * @param number $status 返回状态
 * @param string $data	包含的数据
 * @param string $msg	状态说明
 */
function return_client($status = 0, $data = null, $msg = null)
{
	//header('Content-type: application/json');
    global $starttime;
    $resp = array(
        'status' => $status,
        'data' => empty($data) ? null : $data,
        'msg' => empty($msg) ? null : $msg,
        'time' => microtime(true) - $starttime);
    $json = json_encode($resp);
    die($json);
}


/**
 * @支付日志记录
 * @author zhoushuai
 * @param log 日志格式
 * @param data 记录需要的数据
 */
function log_pay($log, $data)
{

    global $starttime;
    $uri = $_SERVER['REQUEST_URI'];
    $useragent = $_SERVER['HTTP_USER_AGENT'];
    $log = sprintf($log, microtime(true) - $starttime, $uri, $useragent, var_export($data, true));
    $path =  ROOT_PATH.'temp/logs/';
    if(is_really_writable($path)&&is_dir($path)){
        $message  = '';
        $filepath = $path.'pay-'.date('Y-m-d').'.php';
        if (!file_exists($filepath))
    	{
    	   $message .="<?php if (!defined('BASEPATH'))exit('No direct script access allowed');\n\n";
    	   $message .="#**********************************".date('Y-m-d',time())."*******************************************邻售交易详细记录**************华丽的分割线*****************************\n\n";
    	}
        $fp = @fopen($filepath, FOPEN_WRITE_CREATE);
        $message .= '#'.date('Y-m-d H:i:s',time()).'   *************单笔交易记录*********华丽的分割线***********************'."\n{$log}\n";
        flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($filepath, FILE_WRITE_MODE);
    }
}

/**
 * Get Smile Array
 *
 * Fetches the config/smile.php file
 *
 * @access	private
 * @return	mixed
 */
if (!function_exists('_get_smile_array')) {
    function _get_smile_array()
    {
        if (defined('ENVIRONMENT') and file_exists(APPPATH . 'config/' . ENVIRONMENT .'/smile.php')) {
            include (APPPATH . 'config/' . ENVIRONMENT . '/smile.php');
        } elseif (file_exists(APPPATH . 'config/smile.php')) {
            include (APPPATH . 'config/smile.php');
        }

        if (isset($smile) and is_array($smile)) {
            return $smile;
        }

        return false;
    }
}

/**
 * Get Alipay Config Array
 *
 * Fetches the common/config/alipay.config.php file
 *
 * @access	private
 * @return	mixed
 */

if (!function_exists('_get_alipay_config')) {
    function _get_alipay_config()
    {
        if (defined('ENVIRONMENT') and file_exists(ROOT_PATH . 'common/config/' .
            ENVIRONMENT . '/alipay.config.php')) {
            include (ROOT_PATH . 'common/config/' . ENVIRONMENT . '/alipay.config.php');
        } elseif (file_exists(ROOT_PATH . 'common/config/alipay.config.php')) {
            include (ROOT_PATH . 'common/config/alipay.config.php');
        }

        if (isset($alipay_config) and is_array($alipay_config)) {
            return $alipay_config;
        }
        return false;
    }
}


/**
 * Get tcp config Array
 *
 * Fetches the config/tcp.php file
 *
 * @access	private
 * @return	mixed
 */


if (!function_exists('_get_tcp_config')) {
    function _get_tcp_config()
    {
        if (defined('ENVIRONMENT') and file_exists(APPPATH . 'config/' . ENVIRONMENT .
            '/tcp.php')) {
            include (APPPATH . 'config/' . ENVIRONMENT . '/tcp.php');
        } elseif (file_exists(APPPATH . 'config/tcp.php')) {
            include (APPPATH . 'config/tcp.php');
        }

        if (isset($tcp) and is_array($tcp)) {
            $config = array();
            foreach ($tcp as $key => $vals) {
                if ($key == ENVIRONMENT) {
                    foreach ($vals as $item => $val) {
                        $config[$item] = $val;
                    }
                }
            }
            return $config;
        }
        return false;
    }
}

/**
 * @author zhoushuai
 * @获取二维数组中的元素
 * @param input array
 * @param columnKey 
 * @param  indexKey
 * @return	mixed
 */

if (!function_exists('array_column')) {
    function array_column($input, $columnKey, $indexKey = null)
    {
        $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
        $indexKeyIsNull = (is_null($indexKey)) ? true : false;
        $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
        $result = array();
        foreach ((array )$input as $key => $row) {
            if ($columnKeyIsNumber) {
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
            } else {
                $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
            }
            if (!$indexKeyIsNull) {
                if ($indexKeyIsNumber) {
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key)) ? current($key) : null;
                    $key = is_null($key) ? 0 : $key;
                } else {
                    $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    }
}


/**
 * 获取上n周的开始和结束，每周从周一开始，周日结束日期
 * @param int $ts 时间戳
 * @param int $n 你懂的(前多少周)
 * @param string $format 默认为'%Y-%m-%d',比如"2012-12-18"
 * @return array 第一个元素为开始日期，第二个元素为结束日期
 */
function lastNWeek($currentTime, $n, $format = '%Y-%m-%d')
{
    $ts = intval($currentTime);
    $n = abs(intval($n));

    // 周一到周日分别为1-7
    $dayOfWeek = date('w', $currentTime);
    if (0 == $dayOfWeek) {
        $dayOfWeek = 7;
    }

    $lastNMonday = 7 * $n + $dayOfWeek - 1;
    $lastNSunday = 7 * ($n - 1) + $dayOfWeek;
    return array('mon' => strftime($format, strtotime("-{$lastNMonday} day", $currentTime)),
            'sun' => strftime($format, strtotime("-{$lastNSunday} day", $currentTime)));
}

/**
 * @生成订单号码
 * @订单号规则：年+随机数7位+月+日（例如：14+0000001+07+22）,订单号：1400000010722，共13位。
 */

function create_order_id()
{
    $y = date('y', time());
    $m = date('m', time());
    $d = date('d', time());
    /*
    $rand='';
    for ($i = 0; $i < 7; $i++) {
    $rand .= mt_rand(0, 9);
    }
    */
    return $y . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT) . $m . $d;
}

/**
 * @生成流水号 
 */

function create_trade_no()
{
    return date('y', time()) . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT) .
        date('m', time()) . date('d', time());
}

/**
 * @生成批次号
 * @必填，格式：当天日期[8位]+序列号[3至16位]，如：201008010000001
 */
function create_batch_no()
{
    return date('Ymd', time()) . str_pad(mt_rand(1, 9999999), 7, '0', STR_PAD_LEFT);
}


/**
 * guid
 */
function create_guid()
{
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45); // "-"
    $uuid = chr(123) // "{"
        . substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid,
        12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12) .
        chr(125); // "}"
    return $uuid;
}


function isMobile()
{
    $useragent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] :
        '';
    $useragent_commentsblock = preg_match('|\(.*?\)|', $useragent, $matches) > 0 ? $matches[0] :
        '';
    $mobile_os_list = array(
        'Google Wireless Transcoder',
        'Windows CE',
        'WindowsCE',
        'Symbian',
        'Android',
        'armv6l',
        'armv5',
        'Mobile',
        'CentOS',
        'mowser',
        'AvantGo',
        'Opera Mobi',
        'J2ME/MIDP',
        'Smartphone',
        'Go.Web',
        'Palm',
        'iPAQ');
    $mobile_token_list = array(
        'Profile/MIDP',
        'Configuration/CLDC-',
        '160×160',
        '176×220',
        '240×240',
        '240×320',
        '320×240',
        'UP.Browser',
        'UP.Link',
        'SymbianOS',
        'PalmOS',
        'PocketPC',
        'SonyEricsson',
        'Nokia',
        'BlackBerry',
        'Vodafone',
        'BenQ',
        'Novarra-Vision',
        'Iris',
        'NetFront',
        'HTC_',
        'Xda_',
        'SAMSUNG-SGH',
        'Wapaka',
        'DoCoMo',
        'iPhone',
        'iPod');

    $found_mobile = CheckSubstrs($mobile_os_list, $useragent_commentsblock) ||
        CheckSubstrs($mobile_token_list, $useragent);
    if ($found_mobile) {
        return true;
    } else {
        return false;
    }
}
/**
 * GET pay mode
 * @支付类型
 */

if (!function_exists('_get_pay_mode')) {
    function _get_pay_mode()
    {
        //PC端支付
        $payMode = 'alipay'; //支付方式
        if (isMobile()) {
            //手机wap支付
            $payMode = 'wapalipay'; //支付方式
        }
        return $payMode;
    }
}


function CheckSubstrs($substrs, $text)
{
    foreach ($substrs as $substr)
        if (false !== strpos($text, $substr)) {
            return true;
        }
    return false;
}


function createimg($length = 4, $width = 80, $height = 34)
{
    $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < $length; $i++) {
        $str .= mb_substr($chars, floor(mt_rand(0, mb_strlen($chars) - 1)), 1);
    }
    $randval = $str;
    $_SESSION['hipigo_verify'] = md5(strtolower($randval));
    //setcookie('hipigo_verify', strtolower($randval), time() + (60), '/', '');
    $width = ($length * 10 + 10) > $width ? $length * 10 + 10 : $width;
    if (function_exists('imagecreatetruecolor')) {
        $im = @imagecreatetruecolor($width, $height);
    } else {
        $im = @imagecreate($width, $height);
    }
    $r = array(
        225,
        255,
        255);
    $g = array(
        225,
        255,
        255,
        0);
    $b = array(
        225,
        236,
        166,
        125);
    $key = mt_rand(0, 3);

    $backColor = imagecolorallocate($im, 244, 244, 244);
    $pointColor = imagecolorallocate($im, 244, 244, 244);
    @imagefilledrectangle($im, 0, 0, $width - 1, $height - 1, $backColor);
    @imagerectangle($im, 0, 0, $width - 1, $height - 1);
    $stringColor = imagecolorallocate($im, mt_rand(0, 200), mt_rand(0, 120), mt_rand
        (0, 120));
    for ($i = 0; $i < 10; $i++) {
        $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0,
            255));
        imagearc($im, mt_rand(-10, $width), mt_rand(-10, $height), mt_rand(80, 300),
            mt_rand(80, 200), 55, 44, $fontcolor);
    }

    for ($i = 0; $i < 25; $i++) {
        $fontcolor = imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0,
            255));
        imagesetpixel($im, mt_rand(0, $width), mt_rand(0, $height), $pointColor);
    }
    for ($i = 0; $i < $length; $i++) {
        imagestring($im, 5, $i * 10 + 20, mt_rand(5, 15), $randval{$i}, $stringColor);
    }
    header("Content-type: image/png");
    ImagePNG($im);
    ImageDestroy($im);
}


//
function unique_arr($array2D, $stkeep = false, $ndformat = true)
{
    // 判断是否保留一级数组键 (一级数组键可以为非数字)
    if ($stkeep)
        $stArr = array_keys($array2D);

    // 判断是否保留二级数组键 (所有二级数组键必须相同)
    if ($ndformat)
        $ndArr = array_keys(end($array2D));

    //降维,也可以用implode,将一维数组转换为用逗号连接的字符串
    foreach ($array2D as $v) {
        $v = join(",", $v);
        $temp[] = $v;
    }
    //去掉重复的字符串,也就是重复的一维数组
    $temp = array_unique($temp);

    //再将拆开的数组重新组装
    foreach ($temp as $k => $v) {
        if ($stkeep)
            $k = $stArr[$k];
        if ($ndformat) {
            $tempArr = explode(",", $v);
            foreach ($tempArr as $ndkey => $ndval) {
                $output[$k][$ndArr[$ndkey]] = $ndval;
            }
        } else {
            $output[$k] = explode(",", $v);
        }
    }

    return $output;
}


/**
 * 建立文件夹目录
 * @param string $dirs 文件夹目录路径
 * @param string $mode 权限
 */
function make_dir($dirs = '', $mode = 0777)
{
    $dirs = str_replace('\\', '/', trim($dirs));
    if (!empty($dirs) && !is_dir($dirs)) {
        make_dir(dirname($dirs));
        mkdir($dirs, $mode) or exit('权限不足,建立' . $dirs . '目录失败');
    }
}

/**
 * @提取字符串中的数字
 */
function findNum($str = '')
{
    $str = trim($str);
    if (empty($str)) {
        return '';
    }
    $result = '';
    for ($i = 0; $i < strlen($str); $i++) {
        if (is_numeric($str[$i])) {
            $result .= $str[$i];
        }
    }
    return $result;
}


/**
 *  版本号比较
 *  @param $version1 新版本号
 *  @param $version2 旧版本号
 *  return boolean
 */
function compareVersion($version1, $version2)
{
    if (empty($version1)) {
        return false;
    }
    $list1 = explode('.', $version1);
    $list2 = explode('.', $version2);
    $len = count($list1) > count($list2) ? count($list1) : count($list2);
    for ($i = 0; $i < $len; $i++) {
        $num1 = $list1[$i] || 0;
        $num2 = $list2[$i] || 0;
        if ($num1 > $num2) {
            return true; //需要更新
        } else
            if ($num1 < $num2) {
                return false; //不需要更新
            }
    }
    return false;
}

/**
 * @author zhoushuai
 * @param length 长度
 * 生成随机的数字
 */
function getRandom($length = 6)
{
    return rand(pow(10, $length - 1), pow(10, $length) - 1);
}

/**
 * @author zhoushuai
 * @精确时间间隔函数
 * $time 发布时间 如 1356973323
 * $str 输出格式 如 Y-m-d H:i:s
 * 7天 604800
 * '31536000'=>'年',
 * '2592000'=>'个月',
 * '604800'=>'星期',
 * '86400'=>'天',
 * '3600'=>'小时',
 * '60'=>'分钟',
 * '1'=>'秒'
 * 半年的秒数为15552000，1年为31104000，此处用半年的时间
 */
function format_date($time, $str = '')
{
    isset($str) ? $str : $str = 'm-d';
    $way = time() - $time;
    $r = '';
    if ($way < 60) {
        $r = '刚刚';
    } elseif ($way >= 60 && $way < 3600) {
        $r = floor($way / 60) . '分钟前';
    } elseif ($way >= 3600 && $way < 86400) {
        $r = floor($way / 3600) . '小时前';
    } elseif ($way >= 86400 && $way < 604800) {
        $r = floor($way / 86400) . '天前';
    } elseif ($way >= 604800 && $way < 31536000) {
        $r = date("$str", $time);
        ;
    } else {
        $r = floor($way / 31536000) . '年前';
    }
    return $r;
}


/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstring($para)
{
    $arg = "";
    while (list($key, $val) = each($para)) {
        $arg .= $key . "=" . $val . "&";
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, count($arg) - 2);

    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }

    return $arg;
}
/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para)
{
    $arg = "";
    while (list($key, $val) = each($para)) {
        $arg .= $key . "=" . urlencode($val) . "&";
    }
    //去掉最后一个&字符
    $arg = substr($arg, 0, count($arg) - 2);

    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $arg = stripslashes($arg);
    }

    return $arg;
}

/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para)
{
    $para_filter = array();
    while (list($key, $val) = each($para)) {
        if ($key == "sign" || $key == "sign_type" || $val == "")
            continue;
        else
            $para_filter[$key] = $para[$key];
    }
    return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para)
{
    ksort($para);
    reset($para);
    return $para;
}
/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word = '')
{
    $fp = fopen("log.txt", "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}
/**
 * 远程获取数据，POST模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * @param $para 请求的数据
 * @param $input_charset 编码格式。默认值：空值
 * return 远程输出的数据
 */
function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '')
{

    if (trim($input_charset) != '') {
        $url = $url . "_input_charset=" . $input_charset;
    }
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
    curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
    curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
    curl_setopt($curl, CURLOPT_POST, true); // post传输数据
    curl_setopt($curl, CURLOPT_POSTFIELDS, $para); // post传输数据
    $responseText = curl_exec($curl);
    //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
    curl_close($curl);

    return $responseText;
}

/**
 * 远程获取数据，GET模式
 * 注意：
 * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
 * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
 * @param $url 指定URL完整路径地址
 * @param $cacert_url 指定当前工作目录绝对路径
 * return 远程输出的数据
 */
function getHttpResponseGET($url, $cacert_url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 显示输出结果
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
    curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //证书地址
    $responseText = curl_exec($curl);
    //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
    curl_close($curl);

    return $responseText;
}

/**
 * 实现多种字符编码方式
 * @param $input 需要编码的字符串
 * @param $_output_charset 输出的编码格式
 * @param $_input_charset 输入的编码格式
 * return 编码后的字符串
 */
function charsetEncode($input, $_output_charset, $_input_charset)
{
    $output = "";
    if (!isset($_output_charset))
        $_output_charset = $_input_charset;
    if ($_input_charset == $_output_charset || $input == null) {
        $output = $input;
    } elseif (function_exists("mb_convert_encoding")) {
        $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
    } elseif (function_exists("iconv")) {
        $output = iconv($_input_charset, $_output_charset, $input);
    } else
        die("sorry, you have no libs support for charset change.");
    return $output;
}
/**
 * 实现多种字符解码方式
 * @param $input 需要解码的字符串
 * @param $_output_charset 输出的解码格式
 * @param $_input_charset 输入的解码格式
 * return 解码后的字符串
 */
function charsetDecode($input, $_input_charset, $_output_charset)
{
    $output = "";
    if (!isset($_input_charset))
        $_input_charset = $_input_charset;
    if ($_input_charset == $_output_charset || $input == null) {
        $output = $input;
    } elseif (function_exists("mb_convert_encoding")) {
        $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
    } elseif (function_exists("iconv")) {
        $output = iconv($_input_charset, $_output_charset, $input);
    } else
        die("sorry, you have no libs support for charset changes.");
    return $output;
}

/**
 * RSA签名
 * @param $data 待签名数据
 * @param $private_key_path 商户私钥文件路径
 * return 签名结果
 */
function rsaSign($data, $private_key_path)
{
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    openssl_sign($data, $sign, $res);
    openssl_free_key($res);
    //base64编码
    $sign = base64_encode($sign);
    return $sign;
}

/**
 * RSA验签
 * @param $data 待签名数据
 * @param $ali_public_key_path 支付宝的公钥文件路径
 * @param $sign 要校对的的签名结果
 * return 验证结果
 */
function rsaVerify($data, $ali_public_key_path, $sign)
{
    $pubKey = file_get_contents($ali_public_key_path);
    $res = openssl_get_publickey($pubKey);
    $result = (bool)openssl_verify($data, base64_decode($sign), $res);
    openssl_free_key($res);
    return $result;
}

/**
 * RSA解密
 * @param $content 需要解密的内容，密文
 * @param $private_key_path 商户私钥文件路径
 * return 解密后内容，明文
 */
function rsaDecrypt($content, $private_key_path)
{
    $priKey = file_get_contents($private_key_path);
    $res = openssl_get_privatekey($priKey);
    //用base64将内容还原成二进制
    $content = base64_decode($content);
    //把需要解密的内容，按128位拆开解密
    $result = '';
    for ($i = 0; $i < strlen($content) / 128; $i++) {
        $data = substr($content, $i * 128, 128);
        openssl_private_decrypt($data, $decrypt, $res);
        $result .= $decrypt;
    }
    openssl_free_key($res);
    return $result;
}

/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key)
{
    $prestr = $prestr . $key;
    return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key)
{
    $prestr = $prestr . $key;
    $mysgin = md5($prestr);

    if ($mysgin == $sign) {
        return true;
    } else {
        return false;
    }
}


//对参数进行排序
function arg_sort($para)
{
    ksort($para);
    reset($para);
    return $para;
}
//把数组转正key=value&类型的字符串
function arr_to_url_string($para)
{
    $str = '';
    foreach ($para as $k => $v) {
        if ($v) {
            $str .= $k . '=' . urlencode($v) . '&';
        }
    }
    $str = substr($str, 0, -1);
    return $str;
}

//对数据进行签名
function md5_sign($str, $key)
{
    return strtoupper(md5($str . $key));
}


function check_sign($data, $sign)
{
    //获取支付配置
    $pay_config = _get_pay_config();
    $para = arg_sort($data);
    $key = $pay_config['app_token'] . $pay_config['biz_token'];
    $mysign = md5_sign(arr_to_url_string($para), $key);
    if (strtoupper($sign) == $mysign) {
        return true;
    } else {
        return false;
    }
}

function simplest_xml_to_array($xmlstring)
{
    return json_decode(json_encode((array )simplexml_load_string($xmlstring)), true);
}

/**
 * 重写URL
 * 使用方法 <!--{rewrite url='community/home'}-->
 * @author zhoushuai
 * @version 2.1
 */
function rewrite($params, $sysParams)
{
    $url = explode('/', $params['url']);
    list($controller, $method) = $url;
}


function getImageInfo($src)
{
    return getimagesize($src);
}
/**
 * 创建图片，返回资源类型
 * @param string $src 图片路径
 * @return resource $im 返回资源类型
 * **/
function create($src)
{
    $info = getImageInfo($src);
    switch ($info[2]) {
        case 1:
            $im = imagecreatefromgif($src);
            break;
        case 2:
            $im = imagecreatefromjpeg($src);
            break;
        case 3:
            $im = imagecreatefrompng($src);
            break;
    }
    return $im;
}
/**
 * 缩略图主函数
 * @param string $src 图片路径
 * @param int $w 缩略图宽度
 * @param int $h 缩略图高度
 * $is_cut  是否裁减  0：压缩  1：裁减
 * @return mixed 返回缩略图路径
 * **/

function resize($src, $w, $h, $is_cut = 0)
{
    $temp = pathinfo($src);
    $name = $temp["filename"]; //文件名
    $dir = $temp["dirname"]; //文件所在的文件夹
    $extension = $temp["extension"]; //文件扩展名
    $savepath = "{$dir}/{$name}_{$w}x{$h}.{$extension}"; //缩略图保存路径,新的文件名为*.thumb.jpg

    //获取图片的基本信息
    $info = getImageInfo($src);
    $width = $info[0]; //获取图片宽度
    $height = $info[1]; //获取图片高度

    if ($is_cut) {
        $ci = &get_instance();
        $ci->load->model('picture_model', 'picture');
        //裁剪并生成缩略图
        $ci->picture->crop(array(
            'file_path' => $src,
            'width' => array($w),
            'height' => array($h),
            'x' => 0,
            'y' => 0), array($w, $h));
    } else {
        $per1 = round($width / $height, 2); //计算原图长宽比
        $per2 = round($w / $h, 2); //计算缩略图长宽比

        //计算缩放比例
        if ($per1 > $per2 || $per1 == $per2) {
            //原图长宽比大于或者等于缩略图长宽比，则按照宽度优先
            $per = $w / $width;
        }
        if ($per1 < $per2) {
            //原图长宽比小于缩略图长宽比，则按照高度优先
            $per = $h / $height;
        }
        $temp_w = intval($width * $per); //计算原图缩放后的宽度
        $temp_h = intval($height * $per); //计算原图缩放后的高度
        $temp_img = imagecreatetruecolor($temp_w, $temp_h); //创建画布
        $im = create($src);
        imagecopyresampled($temp_img, $im, 0, 0, 0, 0, $temp_w, $temp_h, $width, $height);
        if ($per1 > $per2) {
            imagejpeg($temp_img, $savepath, 100);
            imagedestroy($im);
            return addBg($savepath, $w, $h, "w");
            //宽度优先，在缩放之后高度不足的情况下补上背景
        }
        if ($per1 == $per2) {
            imagejpeg($temp_img, $savepath, 100);
            imagedestroy($im);
            return $savepath;
            //等比缩放
        }
        if ($per1 < $per2) {
            imagejpeg($temp_img, $savepath, 100);
            imagedestroy($im);
            return addBg($savepath, $w, $h, "h");
            //高度优先，在缩放之后宽度不足的情况下补上背景
        }
    }
}
/**
 * 添加背景
 * @param string $src 图片路径
 * @param int $w 背景图像宽度
 * @param int $h 背景图像高度
 * @param String $first 决定图像最终位置的，w 宽度优先 h 高度优先 wh:等比
 * @return 返回加上背景的图片
 * **/
function addBg($src, $w, $h, $first = "w")
{
    $bg = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($bg, 255, 255, 255);
    imagefill($bg, 0, 0, $white); //填充背景

    //获取目标图片信息
    $info = getImageInfo($src);
    $width = $info[0]; //目标图片宽度
    $height = $info[1]; //目标图片高度
    $img = create($src);
    if ($first == "wh") {
        //等比缩放
        return $src;
    } else {
        if ($first == "w") {
            $x = 0;
            $y = ($h - $height) / 2; //垂直居中
        }
        if ($first == "h") {
            $x = ($w - $width) / 2; //水平居中
            $y = 0;
        }
        imagecopymerge($bg, $img, $x, $y, 0, 0, $width, $height, 100);
        imagejpeg($bg, $src, 100);
        imagedestroy($bg);
        imagedestroy($img);
        return $src;
    }
}

/**
 * @判断客户端是否为IOS及Android
 * @author zhoushuai
 */
//获取客户端设备的类型
function get_device_type()
{
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strripos($agent, 'micromessenger')) {
        $type = 'weixin';
    } elseif (strpos($agent, 'iphone') || strpos($agent, 'ipad') || strripos($agent,
    'ipod')) {
        $type = 'ios';
    } elseif (strpos($agent, 'android')) {
        $type = 'android';
    } elseif (strripos($agent, 'windows nt')) {
        $type = 'PC';
    } else {
        $type = 'other';
    }
    return $type;
}
/**
 * @返回平台类型
 * @author zhoushuai
 */
function platform()
{
    $plat = 'other';
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (strpos($agent, 'iphone') || strpos($agent, 'ipad') || strripos($agent,
        'ipod')) {
        $plat = 'ios';
    } elseif (strpos($agent, 'android')) {
        $plat = 'android';
    } elseif (strripos($agent, 'windows nt')) {
        $plat = 'PC';
    }
    return $plat;
}

/** 
 * @author zhoushuai
 * 把返回的数据集转换成Tree 
 * @param array $list 要转换的数据集 
 * @param array $listId 要转换的数据集中数据的id
 * @param string $parentid parent标记字段 父类
 * @param string $child 子类字段
 * @param int root 顶级父类di
 * @return array 
 */  
function _list_to_tree($list, $listId='id_tag', $parentid = 'id_parent', $child = 'child', $root = 0) {  
    // 创建Tree  
    $tree = array();  
    if(is_array($list)) {  
        // 创建基于主键的数组引用  
        $refer = array();  
        foreach ($list as $key => $data) {  
            $refer[$data[$listId]] =& $list[$key];  
        }  
        foreach ($list as $key => $data) {  
            // 判断是否存在parent  
            $parentId =  $data[$parentid];  
            if ($root == $parentId) {  
                $tree[] =& $list[$key];  
            }else{  
                if (isset($refer[$parentId])) {  
                    $parent =& $refer[$parentId];  
                    $parent[$child][] =& $list[$key];  
                }  
            }  
        }  
    }  
    return $tree;  
}  


/* End of file funcs.php */
