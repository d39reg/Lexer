<?php
	$T_NAME='';
	$T_TYPE=0;
	$T_LINE=0;
	// Тип токена (список)
	define('END',0);
	define('DEC',1);
	define('STR',2);
	define('FNC',3);
	define('VRS',4);
	define('IND',5);
	define('RGS',6);
	define('CMN_LINE',7);
	define('CMN_TEXT',8);
	define('COMENT_ON',1);
	define('CONVERT_ON',2);
	//Debug
	$_DEBUG_NAME_TYPE = array('Завершение','Число','Строка','Функция','Переменная','Идентификатор','Комментарий однострочный','Комментарий многострочный');
	// Класс токена-генератора
	class lexer {
		public $LINE_N=1;
		public $LINE_I=-1;
		public $LINE=array();
		public $cmd_hex=false;
		private $hexdata=array('a','b','c','d','e','f','A','B','C','D','E','F');
		public $BLEN=0;
		private $DATA='';
		private $I=-1;
		private $LEN=0;
		private $token='';
		private $type=null;
		public $quote='';
		public $cmd=0;
		private $cur_next=-1;
		private $data_arr=array();
		private $sym_10="\n";
		public function reset()
		{
			
		}
		public function load($code)
		{
			$this->sym_10=chr(10);
			$this->DATA=$code.' ';
			$this->I=-1;
			$this->LEN=strlen($code);
			$this->token='';
			$this->type=null;
			$array=array();
			$i=-1;
			$this->_next();
			while($this->type!=END){$array[++$i]=array($this->token,$this->type,$this->current_line());$this->_next();}
			$array[++$i]=array(null,0,$this->current_line());
			$this->BLEN=$i+1;
			$this->data_arr=$array;//print_r($array);
			return $array; // Возвращает массив (0)=>Название токена, (1)=>Тип токена
		}
		public function next()
		{
			global $T_NAME,$T_TYPE,$T_LINE;
			if(++$this->cur_next>=$this->BLEN)--$this->cur_next;
			$token=$this->data_arr[$this->cur_next];
			$T_NAME=$token[0];
			$T_TYPE=$token[1];
			$T_LINE=$token[2];
			return $T_TYPE;
		}
		public function expect_current($s)
		{
			global $T_NAME;
			$this->current();
			return $T_NAME==$s;
		}
		public function expect_next($s)
		{
			global $T_NAME;
			$this->next();
			if($T_NAME==$s)return true;
			$this->back();
			return false;
		}
		public function expect_back($s)
		{
			global $T_NAME;
			$this->back();
			if($T_NAME==$s)return true;
			$this->next();
			return false;
		}
		public function back()
		{
			global $T_NAME,$T_TYPE,$T_LINE;
			if(--$this->cur_next<0)$this->cur_next=0;
			$token=$this->data_arr[$this->cur_next];
			$T_NAME=$token[0];
			$T_TYPE=$token[1];
			$T_LINE=$token[2];
			return $T_TYPE;
		}
		public function current()
		{
			global $T_NAME,$T_TYPE,$T_LINE;
			if($this->cur_next<0)$this->cur_next=0;
			$token=$this->data_arr[$this->cur_next];
			$T_NAME=$token[0];
			$T_TYPE=$token[1];
			$T_LINE=$this->current_line();
			return $T_TYPE;
		}
		private function white($s)
		{
			if($s==$this->sym_10)++$this->LINE_N; 
			return $s==chr(13)||$s==' '||$s==$this->sym_10||$s==chr(9);
		}
		private function _strchr($a,$b)
		{
			if(($a!='')&&($b!=''))return strpos($a,$b)!==false;
			return false;
		}
		private function number($s)
		{
			if($this->cmd_hex&&in_array($s,$this->hexdata))return true;
			return $s>='0'&&$s<='9';
		}
		private function word($s){return ($s>='A')&&($s<='z')&&!($this->_strchr('[\]^`',$s));}
		public function current_line()
		{
			return $this->LINE[$this->LINE_I];
		}
		public function back_line()
		{
			if(--$this->LINE_I<0)return $this->LINE[++$this->LINE_I];
			return $this->LINE[$this->LINE_I];
		}
		public function next_line()
		{
			if(++$this->LINE_I>=count($this->LINE))return $this->LINE[--$this->LINE_I];
			return $this->LINE[$this->LINE_I];
		}
		private function _next()
		{
			$this->type=null;
			$i=$this->I;
			$data=$this->DATA;
			$l=$this->LEN;
			beg1:
			if($l<=++$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			$s=$data[$i];
			$token=array();
			$ii=-1;
			while($this->white($s))
			{ 
				++$i;
				if($i>=$l)
				{ 
					$this->I=$i;
					$this->token='';
					$this->type=END;
					return '';
				} 
				$s=$data[$i];
			}
			if($l<=$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			if($s=='/')
			{
				if($l>$i+1)
				{
					if($data[$i+1]=='/')
					{
						$this->type=CMN_LINE;
						$i+=2;
						$tmp=$data[$i];
						while($tmp!=$this->sym_10)
						{
							$token[++$ii]=$tmp;
							if($tmp==chr(13))break;
							if($l<=++$i)
							{
								if(!($this->cmd&COMENT_ON))goto beg1;
								goto end_func;
							}
							$tmp=$data[$i];
						}
						if(!($this->cmd&COMENT_ON))goto beg1;
						//++$this->LINE_N;
						goto end_func;
					}
					elseif($data[$i+1]=='*')
					{
						$i+=2;
						if($l<=$i)
						{
							$this->type=END;
							return '';
						}
						while(!($data[$i]=='*'&&$data[$i+1]=='/'))
						{
							$token[++$ii]=$data[$i];
							if($data[$i]==$this->sym_10)++$this->LINE_N;
							if($l<=++$i)
							{
								if(!($this->cmd&COMENT_ON))goto beg1;
								goto end_func;
							}
						}
						++$i;
						if(!($this->cmd&COMENT_ON))goto beg1;
						$this->type=CMN_TEXT;
						goto end_func;
					} 
					else goto end1;
				}
				if($l<=++$i) return;
				$s=$data[$i];
			}
			end1:
			$this->LINE[++$this->LINE_I]=$this->LINE_N;
			while($this->white($s))
			{
				if(++$i>=$l)
				{
					$this->type=END;
					return '';
				}
				$s=$data[$i];
			}
			if($l<=$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			elseif($this->_strchr(';(,)}{[]+.*/:^%?$@',$s))
			{
				$this->type=IND;
				$this->I=$i;
				$this->token=$s;
				return $s;
			} 
			elseif($this->_strchr('=<>!~&|#',$s))
			{
				while($this->_strchr('=<>!~&|#',$s))
				{
					$token[++$ii]=$s;
					if($l<=++$i) break;
					$s=$data[$i];
				}
				--$i;
				$this->type=IND;
			} 
			elseif($this->number($s)||$s=='-')
			{
				if($s=='-')
				{
					$token[++$ii]=$s;
					if($l<=++$i) break;
					$s=$data[$i];
				}
				$this->cmd_hex=false;
				$beg_sym=$s;
				
				while($this->number($s)||$s=='.')
				{
					$token[++$ii]=$s;
					if($l<=++$i) break;
					$s=$data[$i];
					if(($s=='x'||$s=='X')&&!$ii&&$beg_sym=='0')
					{
						$this->cmd_hex=true;
						if($l<=++$i) break;
						$s=$data[$i];
						--$ii;
					}
				}
				if($this->cmd&CONVERT_ON&&$this->cmd_hex)
				{
					$tmp=hexdec(implode('',$token));
					$token=array();
					$token[0]=$tmp;
				}
				--$i;
				$this->type=DEC;
			} 
			elseif($this->word($s))
			{
				$this->type=VRS;
				while($this->word($s)||$this->number($s))
				{
					$token[++$ii]=$s;
					if($l<=++$i) break;
					$s=$data[$i];
				}
				if($l>$i+1)
				{
					if($this->white($data[$i+1]))
					{
						while($this->white($s))
						{
							if($l<=$i+1)break;
							$s=$data[++$i];
						}
					}
					if($s=='(') $this->type=FNC;
				}
				--$i;
			} 
			elseif($s=='"'||$s=='\'')
			{
				$tmp=$this->quote=$s;
				$s=$data[++$i];
				while($s!=$tmp)
				{
					$token[++$ii]=$s;
					if($s=='\\')
					{
						if($l<=++$i) break;
						$token[++$ii]=$data[$i];
					}
					elseif($s=="\r")++$this->LINE_N;
					if($l<=++$i) break;
					$s=$data[$i];
				}
				$this->type=STR;
			}
			end_func:
			
			
			$this->I=$i;
			return $this->token=implode('',$token);
		}
	}
	