<?php
namespace Integral\Controller;
use Think\Controller;

class GlobalController extends Controller {

    protected $redis = NULL;
    public function __construct(){
        if(!$this->redis){
            $this->redis = getRedis();
        }
        parent::__construct();
    }
	
}