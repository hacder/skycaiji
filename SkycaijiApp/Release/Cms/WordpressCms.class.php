<?php
/*发布到本地cms*/
namespace Release\Cms;
class WordpressCms extends BaseCms{
	public $_params=array(
		'post_author' => array (
			'name' => '作者账号',
			'tag' => 'select',
			'option' => 'function:param_option_post_author',
			'require'=>1,
			'value' => ''
		),
		'post_category' => array (
			'name' => '分类',
			'tag' => 'select',
			'option' => 'function:param_option_post_category',
			'require'=>1,
			'value' => '' 
		),
		'post_title' => array (
			'name' => '文章标题',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
			'value' => ''
		),
		'post_content' => array (
			'name' => '文章内容',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
			'value' => '' 
		),
	);//参数

	/*导入数据*/
	public function runImport($params){
		$newPost=array(
			'post_date'=>date('Y-m-d H:i:s',time()),
			'post_date_gmt'=>gmdate('Y-m-d H:i:s',time()),
			'post_content' => $params['post_content'],
			'post_title' => $params['post_title'],
			'post_status' => 'publish',
			'post_type' => 'post',
			'comment_status' => 'open',
			'ping_status' => 'open',
			'post_modified'=>date('Y-m-d H:i:s',time()),
			'post_modified_gmt'=>gmdate('Y-m-d H:i:s',time()),
		);
		$newPost['post_author']=$this->db->table('__USERS__')->where(array('user_login'=>$params['post_author']))->getField('ID');
		
		$return=array();//返回的数据
		$return['id']=$this->db->table('__POSTS__')->add($newPost);
		if($return['id']>0){
			//返回成功内容网址
			//添加分类
			$this->db->table('__TERM_RELATIONSHIPS__')->add(array('object_id'=>$return['id'],'term_taxonomy_id'=>$params['post_category']));
			$return['target']=$this->get_siteurl().'/?p='.$return['id'];
		}
		return $return;
	}
	
	/*获取网站网址*/
	public function get_siteurl(){
		return $this->db->table('__OPTIONS__')->where(array('option_name'=>'siteurl'))->getField('option_value');
	}
	/*获取作者*/	
	public function param_option_post_author(){
		$users=$this->db->table('__USERS__')->limit(100)->select();
		$options='';
		foreach ($users as $user){
			$options.="<option value=\"{$user['user_login']}\">{$user['user_login']}</option>";
		}
		return $options;
	}
	/*获取分类*/	
	public function param_option_post_category(){
		$list=$this->db->table('__TERMS__')->select();
		$options='';
		foreach ($list as $item){
			$options.="<option value=\"{$item['term_id']}\">{$item['name']}</option>";
		}
		return $options;
	}
}
?>