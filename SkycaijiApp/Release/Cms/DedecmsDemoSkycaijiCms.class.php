<?php
/**
 * 示例：dedecms文章
 * 您可参考代码自行开发dedecms的更多功能
 * 您可以使用 thinkphp3.2的函数
 */
namespace Release\Cms;
class DedecmsDemoSkycaijiCms extends BaseCms{
	public $siteurl;//cms站点网址
	//初始化扩展
	public function init_extend(){
		$siteurls=$this->db()->table('__SYSCONFIG__')->where("`varname` in ('cfg_basehost','cfg_cmspath')")->select(array('index'=>'varname,value'));
		$this->siteurl=rtrim($siteurls['cfg_basehost'].$siteurls['cfg_cmspath'],'\/\\');
	}
	//参数
	public $_params=array(
		'typeid' => array (
			'name' => '栏目id',
			'tag' => 'select',
			'option' => 'function:param_option_typeid',
			'require'=>1
		),
		'title' => array (
			'name' => '文章标题',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1
		),
		'content' => array (
			'name' => '文章内容',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1
		),
		'author' => array (
			'name' => '作者',
			'tag' => 'select',
			'option' => array(
				'admin'=>'admin',
				'网络'=>'网络',
				'佚名'=>'佚名',
			)
		),
		'desc' => array (
			'name' => '自动摘要',
			'tag' => 'radio',
		),
		'cover' => array (
			'name' => '自动封面',
			'tag' => 'radio',
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
		$newArticle=array(
			'typeid'=>$params['typeid'],
			'typeid2'=>0,
			'arcrank'=>0,
			'channel'=>1,
			'senddate'=>time(),
			'sortrank'=>time(),
			'mid'=>1,
		);
		$articleId=$this->db()->table('__ARCTINY__')->add($newArticle);//返回文章id
		if($articleId>0){
			$newArticle['id']=$articleId;
			$newArticle['title']=$params['title'];
			$newArticle['writer']=$params['author'];
			$newArticle['pubdate']=time();
			$newArticle['flag']='';
			$newArticle['ismake']=-1;
			$newArticle['litpic']='';
			$newArticle['description']='';
			if($params['desc']){
				//自动摘要
				$newArticle['description']=mb_substr(trim(preg_replace('/\s+/',' ',strip_tags($params['content']))),0,200,'utf-8');
			}
			
			if($params['cover']){
				//生成封面
				if(preg_match('/<img[^<>]*src=[\'\"]([^\"\'<>]+)[\'\"]/i',$params['content'],$mcover)){
					$newArticle['litpic']=$mcover[1];
				}
			}
			
			$this->db()->table('__ARCHIVES__')->add($newArticle);//添加文章
			
			$newAddon=array(
				'aid'=>$articleId,
				'typeid'=>$newArticle['typeid'],
				'body'=>$params['content'],
			);
			$this->db()->table('__ADDONARTICLE__')->add($newAddon);//添加文章附加
			
			$target=$this->siteurl.'/plus/view.php?aid='.$articleId;
			return array('id'=>$articleId,'target'=>$target);
		}else{
			$error='文章入库失败';
			return array('id'=>0,'error'=>'文章入库失败');
		}
	}
	/*
	 * 文章主栏目选项
	 * 必须返回键值对形式的数组
	 */
	public function param_option_typeid(){
		$typeDb=$this->db()->table('__ARCTYPE__')->where('topid=1')->order('sortrank asc')->select();
		$typeList=array();
		foreach ($typeDb as $v){
			$typeList[$v['id']]=auto_convert2utf8($v['typename']);
		}
		return $typeList;
	}
}
?>