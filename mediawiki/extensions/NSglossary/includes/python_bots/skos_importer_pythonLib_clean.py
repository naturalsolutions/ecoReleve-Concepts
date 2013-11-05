# -*- coding: utf-8

import os
scriptdir = os.path.dirname(os.path.realpath(__file__))

activate_this = scriptdir+'/bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

import sys
sys.path.append(scriptdir+'/pywikipedia')
sys.path.append(scriptdir+'/python-skos-0.0.3')
sys.path.append(scriptdir+'/skosTemplateMediaWiki')


##################################################################################################################
# load the required libraries

from rdflib import Graph, URIRef, Literal, Namespace, RDF
from rdflib.plugins.sparql.processor import prepareQuery

import wikipedia, login, category
import mwskostemplate

import skos
import json

print '------------------------------------------------------------------------------------------------------'
print '------------------------------------------------------------------------------------------------------'
print '-----------------------------------------------START IMPORT ------------------------------------------'
print '------------------------------------------------------------------------------------------------------'
print '------------------------------------------------------------------------------------------------------'
print ''
print ''
fileName = sys.argv[1]
overwrite =(True if sys.argv[2] == '1' else False)
importMessage = sys.argv[3]
importSchema = (True if sys.argv[4] == 'schemes' else False)
importCollection = (True if sys.argv[5] == 'collections' else False)
importConcept = (True if sys.argv[6] == 'concepts' else False)

i = Graph()
# Create the required namespaces
i.bind("ex", "myGraph.org/ex#")

print '-----------------------------------------------LOAD graph ------------------------------------------'
print ''
i.load(fileName)

loader = skos.RDFLoader(i)
mwimporter = mwskostemplate.ImportMediawikiSKOSTemplatePage('en', overwrite, importMessage)


print ''
if importSchema : 
  print '-----------------------------------------------SCHEMES ------------------------------------------'
  schemes = loader.getConceptSchemes()
  for c in schemes : 
    scheme = loader[c]
    print mwimporter.createOrUpdatePage(scheme, loader)
print ''
if importCollection : 
  print '-----------------------------------------------COLLECTIONS ------------------------------------------'
  collections = loader.getCollections()
  for c in collections : 
    coll = loader[c]
    print mwimporter.createOrUpdatePage(coll, loader)
print ''
if importConcept : 
  print '-----------------------------------------------CONCEPTS ------------------------------------------'
  concepts = loader.getConcepts()
  for c in concepts : 
    concept = loader[c]
    print mwimporter.createOrUpdatePage(concept, loader)
