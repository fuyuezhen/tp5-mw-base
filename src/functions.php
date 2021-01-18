<?php
// 应用公共文件
use think\Loader;
use think\facade\Env;
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__ . DS . '..' . DS . '..' . DS);
Loader::addNamespace('logic', Env::get('app_path') . 'common' . DS . 'logic' . DS);
Loader::addNamespace('validate', Env::get('app_path') . 'common' . DS . 'validate' . DS);
Loader::addNamespace('model', Env::get('app_path') . 'common' . DS . 'model' . DS);

// 用户逻辑
Loader::addNamespace('loadLogic', Env::get('app_path') . 'index' . DS . 'logic' . DS);
Loader::addNamespace('loadModel', Env::get('app_path') . 'index' . DS . 'model' . DS);
Loader::addNamespace('loadValid', Env::get('app_path') . 'index' . DS . 'validate' . DS);

/**
 * 检查是否是本地服务器
 * @param 
 **/
function is_localhost() {
    $localhost_ids = array('localhost','127.0.0.1');
    if(in_array($_SERVER['HTTP_HOST'],$localhost_ids) || in_array($_SERVER['REMOTE_ADDR'],$localhost_ids)){
        return true;
    }
    return false;
}

/**
 * 检测是否为微信中打开
 */
function isWechat()
{
    return true === strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger');
}

/**
 * Url生成
 * @param string        $url 路由地址
 * @param string|array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function __url($url = '', $vars = '', $suffix = true, $domain = false)
{
    $module     = request()->module();
    $controller = request()->controller();
    $action     = request()->action();

    if(!empty($url)){
        $url_array = explode('/', $url);
        if(count($url_array) == 1){
            $url = $module . '/' . $controller . '/' . $url;
        }
        if(count($url_array) == 2){
            $url = $module . '/' . $url;
        }
    }else{
        $url = $module . '/' . $controller . '/' . $action;
    }

    return Url::build($url, $vars, $suffix, $domain);
}

/**
 * 获取接口数据
 * @param [type] $url
 * @return void
 */
function __curl($url, $data = [], $type = 'get'){
    if(is_array($data)){
        $data  = json_encode($data);    
    }
    $headerArray = array("Content-type:application/json;charset='utf-8'", "Accept:application/json");
    // 初始化CURL句柄
    $curl = curl_init(); 
    // 设置请求的URL
    curl_setopt($curl, CURLOPT_URL, $url); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
    if($type == 'post' || !empty($data)){
        if($type == 'post' || $type == 'get'){
            curl_setopt($curl, CURLOPT_POST, 1);
        }else{
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type); //设置请求方式
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // 设置提交的字符串
    }
    curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
    // 设为TRUE把curl_exec()结果转化为字串，而不是直接输出 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    $output = json_decode($output,true);

    // if(isset($output['data'])){
    //     $output = $output['data'];
    // }
    return $output;
}


/**
 * 过滤特殊字符
 * @param [type] $str
 * @return void
 */
function filterEmoji($str){
    $str = preg_replace_callback(
        '/./u',
        function (array $match) {
        return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);

    return $str;
}

/** getmessagepic()提取文章内容中的图片
* @param string $content
* @return string
*/
function getcontentpic($content, $type = false) {
    $pic = '';
    $content = stripslashes($content);
    $content = preg_replace("/\<img src=\".*?image\/face\/(.+?).gif\".*?\>\s*/is", '', $content); //移除表情符
    if($type){
        $pic = [];
        preg_match_all("/src\=[\"\']*([^\>\s]{25,105})\.(jpg|gif|png)/i", $content, $mathes);
        if(!empty($mathes[1]) || !empty($mathes[2])) {
            foreach($mathes[1] as $key=>$filename){
                $pic[] = "{$filename}.{$mathes[2][$key]}";
            }
        }
        return $pic;
    }else{
        preg_match("/src\=[\"\']*([^\>\s]{25,105})\.(jpg|gif|png)/i", $content, $mathes);
        if(!empty($mathes[1]) || !empty($mathes[2])) {
            $pic = "{$mathes[1]}.{$mathes[2]}";
        }
        return addslashes($pic);
    }
}

/**
 * 过滤html并截取
 *
 * @param [type] $string
 * @param [type] $length
 * @param boolean $append
 * @return void
 */
function subtext($string,$length,$append = true){
    $string = clear_all(trim($string));
    $strlength = strlen($string);
    if ($length == 0 || $length >= $strlength){
        return $string;
    }elseif ($length < 0){
        $length = $strlength + $length;
        if ($length < 0)
        {
            $length = $strlength;
        }
    }
    if (function_exists('mb_substr')){
        $newstr = mb_substr($string, 0, $length,"UTF-8");
    }elseif (function_exists('iconv_substr')){
        $newstr = iconv_substr($string, 0, $length,"UTF-8");
    }else{
      for($i=0;$i<$length;$i++){
            $tempstring=substr($string,0,1);
            if(ord($tempstring)>127){
               $i++;
               if($i<$length){
                  $newstring[]=substr($string,0,3);
                  $string=substr($string,3);
               }
            }else{
               $newstring[]=substr($string,0,1);
               $string=substr($string,1);
            }
         }
      $newstr =join($newstring);
    }
    if ($append && $string != $newstr){
        $newstr .= '...';
    }
    return $newstr;
}
function clear_all($area_str){ //过滤成纯文本用于显示
    if ($area_str!=''){
        $area_str = trim($area_str); //清除字符串两边的空格
        $area_str = strip_tags($area_str,""); //利用php自带的函数清除html格式
        $area_str = str_replace("&nbsp;","",$area_str);
         
        $area_str = preg_replace("/   /","",$area_str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/
/","",$area_str); 
        $area_str = preg_replace("/ /","",$area_str);
        $area_str = preg_replace("/  /","",$area_str);  //匹配html中的空格
        $area_str = trim($area_str); //返回字符串
    }
    return $area_str;
}


//id转换
function alphaID($in, $to_num = false, $pad_up = false, $pass_key = null)
{
    $out = '';
    $index = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = strlen($index);

    if ($pass_key !== null) {
        // Although this function's purpose is to just make the
        // ID short - and not so much secure,
        // with this patch by Simon Franz (http://blog.snaky.org/)
        // you can optionally supply a password to make it harder
        // to calculate the corresponding numeric ID

        for ($n = 0; $n < strlen($index); $n++) {
            $i[] = substr($index, $n, 1);
        }

        $pass_hash = hash('sha256', $pass_key);
        $pass_hash = (strlen($pass_hash) < strlen($index) ? hash('sha512', $pass_key) : $pass_hash);

        for ($n = 0; $n < strlen($index); $n++) {
            $p[] = substr($pass_hash, $n, 1);
        }

        array_multisort($p, SORT_DESC, $i);
        $index = implode($i);
    }

    if ($to_num) {
        // Digital number  <<--  alphabet letter code
        $len = strlen($in) - 1;

        for ($t = $len; $t >= 0; $t--) {
            $bcp = bcpow($base, $len - $t);
            $out = floatval($out) + strpos($index, substr($in, $t, 1)) * $bcp;
        }

        if (is_numeric($pad_up)) {
            $pad_up--;

            if ($pad_up > 0) {
                $out -= pow($base, $pad_up);
            }
        }
    } else {
        // Digital number  -->>  alphabet letter code
        if (is_numeric($pad_up)) {
            $pad_up--;

            if ($pad_up > 0) {
                $in += pow($base, $pad_up);
            }
        }

        for ($t = ($in != 0 ? floor(log($in, $base)) : 0); $t >= 0; $t--) {
            $bcp = bcpow($base, $t);
            $a = floor($in / $bcp) % $base;
            $out = $out . substr($index, $a, 1);
            $in = $in - ($a * $bcp);
        }
    }

    return $out;
}

// 获取手机设备
function get_device()
{
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if (stristr($agent, 'iPad')) {
        $fb_fs = "iPad";
    } else if (preg_match('/Android (([0-9_.]{1,3})+)/i', $agent, $version)) {
        $fb_fs = "手机(Android " . $version[1] . ")";
    } else if (stristr($agent, 'Linux')) {
        $fb_fs = "电脑(Linux)";
    } else if (preg_match('/iPhone OS (([0-9_.]{1,3})+)/i', $agent, $version)) {
        $fb_fs = "手机(iPhone " . $version[1] . ")";
    } else if (preg_match('/Mac OS X (([0-9_.]{1,5})+)/i', $agent, $version)) {
        $fb_fs = "电脑(OS X " . $version[1] . ")";
    } else if (preg_match('/unix/i', $agent)) {
        $fb_fs = "Unix";
    } else if (preg_match('/windows/i', $agent)) {
        $fb_fs = "电脑(Windows)";
    } else {
        $fb_fs = "Unknown";
    }
    return $fb_fs;
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装） 
 * @return mixed
 */
function get_client_ip($type = 0,$adv=false) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

// 获取客户端浏览器类型
function get_client_browser()
{
   if (empty($_SERVER['HTTP_USER_AGENT'])) {
      return '';
   }
   $agent = $_SERVER['HTTP_USER_AGENT']; //获取客户端信息
   $browser = '';
   $browser_ver = '';

   if (preg_match('/OmniWeb\/(v*)([^\s|;]+)/i', $agent, $regs)) {
      $browser = 'OmniWeb';
      $browser_ver = $regs[2];
   } elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
      $browser = 'Netscape';
      $browser_ver = $regs[2];
   } elseif (preg_match('/Chrome\/([^\s]+)/i', $agent, $regs)) {
      $browser = 'Chrome';
      $browser_ver = $regs[1];
   } elseif (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
      $browser = 'Internet Explorer';
      $browser_ver = $regs[1];
   } elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
      $browser = 'Opera';
      $browser_ver = $regs[1];
   } elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
      $browser = '(Internet Explorer ' . $browser_ver . ') NetCaptor';
      $browser_ver = $regs[1];
   } elseif (preg_match('/Maxthon/i', $agent, $regs)) {
      $browser = '(Internet Explorer ' . $browser_ver . ') Maxthon';
      $browser_ver = '';
   } elseif (preg_match('/360SE/i', $agent, $regs)) {
      $browser = '(Internet Explorer ' . $browser_ver . ') 360SE';
      $browser_ver = '';
   } elseif (preg_match('/SE 2.x/i', $agent, $regs)) {
      $browser = '(Internet Explorer ' . $browser_ver . ') 搜狗';
      $browser_ver = '';
   } elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
      $browser = 'FireFox';
      $browser_ver = $regs[1];
   } elseif (preg_match('/Lynx\/([^\s]+)/i', $agent, $regs)) {
      $browser = 'Lynx';
      $browser_ver = $regs[1];
   } elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
      $browser = 'Safari';
      $browser_ver = $regs[1];
   }

   if ($browser != '') {
      return addslashes($browser . ' ' . $browser_ver);
   } else {
      return 'Unknow browser';
   }
}