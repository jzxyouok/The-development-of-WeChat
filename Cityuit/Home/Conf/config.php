<?php
return array(
    //'配置项'=>'配置值'
    'TMPL_L_DELIM'=>'<{', //左模版标签
    'TMPL_R_DELIM'=>'}>', //右模版标签

    'URL_MODEL' => '2',

    'URL_ROUTER_ON'   => true, //开启路由
    'URL_ROUTE_RULES' => array( //定义路由规则 
        /* 'Index/:id\d$'    => 'Index/read', */
        /* 'Index/:name$'    => 'Index/read', */
        /* 'Index/:year\d/:month\d'  => 'Index/archive', */
        /* '/^Index\/(\d+)$/' => 'Index/read?id=:1', */
        /* '/^Index\/(\w+)$/' => 'Index/read?name=:1', */
        /* '/^Index\/(\d{4})\/(\d{2})$/' => 'Index/archive?year=:1&month=:2', */
    ),

    //数据库配置信息 thinkPHP默认支持sae云,所以基本配置不需要
    'db_prefix' => 'tp_', // 数据库表前缀 
    'db_charset'=> 'utf8', // 字符集
    'db_debug'  =>  true, // 数据库调试模式 开启后可以记录sql日志

    //设置默认缓存类型 默认支持sae Memcachesae
    //使用方法：S('name',$value,$time)
    'DATA_CACHE_TIME'       =>  7200,      // 数据缓存有效期默认7200秒 0表示永久缓存

    /* 'SHOW_PAGE_TRACE' => true, //开启事务查看 */ 
    /* 必须关闭才能通过微信接口验证 */

    /* 'WEB_HOST' => 'http://2.cityuit.sinaapp.com' */

    //自定义常量

    'START_DATE_OF_SCHOOL' => '2016-3-6',   //开学前一天日期，每学期需要手动修改

    'CITY_LINK' => 'http://120.27.53.146:5000/api/',   //校网接口
    /* 'CITY_LINK' => 'http://xxxxxx',   //校网登录验证接口 */
	'WECHAT_TOKEN' => "csxyxzs",
    'WECHAT_APPID' => "wx4ff4d9df9c2d688a",     //AppID(应用ID) 为了安全只有在使用的时候设置
    'WECHAT_APPSECRET' => "b255b255a3cb281e591ad64dd279391f",      //AppSecret(应用密钥)
	/* 'WECHAT_TOKEN' => "xxxxxx", */
    /* 'WECHAT_APPID' => "xxxxxx",      //AppID(应用ID) 为了安全只有在使用的时候设置 */
    /* 'WECHAT_APPSECRET' => "xxxxxx",      //AppSecret(应用密钥) */

    'AUTH_CODE_KEY' => "b255b255a3cb281e591ad64dd279391f",     //为加密算法提供key值
    /* 'AUTH_CODE_KEY' => "xxxxxx",     //为加密算法提供key值 */
    'AUTH_CODE_TIME' => "600",     //key值有效时间
);
