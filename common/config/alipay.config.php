<?php if (!defined('BASEPATH'))exit('No direct script access allowed');
/**
 * @支付宝配置文件
 * @version 2.0
 * @author zhoushuai
 * @copyright 2014-08-11
 */
//↓↓↓↓↓↓↓↓↓↓支付宝基本配置信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

//付款账号
$alipay_config['email'] = 'nh@it008.com';

//付款账户名:个人支付宝账号是真实姓名公司支付宝账号是公司名称
$alipay_config['account_name'] = '成都赏金猎人网络科技有限公司';

//合作身份者id，以2088开头的16位纯数字
$alipay_config['partner'] = '2088311305072626';

//安全检验码，以数字和字母组成的32位字符
//如果签名方式设置为“MD5”时，请设置该参数
$alipay_config['key'] = 'yawfkkwxamg6s3id18z1477gntdi4qmq';

//商户的私钥（后缀是.pen）文件相对路径
$alipay_config['rsa_private_key_path'] = ROOT_PATH.'attachment/key/rsa_private_key.pem';

//支付宝公钥（后缀是.pen）文件相对路径
$alipay_config['ali_public_key_path'] = ROOT_PATH.'attachment/key/alipay_public_key.pem';


/////////////////////////////↑↑↑↑↑↑↑↑↑↑以上是支付宝基本配置信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//签名方式 不需修改
$alipay_config['sign_type'] = strtoupper('RSA');

//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset'] = strtolower('utf-8');

//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
//getcwd()网站根目录
$alipay_config['cacert'] =  ROOT_PATH. 'attachment/key/cacert.pem';

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport'] = 'http';
?>