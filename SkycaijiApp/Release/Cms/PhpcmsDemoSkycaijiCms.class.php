<?php
namespace Release\Cms;
class PhpcmsDemoSkycaijiCms extends BaseCms{
	public $siteurl;//cms站点网址
	//初始化扩展
	public function init_extend(){
		$site=$this->db()->table('__SITE__')->order('siteid asc')->find();
		$this->siteurl=$site['domain'];
	}
	//参数
	public $_params=array(
		'author' => array (
			'name' => '作者账号',
			'tag' => 'select',
			'option' => 'function:param_option_author',
			'require'=>1,
		),
		'category' => array (
			'name' => '文章栏目',
			'tag' => 'select',
			'option' => 'function:param_option_category',
			'require'=>1,
		),
		'title' => array (
			'name' => '文章标题',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
		),
		'content' => array (
			'name' => '文章内容',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
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
		//新文章
		$newNews=array(
			'catid'=>$params['category'],
			'typeid'=>0,
			'title'=>$params['title'],
			'style'=>'',
			'thumb'=>'',
			'keywords'=>'',
			'description'=>'',
			'posids'=>0,
			'url'=>'',
			'listorder'=>'',
			'status'=>99,
			'sysadd'=>1,
			'islink'=>0,
			'username'=>$params['author'],
			'inputtime'=>time(),
			'updatetime'=>time()
		);
		$newsId=$this->db()->table('__NEWS__')->add($newNews);//添加到文章主表
		if($newsId>0){
			//入库成功
			$url=$this->siteurl.'index.php?m=content&c=index&a=show&catid='.$params['category'].'&id='.$newsId;
			$this->db()->table('__NEWS__')->where(array('id'=>$newsId))->save(array('url'=>$url));//修改url
			
			//文章从表
			$newData=array(
				'id'=>$newsId,
				'content'=>$params['content'],
				'readpoint'=>0,
				'groupids_view'=>'',
				'paginationtype'=>0,
				'maxcharperpage'=>10000,
				'template'=>'',
				'paytype'=>0,
				'relation'=>'',
				'voteid'=>0,
				'allow_comment'=>1,
				'copyfrom'=>''
			);
			$this->db()->table('__NEWS_DATA__')->add($newData);//添加到文章从表
			return array('id'=>$newsId,'target'=>$url);
		}else{
			return array('id'=>0,'error'=>'文章入库失败');
		}
	}

	/*
	 * 参数选项：文章栏目
	 * 必须返回键值对形式的数组
	 */
	public function param_option_category(){
		$catsDb=$this->db()->table('__CATEGORY__')->where("`module`='content' and `modelid`=1")->select();
		$catList=array();
		foreach ($catsDb as $cat){
			$catList[$cat['catid']]=auto_convert2utf8($cat['catname']);
		}
		return $catList;
	}
	/*
	 * 参数选项：作者
	 * 必须返回键值对形式的数组
	 */
	public function param_option_author(){
		$usersDb=$this->db()->table('__ADMIN__')->select();
		$userList=array();
		foreach ($usersDb as $user){
			$user['username']=auto_convert2utf8($user['username']);
			$userList[$user['username']]=$user['username'];
		}
		return $userList;
	}
}
?>