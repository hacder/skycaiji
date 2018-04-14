<?php
/**
 * 示例：wordpress文章
 * 您可参考代码自行开发wordpress的更多功能
 * 您可以使用 thinkphp3.2的函数
 */
namespace Release\Cms;
class WordpressDemoSkycaijiCms extends BaseCms{
	public $siteurl;//cms站点网址
	//初始化扩展
	public function init_extend(){
		$this->siteurl=$this->db()->table('__OPTIONS__')->where(array('option_name'=>'siteurl'))->getField('option_value');
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
			'name' => '分类',
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
		$newPost=array(
			'post_date'=>date('Y-m-d H:i:s',time()),
			'post_date_gmt'=>gmdate('Y-m-d H:i:s',time()),
			'post_content' => $params['content'],
			'post_title' => $params['title'],
			'post_status' => 'publish',
			'post_type' => 'post',
			'comment_status' => 'open',
			'ping_status' => 'open',
			'post_modified'=>date('Y-m-d H:i:s',time()),
			'post_modified_gmt'=>gmdate('Y-m-d H:i:s',time()),
		);
		$newPost['post_author']=$this->db()->table('__USERS__')->where(array('user_login'=>$params['author']))->getField('ID');//设置作者id
		
		$postId=$this->db()->table('__POSTS__')->add($newPost);//添加文章并返回id
		if($postId>0){
			//返回成功内容网址
			$this->db()->table('__TERM_RELATIONSHIPS__')->add(array('object_id'=>$postId,'term_taxonomy_id'=>$params['category']));//添加到关系表
			$target=$this->siteurl.'/?p='.$postId;
			return array('id'=>$postId,'target'=>$target);
		}else{
			return array('id'=>0,'error'=>'文章入库失败');
		}
	}
	/*
	 * 参数选项：作者
	 * 必须返回键值对形式的数组
	 */
	public function param_option_author(){
		$usersDb=$this->db()->table('__USERS__')->limit(100)->select();
		$userList=array();
		foreach ($usersDb as $user){
			$userList[$user['user_login']]=$user['user_login'];
		}
		return $userList;
	}
	
	/*
	 * 参数选项：分类
	 * 必须返回键值对形式的数组
	 */
	public function param_option_category(){
		$catsDb=$this->db()->table('__TERMS__')->select();
		$catList=array();
		foreach ($catsDb as $cat){
			$catList[$cat['term_id']]=$cat['name'];
		}
		return $catList;
	}
}
?>