<?php
/**
 * 示例：discuz发帖
 * 您可参考代码自行开发discuz的更多功能
 * 您可以使用 thinkphp3.2的函数
 */
namespace Release\Cms;
class DiscuzDemoSkycaijiCms extends BaseCms{
	public $siteurl;//discuz网站网址
	public function init_extend(){
		$_g=array();
		foreach ($GLOBALS as $k=>$v){
			$_g[$k]=$v;
		}
		//调用discuz代码
		require $this->cmsPath.'/source/class/class_core.php';
		require libfile('function/editor');
		//获取站点url
		$siteurl=$this->db()->table('__COMMON_SETTING__')->where("`skey`='siteurl'")->find();
		$this->siteurl=rtrim($siteurl['svalue'],'\\\/').'/';
		
		$GLOBALS=$_g;//防止全局变量被污染
	}
	//参数
	public $_params=array(
		'forumid' => array (
			'name' => '版块ID',
			'tag' => 'select',
			'option' => 'function:param_option_forumid',
			'require'=>1
		),
		'author' => array (
			'name' => '用户名或ID',
			'tag' => 'text',
			'require'=>1
		),
		'title' => array (
			'name' => '帖子标题',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1
		),
		'content' => array (
			'name' => '帖子内容',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1
		),
	);
	
	
	/*
	 * 导入数据
	 * 必须以数组形式返回：
	 * id（必填）表示入库返回的自增id或状态
	 * target（可选）记录入库的数据位置（发布的网址等）
	 * desc（可选）记录入库的数据位置附加信息
	 * error（可选）记录入库失败的错误信息
	 * 入库的信息可在“已采集数据”中查看
	 * return array('id'=>0,'target'=>'','desc'=>'','error'=>'');
	 */
	public function runImport($params){
		//判断用户存在
		$userData=$this->db()->table('__COMMON_MEMBER__')->where(array(
				'username'=>$params['author'],
				'uid'=>$params['author'],
				'_logic' => 'or'
			))->find();
		if(empty($userData)){
			return array('id'=>0,'error'=>$params['author'].'用户不存在');//返回错误信息
		}
		//添加新主题
		$newThread=array(
			'fid'=>$params['forumid'],
			'author'=>$userData['username'],
			'authorid'=>$userData['uid'],
			'subject'=>$params['title'],
			'dateline'=>time(),
			'lastpost'=>time(),
			'lastposter'=>$userData['username'],
		);
		$target='';//目标网址
		$error='';//错误信息
		$threadId=$this->db()->table('__FORUM_THREAD__')->add($newThread);//返回的id
		if($threadId>0){
			$postId=$this->db()->table('__FORUM_POST_TABLEID__')->add(array('pid'=>0));//获取pid
			if($postId>0){
				//添加帖子
				$newPost=array(
					'pid'=>$postId,
					'fid'=>$params['forumid'],
					'tid'=>$threadId,
					'first'=>1,
					'author'=>$userData['username'],
					'authorid'=>$userData['uid'],
					'subject'=>$params['title'],
					'dateline'=>time(),
					'message'=>html2bbcode($params['content']),//html转成discuz格式
					'position'=>1,
				);
				$this->db()->table('__FORUM_POST__')->add($newPost);
				
				$target=$this->siteurl.'forum.php?mod=viewthread&tid='.$threadId;
			}else{
				$error='添加帖子失败';
			}
		}else{
			$error='添加主题失败';
		}
		return array('id'=>$threadId,'target'=>$target,'error'=>$error);
	}
	/*
	 * 自定义方法：版块选项
	 * 必须返回键值对形式的数组
	 */
	public function param_option_forumid(){
		$forumDb=$this->db()->table('__FORUM_FORUM__')->where("`status`=1 and `type`<>'group'")->select();
		//读取论坛版块
		$forumList=array();
		foreach ($forumDb as $forum){
			$forumList[$forum['fid']]=auto_convert2utf8($forum['name']);//自动转码
		}
		return $forumList;
	}
}
?>