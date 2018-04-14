<?php
/**
 * 示例：metinfo文章
 * 您可参考代码自行开发metinfo的更多功能
 * 您可以使用 thinkphp3.2的函数
 */
namespace Release\Cms;
class MetinfoDemoSkycaijiCms extends BaseCms{
	//参数
	public $_params=array(
		'columnid' => array (
			'name' => '文章栏目',
			'tag' => 'select',
			'option' => 'function:param_option_columnid',
			'require'=>1,
		),
		'title' => array (
			'name' => '标题',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
		),
		'content' => array (
			'name' => '内容',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
			'require'=>1,
		),
		'keywords' => array (
			'name' => 'keywords',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
		),
		'description' => array (
			'name' => 'description',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
		),
		'imgurl' => array (
			'name' => '封面图',
			'tag' => 'select',
			'option' => 'function:param_option_fields',
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
		//设置栏目id
		$class1=$class2=$class3=0;
		$column1=$this->get_met_column($params['columnid']);
		if($column1['bigclass']>0){
			//有父栏
			$column2=$this->get_met_column($column1['bigclass']);
			if($column2['bigclass']>0){
				//有父栏
				$class1=$column2['bigclass'];
				$class2=$column2['id'];
				$class3=$column1['id'];
			}else{
				$class1=$column1['bigclass'];
				$class2=$column1['id'];
			}
		}else{
			//只有一栏
			$class1=$column1['id'];
		}
		//新文章
		$newArticle=array(
			'title'=>$params['title'],
			'content'=>$params['content'],
			'keywords'=>$params['keywords'],
			'description'=>$params['description'],
			'class1'=>$class1,
			'class2'=>$class2,
			'class3'=>$class3,
			'imgurl'=>$params['imgurl'],
			'imgurls'=>$params['imgurl'],
			'lang'=>$column1['lang'],
			'hits'=>0,
			'updatetime'=>date('Y-m-d H:i:s'),
			'addtime'=>date('Y-m-d H:i:s'),
			'issue'=>'admin',
			'displaytype'=>1
		);
		
		$newsId=$this->db()->table('__NEWS__')->add($newArticle);
		if($newsId>0){
			//返回成功内容网址
			$target=$this->get_met_weburl($column1['lang']).$column1['foldername']."/shownews.php?lang={$column1['lang']}&id={$newsId}";
			return array('id'=>$newsId,'target'=>$target);
		}else{
			return array('id'=>0,'error'=>'文章入库失败');
		}
	}
	
	//获取栏目
	public function get_met_column($columnId){
		$column=null;
		if($columnId>0){
			$column=$this->db()->table('__COLUMN__')->where(array('id'=>$columnId))->find();
		}
		return $column;
	}
	//获取metinfo网站url
	public function get_met_weburl($lang,$key='value'){
		$url=$this->db()->table('__CONFIG__')->where(array('name'=>'met_weburl','lang'=>$lang))->find();
		return empty($url['value'])?'':$url['value'];
	}
	
	//栏目选项
	public function param_option_columnid(){
		static $sltHtml=null;
		if(!isset($sltHtml)){
			$sltHtml='';
			//获取文章栏目
			$columnsData=$this->db()->table('__COLUMN__')->where('module=2')->order('no_order asc')->select();
			$columnList=array();
			foreach ($columnsData as $column){
				switch ($column['classtype']){
					case 1:$columnList[1][$column['id']]=$column;break;
					case 2:$columnList[2][$column['bigclass']][$column['id']]=$column;break;
					case 3:$columnList[3][$column['bigclass']][$column['id']]=$column;break;
				}
			}
			$columnList=$this->auto_convert2utf8($columnList);//转码
			//网站语言
			$langList=$this->db()->table('__LANG__')->where("`lang`!='metinfo'")->order('no_order asc')->select();
				
			//语言栏目分类
			$langColumn=array();
			foreach($columnList[1] as $colid=>$colval){
				$langColumn[$colval['lang']][$colval['id']]=$colval;
			}
			foreach($langList as $lang){
				if(!empty($langColumn[$lang['lang']])){
					$sltHtml.="<optgroup label=\"{$lang['name']}\">";
					foreach($langColumn[$lang['lang']] as $col1){
						$sltHtml.="<option value=\"{$col1['id']}\">{$col1['name']}</option>";
						foreach($columnList[2][$col1['id']] as $col2){
							$sltHtml.="<option value=\"{$col2['id']}\">——{$col2['name']}</option>";
							foreach($columnList[3][$col2['id']] as $col3){
								$sltHtml.="<option value=\"{$col3['id']}\">————{$col3['name']}</option>";
							}
						}
					}
					$sltHtml.='</optgroup>';
				}
			}
		}
		return $sltHtml;
	}
}
?>