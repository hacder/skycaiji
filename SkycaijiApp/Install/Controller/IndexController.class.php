<?php
/*
 |--------------------------------------------------------------------------
 | SkyCaiji (蓝天采集器)
 |--------------------------------------------------------------------------
 | Copyright (c) 2018 http://www.skycaiji.com All rights reserved.
 |--------------------------------------------------------------------------
 | 使用协议  http://www.skycaiji.com/licenses
 |--------------------------------------------------------------------------
 */

namespace Install\Controller; use Think\Controller; use Think\Storage; use Think\Db; use Admin\Model\UserModel; use Admin\Model\CollectedModel; use Install\Event\UpgradeDbEvent; if(!defined('IN_SKYCAIJI')) { exit('NOT IN SKYCAIJI'); } class IndexController extends Controller { public function success($message='',$jumpUrl='',$ajax=false){ parent::success($message,$jumpUrl,$ajax); exit(); } public function __construct(){ parent::__construct(); session_start(); if(file_exists(C('ROOTPATH').'/'.APP_PATH.'/Install/Data/install.lock')){ $this->success('程序已安装',U('Admin/Index/index')); } } public function indexAction(){ $this->display(); } public function step1Action(){ $serverInfoList=array( 'os'=>array('操作系统','不限制',php_uname('s').' '.php_uname('r'),true), 'php'=>array('PHP版本','5.3',phpversion()) ); if(version_compare($serverInfoList['php'][1],$serverInfoList['php'][2])<=0){ $serverInfoList['php'][3]=true; }else{ $serverInfoList['php'][3]=false; } $phpModuleList=array( array('curl',extension_loaded('curl')), array('mb_string',extension_loaded('mbstring')), array('gd',extension_loaded('gd')), ); $pathFiles=array('./data','./data/config.php','./data/images','./data/program/upgrade','./data/program/backup',APP_PATH.'Runtime'); $pathFileList=array(); foreach ($pathFiles as $pathFile){ $filename=C('ROOTPATH').'/'.$pathFile; if(!file_exists($filename)){ if(preg_match('/\w+\.\w+/', $pathFile)){ Storage::put($filename,null,'F'); }else{ mkdir($filename,0777,true); } } $pathFileList[]=array( $pathFile, is_writeable($filename), is_readable($filename) ); } $this->assign('serverInfoList',$serverInfoList); $this->assign('phpModuleList',$phpModuleList); $this->assign('pathFileList',$pathFileList); $this->display(); } public function step2Action(){ if(IS_POST){ $db_config=array( 'db_host'=>I('db_host'), 'db_port'=>I('db_port'), 'db_name'=>I('db_name'), 'db_user'=>I('db_user'), 'db_prefix'=>trim(I('db_prefix'),'_') ); foreach ($db_config as $k=>$v){ if(empty($v)){ $this->error(L('empty_db',array('type'=>L($k)))); } } $db_config['db_type']='mysql'; $db_config['db_pwd']=I('db_pwd'); $db_config['db_prefix'].='_'; $adminUser=array( 'user_name'=>I('user_name'), 'user_pwd'=>I('user_pwd'), 'user_repwd'=>I('user_repwd'), 'user_email'=>I('user_email') ); if(empty($adminUser['user_name'])){ $this->error('请输入创始人用户名'); } $check=UserModel::right_username($adminUser['user_name']); if(!$check['success']){ $this->error('创始人'.$check['msg']); } if(empty($adminUser['user_pwd'])){ $this->error('请输入创始人密码'); } $check=UserModel::right_pwd($adminUser['user_pwd']); if(!$check['success']){ $this->error('创始人'.$check['msg']); } if($adminUser['user_pwd']!=$adminUser['user_repwd']){ $this->error('创始人密码不一致'); } if(empty($adminUser['user_email'])){ $this->error('请输入创始人邮箱'); } $check=UserModel::right_email($adminUser['user_email']); if(!$check['success']){ $this->error('创始人邮箱格式错误'); } try { $dbConn=Db::getInstance($db_config); $dbTables=$dbConn->getTables(); }catch(\Exception $ex){ if(!empty($dbTables)){ $this->error($ex->getMessage()); } } session('install_config',array('db'=>$db_config,'admin'=>$adminUser)); $this->success(array('has_data'=>$has_data)); }else{ $this->display(); } } public function step3Action(){ $this->display(); $installConfig=session('install_config'); if(empty($installConfig)){ $this->error('请先安装数据',U('Install/index/step2')); } $dbConfig=$installConfig['db']; $installDataPath=C('ROOTPATH').'/'.APP_PATH.'/Install/Data'; $sqlFile=$installDataPath.'/install.sql'; if(!file_exists($sqlFile)){ $this->error('sql安装文件不存在'); } $installSql=file_get_contents($sqlFile); $installSql=preg_replace('/\s+`skycaiji_/i', ' `'.$dbConfig['db_prefix'], $installSql); if(preg_match_all('/[\s\S]+?\;[\r\n]/',$installSql,$sqlList)){ $sqlList=$sqlList[0]; }else{ $this->error('没有sql安装语句'); } try { $this->_echo_msg('正在安装...'); $dbName=$dbConfig['db_name']; unset($dbConfig['db_name']); $dbConn=M('','',$dbConfig); $dbConn->execute('create database if not exists '.$dbName.' default character set utf8'); $dbConn->execute('use '.$dbName); foreach($sqlList as $sql){ $dbConn->execute($sql); $msg=''; if(preg_match('/^\s*create\s+table\s+`'.$dbConfig['db_prefix'].'(?P<table>[^\s]+?)`/i',$sql,$tableName)){ $msg=$dbConfig['db_prefix'].$tableName['table'].' 表创建成功！'; } if($msg){ $this->_echo_msg($msg); } } $createConfig=file_get_contents($installDataPath.'/config.php'); foreach ($installConfig['db'] as $k=>$v){ $createConfig=str_replace('{$'.strtoupper($k).'}', $v, $createConfig); } $createConfig=preg_replace('/\{\$db_([^\s]+?)\}/i', '', $createConfig); if(empty($createConfig)){ $this->error('配置文件不能为空'); } if(file_put_contents(C('ROOTPATH').'/data/config.php', $createConfig)===false){ $this->error('配置文件创建失败'); } $this->_echo_msg('配置文件创建成功！'); $founderGid=$dbConn->table($dbConfig['db_prefix'].'usergroup')->where(array('founder'=>1))->getField('id'); $dbConn->table($dbConfig['db_prefix'].'user')->add(array( 'username'=>$installConfig['admin']['user_name'], 'password'=>pwd_encrypt($installConfig['admin']['user_pwd']), 'groupid'=>$founderGid, 'email'=>$installConfig['admin']['user_email'], 'regtime'=>time() )); $this->_echo_msg('创始人账号'.$installConfig['admin']['user_name'].'添加成功！'); $upgradeDb=new UpgradeDbEvent(); $upgradeResult=$upgradeDb->run(); if(!$upgradeResult['success']){ $this->_echo_msg('数据库升级失败'); } $this->_echo_msg('安装完成！'); Storage::put(C('ROOTPATH').'/'.APP_PATH.'/Install/Data/install.lock','1','F'); $this->_echo_msg('<a href="'.U('Admin/Index/index').'" class="btn btn-lg btn-success">开始使用</a>'); }catch (\Exception $ex){ $this->error($ex->getMessage(),'',10); } } public function _echo_msg($msg){ echo '<script type="text/javascript">echo_msg("'.addslashes($msg).'");</script>'; ob_flush(); flush(); } }