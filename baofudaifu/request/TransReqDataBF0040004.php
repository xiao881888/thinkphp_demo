<?php

 class TransReqDataBF0040004{
		 
		 public $values = array('trans_no' => '商户订单号', 
		 'trans_money' => '转账金额',
		 'to_acc_name' => '收款人姓名', 
		 'to_acc_no' => '收款人银行帐号',
		 'to_bank_name' => '收款人银行名称', 
		 'to_pro_name' => '收款人开户行省名',
		 'to_city_name' => '收款人开户行市名', 
		 'to_acc_dept' => '收款人开户行机构名');
		 
		 
		 
		// __get()方法用来获取私有属性	
		public function _get($property_name)
		{
			echo "获取属性：",$property_name."，值：",$this->values[$property_name],"\n";
			if (isset($this->values[$property_name])) {  //判断一下
				return $this->values[$property_name];
			} else {
				echo '没有此属性！'.$property_name;
			} 
				
		}
		// __set()方法用来设置私有属性
		public function _set($property_name, $value)
		{
			echo "设置属性：",$property_name,"，值：",$value,"\n";
			if (isset($this->values[$property_name])) {  //这里也判断一下
				$this->values[$property_name] = $this->validate($value);
			} else {
				echo '没有此属性！'.$property_name;
			} 
		}
		
		public function _getValues(){
			//var_dump($this->values);
			return $this->values;
		}
		
		private function validate($value){
			return htmlspecialchars(addslashes($value));
        //等等
		} 
	}


 ?>