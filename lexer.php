<?php
	// Глобальные переменные
	$T_NAME  = '';
	$T_TYPE  = 0;
	$T_LINE  = 0;
	$T_QUOTE = '';
	
	// Тип токена (список)
	define('END'       ,0);
	define('DEC'       ,1);
	define('STR'       ,2);
	define('FNC'       ,3);
	define('VRS'       ,4);
	define('IND'       ,5);
	define('CMN_LINE'  ,6);
	define('CMN_TEXT'  ,7);
	define('ENT'       ,8);
	define('WHT'       ,9);
	
	
	define('COMENT_ON' ,1);
	
	//Debug
	$_DEBUG_NAME_TYPE = array('Завершение','Число','Строка','Функция','Переменная','Идентификатор','Комментарий однострочный','Комментарий многострочный','Переход строки','Пробельные символы');
	
	// Класс лексического-генератора
	class lexer 
	{
		
		public $LINE_N=1;
		public $LINE_I=-1;
		public $LINE=array(1);
		
		public $addQuote = false;
		public $addWhite = false;
		public $addComment = false;
		public $addShield = true;
		
		public $convertInteger = false;
		
		public $cmd_hex=false;
		private $hexdata=array('a','b','c','d','e','f','A','B','C','D','E','F');
		
		public $cmd_bin=false;
		
		public  $BLEN     = 0;
		private $DATA     = [];
		private $I        = -1;
		private $LEN      = 0;
		private $token    = '';
		private $type     = null;
		public  $quote    = '';
		public  $cmd      = 0;
		public  $encode   = '';
		private $cur_next = -1;
		private $data_arr = array();

		public function load($code)
		{
			if(gettype($code) == 'string')
			{
				$this->encode = mb_detect_encoding($code);
				$code = mb_convert_encoding($code, 'UTF-8', $this->encode);
				$i = 0; $l = strlen($code);
				while(true)
				{
					$s = mb_substr($code, $i, 1, $this->encode);
					if($s == null) break;
					$this->DATA[$i++] = $s;
				}
				$this->LEN = count($this->DATA);
			}
			$this->I = -1;
			$this->token = '';
			$this->cur_next = -1;
			$this->type = null;
			$array = array();
			$i = -1;
			
			$this->_next();
			while ($this->type != END)
			{
				$array[++$i] = array(
					$this->token
					,$this->type
					,$this->current_line()
					,$this->quote
				);
				$this->_next();
			}
			
			$array[++$i] = array(
				null
				,0
				,$this->current_line()
				,''
			);
			
			$this->BLEN = $i+1;
			$this->data_arr = $array;//print_r($array);
			
			return $array; // Возвращает массив (0)=>Название токена, (1)=>Тип токена
		}
		private function whiteConvert($s)
		{
			if(!$this->addShield) return $s;
			$symbol = array(
				"\r"=>'\\r'
				,"\n"=>'\\n'
				,"\t"=>'\\t'
				," "=>'\\x20'
			);
			if(!array_key_exists($s,$symbol)) return $s;
			return $symbol[$s];
		}
		private function translit($str)
		{
			$rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
			$lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
			return str_replace($rus, $lat, $str);
		}
		public function __get($property)
		{
			global $T_NAME,$T_TYPE,$T_LINE,$T_QUOTE;
			
			if ($property == 'next' || $property == 'current' || $property == 'back')
			{
				next__:
				switch ($property)
				{
					case 'next':
						if (++$this->cur_next>=$this->BLEN)--$this->cur_next;
						$token = $this->data_arr[$this->cur_next];
						if (!$this->addWhite && ($token[1] == ENT || $token[1] == WHT)) goto next__;
						break;
					case 'current':
						if ($this->cur_next<0)$this->cur_next=0;
						$token = $this->data_arr[$this->cur_next];
						if (!$this->addWhite && ($token[1] == ENT || $token[1] == WHT)) goto next__;
						break;
					case 'back':
						if (--$this->cur_next<0)$this->cur_next=0;
						$token = $this->data_arr[$this->cur_next];
						if (!$this->addWhite &&  ($token[1] == ENT || $token[1] == WHT)) goto next__;
						break;
				}
				
				$T_NAME  = $token[0];
				$T_TYPE  = $token[1];
				$T_LINE  = $token[2];
				$T_QUOTE = $token[3];
				
				return $T_TYPE;
			}
			elseif ($property == 'reset')
			{
				$this->LINE_N = 1;
				$this->load($this->DATA);
				return $T_TYPE;
			}
		}
		
		public function expect_current($s)
		{
			global $T_NAME;
			$this->current;
			return $T_NAME == $s;
		}
		
		public function expect_next($s)
		{
			global $T_NAME;
			$this->next;
			if ($T_NAME == $s)return true;
			$this->back;
			return false;
		}
		
		public function expect_back($s)
		{
			global $T_NAME;
			$this->back;
			if ($T_NAME == $s)return true;
			$this->next;
			return false;
		}
		
		private function white1($s)
		{
			if ($s == "\n")++$this->LINE_N; 
			return $s == "\r"||$s == "\n";
		}
		private function white2($s)
		{
			return $s == ' '||$s == "\t";
		}
		
		private function _strchr($a,$b)
		{
			return strpos($a,$b) !==false;
		}
		
		private function number($s)
		{
			return $s>='0'&&$s<='9';
		}
		
		private function word($s){return ((($s>='A')&&($s<='z'))||(($s>=chr(192))&&($s<=chr(255))))&&!($this->_strchr('[\]^`',$s));}
		
		public function current_line()
		{
			return $this->LINE[$this->LINE_I<0?0:$this->LINE_I];
		}
		
		public function back_line()
		{
			return $this->LINE[--$this->LINE_I<0?++$this->LINE_I:$this->LINE_I];
		}
		
		public function next_line()
		{
			return $this->LINE[++$this->LINE_I>=count($this->LINE)?--$this->LINE_I:$this->LINE_I];
		}
		
		private function _next()
		{
			$this->type=null;
			$i=$this->I;
			$data=$this->DATA;
			$l=$this->LEN;
			beg1:
			if ($l<=++$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			$s = $data[$i];
			$token = array();
			$ii = -1;
			if ($this->white1($s))
			{
				do
				{
					if ($this->addWhite) $token[++$ii] = $this->whiteConvert($s);
					if (++$i >= $l)
					{
						$this->I=$i;
						$this->token='';
						$this->type=END;
						return '';
					}
					$s = $data[$i];
				}
				while ($this->white1($s));
				
				if ($this->addWhite)
				{
					--$i;
					$this->type = ENT;
					$this->LINE[++$this->LINE_I] = $this->LINE_N;
					goto end_func;
				}
				
			}
			if ($this->white2($s))
			{
				do
				{
					if ($this->addWhite) $token[++$ii] = $this->whiteConvert($s);
					if (++$i >= $l)
					{
						$this->I=$i;
						$this->token='';
						$this->type=END;
						return '';
					}
					$s = $data[$i];
				}
				while ($this->white2($s));
				
				if ($this->addWhite)
				{
					--$i;
					$this->type = WHT;
					$this->LINE[++$this->LINE_I] = $this->LINE_N;
					goto end_func;
				}
				
			}
			if ($l<=$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			
			if ($s == '/')
			{
				if ($l>$i+1)
				{
					if ($data[$i+1] == '/')
					{
						$this->type = CMN_LINE;
						$i += 2;
						$tmp = $data[$i];
						while ($tmp != "\n")
						{
							if ($l<=++$i)
							{
								if ($this->addComment) goto end_func;
								goto beg1;
							}
							if ($tmp == "\r")
							{
								if ($data[$i] != "\n") --$i;
								break;
							}
							$token[++$ii]=$this->whiteConvert($tmp);
							$tmp = $data[$i];
						}
						++$this->LINE_N;
						if ($this->addComment)goto end_func;
						goto beg1;
						
					}
					elseif ($data[$i+1] == '*')
					{
						$i+=2;
						if ($l<=$i)
						{
							$this->type=END;
							return '';
						}
						while (!($data[$i] == '*'&&$data[$i+1] == '/'))
						{
							$token[++$ii]=$this->whiteConvert($data[$i]);
							
							if ($data[$i] == "\n")++$this->LINE_N;
							if ($l<=++$i)
							{
								if ($this->addComment)goto end_func;
								goto beg1;
								
							}
						}
						++$i;
						$this->type=CMN_TEXT;
						if ($this->addComment) goto end_func;
						goto beg1;
					} 
					else goto end1;
				}
				if ($l<=++$i) return;
				$s=$data[$i];
			}
			
			end1:
			
			$this->LINE[++$this->LINE_I]=$this->LINE_N;
			
			if ($this->white1($s))
			{
				do
				{
					if ($this->addWhite) $token[++$ii] = $this->whiteConvert($s);
					if (++$i >= $l)
					{
						$this->I=$i;
						$this->token='';
						$this->type=END;
						return '';
					}
					$s = $data[$i];
				}
				while ($this->white1($s));
				
				if ($this->addWhite)
				{
					--$i;
					$this->type = ENT;
					$this->LINE[++$this->LINE_I] = $this->LINE_N;
					goto end_func;
				}
				
			}
			if ($this->white2($s))
			{
				do
				{
					if ($this->addWhite) $token[++$ii] = $this->whiteConvert($s);
					if (++$i >= $l)
					{
						$this->I=$i;
						$this->token='';
						$this->type=END;
						return '';
					}
					$s = $data[$i];
				}
				while ($this->white2($s));
				
				if ($this->addWhite)
				{
					--$i;
					$this->type = WHT;
					$this->LINE[++$this->LINE_I] = $this->LINE_N;
					goto end_func;
				}
				
			}
			if ($l<=$i)
			{ 
				$this->type=END; 
				return $this->token='';
			}
			elseif ($this->_strchr(';(,)}{[]+-.*/:^%?$@',$s))
			{
				$this->type=IND;
				$this->I=$i;
				$this->token=$s;
				return $s;
			} 
			elseif ($this->_strchr('=<>!~&|#',$s))
			{
				while ($this->_strchr('=<>!~&|#',$s))
				{
					$token[++$ii]=$s;
					if ($l<=++$i) break;
					$s=$data[$i];
				}
				--$i;
				$this->type=IND;
			} 
			elseif ($this->number($s))
			{
				$this->cmd_hex = false;
				$this->cmd_bin = false;
				$begSym = $s;
				$hex = false;
				while ($this->number($s)||$s == '.'||($this->cmd_hex&&in_array($s,array('a','b','c','d','e','f','A','B','C','D','E','F'))))
				{
					$token[++$ii] = $s;
					if ($l <= ++$i) break;
					$s = $data[$i];
					if (!$ii&&$begSym == '0')
					{
						if($s == 'x'||$s == 'X')
						{
							$this->cmd_hex = true;
							if ($l<=++$i) break;
							$s = $data[$i];
							--$ii;
						}
						elseif($s == 'b'||$s == 'B')
						{
							$this->cmd_bin = true;
							if ($l<=++$i) break;
							$s = $data[$i];
							--$ii;
						}
					}
				}
				/*if($s=='h')
				{
					$tmp = implode('',$token);
					if ($this->convertInteger) $tmp = hexdec($tmp);
					else $tmp = $tmp.'h';
					$token = array();
					$token[0] = $tmp;
					++$i;
				}
				*/
				
				if($this->cmd_hex)
				{
					$tmp = implode('',$token);
					if ($this->convertInteger) $tmp = hexdec($tmp);
					else $tmp = '0x'.$tmp;
					$token = array();
					$token[0] = $tmp;
				}
				elseif($this->cmd_bin)
				{
					$tmp = implode('',$token);
					if ($this->convertInteger) $tmp = bindec($tmp);
					else $tmp = '0b'.$tmp;
					$token = array();
					$token[0] = $tmp;
				}
				--$i;
				$this->type=DEC;
			} 
			elseif ($this->word($s))
			{
				while ($this->word($s)||$this->number($s))
				{
					$token[++$ii]=$s;
					if ($l <= ++$i) break;
					$s=$data[$i];
				}
				if ($i+1 < $l && $s == '(') $this->type=FNC;
				else $this->type=VRS;
				--$i;
			} 
			elseif ($s == '"'||$s == '\'')
			{
				$tmp=$this->quote = $s;
				if ($this->addQuote) $token[++$ii] = $s;
				$s = $data[++$i];
				while ($s != $tmp)
				{
					$token[++$ii]=$s;
					if ($s == '\\')
					{
						if ($l <= ++$i) break;
						$token[++$ii]=$data[$i];
					}
					elseif ($s == "\r") ++$this->LINE_N;
					if ($l<=++$i) break;
					$s=$data[$i];
				}
				if ($this->addQuote) $token[++$ii] = $this->quote;
				$this->type = STR;
			}
			
			end_func:
			
			$this->I=$i;
			$return = implode('',$token);
			return $this->token=$return;
		}
	}
	