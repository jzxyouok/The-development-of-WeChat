<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第二排按钮，校园服务控制实现
 */
class CampusController extends Controller {
    public function index(){
        /* $this->display(); */
    }

    /*
     *发送提醒输入图书接口
     */
    public function askLibrary($weChat){
        $weChat->text("请输入书名（也可以输入关键字）。\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','library','120');      //提醒输入图书时间预设2分钟 
    }

    /*
     *发送快递查询接口
     */
    public function checkLibrary(){
/* $query_sql = "SELECT * FROM books where upper(book_name) like upper('%".$book_name."%') or upper(author_name) like upper('%".$book_name."%') LIMIT  $offset , $pagesize"; */
        $Library=M('Library');
        $where['booktitle']=array('like',(String)$bookKey.'%');
        $where['booktitle']=array('like','%'.(String)$bookKey);
        $where['booktitle']=array('like','%'.(String)$bookKey.'%');
        $Library->where($where)->select();
        echo $Library->getLastSql();
        /* $weChat->text($Library->getLastSql())->reply(); */
        exit;
        /* $url = "http://m.kuaidi100.com/index_all.html?postid=".$expressid; */
        /* $title = '快递单号：'.$expressid; */
        /* $ex = array( */
        /*     "0"=>array( */
        /*         'Title'=> $title, */
        /*         'Description'=>"为了方便查询，自动记录单号。\n\n查询其它快递单号，回复【快递】", */
        /*         'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/kuaidi.png', */
        /*         'Url'=> $url, */
        /*     ), */
        /*  ); */
        /* $weChat->news($ex)->reply(); */
        /* S($weChat->getRevFrom().'_do',null);   //删除操作缓存 */
    }

    /*
     *如果是文本收到快递，默认删除缓存单号
     */
    public function resetExpress($weChat){
        S($weChat->getRevFrom().'_spress',null);
        $this->askExpress($weChat);
    }


    /*
     *快递消息处理
     */
    public function dealExpress($weChat){
        if($expressid = S($weChat->getRevFrom().'_spress')){
            //如果缓存里有快递单号，直接返回
            $this->checkExpress($weChat, $expressid);
        }else{
            $this->askExpress($weChat);
        }
    }

    /*
     *发送提醒输入快递单号接口
     */
    public function askExpress($weChat){
        $weChat->text("请输入快递单号（不用输入快递公司）。\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','express','300');     
    }

    /*
     *发送快递查询接口
     */
    public function checkExpress($weChat, $expressid){
        $url = "http://m.kuaidi100.com/index_all.html?postid=".$expressid;
        $title = '快递单号：'.$expressid;
        $ex = array(
            "0"=>array(
                'Title'=> $title,
                'Description'=>"为了方便查询，自动记录单号。\n\n查询其它快递单号，回复【快递】",
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/kuaidi.png',
                'Url'=> $url,
            ),
         );
        $weChat->news($ex)->reply();
        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
    }

    /*
     *处理食堂查询
     */
    public function askShiting($weChat){
        $weChat->text("请输入查询几食堂\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','shitang','120');      //缓存查几食堂 
    }

    /*
     *返回食堂信息
     */
    public function dealShitang($weChat, $num){
        $Shitang = M('Shitang');
        if(strpos($num, "2")!==false || strpos($num, "二")!==false){   //查询二食堂
            $where['location']="二食堂";
            $num = "二食堂";
        }else if(strpos($num, "3")!==false || strpos($num, "三")!==false){
            $where['location']="三食堂";
            $num = "三食堂";
        }else{
            $weChat->text("食堂不存在，请重新输入\n例：'2'或者'二'再或者'二食堂'\n\n或输入【exit】退出操作。")->reply();
            exit;
        }
        $shitangArr = $Shitang->where($where)->select();
        $this->showShitang($weChat, $shitangArr, $num);
    }

    /*
     *发送食堂档口信息格式化
     */
    public function showShitang($weChat, $shitangArr, $num){
        $shitangstring = $num."档口：\n";
        $arr = array();
        for($i=0 ; $i<count($shitangArr) ; $i++){
                $shitangstring .= $i.". ".$shitangArr[$i]['name']."\n".$shitangArr[$i]['telephone']."\n";
                $arr[$i]=$shitangArr[$i]['id'];    //存储档口编号
        }

        $shitangstring .= "\n回复对应编号查看菜单\n回复【exit】退出操作";

        S($weChat->getRevFrom().'_do','caidan','120');   //菜单操作缓存
        S($weChat->getRevFrom().'_date', json_encode($arr),'120');   //编号数据缓存
        $weChat->text($shitangstring)->reply();
    }

    /*
     *处理菜单查询
     */
    public function dealCaidan($weChat, $num){
        
        $arr = S($weChat->getRevFrom().'_date');   //编号数据缓存
        $arr = json_decode($arr);
        $id = $arr[$num];   //获取编号对应的数据库中编号
        if($id != ""){
            $Caidan = M('Caidan');
            $where['id']=$id;
            $caidanArr = $Caidan->where($where)->select();
            $this->showCaidan($weChat, $caidanArr);
        }else{
            $weChat->text("编号错误，重新输入\n\n或输入【exit】退出操作。")->reply();
        }
    }

    /*
     *发送档口菜单信息格式化
     */
    public function showCaidan($weChat, $caidanArr){
        $caidanstring = "菜单：";
        $arr = array();
        for($i=0 ; $i<count($caidanArr) ; $i++){
            $caidanstring .= "\n".$caidanArr[$i]['name']." ￥".$caidanArr[$i]['price'];
        }

        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
        $weChat->text($caidanstring)->reply();
    }


    /*
     *小助手微信墙
     */
    public function loveWall($weChat)
    {
        $wall = array(
            "0"=>array(
                'Title' => '小助手微信墙',
                'Description'=>'表白，吐槽，心愿~',
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/wall.jpg',
                'Url'=> 'http://csxywxq.sinaapp.com/w/'
            ),
         );
        $weChat->news($wall)->reply();
        exit;
    }

    /*
     *处理四六级查询接口
     */
    public function daelCet(){
    }



    /*
     *四六级爬虫接口
     */
    public function httpGetCet(){
        header("content-Type: text/html; charset=utf-8");
        ignore_user_abort();

        $url='http://www.chsi.com.cn/cet/';

        $header[]='Host:www.chsi.com.cn';
        $header[]='Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $header[]='Referer:http://www.chsi.com.cn/cet/';
        $header[]='User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36';
        //$header[]='X-FORWARDED-FOR:120.27.53.164';

        //$header[]='X-Requested-With:XMLHttpRequest';
        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER,true);
        curl_setopt($ch, CURLOPT_NOBODY,true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headerc);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $html=curl_exec($ch);
        curl_close($ch);
        preg_match('#Set\-Cookie: (.*?);#i',$html,$matchs);

        print_r($matchs);

        $url='http://www.chsi.com.cn/cet/query?zkzh=211150152201126&xm=张坤';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
        //curl_setopt($ch, CURLOPT_PROXY, "115.148.71.4:9000"); //代理服务器地址
        curl_setopt($ch,CURLOPT_PROXY,'180.106.229.241:80');
        /* //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式 */
        //curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
        curl_setopt($ch, CURLOPT_COOKIE,$matchs[1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,false);
        curl_exec($ch);
        curl_close($ch);
    }

}
