/*
 |--------------------------------------------------------------------------
 | SkyCaiji (蓝天采集器)
 |--------------------------------------------------------------------------
 | Copyright (c) 2018 http://www.skycaiji.com All rights reserved.
 |--------------------------------------------------------------------------
 | 使用协议  http://www.skycaiji.com/licenses
 |--------------------------------------------------------------------------
 */
$(document).ready(function(){$('#op_clean').bind('click',function(){var obj=$(this);if(obj.attr('cleaning')==1){return!1}
confirmRight('确定清除缓存？',function(){obj.attr('cleaning',1).html('正在清理...');$.ajax({type:'get',dataType:'json',url:ulink('Setting/clean'),success:function(data){obj.attr('cleaning',0);if(data.status==1){obj.html('清理完成')}else{obj.html('清理失败')}}})});return!1});$('#upgrade_check').html('正在检测更新...');$.ajax({type:'get',dataType:'json',async:!0,url:ulink('Upgrade/newVersion'),success:function(data){if(data.status==1){$('#upgrade_check').html('<a href="" id="op_upgrade">检测到新版本 V'+data.info.new_version+'，点击更新</a>')}else{$('#upgrade_check').html('暂无更新')}}});$('body').on('click','#op_upgrade',function(){var obj=$(this);if(obj.attr('upgrading')==1){return!1}
obj.html('正在检索更新文件...');$.ajax({type:'get',dataType:'json',url:ulink('Upgrade/newFiles'),success:function(data){if(data.status==1){var fileList=data.info.files;obj.attr('upgrading',1);var fileNum=fileList.length;var downNum=0;if(fileNum>0){obj.html('正在更新...');for(var i in fileList){(function(i){$.ajax({type:'get',dataType:'json',url:ulink('Upgrade/downFile'),data:{filename:fileList[i].file,filemd5:fileList[i].md5},success:function(data){if(data.status==1){downNum++;obj.html('正在更新... '+downNum+'/'+fileNum)}else{obj.html('更新失败');$('#upgrade_error').show();if(data.info){$('#upgrade_error').append(data.info+"\r\n")}else{$('#upgrade_error').append('更新失败：'+fileList[i].file+"\r\n")}}},error:function(){obj.html('更新失败');$('#upgrade_error').show();$('#upgrade_error').append('获取失败：'+fileList[i].file+"\r\n")},complete:function(){if(i+1>=fileNum){if(downNum>=fileNum){obj.html('正在校验更新文件...');$.ajax({type:'get',dataType:'json',url:ulink('Upgrade/downComplete'),success:function(data){if(data.status==1){obj.html('更新成功')}else{obj.html('更新失败');$('#upgrade_error').show();for(var fi in data.info){$('#upgrade_error').append('文件校验失败：'+data.info[fi]+"\r\n")}}}})}}}})})(i)}}}else{obj.attr('upgrading',0);obj.html('暂无需要更新的文件')}}});return!1});$.ajax({type:'get',dataType:'jsonp',async:!0,url:ulink('Base/adminIndex'),success:function(data){$('#skycaiji_admin_index').html(data.html)}})})