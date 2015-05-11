<?php
// ----------------------------------------------------------------------------------------
// Autor: Janssen dos Reis Lima <janssenreislima@gmail.com>
// Script: tickets_zabbix_glpi.php
// Ultima Atualizacao: 11/05/2015
// Versao: 0.3b
// Descricao: Abrir e fechar tickets no GLPI de acordo com o status da trigger no Zabbix (OK/PROBLEM).
// Dependencias: GLPI Webservices plugin. Acesso remoto habilitado para o Mysql no servidor GLPI.


// -----------------------------------------------------------------------------------------
// Configuracoes:
// -----------------------------------------------------------------------------------------
$user =     	"glpi";						
$password = 	"glpi";						
$xmlhost =  	"localhost";					
$xmlurl =   	"glpi/plugins/webservices/xmlrpc.php";		
$category = 	"";						
$watcher = 	"2";						
$watchergroup = "1";						
$sqlhost = 	"localhost";					
$sqldb = 	"glpidb";					
$sqluser =  	"glpiuser";                             	
$sqlpwd =   	"glpiwd";                        		
$path_zabbix = 	"/opt/zabbix/externalscripts";			
// ------------------------------------------------------------------------------------------------------------------------
// ATENCAO: Nao altere o codigo abaixo, a nao ser que voce deseja modificar as frases de abertura e fechamento dos tickets.
// ------------------------------------------------------------------------------------------------------------------------

$arg[] = "method=glpi.test";
$arg[] = "url=$xmlurl";
$arg[] = "host=$xmlhost";
$response = getxml($arg);
unset($arg);
$webservices_version = $response['webservices'];

$eventval=array();
if ($argv>1) {
	for ($i=1 ; $i<count($argv) ; $i++) {
		$it = explode("=",$argv[$i],2);
		$it[0] = preg_replace('/^--/','',$it[0]);
		$eventval[$it[0]] = (isset($it[1]) ? $it[1] : true);
	}
}

$eventhost=$eventval['eventhost'];
$event=$eventval['event'];
$state=$eventval['state'];
$hostproblemid=$eventval['hostproblemid'];
$lasthostproblemid=$eventval['lasthostproblemid'];
$servico=$eventval['servico'];
$eventzabbix=$eventval['eventzabbix'];
$triggerid=$eventval['triggerid'];
unset($eventval);

function getxml($arg) {
	$args=array();
	if ($arg>1) {
	   for ($i=0 ; $i<count($arg) ; $i++) {
		  $it = explode("=",$arg[$i],2);
		  $it[0] = preg_replace('/^--/','',$it[0]);
		  if (strpos($it[1],',') !== false) {
			$it[1] = explode(",", $it[1]);
		  }
		  $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
	   }
	}
	$method=$args['method'];
	$url=$args['url'];
	$host=$args['host'];
	
	if (isset($args['session'])) {
	   $url.='?session='.$args['session'];
	   unset($args['session']);
	}

	$header = "Content-Type: text/xml";

	echo "+ Calling '$method' on http://$host/$url\n";
	
	$request = xmlrpc_encode_request($method, $args);
	$context = stream_context_create(array('http' => array('method'  => "POST",
														   'header'  => $header,
														   'content' => $request)));

	$file = file_get_contents("http://$host/$url", false, $context);
	if (!$file) {
	   die("+ No response\n");
	}

	if (in_array('Content-Encoding: deflate', $http_response_header)) {
	   $lenc=strlen($file);
	   echo "+ Compressed response : $lenc\n";
	   $file = gzuncompress($file);
	   $lend=strlen($file);
	   echo "+ Uncompressed response : $lend (".round(100.0*$lenc/$lend)."%)\n";
	}
	$response = xmlrpc_decode($file);
	if (!is_array($response)) {
	   echo $file;
	   die ("+ Bad response\n");
	}

	if (xmlrpc_is_fault($response)) {
		echo("xmlrpc error(".$response['faultCode']."): ".$response['faultString']."\n");
	} else {
	   return $response;
	}
}

if (!extension_loaded("xmlrpc")) {
   die("Extension xmlrpc not loaded\n");
}
?>
