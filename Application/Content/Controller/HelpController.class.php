<?php
namespace Content\Controller;
use Think\Controller;
/**
 * @date 2014-12-9
 * @author tww <merry2014@vip.qq.com>
 */
class HelpController extends Controller{

    public function aboutus(){
        $version = I('v');
        $this->assign('version', $version);
        $package = I('package');
        if(empty($package)){
            $this->display('aboutus');
        }
        $app_id = getRequestAppId($package);
        if($app_id == C('APP_ID_LIST.TIGER')){
            $this->display('aboutus');
        }elseif($app_id == C('APP_ID_LIST.BAIWAN')){
            $this->display('aboutus_baiwan');
        }elseif($app_id == C('APP_ID_LIST.NEW')){
            $this->assign('package', $package);
            $this->display('aboutus_new');
        }
    }

    public function jcrxj(){
        $this->display('jcrxj');
    }

    public function jcsfc(){
        $this->display('jcsfc');
    }

	public function _empty(){
		$this->ssq();
	}
	
	public function coupon(){
		$this->assign('meta_title','红包管理帮助');
	    $this->display('coupon');
	}

	public function coupon_exchange(){
		$this->assign('meta_title','兑换红包帮助');
		$this->display();
	}

	public function withdraw(){
		$this->assign('meta_title','提现帮助');
		$this->display();
	}
	
	public function ssq(){
		$this->display('ssq');
	}
	
	public function p3(){
		$this->display('p3');
	}
	
	public function dlt(){
		$this->display('dlt');
	}
	
	public function p5(){
		$this->display('p5');
	}
	
	public function ks(){
		$this->display('ks');
	}
	
	public function jczq(){
		$this->display('jczq');
	}
	
	public function jclq(){
		$this->display('jclq');
	}

	public function jxzh(){
        $this->display('jxzh');
    }

    public function hm(){
        $this->display('hm');
    }
	
	/*public function aboutus(){
		$version = I('v');
		$this->assign('version', $version);
		$this->display('aboutus');
	}*/
	
	public function linkus(){
		$info = D('Config')->getInfoByName('LINK_US');
		$this->assign('meta_title', '联系我们');
		$this->assign('info',$info);
		$this->display('linkus');
	}
	
	public function regagreement(){
		$info = D('Config')->getInfoByName('REG_AGREEMENT');
		$this->assign('meta_title', '注册协议');
		$this->assign('info',$info);
		$this->display('regagreement');
	}
	
	public function betagreement(){
		$info = D('Config')->getInfoByName('BET_AGREEMENT');
        $this->assign('app_id', I('app_id'));
		$this->assign('meta_title', '投注协议');
		$this->assign('info',$info);
		$this->display('betagreement');
	}

	public function xiaomiAutoStartApp(){
		$this->display();
	}	
}