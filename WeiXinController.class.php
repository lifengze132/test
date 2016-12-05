<?php

namespace Home\Controller;

use Think\Controller;

use Gaoming13\WechatPhpSdk\Wechat;

class WeiXinController extends Controller 
{
    protected $api;

    public function __construct()
    {
       parent::__construct();

       //在构造方法中实例化$api这个对象
       // api模块 - 包含各种系统主动发起的功能
       $this->api = new \Gaoming13\WechatPhpSdk\Api(
              array(
                  'appId' => 'wx3e466be0fdc64643',
                  'appSecret' => '62d45b90b88a385efd4fff42a500d215',
                  'get_access_token' => function(){
                      // 用户需要自己实现access_token的返回
                      
                      return S('wechat_token');
                  },
                  'save_access_token' => function($token) {
                      // 用户需要自己实现access_token的保存
                      
                      S('wechat_token', $token);
                  }
              )
        );

    }

    //生成自定义菜单
    public function makeMenu()
    {

        

        //csdn
        //点击首页，返回一个图文消息
        $json = '{
             "button":[
             {  
                  "type":"click",
                  "name":"首页",
                  "key":"V1001_TODAY_MUSIC"
              },
              {
                   "name":"其他",
                   "sub_button":[
                    {
                       "type":"view",
                       "name":"博客2",
                       "url":"http://www.3maio.com/"
                    },
                    {
                       "type":"click",
                       "name":"赞一下我们",
                       "key":"V1001_GOOD"
                    },
                    {
                       "type":"click",
                       "name":"我要看黄图",
                       "key":"php"
                    }]
               }]
         }';


        $this->api->create_menu($json);
    }

    //这个方法用来上传我们服务器图片到微信服务器
    public function uploadMedia()
    {

       // echo __FILE__;
       $picPath  = '/alidata/www/default/liangzhi/lamp/Public/Uploads/2.gif';
       //upload_media()第二个参数是图片绝对路径
       $res = $this->api->upload_media('image', $picPath);

       dump($res);
    }

    public function admin()
    {
        // echo 'aaa';
        if(IS_POST){

            $data['title'] = I('post.title');
            $data['descp'] = I('post.descp');
            $data['url'] = I('post.url');

            /**
             *
             *  create table new(id int primary key auto_increment, title varchar(255) not null,descp varchar(100) not null, url varchar(200) not null)engine=innodb default charset=utf8;
             * 
             */
            
            M('new')->add($data);


        }else{

            $this->display('Index/admin');
        }
    }


    public function index()
    {

        $wechat = new Wechat(array( 
            // 开发者中心-配置项-AppID(应用ID)        
            'appId'         =>  'wx3e466be0fdc64643',
            // 开发者中心-配置项-服务器配置-Token(令牌)
            'token'         =>  'test'
            // 开发者中心-配置项-服务器配置-EncodingAESKey(消息加解密密钥)
            // 可选: 消息加解密方式勾选 兼容模式 或 安全模式 需填写
            
        ));


        

        // 获取微信消息
        $msgObj = $wechat->serve();


        $data = json_encode($msgObj);

        $map['xml'] = $data;
        M('tmp')->add($map);
        // var_dump($msgObj);

        //判断用户点击的是不是首页这个菜单
        switch ( $msgObj->MsgType) {

          //如果能够进入这个case，代表用户进行某个事件
          case 'event':
            
            switch ($msgObj->Event ) {

              //如果能够进入这个case，代表用户进行一个点击事件
              case 'CLICK':
                
                switch ($msgObj->EventKey ) {

                  //说明用户点击的是首页这个菜单
                  case 'V1001_TODAY_MUSIC':
                    
                    //返回图文消息
                    $wechat->reply(array(
                      'type' => 'news',
                          'articles' => array(
                             array(
                                'title' => '首页',                               //可选
                                'description' => '这是一个很好的首页',                     //可选
                                'picurl' => 'http://me.diary8.com/data/img/demo1.jpg',  //可选
                                'url' => 'http://www.3maio.com/'                      //可选
                             )
                          )
                    ));  
     //              $wechat->reply(array(
					//     'type' => 'text',
					//     'content' => '嘿嘿，呵呵~~'
					// ));


                    break;

                  //点击是赞一下
                  case 'V1001_GOOD':
                    $wechat->reply('你点了一下赞');
                  break;

                  //用户点击了“我要看黄图”菜单,需求：用户点击“我要看黄图” 回复一张图片
                  case 'php':

                    $wechat->reply(array(
                      'type' => 'image',
                      // 通过素材管理接口上传多媒体文件，得到的id
                      'media_id' => 'gDluNKEpY6wG60tCZW-xl8nEzOPXKXqG-V1Oqr33gsSHS6G4NNbgaFD04BrLD8r3'
                    ));

                  break;
                  
                  default:
                    # code...
                    break;
                }


                break;
              

              //用户进行了关注
              case 'subscribe':

                // $link = 'http://101.200.142.157/liangzhi/lamp/index.php/Home/WeiXin/getUserInfo';

                $link = $this->api->get_authorize_url('snsapi_userinfo', 'http://101.200.142.157/liangzhi/lamp/index.php/Home/WeiXin/getUserInfo');

                $sub =  array(
                  'type' => 'news',
                  'articles' => array(
                     array(
                        'title' => '欢迎来我的小站',                               //可选
                        'description' => '一个国内最优秀开发者的站点',                     //可选
                        'picurl' => 'http://me.diary8.com/data/img/demo1.jpg',  //可选
                        'url' => "$link"                      //可选
                     )
                  
                  )
                );

                $wechat->reply($sub);
              break;
              default:
                # code...
                break;
            }

            break;
          
          default:
            # code...
            break;
        }


        //判断用户是否进行一个关注
        if( $msgObj->MsgType == 'event' && $msgObj->Event == 'subscribe' ){

            $wechat->reply('欢迎光临234');

        }

        //判断用户发送过来的是不是?
        if( $msgObj->Content == '?' ){
            //回复一个文本消息，文本消息的内容是"你好"
            $wechat->reply("你好");
        }


        //图文，回复图文消息
        if( $msgObj->Content == '图文' ){


            /*
            
            array(
                'type' => 'news',
                'articles' => array(
                     array(
                        'title' => '图文消息标题1',                               //可选
                        'description' => '图文消息描述1',                     //可选
                        'picurl' => 'http://me.diary8.com/data/img/demo1.jpg',  //可选
                        'url' => 'http://www.example.com/'                      //可选
                     ),
                    array(
                        'title' => '图文消息标题2',
                        'description' => '图文消息描述2',
                        'picurl' => 'http://me.diary8.com/data/img/demo2.jpg',
                        'url' => 'http://www.example.com/'
                    ),
                    array(
                        'title' => '图文消息标题3',
                        'description' => '图文消息描述3',
                        'picurl' => 'http://me.diary8.com/data/img/demo3.jpg',
                        'url' => 'http://www.example.com/'
                    )
                )
            )
             */
            
             
            $data = M('new')->select();
            $reply['type'] = 'news';
            $reply['articles'] = M('new')->field('title,descp as description,url,picurl')->select();


            $wechat->reply($reply);
        }


    }



    /**
     *  需求：当用户点击图文消息，跳转到我们网站某个方法中,在这个方法中获取跳转过来那个用户新，并且显示出来。
     */

    public function getUserInfo()
    {

        // header("Content-type:text/html;charset=utf-8");
        list($err, $user_info) = $this->api->get_userinfo_by_authorize('snsapi_userinfo');
        if ($user_info !== null) {

            // dump( (array) $user_info );;
            $userInfo =  (array) $user_info ;

            $this->assign('userinfo', $userInfo);
        } else {
            echo '授权失败！';
        }

        $this->display('Index/show');
        // dump('aaa');
        // $link = $this->api->get_authorize_url('snsapi_userinfo', 'http://101.200.142.157/liangzhi/lamp/index.php/Home/WeiXin/getUserInfo');

        // echo $link;
        // echo 'aaa';exit;
        /*$userInfo = $this->api->get_user_info('oXbFkt51vX0SIVkKD2R6AAeQrThI');

        dump($userInfo);*/
    }
}
