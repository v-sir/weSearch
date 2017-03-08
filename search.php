<?php
/**
 * Created by PhpStorm.
 * User: 黄炜
 * Date: 2017/3/5
 * Time: 17:23
 */
include "./lib/Snoopy.class.php";
class search{
    var $keyWord;
    var $site;
    var $HtmlConfig;
    var $q;
    var $reData;
    var $length;
    var $siteArr;
    var $page;

    public function __construct(){
        /*
         * 这里配置展示页面的HTML元素，比如title.....
         */
        $this->HtmlConfig=array(
            'title'=>'煒搜-致力打造最强的资源搬运工'
        );
        $this->reData=array();
        $this->length=array();
        $this->siteArr=array();
        $this->keyWord=&$_GET['keyWord'];
        $this->site=&$_GET['site'];
        $this->q=&$_GET['q'];
        $this->init($this->keyWord,$this->site,$this->q);



    }
    public function init($keyWord='',$site='',$q=''){
        if( isset($q) && $q!=""){
            $this->url_decode($q);

        }else{
            isset($keyWord) && $keyWord!="" ? $this->siteArr($site):$this->error(400);

        }


    }
    public function siteArr($site=''){
        if($site==""){
            $site=array('site:yun.baidu.com','site:pan.baidu.com','site:yunpan.cn');
        }
        else if($site=="baidu"){
            $site=array('site:pan.baidu.com','site:yun.baidu.com');
        }
        else if($site=="360"){
            $site=array('site:yunpan.cn');
        }
     //return $site;
       // print_r($site);

        $this->reSite($this->keyWord,$site);

    }

    /***
     * @param $code
     * @return array
     */
    public function error($code="",$reData=""){
        if ($code==400){
            $err=array(
                'code'=>400,
                'msg'=>'Parameter missing!'

            );
            $err=json_encode($err);


        }
        else if($code==0){
            $err=array(
                'code'=>0,
                'msg'=>'OK!',
                'data'=>$reData,

            );
            $err=json_encode($err);


        }
       exit($err);


    }

    /***
     * @param $keyWord
     * @param $site
     */
    public function reSite($keyWord,$site){
        $length=count($site);
        if($length>1){
            for($i=0;$i<$length;$i++){
               $tmp=$keyWord."+".$site[$i];
                array_push($this->siteArr,$tmp);//把site存在数组
                $this->googleSearch($tmp,$init=1);
               // echo $tmp;


            }

        }
        else{
            $tmp=$keyWord."+".$site;
            array_push($this->siteArr,$tmp);//把site存在数组
            $this->googleSearch($tmp,$init=1);
          //  echo $tmp;
        }

        $this->pageProcess($init=0);



    }
    public function pageProcess($init){
        $sites=count($this->siteArr);//site数
        $page=array();
        for($i=0;$i<$sites;$i++){
           // $this->googleSearch($this->siteArr[$i]);
            array_push($page,ceil($this->length[$i]/10));//计算出每个site应该有的数据总数

        }
        /**
         * 依次每个site按照页面进行查取数据
         */
        for($i=0;$i<$sites;$i++) {
            for($j=0;$j<$page[$i];$j++){
               $this->googleSearch($this->siteArr[$i],$init,$page[$i]);

            }

        }
     //print_r($this->length);
     //print_r($this->reData);
       //
       // print_r($this->siteArr);
        //$frequency=
        $this->page=$page;
       $this->reData();


    }
    public function reData(){

        $sites=count($this->siteArr);//site数
        $pageAll=0;

        for($i=0;$i<$sites;$i++){
            $pageAll +=$this->length[$i];//计算出数据总数

        }
        $reData=array(
            'total'=>$pageAll,
            'data'=>$this->reData
        );

       $this->error(0,$reData);
    // print_r($this->reData);



    }


    public function googleSearch($str,$init=0,$page=1){
       $page==1 ? $num=0:$num=10*($page-1);
       $Snoopy=new Snoopy();
       $Snoopy->referer = 'https://www.google.com/';
       $Snoopy->agent='Mozilla/5.0 (compatible; Googlebot/2.1; +https://www.google.com/bot.html)';
       $Snoopy->proxy_host = "59.108.44.41";
       $Snoopy->rawheaders['X_FORWARDED_FOR'] = '216.189.52.22';
       $q= $Snoopy->fetch('https://www.google.com/search?q='.$str.'&sa=G&hl=en&gbv=2&sei=wVi9WObyL-eZ0gLLlZGQDg&start='.$num);
       $tmpStr=$q->results;
       $tmpStr=preg_replace('/\/url\?q\=/', '?q=',$tmpStr);//这是为了urldecode
       $tmpStr=preg_replace('/\<h3 class=\"r\"\>\<a/','<h3 class="r"><a target="_blank"',$tmpStr);//更改谷歌搜索的默认的打卡链接形式
       $tmpStr=preg_replace('/\<ul class\=\"med\" id\=\"tbd\"\>([\s\S]*)\<\/ul\>/','',$tmpStr);//去除左侧元素
       $tmpStr=preg_replace('/\<title\>([\s\S]*)\<\/title\>/','<title>'.$this->HtmlConfig['title'].'</title>',$tmpStr);//设置title
       $tmpStr=preg_replace('/\<nobr\>([\s\S]*)\<\/nobr\>/','',$tmpStr);//去除nav元素
      // $tmpStr=preg_replace('/\<tr style\=\"position\:relative\"\>([\s\S]*)\<\/tr\>/','',$tmpStr);
        //$tmpStr=preg_replace('/\<div class\=\"tn\"\>\<div class\=\"\_UXb \_Ihd \_sxc \_Hhd\"\>([\s\S]*)\<\/div\>/','',$tmpStr);
       $tmpStr=preg_replace('/\<p class\=\"\_cD\" id\=\"bfl\" ([\s\S]*)\<\/p\>/','',$tmpStr);//去除footer元素
       $tmpStr=preg_replace('/\<div class\=\"_cD\" id\=\"fll\"([\s\S]*)\<\/div\>/','',$tmpStr);
       $tmpStr=preg_replace('/\<h2 class\=\"hd\"\>Search Options\<\/h2\>/','',$tmpStr);//去除多余的
       preg_match('/\<tbody data\-jibp\=\"h\" data\-jiis\=\"uc\" id\=\"desktop\-search\"\>([\s\S]*)\<\/div\>/',$tmpStr,$dataStr);
       $bool=preg_match('/\<div class\=\"ads\-creative ellip\"\>([\s\S]*)\<\/div\>/',@$dataStr[0],$tmpArr);//如果存在广告去除
       $bool ? $tmpStr=preg_replace('/\<div class\=\"ads\-creative ellip\"\>([\s\S]*)\<\/div\>/','',@$tmpArr[0]):$tmpStr=@$dataStr[0];
       preg_match('/About([\s\S]*)results/',$tmpStr,$totalArr);
       $tmpStr=preg_replace('/\<div class\=\"sd\" id\=\"resultStats\"\>About([\s\S]*)results\<\/div\>/','<div class="sd" id="resultStats"></div>',$tmpStr);//隐藏搜索结果

        if($init){
         //if(@$totalArr[1]!=""){//判断数据是否存在
               array_push($this->length,@$totalArr[1]);//首次爬取数据时记录每个site的总数记录
               array_push($this->reData,@$tmpStr);//首次爬取数据时记录每个site的搜索结果数据

        // }else{
          //array_push($this->length,0);

         // }

       }
       else{

            array_push($this->reData,@$tmpStr);
       }


    }
    public function url_decode($str){
        $url=urldecode($str);
        header("Location:$url");
    }


}




?>