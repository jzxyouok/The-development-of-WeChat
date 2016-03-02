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
     *获取课表处理
     */
    public function dealSchedule($weChat,$studentno){
        $scheduleVal = $this->hasSchedule($studentno);   //获取本地课表
        if($scheduleVal){
            $weChat->text($this->matchSchedule($scheduleVal))->reply();
        }else{
            $password = A('Login')->getPassword($studentno);
            $scheduleValue = $this->getScheduleFromLink($studentno, $password);
            if($scheduleValue){
                $weChat->text($this->matchSchedule($scheduleValue))->reply();
            }else{
                //出错可能1.校网崩了。2.获取课表每学期更新的值不匹配，没有返回值。3.用户的密码更换了。4.之后再找其它问题
                $weChat->text('出错了')->reply();
            }
        }
    }

    /*
     *处理课表取得今天 明天课表
     */
    public function matchSchedule($schedulejson){
        $scheduleArr=json_decode($schedulejson, true); 
        return $scheduleArr['Mon']['1-2'][0]['class_name'];
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
            'action'=>'get'
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
                    return "false"; 
                }
                break;
            case 'School network connection failure':  //校网问题
                return 'false';
                break;
            default:
                return "false";
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
}
