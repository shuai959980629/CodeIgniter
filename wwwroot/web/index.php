<?php
/**
 * @huibaoyangWEB项目入口文件
 * @author zhoushuai
 * @category 2015-03-09
 * @version 1.0
 */
define('MY_START_TIME', $_SERVER['REQUEST_TIME']); //microtime(true).请求开始时间
/*
 *---------------------------------------------------------------
 * APPLICATION FOLDER NAME  定义当前项目录
 *---------------------------------------------------------------
 *
 * If you want this front controller to use a different "application"
 * folder than the default one you can set its name here. The folder
 * can also be renamed or relocated anywhere on your server. If
 * you do, use a full server path. For more info please see the user guide:
 * http://codeigniter.com/user_guide/general/managing_apps.html
 *
 * NO TRAILING SLASH!
 */
$application_folder = 'web';

/*
 *---------------------------------------------------------------
 * VIEW FOLDER NAME 当前项目视图目录
 *---------------------------------------------------------------
 *
 * If you want to move the view folder out of the application
 * folder set the path to the folder here. The folder can be renamed
 * and relocated anywhere on your server. If blank, it will default
 * to the standard location inside your application folder. If you
 * do move this, use the full server path to this folder.
 *
 * NO TRAILING SLASH!
 */
$view_folder = '';
//加载共用的入口文件
require_once '../../common/index.php';

?>