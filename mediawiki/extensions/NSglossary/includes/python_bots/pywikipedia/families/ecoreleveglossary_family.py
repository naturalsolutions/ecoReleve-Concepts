# -*- coding: utf-8  -*-
import config, family, urllib
# MyWiki family                  
# user_config.py:
# usernames['mywiki']['en'] = 'user'
class Family(family.Family):
   def __init__(self):
       family.Family.__init__(self)
       self.name = 'ecoreleveglossary'             #CHANGE
       self.langs = {
           'en': '192.168.1.96/html/ecoReleve-glossary',           #CHANGE, Site URL
          }
       self.namespaces[4] = {
           '_default': [u'ecoReleve-Glossary', self.namespaces[4]['_default']],   #CHANGE
       }
       self.namespaces[5] = {
           '_default': [u'ecoReleve-Glossary talk', self.namespaces[5]['_default']], #CHANGE
       }
   def version(self, code):
       return "1.20.3"   #CHANGE if needed, version of your MW
   def scriptpath(self, code):
       return 
   def apipath(self, code):
       return '/api.php'  #CHANGE if needed. The path to your api script. Its in the same location as your LocalSettings.php.

