zabbix-glpi
===========

Python scripts for integration between Zabbix and GLPI. Under development

The proposal is automatically open/close tickets in the GLPI and acknowledge the event in Zabbix Web interface using API.

More information about implementing this solution can be found on my blog: http://janssenlima.blogspot.com/2013/11/integracao-zabbix-glpi.html

Requirements
========
- Zabbix 2.2 or higher (tested with Zabbix 2.4 and Zabbix 3.x)
- GLPI 0.85.3 ~ 0.90 (does not work with 0.90.1) with Plugin Webservices 1.6.0, available in https://forge.glpi-project.org/projects/webservices/files
- GLPI 9.1 with Plugin Webservices 1.6.1 (modified by Stevenes/), available in https://sourceforge.net/projects/glpiwebservices/
- PHP 5.6.17
- Python 2.7
- API Zabbix development in Python, just execute in terminal -> # pip install zabbix-api

**Note**: Tested and running on Debian (7, 8) and CentOS 7, both 64 bit.

Support
========

- http://janssenlima.blogspot.com
- janssenreislima@gmail.com
