<?php
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

//$mysqli = new mysqli('123.56.106.97', 'root', 'gao1990', 'nuomi');
//if ($mysqli->connect_error) {
//    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
//}

// set cookie file
$http->setCookiePath('cookie.dat');

// add text/plain header for web sapi handler
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain');
}


//取出详情页的连接列表
$array = array();
$start = 20;
$max = $start + 20;
for($i=$start;$i<$max;$i++)
{
    $response = $http->get('http://xiangtan.nuomi.com/326-page'.$i.'?#j-sort-bar');
    $html = $response;
    $html_dom = new \HtmlParser\ParserDom($html);
    $p_array = $html_dom->find('li.j-card');

    foreach($p_array as $k => $v)
    {
        $str = $v->innerHtml();
        $s = strpos($str,'http:');
//    $str=substr($str, $s);//去除前面
        $n = strpos($str,'.html');//寻找位置
        $str = substr($str, $s, $n);//删除后面
        $n1 = strpos($str,'.html');//寻找位置
        $str = substr($str, 0, $n1);//删除后面
//    $str = rtrim($str,'"');
        if(!empty($str))
        {
//        echo $str.'.html'. "\n\n";
            $array[] = $str.'.html';
        }

    }

}


$myfile = fopen("nuomi_xiangtan_meishi".$start."-".$max.".text", "w") or die("Unable to open file!");


//根据连接列表获取门店列表连接
foreach($array as $k => $v1)
{
    //发送http请求
    $goods = $http->get($v1);
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
    //获取商品短连接
    $s = strpos($v1,'deal/');
    $vurl = substr($v1, $s+5, -5);//
    $url = 'http://m.nuomi.com/webapp/tuan/storelist?dealTinyUrl='.$vurl;
    $infos = $http->get($url);
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

        $txt = '("'.$title.'","'.$address.'","'.$tel.'",'.$score.','.$sales.',"美食","'.$fl.'"),'."\n";

        fwrite($myfile, $txt);

    }


}


fclose($myfile);


//$mysqli->close();