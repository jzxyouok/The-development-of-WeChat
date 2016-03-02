<?php
namespace Home\Controller;
use Think\Controller;
/*
 * 默认用来处理文本的接口类，同时处理语音（转换为文本）
 */
class TextController extends Controller {

    public function index(){
        /* $this->display(); */
    }

    public $route_list=array(
        "kuaidi"=>"A('Campus')->resetExpress",
        "exit"=>"A('Help')->exitHelp",
        "closehelp"=>"assistor_close_help",
        "test"=>"assistor_test",
        "bd"=>"assistor_student_bind",
        "jc"=>"assistor_student_remove",
        "tscx"=>"assistor_lib",
        "mxp"=>"assistor_gift",
        "ks"=>"assistor_exam",
        "news"=>"assistor_news",
        "notice"=>"assistor_notice",
        "cet"=>"assistor_cet",
        "joke"=>"assistor_joke",
        "weather"=>"assistor_weather",
        "fj"=>"assistor_holiday",
        "cj"=>"assistor_grade",
        "kb"=>"assistor_kb",
        "st"=>"assistor_canteen",
        "qbst"=>"assistor_canteen_all",
        "teacherbd"=>"assistor_teacher_bind",
        "week"=>"assistor_week_kb",
        "general_course"=>"assistor_general_course",
        "citybox"=>"assistor_citybox",
        "vod"=>"assistor_vod",
        "statistic_st"=>"assistor_count_st",
        "order_food"=>"assistor_order_food",
        "wall"=>"assistor_wall",
        "random_food"=>"assistor_random_food",
        "game"=>"assistor_game",
    );

    public function dealText($weChat){
        $mesText = $weChat->getRevContent();
        $operate=$this->reduce_route($mesText);
        $funcname = $operate[0];
        $params = $operate[1];
        
        //先判断有没有直接功能需要实现
        if(isset($this->route_list[$funcname])){
            if(count($operate) == 1){   //数组一位时代表没有参数
                eval($this->route_list[$funcname].'($weChat);');   //eval将字符串转换为效的代码执行，必须以;结尾
            }
            else{
                $this->route_list[$funcname]($params);
            }
        }else{
            //查看缓存中是否有操作
            switch ( S($weChat->getRevFrom().'_do') ) {
            case 'express':
                if(isExpress($weChat->getRevContent())){
                    A('Campus')->checkExpress($weChat, $weChat->getRevContent());
                    S($weChat->getRevFrom().'_do',null);   //删除操作缓存
                    S($weChat->getRevFrom().'_spress',$weChat->getRevContent(),'86400');   //将单号存入缓存，默认一天时间
                }else{
                    $weChat->text("单号无法识别，重新输入。\n回复【exit】退出操作。")->reply();
                }
                break;
            case 'unbind':
                if($weChat->getRevContent() == '确认'){
                    A('Login')->doLogout($weChat);  //调用解绑方法
                    break;
                }  //如果回复的不是确认。则执行后续的帮助提示
            default:
                $weChat->text("有疑问，请输入关键词【帮助】查询。\n也可以直接回复消息，主页君不定时上线 ^_^|||")->reply();
            }
        }
    }


    public function reduce_route($form_Content){
        if(strtolower($form_Content)=="help"){
            return array("help");
        }
        if(strtolower($form_Content)=="exit" || strtolower($form_Content)=="q"){
            return array("exit");
        }
        if(strtolower($form_Content)=="test"){
            return array("test");
        }
        if( $form_Content=="绑定" || strtolower($form_Content)=="bd" ){
            return array("bd");
        }
        if( $form_Content=="解除" || $form_Content=="取消绑定" || $form_Content=="解除绑定" || strtolower($form_Content)=="jc" ){
            return array("jc");
        }
        if( $form_Content=="图书" || strtolower($form_Content)=="tscx" || $form_Content=="图书馆" || $form_Content=="书籍查询" || $form_Content=="图书查询"){
            return array("tscx");
        }
        if($form_Content=="我要明信片" ||$form_Content=="纪念品" || $form_Content=="明信片" || strtolower($form_Content)=="mxp"){
            return array("mxp");
        }
        if( $form_Content=="考试时间" || $form_Content=="考试" || strtolower($form_Content)=="ks" || strtolower($form_Content)=="exam" ){
            return array("ks");
        }
        if ($form_Content=="新闻" || $form_Content=="学院新闻" || strtolower($form_Content)=="news"){
            return array("news");
        }
        if ($form_Content=="通知" || $form_Content=="公告"|| $form_Content=="学院公告" || strtolower($form_Content)=="notice"){
            return array("notice");
        }
        if(strtolower($form_Content)=="cet" || $form_Content=="四六级" || $form_Content=="四六级成绩"|| $form_Content=="四级" || $form_Content=="六级"){
            return array("cet");
        }
        if( $form_Content=="快递" || $form_Content=="快递查询" || strtolower($form_Content)=="kd" || strtolower($form_Content)=="kuaidi" ){
            return array("kuaidi");
        }
        if( $form_Content=="哈哈" || $form_Content=="笑话" || strtolower($form_Content=="joke") ){
            return array("joke");
        }
        if($form_Content=="天气" || $form_Content=="大连天气" || $form_Content=="明天天气" || strtolower($form_Content)=="weather" ){
            return array("weather");
        }
        if($form_Content=="放假" || $form_Content=="放假时间" || strtolower($form_Content)=="fjxx" || strtolower($form_Content)=="fj" ){
            return array("fj");
        }
        if($form_Content=="历史成绩" || $form_Content=="上学期成绩" || strtolower($form_Content)=="cjcx" || $form_Content=="成绩"){
            return array("cj", -1);
        }
        if($form_Content=="本学期成绩" || $form_Content=="最新成绩"){
            return array("cj", 1);
        }
        if($form_Content=="课表" || $form_Content=="今天课表" || $form_Content=="今天" || strtolower($form_Content)=="jtkb" || strtolower($form_Content)=="kb"){
            return array("kb", 0);
        }
        if($form_Content=="明天" || $form_Content=="明天课表" || strtolower($form_Content)=="mt"){
            return array("kb", 1);
        }
        if($form_Content=="后天" || $form_Content=="后天课表" || strtolower($form_Content)=="ht"){
            return array("kb", 2);
        }
        if(strtolower($form_Content)=="st" || $form_Content=="食堂" || $form_Content=="订餐电话"){
            return array("st");
        }
        if(strtolower($form_Content)=="ls" || strtolower($form_Content)=="all" || $form_Content=="所有食堂" || $form_Content=="全部食堂" ){
            return array("qbst");
        }
        if( $form_Content=="教师绑定" || strtolower($form_Content)=="teacher" ){
            return array("teacherbd");
        }
        if( $form_Content=="周课表" || strtolower($form_Content)=="week" ){
            return array("week");
        }
        if( $form_Content=="通识课" || $form_Content=="选修课" || $form_Content=="选修" ){
            return array("general_course");
        }
        if( $form_Content=="城院盒子" || $form_Content=="盒子" || strtolower($form_Content)=="citybox" ){
            return array("citybox");
        }
        if( $form_Content=="vod更新" || $form_Content=="vod视频" || strtolower($form_Content)=="vod" ){
            return array("vod");
        }
        if( stristr($form_Content,"档口") ){
            return array("statistic_st");
        }
        if( $form_Content=="订餐" ){
            return array("order_food");
        }
        if( stristr($form_Content,"表白") || stristr($form_Content,"吐槽") || stristr($form_Content,"心愿") || stristr($form_Content,"墙")){
            return array("wall");
        }
        if( $form_Content=="今天吃什么" ){
            return array("random_food");
        }
        if( $form_Content=="game" ){
            return array("game");
        }
    }
}
