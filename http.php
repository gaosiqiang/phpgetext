<?php
set_time_limit(0);
error_reporting(E_ALL^E_NOTICE);
/**
 * Created by PhpStorm.
 * User: gaosiqiang
 * Date: 16/4/8
 * Time: 09:58
 */
require_once 'httpclient.inc.php';

require 'html/vendor/autoload.php';//引入html解析

use hightman\http\Client;
use hightman\http\Request;
use hightman\http\Response;

// create client instance
$http = new Client();

// set cookie file
$http->setCookiePath('cookie.dat');

// add text/plain header for web sapi handler
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}


//取出详情页的连接列表
$array = array();
$start = 1;
$max = $start + 122;

for($i=$start;$i<$max;$i++)
{
    $html = curl_https('https://nn.nuomi.com/326-page'.$i.'?#j-sort-bar');
    if($html)
    {
        $html_dom = new \HtmlParser\ParserDom($html);
        $p_array = $html_dom->find('li.j-card');
        foreach($p_array as $k => $v)
        {
            $str = $v->innerHtml();
            $s = strpos($str,'//www');
//    $str=substr($str, $s);//去除前面
            $n = strpos($str,'.html');//寻找位置
            $str = substr($str, $s, $n);//删除后面
            $n1 = strpos($str,'.html');//寻找位置
            $str = substr($str, 0, $n1);//删除后面
//    $str = rtrim($str,'"');
            if(!empty($str))
            {
//        echo $str.'.html'. "\n\n";
                $array[] = 'https:'.$str.'.html';
            }

        }
    }


}



$myfile = fopen("nuomi_nn_meishi".$start."-".$max.".text", "w") or die("Unable to open file!");
//发送http请求
foreach($array as $uk => $uv)
{
//    $goods = $http->get($uv);
    $goods = curl_https($uv);
    if($goods)
    {
        //处理结果
        $html_dom = new \HtmlParser\ParserDom($goods);
        $fl = $html_dom->find('li.crumb');
        $fl = $fl[2]->node->nodeValue;
        $fl = str_replace(PHP_EOL, '', $fl);//去除字符串中\n和&nbsp; //二级分类
        if(empty($fl))
        {
            $fl = '美食';
        }
        $sales = $html_dom->find('span.intro-strong');
        $sales = str_replace(PHP_EOL, '', $sales[0]->node->nodeValue);//销量
        if(empty($sales))
        {
            $sales = 0;
        }
        $score = $html_dom->find('div.us-grade');
        $score = str_replace(PHP_EOL, '', $score[0]->node->nodeValue);//评分
        if(empty($score))
        {
            $score = 0.0;
        }
        $s = strpos($uv,'deal/');
        $vurl = substr($uv, $s+5, -5);//
        $url = 'http://m.nuomi.com/webapp/tuan/storelist?dealTinyUrl='.$vurl;
        $infos = curl_https($url);
        $html_dom = new \HtmlParser\ParserDom($infos);
        $shop_title = $html_dom->find('p.shop-title');//商户名称
        $shop_address = $html_dom->find('p.shop-address');//商户地址
        foreach($shop_title as $key => $value)
        {
            $title = $value->node->nodeValue;//name
            $address = $shop_address[$key]->node->nodeValue;//address
            $s = strpos($infos,$shop_address[$key]->node->nodeValue);
            $infos = substr($infos, $s);
            $s = strpos($infos, 'bn-phone=');
            $infos = substr($infos, $s+10);
            $n = strpos($infos,'mon');
            $tel = substr($infos, 0, $n-2);//tel
            if(empty($tel))
            {
                $tel = 0;
            }

            $txt = '("'.$title.'","'.$address.'","'.$tel.'",'.$score.','.$sales.',"生活","'.$fl.'"),'."\n";
            fwrite($myfile, $txt);
            echo $txt;
        }
    }

}
fclose($myfile);



function curl_https($url){
    $ch = curl_init();
    $header = array(
        'CLIENT-IP:219.159.235.101',
        'X-FORWARDED-FOR:219.159.235.101',
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    curl_setopt($ch, CURLOPT_URL, $url);//设置访问的url地址
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置请求头
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);// 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);// 从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if($error=curl_error($ch)){
//        die($error);
        return false;
    }

    curl_close($ch);

    return $response;

}