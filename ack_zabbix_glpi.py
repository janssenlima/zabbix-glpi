## Autor: Janssen dos Reis Lima <janssenreislima@gmail.com>
## Ultima atualizacao: 11/05/2015
## Execucao: python ack_zabbix_glpi.py <eventid> <ticket_glpi>
## Observacoes: Este script eh executado automaticamente apos a abertura do ticket no GLPI

from zabbix_api import ZabbixAPI
import sys
 
server = "http://localhost/zabbix"
username = "admin"             
password = "zabbix"     
 
conexao = ZabbixAPI(server = server)
conexao.login(username, password)

reconhecer_evento = conexao.event.acknowledge({"eventids": sys.argv[1], "message": "Ticket " + str(sys.argv[2]) + " criado no GLPI."})
