<?php

 class TransReqData{
		 
	private $trans_reqDatas = array();
		 
		
	// 保存值
    function __array_push($array)
    {
        //echo "TransReqDatas set:$name=$array","\n";
		$trans_reqData = array("trans_reqData"=>$array);
		
		var_dump($trans_reqData);
		
        array_push($this->trans_reqDatas,$trans_reqData);
    }
	
	// 取得属性名称对应的值
	function __getArray2Json()
	{
		return json_encode($this->trans_reqDatas);
	}

	function __getTransReqDatas()
	{
		return $this->trans_reqDatas;
	}
		
		
	}


 ?>