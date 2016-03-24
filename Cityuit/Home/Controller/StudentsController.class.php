<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第一排按钮，个人查询控制实现
 */
class StudentsController extends Controller {
    public function index(){

    }

    /*
     *获取课表处理
     */
    public function dealScore($weChat, $type = 0){

        if($type == 0){
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
        }else{
            $weChat->text("请输入查询年级\n------------------\n范例：大一上\n\n回复【exit】退出操作")->reply();
            S($weChat->getRevFrom().'_do','score','180');      //提醒输成绩时间预设3分钟 
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
            'action'=>'update',  //更新的方式查询
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
     *文本查询成绩时返回成绩
     */
    public function getScore($weChat, $time){
        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom());  //如果没有绑定直接exit
        $termString = $this->checkTermString($studentno, $time);
        if($termString){
            $scoreVal = $this->hasScore($studentno, $termString);   //获取本地课表
            if($scoreVal){
                /* $weChat->text($scoreVal)->reply(); */
                $this->showScore($scoreVal, $weChat, $termString, 1);   //发送成绩按照模版
            }else{
                $password = A('Login')->getPassword($studentno);
                $scoreValue = $this->getScoreFromLink($studentno, $password, $termString);
                if($scoreValue){
                    /* $weChat->text($scoreValue)->reply(); */
                    $this->showScore($scoreValue, $weChat, $termString, 1);
                }else{
                    //出错可能1.校网崩了。2.获取课表每学期更新的值不匹配，没有返回值。3.用户的密码更换了。4.之后再找其它问题
                    $this->showBug($weChat);
                }
            }
        }else{
            $weChat->text("成绩查询格式错误\n--------------------\n范例：大一上\n\n回复【exit】退出操作")->reply();
        }
        
    }

    /*
     *检验查询格式
     */
    public function checkTermString($studentno, $time = ""){
        $year = substr($studentno, 0, 4);
        switch ( $time ) {
            case '大一上':
                $termstring = (String)$year."-".(String)($year+1)."学年第1学期";
                return $termstring;
                break;
            case '大一下':
                $termstring = (String)$year."-".(String)($year+1)."学年第2学期";
                return $termstring;
                break;
            case '大二上':
                $termstring = (String)($year+1)."-".(String)($year+2)."学年第1学期";
                return $termstring;
                break;
            case '大二下':
                $termstring = (String)($year+1)."-".(String)($year+2)."学年第2学期";
                return $termstring;
                break;
            case '大三上':
                $termstring = (String)($year+2)."-".(String)($year+3)."学年第1学期";
                return $termstring;
                break;
            case '大三下':
                $termstring = (String)($year+2)."-".(String)($year+3)."学年第2学期";
                return $termstring;
                break;
            case '大四上':
                $termstring = (String)($year+3)."-".(String)($year+4)."学年第1学期";
                return $termstring;
                break;
            case '大四下':
                $termstring = (String)($year+3)."-".(String)($year+4)."学年第2学期";
                return $termstring;
                break;
            default:
                return false;
        }
    }

    /*
     *发送成绩格式化
     *@param $type => 0 表示按钮返回数据 $type => 1 表示文字返回数据
     */
    public function showScore($scoreVal, $weChat, $time = "", $type = 0){
        $time = $time == "" ? $this->getTimeString() : $time;
        $scoreArr = json_decode($scoreVal, true)['grade'];

        //判断有没有课
        $hava = false;
        $scoreString = "";
        $name = "";
        for($i=0 ; $i<count($scoreArr) ; $i++){
            $have = true;
            if($name != $scoreArr[(String)$i]['课程名称']){
                $scoreString .= "课程名称：".$scoreArr[(String)$i]['课程名称']."\n平时成绩：".$scoreArr[(String)$i]['平时成绩']."    期末成绩：".$scoreArr[(String)$i]['期末成绩']."\n课程成绩：".$scoreArr[(String)$i]['课程成绩']."    课程学分：".$scoreArr[(String)$i]['学分']."\n\n";
                $name = $scoreArr[(String)$i]['课程名称'];
            }else{
                $scoreString .= "课程名称：".$scoreArr[(String)$i]['课程名称']." 【重修】\n平时成绩：".$scoreArr[(String)$i]['平时成绩']."    期末成绩：".$scoreArr[(String)$i]['期末成绩']."\n课程成绩：".$scoreArr[(String)$i]['课程成绩']."    课程学分：".$scoreArr[(String)$i]['学分']."\n\n";
            }
        }
        if(!$have){
            $scoreString .= "课程还没有出来，敬请期待！\n\n";
        }
        if($type == 0){
            $scoreString .= "回复【成绩】查看更多学期课表";
        }else if($type == 1){
            $scoreString .= "请继续查询成绩\n回复【exit】退出操作";
        }


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
        $time = $time == "" ? $this->getTimeString() : $time;   //没有使用默认上学期时间查询
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
                'Title'=>">网络延迟：建议再试一次\n>密码已修改：试试【信息更新】\n>校网处于维护：等待:(\n\n如果都无法解决希望可以加入QQ群308407868反馈，我们会为大家尽快处理，谢谢支持。",
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
            $time = strpos($value['classrom'], '教学楼') !== false ? "10:10-11:50" : "10:00-11:35";
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
            'action'=>'update'   //已更新的方式查询
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
                return json_encode($scheduleArr, JSON_UNESCAPED_UNICODE);   //以JSON格式传入所以也以JSON格式返回
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


    /*
     *查自习处理
     */
    public function askClass($weChat){
        $weChat->text("请输入哪节小课（1~12）\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','emptyroom','120');      //提醒输入图书时间预设2分钟 
        exit;
    }

    /*
     *验证查自习的格式，正确则返回数据
     */
    public function checkClass($weChat, $class){
        $c = array(1,2,3,4,5,6,7,8,9,10,11,12);
        if(in_array($class, $c)){   //如果格式正确
            $this->dealSelfRoom($weChat, $class);
            S($weChat->getRevFrom().'_do',null);
        }else{
            $weChat->text("查询出错，请输入1~12之间的值\n\n或输入【exit】退出操作。")->reply();
            exit;
        }
    }

    /*
     *处理自习室查询
     */
    public function dealSelfRoom($weChat, $class = 0){
    /* public function dealSelfRoom(){ */
        $JX = S('JXL') ? S('JXL') : $this->backAllRoom(0);    //先获取所有空教室，缓存有就缓存取，没有就数据库拿
        $SY = S('SYL') ? S('SYL') : $this->backAllRoom(1);
        /* $JX = $this->backAllRoom(0);    //想要更新缓存的数据，使用这两条 */
        /* $SY = $this->backAllRoom(1); */
        if($class == 0){
            $timeNow = time() - strtotime(date("Y-m-d"));   //现在时间。
            /* $timeNow = strtotime("2016-03-11 11:40:00") - strtotime('2016-03-11');   //现在时间。 */
            if($timeNow < 29100 || $timeNow > 73500 || ($timeNow > 42600 && $timeNow < 48600)){   //早上，午饭，晚上时间
                if($emptyArr = json_decode(S('empty_'.$class), true)){     //先从缓存获取，如果有直接发送
                    $this->showEmptyroom($weChat, $emptyArr, $classJX);     //将数据传给显示模版
                }else{
                    //缓存都没有教室的课的信息
                    $emptyArr = $this->formatEmptyroom($JX, $SY);         //获取格式化后的数据
                    S('empty_'.$class, json_encode($emptyArr), 86400);    //默认缓存一天，但是12点自动清空
                    $this->showEmptyroom($weChat, $emptyArr);     //将数据传给显示模版
                }
            }else{
                $week = $this->calcWeek();    //当前周
                $day = date('w',strtotime(date("Y-m-d")));
                $class = $this->backClass();
                $classJX = substr($class,0,1);   //获取教学楼的上哪节课
                $classSY = substr($class,2,1);   //获取实验楼的上哪节课
                if($classJX == $classSY){    //如果查寻时间教学楼和实验楼小节数一样，查一次即可
                    if($emptyArr = json_decode(S('empty_'.$classJX), true)){     //先从缓存获取，如果有直接发送
                        $this->showEmptyroom($weChat, $emptyArr, $classJX);     //将数据传给显示模版
                    }else{
                        $roomJX = $roomSY = $this->backClassroom($week, $day, $classJX);
                        $roomArr = $this->assortment($roomJX);
                        $emptyJXArr = $this->array_diff_fast($JX, $roomArr['JX']);    //函数用于取差，所有教室减去上课的教室
                        $emptySYArr = $this->array_diff_fast($SY, $roomArr['SY']);
                        $emptyArr = $this->formatEmptyroom($emptyJXArr, $emptySYArr);         //获取格式化后的数据
                        S('empty_'.$classJX, json_encode($emptyArr), 86400);    //默认缓存一天，但是12点自动清空
                        $this->showEmptyroom($weChat, $emptyArr);     //将数据传给显示模版
                    }
                }else if($classSY == 0){     //中午存在某时刻实验楼没有课，教学楼有课
                    $roomJX = $this->backClassroom($week, $day, $classJX);
                    $roomArr = $this->assortment($roomJX);    //只取教学楼的科
                    $emptyJXArr = $this->array_diff_fast($JX, $roomArr['JX']);    //只取教学楼的课
                    $emptyArr = $this->formatEmptyroom($emptyJXArr, $SY);         //获取格式化后的数据
                    $this->showEmptyroom($weChat, $emptyArr);     //将数据传给显示模版
                }else{
                    //因为实验课和教学课上课时间不同，所以分开处理。
                    //获取教学楼上课的教室。
                    $roomJX = $this->backClassroom($week, $day, $classJX);
                    $roomArr = $this->assortment($roomJX);
                    $emptyJXArr = $this->array_diff_fast($JX, $roomArr['JX']);    //函数用于取差，所有教学楼教室减去上课的教室
                    
                    $roomSY = $this->backClassroom($week, $day, $classSY);
                    $roomArr = $this->assortment($roomSY);
                    $emptySYArr = $this->array_diff_fast($SY, $roomArr['SY']);     //实验楼课

                    $emptyArr = $this->formatEmptyroom($emptyJXArr, $emptySYArr);         //获取格式化后的数据
                    $this->showEmptyroom($weChat, $emptyArr);     //将数据传给显示模版
                }
            }
        }else{
            if($emptyArr = json_decode(S('empty_'.$class), true)){     //先从缓存获取，如果有直接发送
                $this->showEmptyroom($weChat, $emptyArr, $class);     //将数据传给显示模版
            }else{
                $week = $this->calcWeek();    //当前周
                $day = date('w',strtotime(date("Y-m-d")));
                $roomJX = $roomSY = $this->backClassroom($week, $day, $class);
                $roomArr = $this->assortment($roomJX);
                $emptyJXArr = $this->array_diff_fast($JX, $roomArr['JX']);    //函数用于取差，所有教室减去上课的教室
                $emptySYArr = $this->array_diff_fast($SY, $roomArr['SY']);
                $emptyArr = $this->formatEmptyroom($emptyJXArr, $emptySYArr);         //获取格式化后的数据
                /*
                 *小节数相同的课的空教室缓存处理
                 *如果已经有缓存，返回该小节数据
                 *如果不存在缓存，返回false，调用处进行缓存处理
                 *缓存会在每天12点清空，继续开始存取新一天的数据
                 */
                S('empty_'.$class, json_encode($emptyArr), 86400);    //默认缓存一天，但是12点自动清空
                $this->showEmptyroom($weChat, $emptyArr, $class);     //将数据传给显示模版
            }
        }
    }
    public function getarr(){
        $class = I('in');
        $emptyArr = S('empty_'.$class);
        dump(json_decode($emptyArr));
    }

    /*
     *删除空教室缓存接口，每天24点定时执行
     */
    public function delEmpty(){
        for($i=0; $i<13; $i++){
            S('empty_'.$i,null);
        }
    }

    /*
     *根据周、星期、课返回上课教室
     */
    public function backClassroom($week, $day, $class){
        $Techroom = M('Techroom');
        $where['week'] = $week;
        $where['day'] = $day;
        /* $where['day'] = $day-1;    //测试时间记得将星期和周调到和测试时间一致 */
        $where['class'] = $class;
        return $Techroom->where($where)->getField('room');
    }

    /*
     *将数组不同部分以数组形式返回
     */
    public function array_diff_fast($data1, $data2) {
        $data1 = array_flip($data1);
        $data2 = array_flip($data2);
        foreach($data2 as $hash => $key) {
            if (isset($data1[$hash])) unset($data1[$hash]);
        }
        return array_flip($data1);
    } 

    /*
     *负责自习室格式化，分类
     */
    public function formatEmptyroom($JX, $SY){
        $JX = array_merge($JX);     //经过筛选的教室，可能下标没有对齐，导致for循环终止，
        $SY = array_merge($SY);     //array_merge传入一个参数时会将下标重新排序
        for($i=0 ; $i<count($JX) ; $i++){ 
            $area = substr($JX[$i],0,1);   //获取教学楼1区和2区
            if($area == '1'){
                $JXJS1 .= $JX[$i]." ";   //1区
            }else if($area == '2'){
                $JXJS2 .= $JX[$i]." ";
            }
        }
        for($i=0 ; $i<count($SY) ; $i++){
            if(strpos($SY[$i], "机房") !== false){    //该教室为教学楼机房
                $SYJF .= str_replace("机房", '', $SY[$i])." ";
            }else if(strpos($SY[$i], "语音室") !== false){
                $SYYYS .= str_replace("语音室", '', $SY[$i])." ";
            }else if(strpos($SY[$i], "JT") !== false){
                $SYJT .= str_replace("JT", '', $SY[$i])." ";
            }else{
                $SYJS .= $SY[$i]." ";
            }
        }
        $emptyJXJS1 = array(
            'Title'=>"教学楼1区：\n".$JXJS1,
        );
        $emptyJXJS2 = array(
            'Title'=>"教学楼2区：\n".$JXJS2,
        );
        $emptySYJS = array(
            'Title'=>"实验楼教室：\n".$SYJS,
        );
        $emptySYJF = array(
            'Title'=>"实验楼机房：\n".$SYJF,
        );
        $emptySYJT = array(
            'Title'=>"实验楼阶梯：\n".$SYJT,
        );
        $emptySYYYS = array(
            'Title'=>"实验楼语音室：\n".$SYYYS,
        );
        $emptyArr['JXJS1'] = $emptyJXJS1;
        $emptyArr['JXJS2'] = $emptyJXJS2;
        $emptyArr['SYJF'] = $emptySYJF;
        $emptyArr['SYYYS'] = $emptySYYYS;
        $emptyArr['SYJT'] = $emptySYJT;
        $emptyArr['SYJS'] = $emptySYJS;
        return $emptyArr;    //返回格式化二维数组
    }

    /*
     *负责发送自习室内容的模版
     *@param array $emptyArr 经过格式化后的所有空教室
     */
    public function showEmptyroom($weChat, $emptyArr, $class = 0){
        //发送装载
        $emptyMes = Array();
        if($class != 0){
            $top = array(
                'Title'=>"今天第".$class."小节没课的教室有：",
            );
        }else{
            $top = array(
                'Title'=>"当前没课的教室有：",
            );
        }
        $end = array(
            'Title'=>"回复【查自习】查看任意节课空教室",
        );
        $emptyMes[] = $top;
        $emptyMes[] = $emptyArr['JXJS1'];
        $emptyMes[] = $emptyArr['JXJS2'];
        $emptyMes[] = $emptyArr['SYJS'];
        $emptyMes[] = $emptyArr['SYJF'];
        $emptyMes[] = $emptyArr['SYJT'];
        $emptyMes[] = $emptyArr['SYYYS'];
        $emptyMes[] = $end;
        $weChat->news($emptyMes)->reply();
    }

    /*
     *将数据库数据字符串，分类（实验楼和教学楼）
     *return array
     */
    public function assortment($roomString){
        $roomArr = explode(",", $roomString); 
        for($i=0 ; $i<count($roomArr)-1 ; $i++){
            if(strpos($roomArr[$i], "教学楼") !== false){    //该教室为教学楼教室
                $Room['JX'][] = str_replace("教学楼", '', $roomArr[$i]);
            }else if(strpos($roomArr[$i], "实验楼") !== false){
                $Room['SY'][] = str_replace('.', '', str_replace("实验楼", '', $roomArr[$i]));   //全校课表中个别教室后面添加了"."，为了美观加了过滤
            }
        }
        /* dump($Room); */
        return $Room;
    }

    /*
     *返回所有教室
     *@param $key = 0 返回教学楼所有教室 反之实验楼
     */
    public function backAllRoom($key = 0){
        $Classroom = M('Classroom');
        $roomAll = $Classroom->where(array("key"=>'room'))->getField('value');
        $roomArr = json_decode($roomAll, true);
        sort($roomArr);    //对数组排序
        for($i=0 ; $i<count($roomArr) ; $i++){
            if(strpos($roomArr[$i], "教学楼") !== false){    //该教室为教学楼教室
                $JX[] = str_replace("教学楼", '', $roomArr[$i]);
            }else if(strpos($roomArr[$i], "实验楼") !== false){
                $SY[] = str_replace('.', '', str_replace("实验楼", '', $roomArr[$i]));   //全校课表中个别教室后面添加了"."，为了美观加了过滤
            }
        }
        S('JXL',$JX,'86400');      //将所有的教室缓存，不用每次都去数据库查询，因为数据永远不会变化，所以时间可以久点 
        S('SYL',$SY,'86400');      //将所有的教室缓存，不用每次都去数据库查询，因为数据永远不会变化，所以时间可以久点 
        if($key == 0){
            return $JX;
        }else{
            return $SY;
        }
    }

    /*
     *返回当前时间第几节课
     *例：1,1 => 教学楼第一节，实验课第一节    4,0 => 教学楼第四节，实验楼没课
     */
    public function backClass(){
        $back = "";
        $timeNow = time() - strtotime(date("Y-m-d"));   //现在时间。
        /* $timeNow = strtotime("2016-03-11 09:43:00") - strtotime('2016-03-11');   //测试时间。 */
        //预设教学楼每天的每节课上下课时间戳
        $JXTime = array(29100, 31800, 32400, 35100, 36600, 39300, 39900, 42600, 48600, 51300, 51900, 54600, 55500,
                        58200, 58800, 61500, 64800, 66900, 66900, 69000, 69600, 71400, 71400, 73500);
        //处理第几节课时注意：每次把课下时间归结到下一小节课上，即课间也返回下一小节课。
        //其中9-10，11-12手动的添加分割点，中间没有课间休息
        for($i=1 ; $i<24 ; $i+=2){
            if($timeNow < $JXTime[$i]){
                $back .= (String)(($i+1)/2).",";
                break;
            } 
        }

        //预设实验楼每天的每节课上下课时间戳
        $JXTime = array(29100, 31800, 32100, 34800, 36000, 38700, 39000, 41700, 48600, 51300, 51900, 54600, 55500,
                        58200, 58800, 61500, 64800, 66900, 66900, 69000, 69600, 71400, 71400, 73500);
        //处理第几节课时注意：每次把课下时间归结到下一小节课上，即课间也返回下一小节课。
        //其中9-10，11-12手动的添加分割点，中间没有课间休息
        //因为存在11点35分以后实验楼没课，所以在35分到50分这段时间返回0
        if($timeNow > 41700 && $timeNow < 42600){
            $back .= '0';
        }else{
            for($i=1 ; $i<24 ; $i+=2){
                if($timeNow < $JXTime[$i]){
                    $back .= (String)(($i+1)/2);
                    break;
                } 
            }
        }
        return $back;
    }
}
