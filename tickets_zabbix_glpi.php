<?php
// ----------------------------------------------------------------------------------------
// Autor: Janssen dos Reis Lima <janssenreislima@gmail.com>
// Script: tickets_zabbix_glpi.php
// Localizacao: /opt/zabbix/externalscripts/
// Descricao: Abrir e fechar tickets no GLPI de acordo com o status da trigger no Zabbix (OK/PROBLEM).
// Dependencias: GLPI Webservices plugin. Acesso remoto habilitado para o Mysql no servidor GLPI.
//
// Variaveis requisitadas pelo script:
//		eventhost={HOSTNAME}
//		event=<DOWN> ou <UP>
//		state=<OK> ou <PROBLEM>
//		hostproblemid=<0> ou <1> - 0=abrir chamado 1=fechar chamado
//		lasthostproblemid=<0> ou <1> - 0=abrir chamado 1=fechar chamado
//		servico={TRIGGER.NAME}
//		eventzabbix={EVENT.ID}
//		triggerid={TRIGGER>ID}
//
// Obs.: Variaveis entre <> devem ser apenas uma das opcoes; variaveis entre {} sao macros do Zabbix. Nao se preocupe com as variaveis, pois o Zabbix ja envia tudo certinho. Ao menos se voce deseja testar a execucao manualmente.
// Creditos: http://homeofdrock.com/2012/10/22/glpi-integration-with-nagios/

// -----------------------------------------------------------------------------------------
// Configuracoes:
// -----------------------------------------------------------------------------------------
$user =     	"glpi";						// Conta do usuario GLPI
$password = 	"glpi";						// Senha do usuario GLPI   
$xmlhost =  	"192.168.0.1";					// IP do Servidor GLPI - Tem que ser o IP.
$xmlurl =   	"glpi/plugins/webservices/xmlrpc.php";		// Diretorio para o xmlrpc no servidor GLPI
$category = 	"";						// Nao altere essa variavel. Necessaria para o Webservices. 
$watcher = 	"2";						// Nao altere essa variavel. Necessaria para o Webservices.
$watchergroup = "1";						// Nao altere essa variavel. Necessaria para o Webservices.
$sqlhost = 	"localhost";					// Altere caso o seu banco de dados estiver em outro host.
$sqldb = 	"glpidb";					// Nome do bando de dados
$sqluser =  	"glpiuser";                             	// Usuario MySQL com acesso a base do GLPI
$sqlpwd =   	"glpiwd";                        		// Senha usuario MySQL
$path_zabbix = 	"/opt/zabbix/externalscripts";			// Diretorio onde estao os scripts necessarios para a integracao
// -----------------------------------------------------------------------------------------
// ATENCAO: Cuidado ao alterar o codigo abaixo.
// -----------------------------------------------------------------------------------------

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

# Seleciona o evento UP ou DOWN
switch ($event) {
	case "UP":
		# Se o evento for UP, eh porque o status da trigger esta normalizado (OK), entao vamos fechar o registro no GLPI.
		if ($lasthostproblemid != 0) { 
			$arg[] = "method=glpi.doLogin";
			$arg[] = "url=$xmlurl";
			$arg[] = "host=$xmlhost";
			$arg[] = "login_password=$password";
			$arg[] = "login_name=$user";

			$response = getxml($arg);
			$session = $response['session'];
			
			$mysql = mysql_connect($sqlhost, $sqluser, $sqlpwd) or die(mysql_error());
      mysql_select_db($sqldb) or die(mysql_error());
			$consulta_chamado = mysql_query("SELECT id FROM glpi_tickets WHERE status <> 5 AND name like '%$eventhost%' AND content like '%$triggerid%'");

			$pega_id_ticket = mysql_fetch_array($consulta_chamado);
			$num_ticket = "{$pega_id_ticket['id']}";

			mysql_query("UPDATE glpi_tickets SET status='5' WHERE id='$num_ticket'") or die(mysql_error());

			mysql_close($mysql);
			
			// conteudo que sera gravado no followup do ticket. a variavel $content pode ser personalizada	
			$content = "Trigger $servico normalizada. Registro fechado automaticamente atraves do evento $eventzabbix.";
						
			$arg[] = "method=glpi.addTicketFollowup";
			$arg[] = "url=$xmlurl";
			$arg[] = "host=$xmlhost";
			$arg[] = "session=$session";
			$arg[] = "ticket=$num_ticket";
			$arg[] = "content=$content";
			$resp = getxml($arg);
			unset($arg);
			unset($resp);
			
			$arg[] = "method=glpi.doLogout";
			$arg[] = "url=$xmlurl";
			$arg[] = "host=$xmlhost";
			$arg[] = "session=$session";

			$response = getxml($arg);
			unset($arg);
			unset($response);
		}
		
	case "DOWN":
		# Abre o ticket caso o evento chegar como DOWN, ou seja, estiver com problema. Futuramente, poderemos incluir outros tipos de eventos e status.
			switch ($state) {
				case "PROBLEM":
					if ($lasthostproblemid != 1) {
						$arg[] = "method=glpi.doLogin";
						$arg[] = "url=$xmlurl";
						$arg[] = "host=$xmlhost";
						$arg[] = "login_password=$password";
						$arg[] = "login_name=$user";

						$response = getxml($arg);
						$session = $response['session'];

						unset($arg);
						unset($response);
						if (!empty($session)) {
							
							$title = "Status da trigger $servico com problema! - Evento $eventzabbix gerado automaticamente pelo Zabbix";
							// Nao altere a variavel $content abaixoi, pois depende desses parametros para fechar o ticket quando a trigger voltar ao status normal.
							$content = "Nome do host: $eventhost. ID da trigger: $triggerid. Status da trigger: $state.";
							if ($category != ''){
								$arg[] = "method=glpi.listDropdownValues";
								$arg[] = "url=$xmlurl";
								$arg[] = "host=$xmlhost";
								$arg[] = "session=$session";
								$arg[] = "dropdown=itilcategories";
								$arg[] = "name=$category";
								$response = getxml($arg);
								$categoryid = $response[0]['id'];
								unset($arg);
								$catarg = "category=$categoryid";
							}
							if (!empty($watcher)) {
								$watcherarg = "observer=$watcher";
							} elseif (!empty($watchergroup)) {
								$arg[] = "method=glpi.listUsers";
								$arg[] = "url=$xmlurl";
								$arg[] = "host=$xmlhost";
								$arg[] = "session=$session";
								$arg[] = "group=$watchergroup";
								$response = getxml($arg);
								foreach($response as $user){
									$watcherids .= $user['id'].",";
								}
								$watcherids = rtrim($watcherids, ",");
								$watcherarg = "observer=$watcherids";
								unset($arg);
							} else {
								// uso futuro
							}
							
							
							$arg[] = "method=glpi.createTicket";
							$arg[] = "url=$xmlurl";
							$arg[] = "host=$xmlhost";
							$arg[] = "session=$session";
							$arg[] = "title=$title";
							$arg[] = "content=$content";
							$arg[] = "urgancy=5";

							if (!empty($catarg)) $arg[] = $catarg;
							if (!empty($watcherarg)) $arg[] = $watcherarg;
							// Se voce nao quiser receber notificacoes dos chamados manipulados por webservice, comente as proximas tres linhas referente a estrutura if.
                                                        if (str_replace(".", "", $webservices_version) >= '120') {
                                                                $arg[] = "use_email_notification=1";
                                                        }
                                                        // comentar as 3 linhas acima se nao quiser receber notificacoes.
							$response = getxml($arg);
							unset($arg);
							unset($response);

						      	// Reconhece (ACK) o evento gerado no Zabbix.
					              	$mysql = mysql_connect($sqlhost, $sqluser, $sqlpwd) or die(mysql_error());
					              	mysql_select_db($sqldb) or die(mysql_error());
					              	$consulta_evento = mysql_query("SELECT id FROM glpi_tickets WHERE name like '%$eventzabbix%'") or die(mysql_error());
					
					              	$pega_id_ticket = mysql_fetch_array($consulta_evento);
					              	$num_ticket = "{$pega_id_ticket['id']}";
					              	sleep(10); //pausa de 10 segundos para dar tempo do ticket ser registrado no banco do GLPI. Se quiser, pode diminuir o tempo, porem pode ocorrer de nao registrar o ticket devido o ticket nao ter sido registrado no banco de dados.
					              	$comando = "python $path_zabbix/ack_zabbix_glpi.py $eventzabbix $num_ticket";
					              	$output = shell_exec($comando);
					              	mysql_close($mysql);
						
							$arg[] = "method=glpi.doLogout";
							$arg[] = "url=$xmlurl";
							$arg[] = "host=$xmlhost";
							$arg[] = "session=$session";
	
							$response = getxml($arg);
							unset($arg);
							unset($response);
						}
					}
			}
}


?>
