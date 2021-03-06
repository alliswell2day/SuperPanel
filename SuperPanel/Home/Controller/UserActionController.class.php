<?php
namespace Home\Controller;
use Think\Controller;
class UserActionController extends Controller {
    public function _initialize()
    {
        C(M('Config')->cache('config', 600)->getField('name,value'));
        
        $this->userinfo = isLogined();
        if(count($this->userinfo)<2){
            die("not login");               //没登陆直接爆炸
        }
    }
    
    public function tickets(){
        $action = I('post.action');
        $tid = I('post.tid');
        
        if($action == 'get_ticket_content'){            //获取工单详细信息
            $data = M('ticket_reply')->where(array('tid'=>$tid))->select();
            $this->ajaxReturn(array('status'=>'success', 'data'=>$data));
        }elseif ($action == 'delete_ticket') {          //删除工单
            $result = M('ticket')->where(array('uid'=>$this->userinfo['uid'], 'tid'=>$tid))->delete();
            if($result == 1){
                $this->ajaxReturn(array('status'=>'success', 'info'=>_('该工单已成功删除')));
            }
            else{
                $this->ajaxReturn(array('status'=>'error', 'info'=>_('未知原因，该工单删除失败')));
            }
        }elseif($action == 'close_ticket'){             //关闭工单
            $result = M('ticket')->where(array('uid'=>$this->userinfo['uid'], 'tid'=>$tid))->save(array('status'=>'closed'));
            if($result == 1){
                $this->ajaxReturn(array('status'=>'success', 'info'=>_('工单已成功关闭')));
            }
            else{
                $this->ajaxReturn(array('status'=>'error', 'info'=>_('未知原因，工单关闭失败')));
            }
        }elseif($action == 'reply_ticket'){             //回复
            $data['uid'] = $this->userinfo['uid'];
            $data['tid'] = $tid;
            $data['message'] = I('post.message');
            if(strlen($data['message']) == 0){
                $this->ajaxReturn(array('status'=>'error', 'info'=>_('消息不能不为空')));
            }
            else{
                $ticket = M('ticket')->where(array('tid'=>$tid, 'uid'=>$this->userinfo['uid']))->find();
                if(empty($ticket))
                    $this->ajaxReturn(array('status'=>'error', _('提交的工单无效，可能是已被删除')));
                elseif($ticket['status'] == 'closed')
                    $this->ajaxReturn(['status'=>'error', info=>'此工单已关闭']);
                else{
                    $result = M('ticket_reply')->add($data);
                    M('ticket')->where(['tid'=>$tid])->setField('status', 'updated');
                    if($result)
                    $this->ajaxReturn(array('status'=>'success', 'info'=>_('提交成功')));
                    else
                        $this->ajaxReturn(array('status'=>'error', 'info'=>_('提交失败')));
                }
            }
        }
        
        $this->ajaxReturn(array('status'=>'error', 'info'=>_('无效的请求')));
    }
    
    public function newTicket(){
        $data['title'] = I('post.type').' - '.I('post.title');
        $data['message'] = I('post.message');
        $data['uid'] = $this->userinfo['uid'];
        $data['username'] = $this->userinfo['username'];
        M('ticket')->add($data);
        $this->ajaxReturn(array('status'=>'success', 'info'=>_('您的工单添加成功！')));
    }
    
    
    public function invite(){
        if(I('post.invite_code_amount')!='' && I('post.money')!=''){
            $money = I('post.money');
            $amount = I('post.invite_code_amount');
            if($amount>$this->userinfo['invite_code_amount']){
                $this->ajaxReturn(array('status'=>'error', 'info'=>_('您当前邀请码剩余数量不足')));
                return;
            }
            if ($amount*money > $this->userinfo['money']) {
                
                $this->ajaxReturn(array('status'=>'error', 'info'=>_('您当前喵币余额不足')));
            }
            
            for(; $amount>0;$amount--)
                $dataList[]=array('uid'=>$this->userinfo['uid'], 'code'=>hash('md5', rand()), 
                    'transfer' => $transfer, 'money'=>$money);
            M('invite_code')->addAll($dataList);
            $amount = I('post.invite_code_amount');
            
            $User = M('user');
            $User->setDec('money', $amount*$money);
            $User->setDec('invite_code_amount', $amount);
            $this->ajaxReturn(array('status'=>'success', 'info'=>sprintf(_('生成成功！<br>共生成邀请码 %d 个'), $amount)));
        }
        else
            $this->ajaxReturn(array('status'=>'error', 'info'=>_('无效的请求')));
    }
    
    public function checkPromoCode(){
        $promo_code = I('post.promo_code');
        $money = intval(I("post.money"));
        if (!empty($promo_code) && C('PROMO_CODE') == $promo_code){
            if($money < C('PROMO_CODE_REQUIRE')){
                $this->ajaxReturn(['status'=>'error','info'=>'邀请码有效，但只有当金额大于'.C("PROMO_CODE_REQUIRE").'元时才能使用']);
            }
            else{
                if(C('PROMO_CODE_VALUE')>0){
                    $moeny = $money*C("PROMO_CODE_VALUE");
                }
                else{
                    $money = $money+C("PROMO_CODE_VALUE");
                }
                if ($money <11) $money = $money +0.5;
                
                $this->ajaxReturn(['status'=>'success','info'=>$money]);
            }
        }
        else{
            $this->ajaxReturn(['status'=>'error','info'=>_('无效的优惠码')]);
        }
    }
    
    public function charge(){
        $promo_code = I('get.promo_code');
        $money = intval(I("get.money"));
        if (!empty($promo_code) && C('PROMO_CODE') == $promo_code){
            if($money >= C('PROMO_CODE_REQUIRE')){
                if(C('PROMO_CODE_VALUE')>0){
                    $money*= C("PROMO_CODE_VALUE");
                }
                else
                    $money += C("PROMO_CODE_VALUE");
            }
        }
        $Alipay = M('alipay');
        $out_trade_no = $Alipay->max('out_trade_no') + 1;
        $data['service'] = 'create_direct_pay_by_user';
        $data['total_fee'] = $money;//支付金额
        $data['partner']= C('SPAY_ID');//spay合作者id
        $data['notify_url']= C('SITE_URL').U('Api/Index/alipay');//不能有get参数 也就是?xxx=xxx&xxx=xxx
        $data['return_url']= C('SITE_URL').U("Home/User/charge");//不能有get参数 也就是?xxx=xxx&xxx=xxx
        $data['out_trade_no']= $out_trade_no;//商户唯一订单号
        
        $order['out_trade_no'] = $out_trade_no;
        $order['uid'] = $this->userinfo['uid'];
        $order['money'] = intval(I("get.money"));
        $order['real_money'] = $money;
        $Alipay->add($order);
        
        $url = spay_alipay_pay($data,C('SPAY_CODE'));
        $this->ajaxReturn(['url'=>$url]);
        //上课无聊瞎他喵的写着玩的自动获取支付婊的QR
        $qrCodeHtml = file_get_contents($url);
        
        
    }
    
    public function deleteUser(){
        $private_key = openssl_pkey_get_private('file://../private_key.pem');
        $password = base64_decode(I('post.password'));
        if(!openssl_private_decrypt($password, $password_dec, $private_key, OPENSSL_PKCS1_PADDING)){
            $this->ajaxReturn(['status'=>'error', 'info'=>_('密码解码失败，请检查您的网络链接或与网站管理员联系')]);
            exit;
        }
        $salt = M('salt')->where(['uid'=>$this->userinfo['uid']])->getField('salt');
        if(password($password_dec, $salt) == $this->userinfo['password']){
            //密码验证成功
            
            //清除用户组信息
            M('user_group_expiration')->where(['uid'=>$this->userinfo['uid']])->delete();
            //清除用户流量信息
            M('user_traffic_log')->where(['uid'=>$this->userinfo['uid']])->delete();
            //清除token
            M('token')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除工单
            M('ticket')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除消息
            M('message')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除支付宝记录
            M('alipay')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除ip记录
            M('ip')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除邀请码
            M('invite_code')->where(['uid'=>$this->userinfo['uid']])->delete();
            //删除盐
            M('salt')->where(['uid'=>$this->userinfo['uid']])->delete();
            //清除用户数据啦
            $userinfo['uid'] = $this->userinfo['uid'];
            $userinfo['port']='';
            $userinfo['password']='0';
            $userinfo['passwd']='0';
            $userinfo['t']=0;
            $userinfo['u']=0;
            $userinfo['d']=0;
            $userinfo['money']=0;
            $userinfo['invite_code_amount']=0;
            $userinfo['last_check_in_time']=0;
            $userinfo['status']="deleted";
            $userinfo['transfer_enable']=0;
            $userinfo['plan']="";
            $userinfo['enable']=0;
            $userinfo['verified']=0;
            M('user')->save($userinfo);
            
            $this->ajaxReturn(['status'=>'success', 'info'=>_('删除成功')]);
        }
        $this->ajaxReturn(['status'=>'error', 'info'=>_('密码错误')]);
    }
    
    
    public function checkGoogle2FactorCode(){
        $code = I('post.code');
        
        if(D('salt')->verifyGoogle2FactorCode($this->userinfo['uid'], $code)){
            $this->ajaxReturn(['status'=>'success']);
        }else{
            $this->ajaxReturn(['status'=>'error', 'info'=>_('验证失败')]);
        }
    }
    
    public function enableGoogle2Factor(){
        
        if(D('salt')->verifyGoogle2FactorCode($this->userinfo['uid'], I('post.code'))){
            M('user')->where(['uid'=>$this->userinfo['uid']])->setField('enable_google2factor', 1);
            $this->ajaxReturn(['status'=>'success']);
        }else{
            $this->ajaxReturn(['status'=>'error', 'info'=>_('验证失败')]);
        }
    }
    
    public function disableGoogle2Factor(){
        
        if(D('salt')->verifyGoogle2FactorCode($this->userinfo['uid'], I('post.code'))){
            M('user')->where(['uid'=>$this->userinfo['uid']])->setField('enable_google2factor', 0);
            $this->ajaxReturn(['status'=>'success']);
        }else{
            $this->ajaxReturn(['status'=>'error', 'info'=>_('验证失败')]);
        }
    }
    
    public function verifyEmail(){
        
    }
    
    public function redistributePort(){
        $new_port = D('user')->redistributePort($this->userinfo['uid']);
        $this->ajaxReturn(['status'=>'success', 'info'=>$new_port]);
    }
    
    public function changePassword(){
        $private_key = openssl_pkey_get_private('file://../private_key.pem');
        $password = base64_decode(I('post.password'));
        if(!openssl_private_decrypt($password, $password_dec, $private_key, OPENSSL_PKCS1_PADDING)){
            $this->ajaxReturn(['status'=>'error', 'info'=>_('密码解码失败，请检查您的网络链接或与网站管理员联系')]);
            exit;
        }
        $salt = M('salt')->where(['uid'=>$this->userinfo['uid']])->getField('salt');
        if(password($password_dec, $salt) == $this->userinfo['password']){
            //密码验证成功
            $url = generate_verify_url($this->userinfo['uid'], 'Home/Auth/resetPassword', 30, 'reset_password');
            $this->ajaxReturn(['status'=>'success', 'info'=>$url]);
        }
        $this->ajaxReturn(['status'=>'error','info'=>_('密码错误')]);
    }
    
    public function getItemInfo(){
        $iid = I('get.iid');
        $item = M('shop_item')->where(['iid'=>$iid])->find();
        if(empty($item)){
            $this->ajaxReturn(['status'=>'error', 'info'=>_('请求的项目不存在')]);
        }else{
            $this->ajaxReturn(['status'=>'success', 'info'=>$item]);
        }
    }
    
    public function purchase(){
        
        Vendor('CommandParser.CommandParser');
        $CommandParser = new \CommandParser();
        
        
        $iid = I('post.iid');
        $item = M('shop_item')->where(['iid'=>$iid])->find();
        if(empty($item)){
            $this->ajaxReturn(['status'=>'error', 'info'=>_('请求的项目不存在')]);
        }else{
            if($this->userinfo['money'] < $item['prices'])
                $this->ajaxReturn(['status'=>'error', 'info'=>_('余额不足')]);
            else{
                if($CommandParser->exec_command($item['commands'], $this->userinfo)){
                    M('user')->where(['uid'=>$this->userinfo['uid']])->setDec('money', $item['prices']);
                    if($item['type'] == 'port'){
                        M('shop_item')->where(['iid'=>$iid])->delete();    //端口项目只允许购买一次，买了当然要立即删除
                    }
                    //别忘了做一下购买记录
                    $log['uid']=$this->userinfo['uid'];
                    $log['item_title'] = $item['title'];
                    $log['spent'] = $item['prices'];
                    $log['balance'] = $this->userinfo['money'] - $item['prices'];
                    M('purchase_log')->add($log);
                    $this->ajaxReturn(['status'=>'success', 'info'=>$this->userinfo['money']-$item['prices']]);
                }
                $this->ajaxReturn(['status'=>'error', 'info'=>_('命令执行失败')]);
            }
        }
    }
    
    public function getExchangCodeInfo(){
        $exchange_code = I('get.exchange_code');
        $exchange_code_info = M('exchange_code')->where(['code'=>$exchange_code])->find();
        
        if(empty($exchange_code_info)){
            $this->ajaxReturn(['status'=>'error', 'info'=>'无效的兑换码']);
        }elseif($exchange_code_info['exchange_time'] == 0){
            //未使用的兑换码
            if($exchange_code_info['expiration'] >= time()){
                //合法且有效额兑换码
                $this->ajaxReturn(['status'=>'success', 'info'=>$exchange_code_info]);
            }else{
                $this->ajaxReturn(['status'=>'error', 'info'=>'兑换码已过期，有效期至：'.date('Y-m-d H:i:s', $exchange_code_info['expiration'])]);
            }
        }else{
            //兑换码已被使用
            if($exchange_code_info['uid'] == $this->userinfo['uid']){
                $this->ajaxReturn(['status'=>'used', 'info'=>'兑换码已被你使用，使用时间为'.date('Y-m-d H:i:s', $exchange_code_info['exchange_time'])]);
            }else{
                $this->ajaxReturn(['status'=>'used', 'info'=>'兑换码已被使用，使用时间为'.date('Y-m-d H:i:s', $exchange_code_info['exchange_time'])]);
            }
        }
    }
    
    public function exchange(){
        $exchange_code = I('post.exchange_code');
        $exchange_code_info = M('exchange_code')->where(['code'=>$exchange_code])->find();
        
        if(empty($exchange_code_info)){
            $this->ajaxReturn(['status'=>'error', 'info'=>'无效的兑换码']);
        }elseif($exchange_code_info['exchange_time'] == 0){
            //未使用的兑换码
            if($exchange_code_info['expiration'] >= time()){
                //合法且有效额兑换码
                
                M('user')->where(['uid'=>$this->userinfo['uid']])->setInc('money', $exchange_code_info['money']);
                M('exchange_code')->where(['code'=>$exchange_code])->setField(['uid'=>$this->userinfo['uid'], 'exchange_time'=>time()]);

                $this->ajaxReturn(['status'=>'success', 'info'=>$this->userinfo['money']+$exchange_code_info['money']]);
            }else{
                $this->ajaxReturn(['status'=>'error', 'info'=>'兑换码已过期，有效期至：'.date('Y-m-d H:i:s', $exchange_code_info['expiration'])]);
            }
        }else{
            //兑换码已被使用
            if($exchange_code_info['uid'] == $this->userinfo['uid']){
                $this->ajaxReturn(['status'=>'used', 'info'=>'兑换码已被你使用，使用时间为'.date('Y-m-d H:i:s', $exchange_code_info['exchange_time'])]);
            }else{
                $this->ajaxReturn(['status'=>'used', 'info'=>'兑换码已被使用，使用时间为'.date('Y-m-d H:i:s', $exchange_code_info['exchange_time'])]);
            }
        }
    }
    
    public function checkin(){
        //先获取用户上次签到的时间
        $last_checkin_time = M('user')->where(['uid'=>$this->userinfo['uid']])->getField('last_check_in_time');
        
        //检查用户是不是今天签到的
        if(date('ymd', $last_checkin_time) == date('ymd', time())){
            //相等说明用户在今天已经签到了
            $this->ajaxReturn(['status'=>'error', 'info'=>'你今天已经签到过了哦！']);
        }
        
        
        $base = mt_rand()%100/100;
        //使用三次函数获得最高奖励的比例
        //函数为0.9x^3 + 0.1;
        $ratio = 0.9 * pow($base, 3) + 0.1;
        $money = round(C('MIN_GIFT_MONEY') + (C('MAX_GIFT_MONEY') - C('MIN_GIFT_MONEY'))*$ratio, 2);
        //根据比率计算出用户签到所获得的喵币
        M('user')->where(['uid'=>$this->userinfo['uid']])->setInc('money', $money);
        M('user')->where(['uid'=>$this->userinfo['uid']])->setField('last_check_in_time', time());
        
        $this->ajaxReturn(['status'=>'success', 'info'=>'签到成功，您获得了'.$money.'个喵币']);
    }
}