<?php
/**
 * Created by PhpStorm.
 * User: 老王
 * Date: 2016/11/2
 * Time: 13:21
 */
namespace app\home\controller;
use app\home\model\Comment;
use app\home\model\Like;
use app\home\model\Picture;
use app\home\model\VolunteerOrder;
use app\home\model\VolunteerOrderReceive;
use app\home\model\VolunteerRecruit;
use app\home\model\VolunteerRecruitReceive;
use app\home\model\VolunteerTeam;
use app\home\model\WechatUser;
use app\home\model\WechatUserTag;


class Volunteer extends Base{
    /**
     * 服务团队
     */
    public function team(){
        $teamModel = new VolunteerTeam();
        //type：1爱心帮扶
        $map1 = array(
            'status' => 1,
            'type' => 1,
        );
        $list1 = $teamModel->where($map1)->order('create_time desc')->limit(7)->select();
        $this->assign('list1',$list1);
        //type：2环境美化
        $map2 = array(
            'status' => 1,
            'type' => 2,
        );
        $list2 = $teamModel->where($map2)->order('create_time desc')->limit(7)->select();
        $this->assign('list2',$list2);
        //type：3平安巡查
        $map3 = array(
            'status' => 1,
            'type' => 3,
        );
        $list3 = $teamModel->where($map3)->order('create_time desc')->limit(7)->select();
        $this->assign('list3',$list3);
        return $this->fetch();
    }
    
    /**
     * 服务团队加载更多
     */
    public function listmore() {
        $len = input('length');
        $map =  array(
            'status' => 1,
            'type' => input('type'),
        );
        $teamModel = new VolunteerTeam();
        $list = $teamModel->where($map)->order('create_time desc')->limit($len,5)->select();
        foreach ($list as $value) {
            $img = Picture::get($value['list_image']);
            $value['path'] = $img['path'];
            $value['time'] = date('Y-m-d',$value['create_time']);
        }

        return $this->success("加载成功","",$list);

    }

    /**
     * 服务团队详情页
     */
    public function teamdetail(){
        $this->anonymous(); //判断是否是游客
        $this->jssdk();

        $uid = session('userId');
        $id = input('id');
        $teamModel = new VolunteerTeam();
        $teamModel::where('id',$id)->setInc('views');     //浏览加一

        $info = $teamModel->get($id);
        //分享图片及链接及描述
        $image = Picture::where('id',$info['list_image'])->find();
        $info['share_image'] = "http://".$_SERVER['SERVER_NAME'].$image['path'];
        $info['link'] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL'];
        $info['desc'] = str_replace('&nbsp;','',strip_tags($info['content']));
        //点赞
        $likeModel = new Like();
        $like = $likeModel->getLike(12,$id,$uid);
        $info['is_like'] = $like;
        $this->assign('info',$info);

        //获取评论
        $commentModel = new Comment();
        $comment = $commentModel->getComment(12,$id,$uid);
        $this->assign('comment',$comment);

        return $this->fetch();
    }

    /**
     * 服务订单
     */
    public function order(){
        $orderModel = new VolunteerOrder();
        $map = array(
            'status' => array('egt',1)
        );
        $list = $orderModel->where($map)->order('create_time desc')->limit(7)->select();
        $this->assign('list',$list);

        return $this->fetch();
    }

    /**
     * 服务订单更多
     */
    public function ordermore() {
        $len = input('length');
        $map =  array(
            'status' => array('egt',1)
        );
        $orderModel = new VolunteerOrder();
        $list = $orderModel->where($map)->order('create_time desc')->limit($len,5)->select();
        foreach ($list as $value) {
            $img = Picture::get($value['list_image']);
            $value['path'] = $img['path'];
            $value['time'] = date('Y-m-d',$value['create_time']);
        }
        if($list) {
            return $this->success("加载成功","",$list);
        }else{
            return $this->error("加载失败");
        }
    }

    /**
     * 服务订单详情页
     */
    public function orderdetail(){
        $this->anonymous(); //判断是否是游客
        $this->jssdk();

        $userId = session('userId');
        $id = input('id');
        $orderModel = new VolunteerOrder();
        $info = $orderModel->get($id);
        //分享图片及链接及描述
        $image = Picture::where('id',$info['list_image'])->find();
        $info['share_image'] = "http://".$_SERVER['SERVER_NAME'].$image['path'];
        $info['link'] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL'];
        $info['desc'] = str_replace('&nbsp;','',strip_tags($info['content']));
        //获取用户是否报名
        $map = array(
            'oid' => $id,
            'userid' => $userId,
            'status' => 1,
        );
        $user = VolunteerOrderReceive::where($map)->find();
        if($user) {
            $info['be'] = 1;    //已报名
        }else{
            $info['be'] = 0;    //未报名
        }
        $this->assign('info',$info);

        //名单
        $map1 = array(
            'oid' => $id,
            'status' => array('eq',1),
        );
        $namelist = VolunteerOrderReceive::where($map1)->select();
        foreach ($namelist as $value) {
            $msg = WechatUser::where('userid',$value['userid'])->find();
            $value['avatar'] = $msg['avatar'];
            $value['name'] = $msg['name'];
            $value['unit'] = $msg['unit'];
        }
        $this->assign('namelist',$namelist);

        return $this->fetch();
    }

    /**
     * 志愿招募
     */
    public function recruit(){
        $userId = session('userId');
        $recruitModel = new VolunteerRecruit();
        $map = array(
            'status' => array('eq',1)
        );
        $list = $recruitModel->where($map)->order('create_time desc')->limit(7)->select();
        $this->assign('list',$list);

        //是否具备我的发布权限,具备为1，无则为0
        $map = array(
            'userid' => $userId,
            'tagid' => 5, //发布权限标签id
        );
        $info = WechatUserTag::where($map)->find();
        if($info) {
            $this->assign('is',1);
        }else{
            $this->assign('is',0);
        }
        return $this->fetch();
    }

    /**
     * 志愿招募
     */
    public function recruitmore() {
        $len = input('length');
        $map =  array(
            'status' => array('eq',1)
        );
        $recruitModel = new VolunteerRecruit();
        $list = $recruitModel->where($map)->order('create_time desc')->limit($len,5)->select();
        foreach ($list as $value) {
            $img = Picture::get($value['list_image']);
            $value['path'] = $img['path'];
            $value['time'] = date('Y-m-d',$value['create_time']);
        }
        if($list) {
            return $this->success("加载成功","",$list);
        }else{
            return $this->error("加载失败");
        }
    }

    /**
     * 志愿招募详情页
     */
    public function recruitdetail(){
        $this->anonymous(); //判断是否是游客
        $this->jssdk();

        $userId = session('userId');
        $id = input('id');
        $orderModel = new VolunteerRecruit();
        $info = $orderModel->get($id);
        //分享图片及链接及描述
        $image = Picture::where('id',$info['list_image'])->find();
        $info['share_image'] = "http://".$_SERVER['SERVER_NAME'].$image['path'];
        $info['link'] = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REDIRECT_URL'];
        $info['desc'] = str_replace('&nbsp;','',strip_tags($info['content']));
        //获取用户是否报名
        $map = array(
            'rid' => $id,
            'userid' => $userId,
            'status' => 1,
        );
        $user = VolunteerRecruitReceive::where($map)->find();
        if($user) {
            $info['be'] = 1;    //已报名
        }else{
            $info['be'] = 0;    //未报名
        }
        $this->assign('info',$info);

        //名单
        $map1 = array(
            'rid' => $id,
            'status' => array('eq',1),
        );
        $namelist = VolunteerRecruitReceive::where($map1)->select();
        foreach ($namelist as $value) {
            $msg = WechatUser::where('userid',$value['userid'])->find();
            $value['avatar'] = $msg['avatar'];
            $value['name'] = $msg['name'];
            $value['unit'] = $msg['unit'];
        }
        $this->assign('namelist',$namelist);
        return $this->fetch();
    }


    /**
     * 我要报名
     */
    public function enroll() {
        $userId = session('userId');
        $type = input('type');
        $id = input('id');
        $user = WechatUser::where('userid',$userId)->find();
        if($userId == 'visitor') {
            return $this->error("游客无法参与报名！");
        }elseif($user) {
            if($type == 1) {
                $model1 = new VolunteerOrder();
                $model2 = new VolunteerOrderReceive();
                $map = array(
                    'oid' => $id,
                    'userid' => $userId,
                );
            }else {
                $model1 = new VolunteerRecruit();
                $model2 = new VolunteerRecruitReceive();
                $map = array(
                    'rid' => $id,
                    'userid' => $userId,
                );
            }
            $father = $model1->get($id);
            //如何人数满则不能报名，改变状态为2
            if($father['status'] == 2) {
                return $this->error("人数已满");
            }else {
                $son = $model2->where($map)->find();
                if($son) {
                    return $this->error("已领取过该订单");
                }else {
                    $info = $model2->create($map);
                    if($info) { //报名成功，领取人数+1；
                        $model1->where('id',$id)->setInc("receive_number");
                        $father = $model1->get($id);
                        if($father['receive_number'] == $father['demand_number']) {  //如果人数已满  改变状态为2
                            $msg['status'] = 2;
                            $model1->where('id',$id)->update($msg);
                        }
                        //返回用户信息
                        $rec = $model2->where($map)->find();
                        $data = WechatUser::where('userid',$rec['userid'])->field('avatar,name,unit')->find();
                        $data['time'] = date("Y-m-d",$rec['create_time']);
                        return $this->success("领取成功","",$data);
                    }else {
                        return $this->error("领取失败");
                    }
                }
            }
        }else{
            return $this->error("用户不存在通讯录，无法报名！");
        }
    }

    /**
     * 志愿发布页
     */
    public function publish(){
        if(IS_POST) {
            $userId = session('userId');
            $data = input('post.');
            $data['userid'] = $userId;
            $data['time'] = strtotime($data['time']);
            $recruitModel = new VolunteerRecruit();
            $model = $recruitModel->create($data);
            if($model) {
                return $this->success("添加成功");    
            }else {
                return $this->error("添加失败");
            }
        }else {
            return $this->fetch();
        }
    }
}
