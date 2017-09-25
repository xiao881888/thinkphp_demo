<?php
namespace H5\Controller;
use Think\Controller;
use Home\Util\Factory;

class BaseController extends Controller{

    protected $code = 0;
    protected $data = array();
    protected $response_data;
    private $request = array();

    protected $lock_time = 5;
    protected $lock_name;
    public static $lock_swich = false;

    protected static $model_instance = array();

    private static $_redis = null;
    private static $_redis_db = 0;


    public $uid = 0;

    public function __construct()
    {
        H5Log('cookie:'.print_r($_COOKIE,true),'debug_base');
        H5Log('session:'.print_r($_SESSION,true),'debug_base');
        H5Log('server:'.print_r($_SERVER,true),'debug_base');

        $this->_checkHttps();
        $this->_declareHeader();

        $this->request = $this->getResquest();

        if (IS_POST){
            H5Log('uid:'.$this->uid.print_r($this->request,true),'h5_post');
        }

        if (IS_GET){
            H5Log(print_r($_GET,true),'h5_get');
        }

        $method = CONTROLLER_NAME.'/'.ACTION_NAME;

        if (in_array($method,C('API_AUTH'))){
            $this->auth();
            if (IS_POST){
                $this->lock_name = md5(json_encode($this->request));
                $this->setLock($this->lockName($this->lock_name));
            }
        }

        $valid_param = C('VAILD_PARAM');

        if (key_exists($method,$valid_param)){
            $this->validParam($valid_param,$method);
        }

    }

    public static function initializeRedis()
    {
        if(empty(self::$_redis)){
            self::$_redis = Factory::createAliRedisObj();
            self::$_redis->select(self::$_redis_db);
        }
    }

    public static function redisInstance()
    {
        return self::$_redis;
    }

    public function getResquest()
    {
        if (!empty($this->request)){
            return $this->request;
        }
        $raw = file_get_contents("php://input");
        return json_decode($raw,true);
    }

    public function input($key)
    {
        if (isset($this->request[$key])){
            return $this->request[$key];
        }
        return false;
    }

    /**
     * @param array $response
     * @param bool $status
     */
    public function response($response = array(), $http_code = 200, $format = array())
    {
        if (empty($response)){
            $response = array();
        }
        $this->data = $response;
        $result = array();

        $result += array(
            'code' => $this->code,
            'data' => $this->data
        );

        $this->response_data = $result;

        $this->releaseLock($this->lockName($this->lock_name));

        return $this->ajaxReturn($this->response_data);
    }

    public function responseError($code = '', $message = array(), $data = array())
    {
        if (empty($code)) {
            $code = RESPONSE_ERROR_WITH_MESSAGE;
        }

        $this->code = $code;

        if (empty($message)) {
            $message = C('RESPONSE_MESSAGE')[$this->code];
        }

        $this->response_data = [
            'code' => $this->code,
            'msg' => (string)$message,
            'data' => $data
        ];

        if ($release_lock){
            $this->releaseLock($this->lockName());
        }

        return $this->ajaxReturn($this->response_data);
    }

    public function auth()
    {
        $token = I('get.token',false);
        if (!$token){
            $this->responseError(RESPONSE_ERROR_WITHOUT_LOGIN);
        }

        if (!$this->checkLogin($token)){
            $this->responseError(RESPONSE_ERROR_WITHOUT_LOGIN);
        }
    }

    public function checkLogin($token = false)
    {
        if (!$token){
            $token = I('get.token',false);
            if (!$token){
                return false;
            }
        }

        if ($this->uid){
            return true;
        }

        $uid = D('Cookies')->getUidByToken($token);

        if ($uid) {
            $this->uid = $uid;
            return true;
        }

        return false;
    }

    public function validParam($valid_param,$method)
    {
        $error_message_default = '{param}格式不正确';
        foreach ($valid_param[$method] as $key => $item){
            $param = I($key,false) ? I($key) : $this->input($key);
            $item_count = count($item);
            $regex = $item_count > 1 ? $item[0] : $item;
            if (!validByRegex($param,$regex)){
                $error_message = $item_count > 1 ? $item[1] : '';
                if (C('API_DEBUG')){
                    if (empty($error_message)){
                        $error_message = str_replace('{param}',$key,$error_message_default);
                    }
                }
                $this->responseError(RESPONSE_ERROR_PARAM_FAILS,$error_message);
            }
        }
    }

    public static function getModelInstance($model_name,$project = 'Home')
    {
        if(array_key_exists($model_name,static::$model_instance)){
            return static::$model_instance[$model_name];
        }

        $class = '\\'.$project.'\Model\\'.str_replace('/','\\',$model_name).'Model';
        $instance = new $class();
        static::$model_instance[$model_name] = $instance;
        return $instance;
    }

    public function lockName($name = '')
    {
        if (empty($this->uid)){
            return 'lock:'.CONTROLLER_NAME.':'.ACTION_NAME . ':' . $name;
        }
        return 'lock:'.$this->uid.':'.CONTROLLER_NAME.':'.ACTION_NAME . ':' . $name;
    }

    public function releaseLock($key)
    {
        self::initializeRedis();
        if (self::$lock_swich and self::$_redis->exists($key)){
            return self::$_redis->del($key);
        }

        return true;
    }

    public function setLock($key)
    {
        self::initializeRedis();
        $result = self::$_redis->setnx($key,json_encode($this->request));

        if ($result){
            self::$_redis->expire($key,$this->lock_time);
            self::$lock_swich = true;
            return true;
        }

        $this->responseError(RESPONSE_ERROR_TOO_OFTEN);
    }

    private function _checkHttps()
    {
        //本机开发环境
        if ($_SERVER['NodeNumber'] == 1 and empty(get_cfg_var('PROJECT_RUN_MODE'))){
            return;
        }
        if (!isSecure()){
            $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: ' . $redirect);
        }
    }

    private function _declareHeader()
    {
        if (C('API_DEBUG')){
            header('Access-Control-Allow-Origin:*');
        }else{
            header('Access-Control-Allow-Origin:'.C('WEB_URL'));
            if (getDomainFormUrl($_SERVER['HTTP_REFERER']) != getDomainFormUrl(C('WEB_URL'))){
                $this->responseError(RESPONSE_ERROR_HEADER);
            }
        }

        header('Access-Control-Allow-Methods:GET, POST, OPTIONS');
        header('Access-Control-Request-Headers: content-type,Access-Control-Allow-Headers, Authorization, X-Requested-With');
        //header('Access-Control-Max-Age: 86400');
        //header('Access-Control-Allow-Credentials: true');
        header('Content-Type: application/json;charset=utf-8');
    }

}