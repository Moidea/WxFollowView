<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章隐藏内容实现微信公众号关注并获取密码插件
 * 
 * @package WxFollowView 
 * @author Roogle
 * @version 2.0
 * @link http://www.moidea.info
 */
class WxFollowView_Plugin implements Typecho_Plugin_Interface
{
	
	// 插件版本号
    protected static $version = '2.0';
	
	
	// 默认加密首尾标签对
	protected static $pluginNodeStart = '<!--wxfollow start-->';
	protected static $pluginNodeEnd = '<!--wxfollow end-->';
	
	
	
	
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
		//检查是否有curl扩展
        if (! extension_loaded('curl')) {
            //throw new Typecho_Plugin_Exception('缺少curl扩展支持.');
        }else{
			// 用于插件作者统计插件使用数量
			self::install();
		}
		
		
		
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('WxFollowView_Plugin','moidea_wx_follow_view');
		//Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('WxFollowView_Plugin','kwparse');
		
		
		//如果你不用richEdit，这两行注释可以打开。
		Typecho_Plugin::factory('admin/write-post.php')->bottom  = array('WxFollowView_Plugin', 'render');
        Typecho_Plugin::factory('admin/write-page.php')->bottom  = array('WxFollowView_Plugin', 'render');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
		return _t('插件已禁用！');
	}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
		$handle = fopen("https://www.moidea.info/WxFollowView/PluginAPI_WxFollowView.json","rb");//更新提示请勿修改
		$content = "";
		while (!feof($handle)) {
			$content .= fread($handle, 10000);
		}
		fclose($handle);
		
		$content = json_decode($content);
		$color = '#42e000;';

		echo('<div style="margin-bottom:14px;font-size:13px;text-align:center;background:'.$color.';padding:10px;">'.$content->description.'此插件最新版本：V'.$content->version.' 修改日期：'.$content->date.'
		<br>如果您发现插件存在bug，请到此页面留言 <a href="https://www.moidea.info/WxFollowView.html" target="_blank"><font style="color:#fff;">【留言反馈】</font></a>或者QQ <a href="tencent://Message/?Uin=1989473781&websiteName=%E9%BB%98%E6%80%9D%E5%88%9B%E6%84%8F=&Menu=yes" target="_blank"><font style="color:#fff;">【1989473781】</font></a>给我私信，我将尽自己最大努力去修复。</div>升级说明：背景颜色为绿色表示当前是最新版本<span style="width:10px;height:10px;background:#42e000;">&nbsp;&nbsp;&nbsp;&nbsp;</span>，背景颜色为黄色表示有新版本<span style="width:10px;height:10px;background:#ffef2a;">&nbsp;&nbsp;&nbsp;&nbsp;</span>，背景颜色为红色表示有重大更新<span style="width:10px;height:10px;background:#fb0000;">&nbsp;&nbsp;&nbsp;&nbsp;</span><br>');
		
        /** 微信公众号名称 */
        $wxname = new Typecho_Widget_Helper_Form_Element_Text('wxname', NULL, '多思多金', _t('微信公众号名称：'));
        $form->addInput($wxname);
		
		/** 微信公众号 */
        $wxid = new Typecho_Widget_Helper_Form_Element_Text('wxid', NULL, 'moideainfo', _t('微信公众号：'));
        $form->addInput($wxid);
		
		/** 回复以下关键词获取密码 */
        $wxkeyword = new Typecho_Widget_Helper_Form_Element_Text('wxkeyword', NULL, 'MIAO', _t('回复以下关键词获取密码：'));
        $form->addInput($wxkeyword);
		
		/** 自动回复的验证码 */
        $wxcode = new Typecho_Widget_Helper_Form_Element_Text('wxcode', NULL, 'MIAOMIAO', _t('自动回复的验证码：'));
        $form->addInput($wxcode);
		
		/** 微信公众号二维码地址 */
        $wxqrcode = new Typecho_Widget_Helper_Form_Element_Text('wxqrcode', NULL, 'https://www.pi001.com/usr/themes/default/img/moideainfo.jpg', _t('微信公众号二维码地址：'));
        $form->addInput($wxqrcode);
		
		/** Cookie有效期 */
        $wxcookie = new Typecho_Widget_Helper_Form_Element_Text('wxcookie', NULL, '30', _t('Cookie有效期：'));
        $form->addInput($wxcookie);
		
		/** 加密密钥 */
        $wxkey = new Typecho_Widget_Helper_Form_Element_Text('wxkey', NULL, md5('moidea.us'.time().rand(10000,99999)), _t('加密密钥：用于加密Cookie'));
        $form->addInput($wxkey);
		
		/** 自定义开始结束字符串 */
        $codestart = new Typecho_Widget_Helper_Form_Element_Text('codestart', NULL, '<!--wxfollow start-->', _t('<br><br><div style="font-size:13px;text-align:center;">插件使用方法: <a href="https://www.moidea.info/WxFollowView.html" target="_blank"><b>使用方法</b></a><br></div>
		
		
		
		<br><div style="font-size:13px;text-align:center;color:#db0000;background: #eee;padding: 10px;">==============高级配置==============<br>高级配置，如果不懂保持默认即可，否则可能出错！！！<br>==============切记切记==============</div><br><br>自定义加密开始字符串'));
        $form->addInput($codestart);
		
		/** 自定义开始结束字符串 */
        $codeend = new Typecho_Widget_Helper_Form_Element_Text('codeend', NULL, '<!--wxfollow end-->', _t('自定义加密开始字符串'));
        $form->addInput($codeend);

		/** 自定义加密块主题样式 */
		$codestyle = new Typecho_Widget_Helper_Form_Element_Textarea('codestyle', NULL, '<style>
.WxFollowView-box{border:1px dashed #F60; padding:10px; margin:10px 0; line-height:200%;  background-color:#FFF4FF; overflow:hidden; clear:both;}
.WxFollowView-wxpic{width:150px;height:150px;margin-left:20px;display:inline;border:none;float:right;}
.WxFollowView-tips{font-size:16px;}
.WxFollowView-form{margin:10px 0;overflow:hidden;}
.WxFollowView-yzm{font-size:16px;float:left;}
.WxFollowView-verifycode{border:none;float:left;width:100px !important; height:30px; line-height:30px; padding:0 5px; border:1px solid #FF6600;-moz-border-radius: 0px;  -webkit-border-radius: 0px;  border-radius:0px !important;}
.WxFollowView-verifybtn{border:none;float:left;width:80px; height:30px; line-height:30px; padding:0 5px; background-color:#F60; text-align:center; border:none; cursor:pointer; color:#FFF;-moz-border-radius: 0px; font-size:14px;  -webkit-border-radius: 0px;  border-radius:0px;}
.WxFollowView-text{color:#00BF30;}
.WxFollowView-text-tips-key{color:blue}
.WxFollowView-text-tips-key-name{color:blue}
.WxFollowView-text-tips-id{color:blue}
.WxFollowView-qrcode{float: right;width: inherit;}
</style>', _t('自定义主题样式'));
        $form->addInput($codestyle);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
	
	
	
	public static function render(){
		
		$pluginNodeStart = Typecho_Widget::widget('Widget_Options')->plugin('WxFollowView')->codestart;
		$pluginNodeEnd = Typecho_Widget::widget('Widget_Options')->plugin('WxFollowView')->codeend;		
		

        if(!$pluginNodeStart){

            $pluginNodeStart = self::$pluginNodeStart;

        }
		if(!$pluginNodeEnd){

            $pluginNodeEnd = self::$pluginNodeEnd;

        }
		
		
		echo "<script>
		$(function() {
	if($('#wmd-button-row').length>0)$('#wmd-button-row').append('<li class=\"wmd-spacer wmd-spacer1\" id=\"wmd-spacer5\"></li><li class=\"wmd-button\" id=\"wmd-secret-button\" title=\"加密\"><img src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAABoVBMVEUAAAC9vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb29vb2QpK6QpK68vb29vb29vb29vb28vL27vLy9vb29vb29vb28vb2QpK6QpK60uLq8vL29vb26u7wAN2xMfpe5u7y9vb28vL20uLqQpK6QpK6QpK6QpK6PpK6XqLCerLOTpa+QpK6QpK6Spa+dq7KXqLCPpK6QpK6QpK6QpK6QpK6QpK6QpK6Po66QpK6QpK6QpK6QpK6Po66QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6OoqyYq7QAITiQpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6Jnqlee4pgfYuQpK6QpK6QpK5yi5hgfYtgfYuQpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK6QpK7///+aiJ4ZAAAAinRSTlMAAAAAJWBgJQAAAE7V4eDVTgAAINinKCeh1yAAAAAAU+kwAAAn4lQAAAAAYeAhAAEZ1mEAAAAAE4LO+besrbX1zoITAABT87apra2tram281MAAF/gIQACACHgXwAAX+AfAAVBX+AfAEHuAF/gIQBT87isrbjzABOCrKysrIISAAAAAAMDAwMAAAD0HrYnAAAAAWJLR0SKhWh3dgAAAAd0SU1FB94MChAKAKhkkWAAAADOSURBVBjTY2BgYGBkYmZhZWPnYGJkgABOLm4eXj5+AUFOqICQsIiomLiEpJQ0mCsjKyevoKikrKKqpi4rAxTQ0NTS1tHV0zcwNDI2MQUKmJlbWFpZ29ja2Ts4OjkDBVxc3dw9PL28fXz9/AMCgQJBwSGhYeEREeFhkVHRMUCB2Lj4hMSk5OSkxIT4uFiQQEpqWnpGZmZGelpqCnYBdC1Z2Tm5KIbm5RcUFhUDQVFJKdjasvKKyqpqIKiqqa2rBwo0NDY1t7QCQUtbe0cnAwAYTECfAJIwMQAAACV0RVh0ZGF0ZTpjcmVhdGUAMjAxNi0wOS0xN1QxNToyMToyNSswODowME6zKwsAAAAldEVYdGRhdGU6bW9kaWZ5ADIwMTQtMTItMTBUMTY6MTA6MDArMDg6MDD4IEsDAAAATXRFWHRzb2Z0d2FyZQBJbWFnZU1hZ2ljayA3LjAuMS02IFExNiB4ODZfNjQgMjAxNi0wOS0xNyBodHRwOi8vd3d3LmltYWdlbWFnaWNrLm9yZ93ZpU4AAAAYdEVYdFRodW1iOjpEb2N1bWVudDo6UGFnZXMAMaf/uy8AAAAYdEVYdFRodW1iOjpJbWFnZTo6SGVpZ2h0ADEyOEN8QYAAAAAXdEVYdFRodW1iOjpJbWFnZTo6V2lkdGgAMTI40I0R3QAAABl0RVh0VGh1bWI6Ok1pbWV0eXBlAGltYWdlL3BuZz+yVk4AAAAXdEVYdFRodW1iOjpNVGltZQAxNDE4MTk5MDAwaPjnQgAAABJ0RVh0VGh1bWI6OlNpemUAMS4zNUtCy0Y24gAAAF90RVh0VGh1bWI6OlVSSQBmaWxlOi8vL2hvbWUvd3d3cm9vdC9zaXRlL3d3dy5lYXN5aWNvbi5uZXQvY2RuLWltZy5lYXN5aWNvbi5jbi9zcmMvMTE4MjAvMTE4MjAyOC5wbmdgSnSkAAAAAElFTkSuQmCC\"/></li>');	
	$(document).on('click', '#wmd-secret-button', function() {		
		getValue(\"text\", \"{$pluginNodeStart}请输入加密内容{$pluginNodeEnd}\");
	}
	);
});

function getValue(objid, str) {
    var myField = document.getElementById(\"\" + objid);
    //IE浏览器
    if (document.selection) {
        myField.focus();
        sel = document.selection.createRange();
        sel.text = str;
        sel.select();
    }

    //火狐/网景 浏览器
    else if (myField.selectionStart || myField.selectionStart == '0') {
        //得到光标前的位置
        var startPos = myField.selectionStart;
        //得到光标后的位置
        var endPos = myField.selectionEnd;
        // 在加入数据之前获得滚动条的高度
        var restoreTop = myField.scrollTop;
        myField.value = myField.value.substring(0, startPos) + str + myField.value.substring(endPos, myField.value
            .length);
        //如果滚动条高度大于0
        if (restoreTop > 0) {
            // 返回
            myField.scrollTop = restoreTop;
        }
        myField.focus();
        myField.selectionStart = startPos + str.length;
        myField.selectionEnd = startPos + str.length;
    } else {
        myField.value += str;
        myField.focus();
    }
}
		</script>";
		
	}
	
	public static function add_button(){
		$dir=Helper::options()->pluginUrl.'/WxFollowView/editer.js';
		echo "<script type=\"text/javascript\" src=\"{$dir}\"></script>";
	}
	
	
	public static function install()
    {
		$url = 'https://www.moidea.info/WxFollowView/ApiTongji.php'; //插件作者用来统计插件安装数目的请勿修改，谢谢
		$weburl = $_SERVER['HTTP_HOST'];
		
		$data = json_encode(array('weburl'=>$weburl));
		
		$json_data = self::post_data($url, $data);      
		//$array = json_decode($json_data,true);
		//echo '<pre>';print_r($array);		
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
	 * @param string $content
     * @return void
     */
    public static function moidea_wx_follow_view($content)
    {
        $cookie_name = 'moidea_wx_follow_view';
		$content = $content;
		$config = Typecho_Widget::widget('Widget_Options')->plugin('WxFollowView');
		
		
		if (preg_match_all('/'.$config->codestart.'([\s\S]*?)'.$config->codeend.'/i', $content, $hide_words))
		{
			
			$cv = md5($config->wxkey.$cookie_name.'moidea.us');
			
			$vtips='';
			if(isset($_POST['moidea_verifycode'])){
				if($_POST['moidea_verifycode']==$config->wxcode){
					setcookie($cookie_name, $cv ,time()+(int)$config->wxcookie*86400, "/");
					$_COOKIE[$cookie_name] = $cv;
				}else{
					$vtips='<script>alert("验证码错误！请输入正确的验证码！");</script>';
				}
			}
			$cookievalue = isset($_COOKIE[$cookie_name])?$_COOKIE[$cookie_name]:'';

			if($cookievalue==$cv){
				$content = str_replace($hide_words[0], '<div class="WxFollowView-box">'.$hide_words[0][0].'</div>'.$config->codestyle, $content);	
			}else{
				$hide_notice = '<div class="WxFollowView-box">
					<img class="WxFollowView-wxpic" src="'.$config->wxqrcode.'" alt="'.$config->wxname.'" />
					
					<span class="WxFollowView-tips">此处内容已经被站长封印，请输入验证码查看</span>
					
					<form method="post" class="WxFollowView-form">
						<span class="WxFollowView-yzm">验证码：</span>
						
						<input class="WxFollowView-verifycode" name="moidea_verifycode" id="verifycode" type="text" value=""/>
						
						<input class="WxFollowView-verifybtn" id="verifybtn" name="" type="submit" value="解除封印" />
					</form>
					
					<div style="clear:left;"></div>
					
					<span class="WxFollowView-text">关注微信公众号，回复“<span class="WxFollowView-text-tips-key">'.$config->wxkeyword.'</span>”，获取验证码。在微信里搜索“<span class="WxFollowView-text-tips-name">'.$config->wxname.'</span>”或者“<span class="WxFollowView-text-tips-id">'.$config->wxid.'</span>”或者扫描右侧二维码关注本站微信公众号。</span>
				<div class="cl"></div>
				</div>'.$config->codestyle.$vtips;
				$content = str_replace($hide_words[0], $hide_notice, $content);
			}		
		}	
		return $content;
    }
	
	/**
     * 插件实现方法
     * 
     * @access public
	 * @param string $content
     * @return void
     */
	public static function post_data($url, $data)      
    {      
        $ch = curl_init();      
        $timeout = 300;       
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(    
            'Content-Type: application/json; charset=utf-8',    
            'Content-Length: ' . strlen($data))    
        ); 		
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);      
        $handles = curl_exec($ch);      
        curl_close($ch);      
        return $handles; 
    }      
}
