<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第一排按钮，个人查询控制实现
 */
class StudentsController extends Controller {
    public function show(){
        echo 'nihao';
        /* $students = D('Students'); */
    }

    /*
     *按钮获取课表处理
     */
    public function dealDefaultScore($weChat){

        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom());  //如果没有绑定直接exit
        $scoreVal = $this->hasScore($studentno);   //获取本地课表
        if($scoreVal){
            /* $weChat->text($scoreVal)->reply(); */
            $this->showScore($scoreVal, $weChat);
        }else{
            $password = A('Login')->getPassword($studentno);
            $scoreValue = $this->getScoreFromLink($studentno, $password);
            if($scoreValue){
                /* $weChat->text($scoreValue)->reply(); */
                $this->showScore($scoreValue, $weChat);
            }else{
                //出错可能1.校网崩了。2.获取课表每学期更新的值不匹配，没有返回值。3.用户的密码更换了。4.之后再找其它问题
                $this->showBug($weChat);
            }
        }
    }

    /*
     * 根据学号，密码获取成绩信息
     */
    public function getScoreFromLink($studentno, $password, $time){
        //校网获取课表接口
        //git@github.com:wuxiwei/csxylink.git
        //curl -d 'username=学号&password=密码&action=动作&termstring=时间段' http://yourserver:port/api/grade
        $time = $time == "" ? $this->getTimeString() : $time;
        $student = array(     
            'username'=>$studentno,
            'password'=>$password,
            'action'=>'update',
            'termstring'=>$time
        );      
        $resultJson = http_post(C('CITY_LINK').'grade',$student);
        $resultArr = json_decode($resultJson, true);
        switch ( $resultArr['status'] ) {
        case 'ok':    //登录成功
            $scoreArr['grade'] = $resultArr['grade'];
            if($scoreArr){  //如果课表为空。可能是校网接口需要修改的值变化了
                $score = array(
                    "studentno"=>$studentno,
                    "score"=>json_encode($scoreArr,JSON_UNESCAPED_UNICODE),  //参数作用中文编码
                    "time"=>$time,
                );
                $this->addScore($score);
                return json_encode($scoreArr,JSON_UNESCAPED_UNICODE);   //以JSON格式传入所以也以JSON格式返回
            }else{
                return false; 
            }
            break;
        case 'School network connection failure':  //校网问题
            return false;
            break;
        default:
            return false;
        }

    }

    /*
     *将校网获取的课表存到本地
     */
    public function addScore($score = array()){
        if($score){
            $Score = M('Score');
            $Score->data($score)->add();
        }
    }

    /*
     *检验查询格式
     */

    /*
     *发送成绩格式化
     */
    public function showScore($scoreVal, $weChat, $time = ""){
        $time = $time == "" ? $this->getTimeString() : $time;
        $scoreArr = json_decode($scoreVal, true)['grade'];

        //判断有没有课
        $hava = false;
        $scoreString = "";
        for($i=0 ; $i<count($scoreArr) ; $i++){
            $have = true;
            $scoreString .= "课程名称：".$scoreArr[(String)$i]['课程名称']."\n平时成绩：".$scoreArr[(String)$i]['平时成绩']."    期末成绩：".$scoreArr[(String)$i]['期末成绩']."\n课程成绩：".$scoreArr[(String)$i]['课程成绩']."\n\n";
        }
        if(!$have){
            $scoreString .= "课程还没有出来，敬请期待！\n\n";
        }
        $scoreString .= "回复【成绩】查看更多学期课表";

        $scoreSend = array();  
        $scoreTop = array(
            'Title'=>' '.$time,
        );
        $scoreSend[] = $scoreTop;
        $scoreBack = array(
            'Title'=>$scoreString,
        );
        $scoreSend[] = $scoreBack;

        $weChat->news($scoreSend)->reply();
    }

    /*
     *判断本地是否有成绩，有返回成绩
     *@param string $studentno 学号
     */
    public function hasScore($studentno = "", $time = ""){
        $time = $time == "" ? $this->getTimeString() : $time;
        $Score = M('Score');
        $where['studentno']=$studentno;
        $where['time']=$time;
        $scoreVal = $Score->where($where)->find();
        if($scoreVal){
            return $scoreVal['score'];
        }else{
            /* return $Score->getLastSql(); */
            return false;
        }
    }

    /*
     *获取最新的课表时间，即上学期时间段
     */
    public function getTimeString(){
        $Date_1=date("Y-m-d");  //获取当天时间
        $Date_2 = $this->calcTestWeek();   //获取考试周时间
        $d1 = strtotime($Date_1);
        $d2 = strtotime($Date_2);

        $year = date('Y', $d2);
        $mouth = date('m', $d2);
        if($mouth>0 && $mouth<3)$num = 2;    //查上学期成绩
        if($mouth>5 && $mouth<9)$num = 1;
        $timeString = (String)($year-1).'-'.(String)($year).'学年第'.$num.'学期'; 
        return $timeString;
    }

    /*
     *计算考试周日期
     *@param
     */
    public function calcTestWeek(){
        $Date_1 = C('START_DATE_OF_SCHOOL');
        $d1 = strtotime($Date_1);
        $d2 = $d1 + 18*7*24*3600 + 24*3600;  //开学时间加上18周时间+1天时间
        $testWeek = date('Y-m-d', $d2);
        return $testWeek;
    }

    /*
     *获取课表处理
     */
    public function dealSchedule($weChat,$offset = 0){
        /* public function dealSchedule(){ */
        /*     $studentno = I('s'); */
        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom());  //如果没有绑定直接exit

        $scheduleVal = $this->hasSchedule($studentno);   //获取本地课表
        if($scheduleVal){
            $this->showSchedule($studentno, $weChat, $scheduleVal, $offset);
            /* $this->matchSchedule($scheduleVal); */
        }else{
            $password = A('Login')->getPassword($studentno);
            $scheduleValue = $this->getScheduleFromLink($studentno, $password);
            if($scheduleValue){
                /* $weChat->text($scheduleValue)->reply(); */
                $this->showSchedule($studentno, $weChat, $scheduleValue, $offset);
            }else{
                //出错可能1.校网崩了。2.获取课表每学期更新的值不匹配，没有返回值。3.用户的密码更换了。4.之后再找其它问题
                $this->showBug($weChat);
            }
        }
    }

    /*
     *大bug回复
     */
    public function showBug($weChat){
        $class = array(
            "0"=>array(
                'Title'=>'抱歉：出问题了 （┬＿┬）↘',
            ),
            "1"=>array(
                'Title'=>"建议先试试【贴心帮助】->【信息更新】\n如果不能解决问题希望可以加入QQ308407868反馈群告诉给我们，我们会为大家尽快处理，谢谢支持。",
            ),
        );
        $weChat->news($class)->reply();
    }

    /*
     *发送课表模版
     *@param $offset 判断符 0 => 今天 1 => 明天  2=> 后天
     */
    public function showSchedule($studentno, $weChat, $scheduleJson, $offset = 0){
        $dateMonth = date("m月d日",strtotime('+'.$offset.' day'));   //获得查询日期中文
        $week = $this->calcWeek(date("Y-m-d",strtotime('+'.$offset.' day')));      //获得查询日期的周数，日期同上处理
        $dateWeek = ' 周'.$this->getWeek(date("Y-m-d",strtotime('+'.$offset.' day')), 'cn');   //星期几  中文
        if(0 < $week && $week < 21){
            $scheduleSend = array();

            $topArray = array(
                'Title' => "第".$week."周（".$dateMonth.$dateWeek."）",
            );

            $scheduleSend[] = $topArray;   //添加头

            //判断有没有课
            $hava = false;
            $searchSchedule = $this->matchSchedule($scheduleJson, $offset);   //获取到某一天的全部课程
            foreach($searchSchedule as $key => $value){
                $scheduleStr = $this->detailOne($key, $value);
                $scheduleArr = array(
                    'Title' => $scheduleStr, 
                );
                $scheduleSend[] = $scheduleArr;   //添加课程
                $have = true;
            }

            if(!$have){
                $haveNo = array(
                    'Title'=>'今日没有课呢~快出去看看吧',
                );
                $scheduleSend[] = $haveNo;   //添加没有课程
            }

            $auth = replaceStr(authcode($studentno,'ENCODE','',604800));
            $allWeek = array(
                'Title'=>'点此chuo进一周课表 ^_^|||',
                'Url'=> $_SERVER['HTTP_HOST'].U("Students/weekSchedule?auth=$auth&week=$week")
            );
            $scheduleSend[] = $allWeek;   //添加尾巴
            $weChat->news($scheduleSend)->reply();
        }else{
            $weChat->text("放假阶段，开开心心玩耍吧！(∩＿∩)")->reply();
        }
    }

    /*
     *查询周课表提示
     */
    public function showWeekSchedule($weChat, $offset = 0){
        $week = $this->calcWeek(date("Y-m-d",strtotime('+'.$offset.' day')));      //获得查询日期的周数，日期同上处理
        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom());
        $auth = replaceStr(authcode($studentno,'ENCODE','',604800));
        $bind = array(
            "0"=>array(
                'Title'=>'周课表',
                'Description'=>"可以把这条消息加入收藏哦！直接打开就能看",
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/schedule.png',
                'Url'=> $_SERVER['HTTP_HOST'].U("Students/weekSchedule?auth=$auth&week=$week")
            ),
         );
        $weChat->news($bind)->reply();

    }

    /*
     *单节课处理样式 加时间判断
     */
    public function detailOne($key, $value){
        switch ( $key ) {
        case '1-2':
            $time = strpos($value['classrom'], '教学楼') !== false ? "8:05-9:45" : "8:05-9:40";
            break;
        case '3-4':
            $time = strpos($value['classrom'], '教学楼') !== false ? "10:10-11:50" : "8:05-9:40";
            break;
        case '5-6':
            $time = strpos($value['classrom'], '教学楼') !== false ? "13:30-15:10" : "13:30-15:10";
            break;
        case '7-8':
            $time = strpos($value['classrom'], '教学楼') !== false ? "15:25-17:05" : "15:25-17:05";
            break;
        case '9-10':
            $time = strpos($value['classrom'], '教学楼') !== false ? "18:00-19:10" : "18:00-19:10";
            break;
        case '11-12':
            $time = strpos($value['classrom'], '教学楼') !== false ? "19:20-20:25" : "19:20-20:25";
            break;
        }
        return $scheduleStr = "时间：".$key."节课  [".$time."]\n课程：".$value["class_name"]."\n授课：".$value["teacher_name"]."  教室：".$value["classrom"];
    }

    /*
     *处理课表。获取到某一天所有的课程
     *@param retunr array
     */
    public function matchSchedule($scheduleJson, $offset = 0){
        $searchSchedule = array();
        $week = $this->calcWeek(date("Y-m-d",strtotime('+'.$offset.' day')));      //获得查询日期的周数
        $dateWeekEn = $this->getWeek(date("Y-m-d",strtotime('+'.$offset.' day')), 'en');   //星期几 英语
        $scheduleArr=json_decode($scheduleJson, true); 
        for($i=0 ; $i<6 ; $i++){   //六节课
            $scheduleDay = $scheduleArr[$dateWeekEn][(String)($i*2+1).'-'.(String)($i*2+2)];
            for($j=0 ; $j<count($scheduleDay) ; $j++){
                if(in_array($week, $scheduleDay[(String)$j]["weeks"])){    //判断当前周有没有这节课
                    $searchSchedule[(String)($i*2+1).'-'.(String)($i*2+2)] = $scheduleDay[(String)$j];
                }
            }
        }
        return $searchSchedule;
    }


    /*
     *显示中文星期几，处理  $Lang = cn
     *提供英语星期前三字符，处理   $Lang = en
     */
    public function getWeek($date, $Lang)
    {
        if($date == 0){
            $date=date("Y-m-d");
        }
        if($Lang == 'cn'){
            $arr = array('天','一','二','三','四','五','六');
        }else{
            $arr = array('Sun','Mon','Tue','Wed','Thu','Fri','Sat');
        }
        return $arr[date('w',strtotime($date))];
    }

    /*
     *计算当前周数
     *@param $date
     */
    public function calcWeek($Date_1 = 0){
        if($Date_1 == 0){
            $Date_1=date("Y-m-d");
        }
        $Date_2 = C('START_DATE_OF_SCHOOL');
        $d1 = strtotime($Date_1);
        $d2 = strtotime($Date_2);
        $week = ceil(($d1-$d2)/3600/24/7);
        return $week;
    }

    /*
     * 根据学号，密码获取课表信息
     */
    public function getScheduleFromLink($studentno, $password){
        //校网获取课表接口
        //git@github.com:wuxiwei/csxylink.git
        //curl -d 'username=学号&password=密码&action=动作' http://yourserver:port/api/schedule 
        $student = array(     
            'username'=>$studentno,
            'password'=>$password,
            'action'=>'update'
        );      
        $resultJson = http_post(C('CITY_LINK').'schedule',$student);
        $resultArr = json_decode($resultJson, true);
        switch ( $resultArr['status'] ) {
        case 'ok':    //登录成功
            $scheduleArr = $resultArr['schedule'];
            if($scheduleArr){  //如果课表为空。可能是校网接口需要修改的值变化了
                $schedule = array(
                    "studentno"=>$studentno,
                    "schedule"=>json_encode($scheduleArr,JSON_UNESCAPED_UNICODE),  //参数作用中文编码
                );
                $this->addSchedule($schedule);
                return json_encode($scheduleArr,JSON_UNESCAPED_UNICODE);   //以JSON格式传入所以也以JSON格式返回
            }else{
                return false; 
            }
            break;
        case 'School network connection failure':  //校网问题
            return false;
            break;
        default:
            return false;
        }

    }

    /*
     *将校网获取的课表存到本地
     */
    public function addSchedule($schedule = array()){
        if($schedule){
            $Schedule = M('Schedule');
            $Schedule->data($schedule)->add();
        }
    }

    /*
     *判断本地是否有课表
     *@param string $studentno 学号
     */
    public function hasSchedule($studentno = ""){
        $Schedule = M('Schedule');
        $where['studentno']=':studentno';
        $scheduleVal = $Schedule->where($where)->bind(':studentno',$studentno)->find();
        if($scheduleVal){
            return $scheduleVal['schedule'];
        }else{
            return false;
        }
    }

    /*
     *周课表信息
     */
    public function weekSchedule(){
        $authGet = I('get.auth','');  
        $week = I('get.week',$this->calcWeek());
        $auth = replaceStr($authGet, false);  //将替换的字符换回来
        $studentno = authcode($auth,'DECODE');   //只有带学号请求才是有效的
        if(!$studentno){
           echo "<h3>链接失效请重新获取课表</h3>";
            exit;
        }
        if($week>20&&$week<0){
           echo "<h3>放假阶段，开开心心玩耍吧！(∩＿∩)</h3>";
           exit;
        }
        $scheduleJson = $this->hasSchedule($studentno);   //获取本地课表
        if(!$scheduleJson){
           echo '<h3>'.$studentno.'没有课表数据！</h3>';
        }
        $scheduleArr=json_decode($scheduleJson, true); 

        $weekDay = array("Mon","Tue","Wed","Thu","Fri","Sat","Sun");
        $res = array();
        for($d=0 ; $d<7 ; $d++){   //周几 共七天
            for($s=0 ; $s<6 ; $s++){    //第几节课 共六节课
                $scheduleDay = $scheduleArr[$weekDay[$d]][(String)($s*2+1).'-'.(String)($s*2+2)];
                for($j=0 ; $j<count($scheduleDay) ; $j++){
                    if(in_array($week, $scheduleDay[(String)$j]['weeks'])){    //判断当前周有没有这节课
                        $res['s'.(String)($s*7+$d)] = '<pre>'.$scheduleDay[(String)$j]['class_name'].'<br/>'.$scheduleDay[(String)$j]['teacher_name'].'<br/>'.$scheduleDay[(String)$j]['classrom'].'</pre>';    //一维数组赋值
                    }
                }
                
            }
        }

        $res['auth'] = $authGet;
        $res['week'] = $week;

        $this->assign($res);
        $this->display();
    }

    /*
     *处理考试时间接口
     */
    public function dealTeam($weChat){
        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom());
        $week = $this->calcWeek(date("Y-m-d",strtotime('+0day')));      //获得查询日期的周数，日期同上处理
        if(0 < $week && $week < 21){
            $weChat->text("当前不是考试周！学习不是一朝一夕的事情，需要平时积累，需要平时的勤学苦练。我们一起加油！")->reply();
        }else{
            //考试安排开发
        }
        exit;
    }
}
