<?php
require('config.php');
require('class-http-request.php');
require('functions.php');
require('mensagens.php');
require('funcoes.php');
$dateformat = "d-m-Y H:i:s";
$dateformatnosec = "d-m-Y H:i";
$timezone = "America/Fortaleza";
date_default_timezone_set($timezone);

$content = file_get_contents('php://input');
$update = json_decode($content, true);
/*$logtext=print_r($update, true);
$logfile=fopen("../log.log","a");
fwrite($logfile,"$logtext\n");
fwrite($logfile,"---\n");*/


if ($update["message"]) {
    $chatID = $update["message"]["chat"]["id"];
    $userID = $update["message"]["from"]["id"];
    $entidades = $update["message"]["entities"];
    $update["message"]["text"]=str_replace("\n"," ",$update["message"]["text"]);
    $msg = '';
    for ($i=0;$i<strlen($update["message"]["text"]);$i++){
        $ltr=substr($update["message"]["text"],$i,1);
        $cha=ord($ltr);
        if ($cha != 240) {
            $ascii[]=str_pad($cha, 3, "0", STR_PAD_LEFT);
            $msg.=$ltr;
        }
        else {
            $i+=3;
            $extra_offset[$i]=3;
        }
    }
    $mensagem=$msg;
    //fwrite($logfile,"Ascii:" . implode(" ",$ascii) . "\n");
    $hashtags = $telefones = $emails =array();
    $cmd=false;
    if (is_array($entidades)) {
        //fwrite($logfile,"Entidades é um array\n");
        for ($i=0;$i<count($entidades);$i++){
            //fwrite($logfile,"Iterando $i\n");
            $elemento=substr(remove_acentos($update["message"]["text"]),$entidades[$i]["offset"],$entidades[$i]["length"]);
            //fwrite($logfile,"Elemento: $elemento\n");
            switch($entidades[$i]["type"]) {
                case "bot_command":
                    $cmd=$elemento;
                    break;
                case "hashtag":
                    array_push($hashtags,$elemento);
                    break;
                case "phone_number":
                    if (strlen($elemento)>10) {
                        array_push($telefones,$elemento);
                    }
                    else { $elemento=''; }
                    break;
                case "email":
                    array_push($emails,$elemento);
                    break;
                default:
                //fwrite($logfile,"Tipo nao reconhecido: " . $entidades[$i]["type"] . "$elemento\n");
            }
            $msg=str_replace("$elemento","",$msg);
        }
        $mais=explode(" ",$msg);
    }
    $username = $update["message"]["chat"]["username"];
    $name = $update["message"]["chat"]["first_name"];
} 
$resposta='';

/*
$logtext=print_r($hashtags, true);
fwrite($logfile,"Hashtags: $logtext\n");
$logtext=print_r($emails, true);
fwrite($logfile,"Email: $logtext\n");
$logtext=print_r($telefones, true);
fwrite($logfile,"Telefones: $logtext\n");
fwrite($logfile,"COMANDO $cmd\n");
fwrite($logfile,"MSG:$msg\n");*/

/*else if($update["callback_query"]["data"]){
    $chatID = $update["callback_query"]["message"]["chat"]["id"];
    $userID = $update["callback_query"]["from"]["id"];
    $msgid = $update["callback_query"]["message"]["message_id"];
} else if($update["inline_query"]["id"]){
    $msg = $update["inline_query"]["query"];
    $userID = $update["inline_query"]["from"]["id"];
    $username = $update["inline_query"]["from"]["username"];
    $name = $update["inline_query"]["from"]["first_name"];
}*/

$result = $dbuser->query("SELECT * FROM BNoteBot_user WHERE userID = '" . $userID . "'") or die("0");
$numrows = mysqli_num_rows($result);
if($numrows == 0 && $update["inline_query"]["id"] == false){
    $query = "INSERT INTO user (id, username, firstname) VALUES ('$userID', '$username', '" . $dbuser->real_escape_string($name) . "')";
    $result = $dbuser->query($query) or die("0");
}
/* else {
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $status = $row['status'];
    $language = $lang = $row['lang'];
    $invertmemodata = $row['invertmemodata'];
    $justwritemode = $row['justwritemode'];
}*/

switch($cmd) {
    case "/start":
        sm($chatID,BEMVINDO);
    break;
    case "/incluir";
        if (count($hashtags) > 0) {
            for ($i=0;$i<count($telefones);$i++){
                $fone=$telefones[$i];
                if (valida_telefone($fone)){
                    $chaves["T"][]=$fone;
                }
                else {
                    $erros["T"][]=$fone;
                }
            }
            for ($i=0;$i<count($emails);$i++){
                $email=$emails[$i];
                if (valida_email($email)) {
                    $chaves["E"][]=$email;
                }
                else {
                    $erros["E"][]=$email;
                }
            }
            for ($i=0;$i<count($mais);$i++){
                $code=$mais[$i];
                if (preg_match("/^[0-9]{3}(\.)?[0-9]{3}(\.)?[0-9]{3}(-)?[0-9]{2}$/",$code)) {
                    $code=preg_replace("/[^0-9]/", "",$code);
                    if (valida_cpf($code)) {
                        $chaves["F"][]=$code;
                    }
                    else {
                        $erros["F"][]=$code;
                    }
                }
                elseif (preg_match("/^[0-9]{2}(\.)?[0-9]{3}(\.)?[0-9]{3}(\/)?[0-9]{4}(-)?[0-9]{2}$/",$code)) {
                    $code=preg_replace("/[^0-9]/", "",$code);
                    if (valida_cnpj($code)) {
                        $chaves["J"][]=$code;
                    }
                    else {
                        $erros["J"][]=$code;
                    }
                }
                elseif (strlen($code) >= 36){
                    if (valida_uuid($uuid)) {
                        $chaves["U"][]=$uuid;
                    }
                    else {
                        $erros["U"][]=$code;
                    }
                }
                elseif (strlen($code) > 0) {
                    $erros["X"][]="$code";
                }
            }
            $reconhecidas=formata_chaves($chaves);
            $resposta="Inclusão de chaves Pix no @meuPix_bot:\n$reconhecidas\n";
            if (count($erros) > 0){
                $resposta.="Erros encontrados:\n" . formata_chaves($erros);
            }
            $resposta.="Hashtag(s): " . implode(", ",$hashtags);
            insere_chaves($chaves,$userID,$hashtags);
        }
        else {
            $resposta="Utilize /incluir [chave] #banco para cadastrar uma chave do pix. É necessário utilizar pelo menos uma hashtag para identificar a chave do pix.";
        }
    break;
    case "/codigo":
        if (count($hashtags) > 0) {
            $brcode=substr($msg,1,strlen($msg)-1);
            $z=decode_brcode($brcode);
            if (is_array($z)) {
                $chave=$z["26"]["01"];
                $tipo='';
                if ((strlen($chave) == 11) && valida_cpf($chave)) {
                    $tipo="cpf";
                }
                elseif ((strlen($chave) == 14) && valida_cnpj($chave)) {
                    $tipo="cnpj";
                }
                elseif ((strlen($chave) == 36) && valida_uuid($chave)) {
                    $tipo="evp";
                }
                elseif (valida_telefone($chave)) {
                    $tipo="telefone";
                }
                elseif (valida_email($email)){
                    $tipo="email";
                }
                if ($tipo != '') {
                    $resposta="Cadastro de chave $tipo: $chave - Beneficiário: " . $z[59];
                    insere_banco($tipo,$chave,$userID,$hashtags);
                }
                else {
                    $resposta="Erro ao identificar o tipo da chave";
                }
            }
            else {
                $resposta="Seu código não é válido: $brcode";
            }
        }
        else {
            $resposta="Utilize /codigo [codigo pix copia e cola] #banco para cadastrar uma chave pix. É necessário utilizar pelo menos uma hashtag para a chave pix.";
        }
    break;
    default:
        $resposta="Não entendi o que você deseja com **" . $update["message"]["text"] . "** por favor digite / para obter a lista de comandos suportados pelo bot.";
    break;
}

if ($resposta != ''){
    sm($chatID,$resposta);
}

fwrite($logfile,"--------------------------\n");
fclose($logfile);


function langmenu($chatID){
    $text = "🇬🇧 - Welcome! Select a language:
🇮🇹 - Benvenuto! Seleziona una lingua:
🇩🇪 - Herzlich willkommen! Wähle eine Sprache:
🇧🇷 - Bem-vindo! Escolha um idioma:
🇷🇺 - Добро пожаловать! Выберите язык:";
    $menu[] = array("English 🇬🇧");
    $menu[] = array("Italiano 🇮🇹");
    $menu[] = array("Deutsch 🇩🇪");
    $menu[] = array("Português 🇧🇷");
    $menu[] = array("Russian 🇷🇺");
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function menu($text){
    global $lang;
    global $chatID;
    $menu[] = array(ADICIONAR);
    $menu[] = array(SALVO);
    $menu[] = array(INFORMACOES, CONTRIBUA);
    $menu[] = array(FEEDBACK);
    $menu[] = array(CONFIGURAR, GITHUB);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function setmenu($text){
    global $lang;
    global $chatID;
    $menu[] = array($lang['inlinemode']);
    $menu[] = array($lang['justwritemode']);
    $menu[] = array($lang['deleteallnote']);
  //  $menu[] = array($lang['settimezone']);
    $menu[] = array($lang['cancel']);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function inlinemodeset($invertmemodata){
    global $lang;
    global $chatID;
    if($invertmemodata == 1){ $invertmemodatatxt = $lang['enabled']; } else { $invertmemodatatxt = $lang['disabled']; }
    $menu[] = array(array(
        "text" => $lang['invertmemodata'] . $invertmemodatatxt,
        "callback_data" => "toggle-0-invertmemodata"));
    sm($chatID, $lang['settingstextinline'], $menu, 'HTML', false, false, false, true);
}

function toendate($date){
    $date = str_ireplace("oggi","today", $date);
    $date = str_ireplace("ieri","yesterday", $date);
    $date = str_ireplace("domani","tomorrow", $date);
    $date = str_ireplace("alle","", $date);
    $date = str_ireplace("at","", $date);
    return $date;
}


?>
