<?php

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

function remove_acentos($texto){
   $search = explode(",","à,á,â,ä,æ,ã,å,ā,ç,ć,č,è,é,ê,ë,ē,ė,ę,î,ï,í,ī,į,ì,ł,ñ,ń,ô,ö,ò,ó,œ,ø,ō,õ,ß,ś,š,û,ü,ù,ú,ū,ÿ,ž,ź,ż,À,Á,Â,Ä,Æ,Ã,Å,Ā,Ç,Ć,Č,È,É,Ê,Ë,Ē,Ė,Ę,Î,Ï,Í,Ī,Į,Ì,Ł,Ñ,Ń,Ô,Ö,Ò,Ó,Œ,Ø,Ō,Õ,Ś,Š,Û,Ü,Ù,Ú,Ū,Ÿ,Ž,Ź,Ż");
   $replace =explode(",","a,a,a,a,a,a,a,a,c,c,c,e,e,e,e,e,e,e,i,i,i,i,i,i,l,n,n,o,o,o,o,o,o,o,o,s,s,s,u,u,u,u,u,y,z,z,z,A,A,A,A,A,A,A,A,C,C,C,E,E,E,E,E,E,E,I,I,I,I,I,I,L,N,N,O,O,O,O,O,O,O,O,S,S,U,U,U,U,U,Y,Z,Z,Z");
   return str_replace($search, $replace, $texto);
}

function decode_brcode($brcode){
   $n=0;
   while($n < strlen($brcode)) {
      $codigo=substr($brcode,$n,2);
      $n+=2;
      $tamanho=substr($brcode,$n,2);
      $n+=2;
      $valor=substr($brcode,$n,$tamanho);
      $n+=2;
      $retorno[$codigo]="$valor";
      $brcode=substr($brcode,$n,strlen($brcode)-$n);
   }
   return $retorno;
}

function valida_uuid($uuid_text) {
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