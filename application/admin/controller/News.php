<?php
/**
 * Created by PhpStorm.
 * User: Lxx<779219930@qq.com>
 * Date: 2016/9/21
 * Time: 14:41
 */
namespace app\admin\controller;

use think\Controller;
use app\admin\model\Picture;
use app\admin\model\Push;
use app\admin\model\PushReview;
use com\wechat\TPQYWechat;
use app\admin\model\News as NewsModel;
use think\Config;
/**
 * Class News
 * @package 第一聚焦控制器
 */
class News extends Admin {

    /**
     * 主页列表
     */
    public function index(){
        $map = array(
            'status' => array('egt',0),
        );
        $list = $this->lists('News',$map);
        int_to_string($list,array(
            'status' => array(0=>"待推送",1=>"<span style='color:#dd0'>待审核</span>",2=>"<span style='color: green'>已发布</span>",3=>"<span style='color: red'>不通过</span>"),
            'recommend' => array( 1=>"推荐" , 0=>"不推荐")
        ));

        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 新闻添加
     */
    public function add(){
        if(IS_POST) {
            $data = input('post.');
            $data['create_user'] = $_SESSION['think']['user_auth']['id'];
            if(empty($data['id'])) {
                unset($data['id']);
            }
            $newModel = new NewsModel();
            $info = $newModel->validate('news')->save($data);
            if($info) {
                return $this->success("新增成功",Url('News/index'));
            }else{
                return $this->error($newModel->getError());
            }
        }else{
            $this->default_pic();
            $this->assign('msg','');

            return $this->fetch('edit');
        }
    }

    /**
     * 修改
     */
    public function edit(){
        if(IS_POST) {
            $data = input('post.');
            $data['create_time'] = time();
            $newModel = new NewsModel();
            $info = $newModel->validate('news')->save($data,['id'=>input('id')]);
            if($info){
                return $this->success("修改成功",Url("News/index"));
            }else{
                return $this->get_update_error_msg($newModel->getError());
            }
        }else{
            $this->default_pic();
            $id = input('id');
            $msg = NewsModel::get($id);
            $this->assign('msg',$msg);
            
            return $this->fetch();
        }
    }

    /**
     * 删除功能
     */
    public function del(){
        $id = input('id');
        $data['status'] = '-1';
        $info = NewsModel::where('id',$id)->update($data);
        if($info) {
            return $this->success("删除成功");
        }else{
            return $this->error("删除失败");
        }

    }
    /*
     * 推送列表
     */
    public function pushlist(){
        if(IS_POST){
            $news_id=input('news_id');//主图文id
            //副图文本周内的新闻消息
            date_default_timezone_set('PRC');     //初始化时区
            $y = date("Y");     //获取当天的年份
            $m = date("m");     //获取当天的月份
            $d = date("d");     //获取当天的日期
            $todayTime=mktime(0,0,0,$m,$d,$y);  //将今天开始的年月日时分秒转换成unix时间戳
            $time=date("N",$todayTime);     //获取星期数进行判断，当前时间做对比取本周一和上周一时间
            //$t为本周周一,$t为上周周一
            switch($time){
                case 1 : $t = $todayTime;
                    break;
                case 2 : $t = $todayTime - 86400*1;
                    break;
                case 3 : $t = $todayTime - 86400*2;
                    break;
                case 4 : $t = $todayTime - 86400*3;
                    break;
                case 5 : $t = $todayTime - 86400*4;
                    break;
                case 6 : $t = $todayTime - 86400*5;
                    break;
                case 7 : $t = $todayTime - 86400*6;
                    break;
                default :
            }
            $info = array(
                'id' => array('neq',$news_id),
                'create_time' => array('egt',$t),
                'status' => 0
            );
            $infoes=NewsModel::where($info)->select();
            return $this->success($infoes);
        }else{
            //新闻消息列表
            $map = array(
                'status' => array('egt',-1)
            );
            $list=$this->lists('Push',$map);
            int_to_string($list,array(
                'type' => array(1=>'企业号推送',2=>'订阅号推送'),
                'status' => array(-1=>'<span style=\'color: red\'>不通过</span>',0=>"<span style='color:#dd0'>待审核</span>",1=>"<span style='color: green'>已发布</span>")
            ));
            //数据重组
            foreach($list as $value){
                $msg = NewsModel::where('id',$value['focus_main'])->find();
                $value['title'] = $msg['title'];
                //审核信息
                $review = PushReview::where('push_id',$value['id'])->find();
                $value['review_name'] = $review['username'];
                $value['review_time'] = $review['review_time'];
            }
            $this->assign('list',$list);
            //主图文本周内的新闻消息
            date_default_timezone_set("PRC");        //初始化时区
            $y = date("Y");        //获取当天的年份
            $m = date("m");        //获取当天的月份
            $d = date("d");        //获取当天的号数
            $todayTime= mktime(0,0,0,$m,$d,$y);        //将今天开始的年月日时分秒，转换成unix时间戳
            $time = date("N",$todayTime);        //获取星期数进行判断，当前时间做对比取本周一和上周一时间。
            //$t为本周周一，$s为上周周一
            switch($time){
                case 1: $t = $todayTime;
                    break;
                case 2: $t = $todayTime - 86400*1;
                    break;
                case 3: $t = $todayTime - 86400*2;
                    break;
                case 4: $t = $todayTime - 86400*3;
                    break;
                case 5: $t = $todayTime - 86400*4;
                    break;
                case 6: $t = $todayTime - 86400*5;
                    break;
                case 7: $t = $todayTime - 86400*6;
                    break;
                default:
            }
            $info = array(
                'create_time' => array('egt',$t),
                'status'      => 0
            );
            $infoes=NewsModel::where($info)->select();
            $this->assign('info',$infoes);
            return $this->fetch();
        }
    }
    /*
     * 新闻推送
     */
    public function push(){
        $data = input('post.');
        $arr1 = $data['focus_main'];      //主图文id
        isset($data['focus_vice']) ? $arr2 = $data['focus_vice'] : $arr2 = "";  //副图文id
        if($arr1 == -1){
            return $this->error('请选择主图文');
        }else{
            //主图文信息
            $info1 = NewsModel::where('id',$arr1)->find();
        }
        $update['status'] = '1';
        $title1 = $info1['title'];
        NewsModel::where(['id'=>$arr1])->update($update); // 更新推送后的状态
        $str1 = strip_tags($info1['content']);
        $des1 = mb_substr($str1,0,40);
        $content1 = str_replace("&nbsp;","",$des1);  //空格符替换成空
        $url1 = "http://dqpb.0571ztnet.com/home/review/detail/id/".$info1['id'].".html";
        $image1 = Picture::get($info1['front_cover']);
        $path1 = "http://dqpb.0571ztnet.com".$image1['path'];
        $information1 = array(
            'title' => $title1,
            'description' => $content1,
            'url'  => $url1,
            'picurl' => $path1
        );
        $information = array();
        if(!empty($arr2)){
            //副图文信息
            $information2 = array();
            foreach($arr2 as $key=>$value){
                NewsModel::where(['id'=>$value])->update($update); // 更新推送后的状态
                $info2 = NewsModel::where('id',$value)->find();
                $title2 = $info2['title'];
                $str2 = strip_tags($info2['content']);
                $des2 = mb_substr($str2,0,40);
                $content2 = str_replace("&nbsp;","",$des2);  //空格符替换成空
                $url2 = "http://dqpb.0571ztnet.com/review/news/detail/id/".$info2['id'].".html";
                $image2 = Picture::get($info2['front_cover']);
                $path2 = "http://dqpb.0571ztnet.com".$image2['path'];
                $information2[] = array(
                    "title" =>$title2,
                    "description" => $content2,
                    "url" => $url2,
                    "picurl" => $path2,
                );
            }
            //数组合并,主图文放在首位
            foreach($information2 as $key=>$value){
                $information[0] = $information1;
                $information[$key+1] = $value;
            }
        }else{
            $information[0] = $information1;
        }
        //重组成article数据
        $send = array();
        $re[] = $information;
        foreach($re as $key => $value){
            $key = "articles";
            $send[$key] = $value;
        }
        if($data['type'] == 1){
            //发送给企业号
            $Wechat = new TPQYWechat(Config::get('party'));
            $message = array(
                "totag" => "4",  // 审核组
                "msgtype" => 'news',
                "agentid" => 11,  // 消息审核
                "news" => $send,
                "safe" => "0"
            );
            $msg = $Wechat->sendMessage($message);  // 推送至审核
        }else{
            //发送到订阅号
        }
        if($msg['errcode'] == 0){
            $data['focus_vice'] ? $data['focus_vice'] = json_encode($data['focus_vice']) : $data['focus_vice'] = null;
            $data['create_user'] = session('user_auth.username');
            $data['status'] = 0;
            //保存到推送列表
            $result=Push::create($data);
            if($result){
                return $this->success('发送成功');
            }else{
                return $this->error('发送失败');
            }
        }else{
            return $this->error('发送失败');
        }
    }
}