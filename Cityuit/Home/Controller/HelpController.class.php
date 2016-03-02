<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第三排按钮帮助功能等接口
 */
class HelpController extends Controller {
    public function index(){
        /* $this->display(); */
    }

    /*
     *退出操作成功提示
     */
    public function exitHelp($weChat){
        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
        $weChat->text("成功退出当前操作。\n有疑问，请输入关键词【帮助】查询。\n也可以直接回复消息，主页君不定时上线 ^_^|||")->reply();
    }
}
