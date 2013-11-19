// Execucao: python ack_zabbix_glpi.py <eventid> <ticket_glpi>
// Este script eh executado automaticamente apos a abertura do ticket no GLPI

from zabbix_api import ZabbixAPI
import sys
import re
 
server = "http://localhost/zabbix" #Endereco ou Ip do servidor do zabbix
username = "admin"              #Usuario
password = "zabbix"     # Senha
 
#Instanciando a API
zapi = ZabbixAPI(server = server, path="", log_level=6)
zapi.login(username, password)

reconhecer_evento = zapi.event.acknowledge({"eventids": sys.argv[1], "message": "Ticket " + str(sys.argv[2]) + " criado no GLPI."})
