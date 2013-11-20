// Autor: Janssen dos Reis Lima <janssenreislima@gmail.com>
// Ultima atualizacao: 19/11/2013
// Execucao: python ack_zabbix_glpi.py <eventid> <ticket_glpi>
// Observacoes: Este script eh executado automaticamente apos a abertura do ticket no GLPI

from zabbix_api import ZabbixAPI
import sys
 
server = "http://localhost/zabbix" # Endereco ou Ip do servidor do zabbix
username = "admin"              # Usuario
password = "zabbix"     # Senha
 
conexao = ZabbixAPI(server = server, path="", log_level=6)
conexao.login(username, password)

reconhecer_evento = conexao.event.acknowledge({"eventids": sys.argv[1], "message": "Ticket " + str(sys.argv[2]) + " criado no GLPI."})
