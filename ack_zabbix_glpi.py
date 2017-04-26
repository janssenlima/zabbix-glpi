## Autor: Janssen dos Reis Lima / janssenreislima@gmail.com>
## Ultima atualizacao: 18/02/2016
## Observacoes: Este script eh executado automaticamente apos a abertura do ticket no GLPI

from zabbix_api import ZabbixAPI
import sys
 
server = "http://localhost/zabbix"
username = "Admin"             
password = "zabbix"     
 
conexao = ZabbixAPI(server = server)
conexao.login(username, password)

reconhecer_evento = conexao.event.acknowledge({"eventids": sys.argv[1], "message": "Ticket " + str(sys.argv[2]) + " criado no GLPI."})
