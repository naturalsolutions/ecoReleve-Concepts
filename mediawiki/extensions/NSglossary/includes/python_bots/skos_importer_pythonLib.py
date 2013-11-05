activate_this = './bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

import sys
sys.path.append('./pywikipedia')
sys.path.append('./python-skos-0.0.3')
sys.path.append('./skosTemplateMediaWiki')


##################################################################################################################
# load the required libraries

from rdflib import Graph, URIRef, Literal, Namespace, RDF
from rdflib.plugins.sparql.processor import prepareQuery

import wikipedia, login, category
import mwskostemplate

import skos
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker


i = Graph()
# Create the required namespaces
i.bind("ex", "myGraph.org/ex#")
#~ 
i.load("skos_example.rdf")
#~ i.load("unescothes_ultralight.rdf")
#~ i.load("unescothes.rdf")
#i.load("/home/administrateur/Documents/skos_voc/agrovoc_wb_20120906.rdf")

predicateRDFType = URIRef('http://www.w3.org/1999/02/22-rdf-syntax-ns#type')
objectType = URIRef('http://www.w3.org/2004/02/skos/core#%s' % 'Concept')
altLabelPr = URIRef('http://www.w3.org/2004/02/skos/core#altLabel')

RDFS = Namespace("http://www.w3.org/2000/01/rdf-schema#")
EX = Namespace("http://aifb.uni-karlsruhe.de/WBS/ex#")
SKOS = Namespace("http://www.w3.org/2004/02/skos/core#")

loader = skos.RDFLoader(i)
concepts = loader.getConcepts()
#~ 
#~ engine = create_engine('sqlite:///:memory:') # the in-memory database
#~ Session = sessionmaker(bind=engine)
#~ session1 = Session() # get a session handle on the database
#~ skos.Base.metadata.create_all(session1.connection()) # create the required database schema
#~ session1.add_all(loader.values()) # add all the skos objects to the database
#~ ##session1.commit() # commit these changes
#~ 
print '-----------------------------------------------COLLECTION ------------------------------------------'
collections = loader.getCollections()
print 'cccccc'
print repr(collections)
print 'cccccccccc'
for c in collections : 
  coll = loader[c]
  coll.title
  print repr(coll.members)
  print coll.associatedPropertiesLg.getAssociatedPropertiesLg(['prefLabel', 'altLabel'], 'fr')

print '-----------------------------------------------getConceptSchemes ------------------------------------------'
schemes = loader.getConceptSchemes()
print 'eeee'
print repr(schemes)
print 'eeee'

for c in schemes : 
  print c
  scheme = loader[c]
  print repr(scheme)
  print scheme.concepts
  print scheme.associatedPropertiesLg.getAssociatedPropertiesLg(['prefLabel', 'altLabel'], 'fr')

print '-----------------------------------------------CONCEPT------------------------------------------'

mwimporter = mwskostemplate.ImportMediawikiSKOSTemplatePage('en', False)

for c in concepts : 
  print repr(c)
  concept = loader[c]
  print '--------------------------------------------------------------'
  print repr(concept.broader)
  print 'cooel'
  print concept.collections
  print 'schema'
  print concept.schemes
  print mwimporter.extractValuesAndCreateTemplate(concept, loader)
  #~ print concept.isTopConcept
  #~ print concept.hasTopConcept
  #~ print concept.associatedPropertiesLg.getAssociatedPropertiesLg(['prefLabel', 'altLabel'], 'fr')

#~ 
#~ 
#~ for c in concepts : 
  #~ print repr(c)
  #~ concept = loader[c]
  #~ print repr(concept.associatedPropertiesLg)
  #~ print concept.associatedPropertiesLg.getAssociatedPropertiesLg(['prefLabel', 'altLabel'], 'fr')
