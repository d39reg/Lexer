<style>
	table
	{
		border:1px solid #000;
		
	}
	td
	{
		border:1px solid #000;
		text-align:center;
	}
	
	td.type1
	{
		background:#EEF;
	}
	td.type2
	{
		background:#AFF;
	}
	td.type3
	{
		background:#AFA;
	}
	td.type4
	{
		background:#AAC;
	}
	td.type5
	{
		background:#CCD;
	}
</style>
<?php
	require_once('lexer.php');
	
	function fload($name)
	{
		$f = fopen($name,"rb");
		$data  = fread($f,filesize($name));
		fclose($f);
		return $data;
	}
	
	$lex=new lexer();
	//$lex->addWhite = true;
	$lex->load(fload('example.txt'));
	echo '<table><tr><td>Имя</td><td>Тип</td><td>Строка</td></tr>';
	while($lex->next)
	{
		$T_NAME = str_replace("\r","\\r",$T_NAME);
		$T_NAME = str_replace("\n","\\n",$T_NAME);
		$T_NAME = str_replace("\t","\\t",$T_NAME);
		$T_NAME = str_replace(" ","\\+",$T_NAME);
		echo "<tr><td>$T_NAME</td>";
		echo "<td class='type$T_TYPE'>$_DEBUG_NAME_TYPE[$T_TYPE]</td>";
		echo "<td>$T_LINE</td></tr>";
	}
	echo '</table>';
	echo "<hr>";
	
	$lex->reset;
	
	echo '<table><tr><td>Имя</td><td>Тип</td><td>Строка</td></tr>';
	while($lex->next)
	{
		echo "<tr><td>$T_NAME</td>";
		echo "<td class='type$T_TYPE'>$_DEBUG_NAME_TYPE[$T_TYPE]</td>";
		echo "<td>$T_LINE</td></tr>";
	}
	echo '</table>';