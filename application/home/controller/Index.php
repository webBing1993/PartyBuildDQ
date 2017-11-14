<?php
/**
 * Created by PhpStorm.
 * User: Lxx<779219930@qq.com>
 * Date: 2016/4/20
 * Time: 13:47
 */

namespace app\home\controller;
use app\home\model\Message;
use app\home\model\WechatUser;
use com\wechat\TPQYWechat;
use think\Config;
use think\Controller;
use app\user\controller\Index as APIIndex;
use think\Log;

/**
 * 党建主页
 */
class Index extends Controller {
    public function index(){

        return $this->fetch();
    }

    /**
     * 用户登入获取信息
     */
    public function login(){
        // 获取用户信息
        $Wechat = new TPQYWechat(config('party'));
        $result = $Wechat->getUserId(input('code'), config('party.agentid'));
        if(empty($result['UserId'])) {
            session('userId', 'visitor');//游客userid为0
            session('name', '游客');
        } else {
            $userInfo = $Wechat->getUserInfo($result['UserId']);
            $data = [
                'userid' => $userInfo['userid'],
                'name' => $userInfo['name'],
                'mobile' => $userInfo['mobile'],
                'gender' => $userInfo['gender'],
                'avatar' => $userInfo['avatar'],
                'department' => json_encode($userInfo['department']), //只选第一个所属部门
                'status' => $userInfo['status'],
                'order' => json_encode($userInfo['order']),
            ];
            if(isset($userInfo['extattr']['attrs'])) {
                $data['extattr'] = json_encode($userInfo['extattr']['attrs']);
            }

            $wechatUser = new WechatUser();
            if ($wechatUser->checkUserExist($userInfo['userid'])) {
                $wechatUser->save($data, ['userid' => $userInfo['userid']]);
            } else {
                $wechatUser->save($data);
            }

            session('userId', $userInfo['userid']);
            session('name', $userInfo['name']);
            session('gender', $userInfo['gender']);
            session('avatar', $userInfo['avatar']);
            session('department', $userInfo['department'][0]);

            $this->redirect(session('requestUri'));
        }
    }
}