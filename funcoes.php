<?php

require_once("config.php");

function play_sql($sql){
	$cnx_sql=new mysqli($hostdb, $userdb, $passworddb, $databasedb);
	mysqli_set_charset($cnx_sql,'utf8');
	$dados=mysqli_query($cnx_sql, $sql);
   mysqli_close($cnx_sql);
	return $dados;
}

function formata_chaves($chaves){
   $txt_retorno='';
   foreach ($chaves as $tp => $v){
      switch($tp){
         case "F":
            $tipo="CPF";
         break;
         case "J":
            $tipo="CNPJ";
         break;
         case "T":
            $tipo="Telefone";
         break;
         case "E":
            $tipo="E-mail";
         break;
         case "U":
            $tipo="EVP";
         break;
         case "X":
            $tipo="Desconhecida";
         break;
      }
      if (count($v) > 1){ $tipo.="s"; }
      $txt_retorno.="- " . count($v) . " $tipo: " . implode(",",$v) . "\n";
   }
   return $txt_retorno;
}

function sql_simples($query){
	$cn_sqlsim=new mysqli($hostdb, $userdb, $passworddb, $databasedb);
	$cn_sqlsim->set_charset("utf8");
	$ex_sqlsim=mysqli_query($cn_sqlsim,$query);
	$retorno=mysqli_fetch_row($ex_sqlsim);
	if (mysqli_errno($cn_sqlsim) > 0) {
		return "ERRO: ".mysqli_error($cn_sqlsim);
	}
	$cn_sqlsim->close();
	return $retorno[0];
}

function insere_chaves($chaves,$usuario,$hashtags){
   $txt_retorno='';
   foreach ($chaves as $tp => $v){
      switch($tp){
         case "F":
            $tbl="cpf";
         break;
         case "J":
            $tbl="cnpj";
         break;
         case "T":
            $tbl="telefone";
         break;
         case "E":
            $tbl="email";
         break;
         case "U":
            $tbl="evp";
         break;
      }
      if ($tp != "X") {
         for($i=0;$i<count($v);$i++){
            insere_banco($tbl,$v[$i],$usuario,$hashtags);
         }
      }
   }
   return $txt_retorno;
}

function valida_email($email){
   return filter_var($email, FILTER_VALIDATE_EMAIL);
}
function insere_banco($tipo,$chave,$usuario,$hashtags){
   if ($tipo == "evp") { $chave="UNHEX('$chave')"; }
   elseif (($tipo=="telefone") && strlen($chave) > 11) { $chave="'" . substr($chave,-11) . "'"; }
   else { $chave="'" . $chave . "'"; }
   $hashes="'" . implode(", ",$hashtags) . "'";
   $query="INSERT INTO $tipo ($tipo, user_id, hashtag) VALUES ($chave, $usuario , $hashes)";
   $logfile=fopen("../log.log","a");
   fwrite($logfile,"$logtext\n");
   fclose($logfile);
}

function remove_acentos($texto){
   $search = explode(",","à,á,â,ä,æ,ã,å,ā,ç,ć,č,è,é,ê,ë,ē,ė,ę,î,ï,í,ī,į,ì,ł,ñ,ń,ô,ö,ò,ó,œ,ø,ō,õ,ß,ś,š,û,ü,ù,ú,ū,ÿ,ž,ź,ż,À,Á,Â,Ä,Æ,Ã,Å,Ā,Ç,Ć,Č,È,É,Ê,Ë,Ē,Ė,Ę,Î,Ï,Í,Ī,Į,Ì,Ł,Ñ,Ń,Ô,Ö,Ò,Ó,Œ,Ø,Ō,Õ,Ś,Š,Û,Ü,Ù,Ú,Ū,Ÿ,Ž,Ź,Ż");
   $replace =explode(",","a,a,a,a,a,a,a,a,c,c,c,e,e,e,e,e,e,e,i,i,i,i,i,i,l,n,n,o,o,o,o,o,o,o,o,s,s,s,u,u,u,u,u,y,z,z,z,A,A,A,A,A,A,A,A,C,C,C,E,E,E,E,E,E,E,I,I,I,I,I,I,L,N,N,O,O,O,O,O,O,O,O,S,S,U,U,U,U,U,Y,Z,Z,Z");
   return remove_emoji(str_replace($search, $replace, $texto));
}

function remove_emoji($string){
   return preg_replace('%(?:
   \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
 | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
 | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
)%xs', '  ', $string);      
}
function decode_brcode($brcode){
   $n=0;
   while($n < strlen($brcode)) {
      $codigo=substr($brcode,$n,2);
      $n+=2;
      $tamanho=intval(substr($brcode,$n,2));
      if (!is_numeric($tamanho)) {
         return false;
      }
      $n+=2;
      $valor=substr($brcode,$n,$tamanho);
      $n+=$tamanho;
      if ($codigo==26) {
         $retorno[$codigo]=decode_brcode($valor);
      }
      else {
         $retorno[$codigo]="$valor";
      }
   }
   return $retorno;
}

function valida_uuid($uuid_text) {
   $uuid_text=str_replace("-","",substr($uuid_text,0,36));
	return preg_match("/^[0-9a-fA-F]{32}$/",$uuid_text);
}

function valida_telefone($fone){
   return preg_match("/^(\+)?(55)?[\s]?\(?\d{2}\)?[\s-]?[\s9]\d{4}-?\d{4}$/",$fone);
}

function valida_cnpj($cnpj) {
    if (!preg_match("/^[0-9]{14}$/",$cnpj) || $cnpj=='00000000000000' || $cnpj=='11111111111111' || $cnpj=='22222222222222' || $cnpj=='33333333333333' || $cnpj=='44444444444444' || $cnpj=='55555555555555' || $cnpj=='66666666666666' || $cnpj=='77777777777777' || $cnpj=='88888888888888' || $cnpj=='99999999999999') { return false; }
    else {
       $soma1 = ($cnpj[0] * 5) +
       ($cnpj[1] * 4) +
       ($cnpj[2] * 3) +
       ($cnpj[3] * 2) +
       ($cnpj[4] * 9) +
       ($cnpj[5] * 8) +
       ($cnpj[6] * 7) +
       ($cnpj[7] * 6) +
       ($cnpj[8] * 5) +
       ($cnpj[9] * 4) +
       ($cnpj[10] * 3) +
       ($cnpj[11] * 2);
       $resto = $soma1 % 11;
       $digito1 = $resto < 2 ? 0 : 11 - $resto;
       $soma2 = ($cnpj[0] * 6) +
       ($cnpj[1] * 5) +
       ($cnpj[2] * 4) +
       ($cnpj[3] * 3) +
       ($cnpj[4] * 2) +
       ($cnpj[5] * 9) +
       ($cnpj[6] * 8) +
       ($cnpj[7] * 7) +
       ($cnpj[8] * 6) +
       ($cnpj[9] * 5) +
       ($cnpj[10] * 4) +
       ($cnpj[11] * 3) +
       ($cnpj[12] * 2);
       $resto = $soma2 % 11;
       $digito2 = $resto < 2 ? 0 : 11 - $resto;
       return (($cnpj[12] == $digito1) && ($cnpj[13] == $digito2));
    }
}

function valida_cpf($cpf) {	// Verifiva se o número digitado contém todos os digitos
    if (!preg_match("/^[0-9]{11}$/",$cpf) || $cpf == '00000000000' || $cpf == '11111111111' || $cpf == '22222222222' || $cpf == '33333333333' || $cpf == '44444444444' || $cpf == '55555555555' || $cpf == '66666666666' || $cpf == '77777777777' || $cpf == '88888888888' || $cpf == '99999999999') { return false; }
    else {
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf{$c} * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf{$c} != $d) {
                return false;
            }
        }
        return true;
    }
}

?>