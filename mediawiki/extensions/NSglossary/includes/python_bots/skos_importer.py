activate_this = './bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

import sys
sys.path.append('./pywikipedia')

import wikipedia, login, category
from rdflib import Graph, URIRef, Literal, Namespace, RDF
class ImportMediawikiSKOSTemplatePage():
  def __init__(self, lg, overide):
    family = "ecoreleveglossary"
    self.mwSite = wikipedia.Site('en')

    self.overide = overide
    self.page = None
    self.template="""{{Definition term simple
      |isTopConcept=%s
      |hasTopConcept=%s
      |prefered term=%s
      |prefered term fr=%s
      |prefered term ru=%s
      |synonyms=%s
      |synonyms fr=%s
      |synonyms ru=%s
      |definition=%s
      |definition fr=%s
      |definition ru=%s
      |reference=%s
      |broader=%s
      |order=%s
      |term_category=%s
      |initial_id=%s
      }}"""
    if lg is None:
      self.mainLg = 'en'
    else : 
      self.mainLg = lg

  def importSkosFile (self, pages) : 
    self.login()
    for pages in pages:
      self.createOrUpdatePage(page)
    wikipedia.stopme()
    
  def extractValuesAndCreateTemplate(self, page):
    #text = self.template % ('isTopConcept', 'hasTopConcept', prefered, prefered fr, prefered ru, sy, sy fr, sy ru, def, def fr, def ru,
    #  ref, broader, order,  category, initialid)
    #print repr(page)
    text = self.template % (
      page['isTopConcept'], 
      self._getTopConcept(page), 
      page['prefLabel_'+self.mainLg][0][1], 
      self._getPreferedlabel(page['prefLabels'], 'fr'), 
      self._getPreferedlabel(page['prefLabels'], 'ru'), 
      self._getAltLabel(page['altLabels'], 'en'),
      self._getAltLabel(page['altLabels'], 'fr'),
      self._getAltLabel(page['altLabels'], 'ru'),
      self._getDefinition(page, 'en'),
      self._getDefinition(page, 'fr'),
      self._getDefinition(page, 'ru'),
      '', 
      self._getBroaders(page['broaders']), 
      '',
      self._getCategory(page),
      ''
    )
    return text
    
  def login(self):
    # login to the wiki
    mgr = login.LoginManager('123456', False, self.mwSite)
    mgr.login()
  
  def createOrUpdatePage(self, page):
    try:
      name = page['prefLabel_'+self.mainLg][0][1]
      mwpage = wikipedia.Page( self.mwSite,name)
      if mwpage.exists() and self.overide == True: # if the page already exists
        print name + " exists already, I did not change it."
      else: # create the text for the page and load it up
        mwpage.put(self.extractValuesAndCreateTemplate(page), 'Added from ImportMediawikiSKOSTemplatePage')
        #print self.extractValuesAndCreateTemplate(page)
        print "Added page " + name
    except IndexError:
      print IndexError
      
  def _getPreferedlabel(self, preferedLabels ,lg ='en'):
    for prefL in preferedLabels:
      if Literal(prefL).language == lg :
        return prefL.value 
    return ''

  def _getDefinition(self, definitions ,lg ='en'):
    for defi in definitions:
      if Literal(defi).language == lg :
        return altLabel.value 
    return ''

  def _getAltLabel(self, altLabels ,lg ='en'):
    formatedAltLabel = ''
    for altLabel in altLabels:
      if Literal(altLabel).language == lg :
        formatedAltLabel += altLabel.value 
        formatedAltLabel +=','
    return formatedAltLabel

  def _getTopConcept(self, page):
    return ''

  def _getBroaders(self, broaders):
    for broader in broaders :
      if 'prefLabel' in broader :
        broaderLabel = Literal(broader['prefLabel'][0][1])
        if broaderLabel.language == self.mainLg:
          return broaderLabel.value 
    return ''

  def _getCategory(self, page) :
    if 'collection' in page and 'prefLabel' in page['collection'] :
      #!!!!!! truc bizare a regarder de plus pres pour les indices
      collectionLabel = Literal(page['collection']['prefLabel'][0][1])
      if collectionLabel.language == self.mainLg:
        return collectionLabel.value
    #On Specifie la categorie comme etant le schema
    else :
      if 'schema' in page :
        for s in page['schema'] :
          if len(s['prefLabel']) >0: 
            print Literal(s['prefLabel'][0][1])
            #!!!!!! truc bizare a regarder de plus pres pour les indices
            collectionLabel = Literal(s['prefLabel'][0][1])
            if collectionLabel.language == self.mainLg:
              return collectionLabel.value
        
    return ''
  
  
from rdflib import Graph, URIRef, Literal, Namespace, RDF
class CustomRDFSKOSLoader():
  def __init__(self, lg, graph):
    self.SKOS = Namespace("http://www.w3.org/2004/02/skos/core#")    
    self.uriRDFpredicat = URIRef('http://www.w3.org/1999/02/22-rdf-syntax-ns#type')
    self.skosPrefix ='http://www.w3.org/2004/02/skos/core#%s'
    self.graph = graph
    if lg is None:
      self.mainLg = 'en'
    else : 
      self.mainLg = lg

  def extractSKOSProperties(self, subject):
    langfilterAll = lambda l: True
    
    skosPage = {'uri': subject}
    skosPage['schema'] = self.iterateSKOSTypeWithPrefLabel('ConceptScheme')
    skosPage['collection'] = self.getObjectSubjectWithPrefLabel(subject, 'member')
    skosPage['prefLabel_'+self.mainLg] =  self.graph.preferredLabel(subject, lang=self.mainLg)
    skosPage['prefLabels'] = self.graph.preferredLabel(subject)
    skosPage['altLabels'] = self.iterateSKOSTypeWithFilter(self.SKOS['altLabel'], langfilterAll, subject)
    skosPage['hasTopConceptO'] = self.getTopConcept( subject)
    skosPage['hasTopConcept'] = self.iterateSKOSType(self.SKOS['hasTopConcept'])
    skosPage['isTopConcept'] = self.subjectIsTopConcept(subject)
    defs = []
    for definition in filter(langfilterAll,self.graph.objects(subject, self.SKOS['definition'])):
      defs.append(definition)
    skosPage['definitions'] = defs
    
    broader = []
    for alt in filter(langfilterAll,self.graph.objects(subject, self.SKOS['broader'])):
      print alt
      broader.append({'broader':alt, 'prefLabel':self.graph.preferredLabel(alt, lang=self.mainLg)})
    
    skosPage['broaders'] = broader
    return skosPage
  
  def subjectIsTopConcept(self,subject):
    obj= URIRef(self.skosPrefix  % 'hasTopConcept')
    if self.graph.value(subject=None, predicate=obj, object=subject, default=None, any=False):
      return 'yes'
    else : 
      return 'no'
  
  def getObjectSubjectWithPrefLabel(self, subject, predicate):
    obj= URIRef(self.skosPrefix  % predicate)
    subject = self.graph.value(subject=None, predicate=obj, object=subject, default=None, any=False)
    if subject:
      return {'uri':subject ,'prefLabel' : self.graph.preferredLabel(subject, lang=self.mainLg)}
    else: 
      return {}
  
  def iterateSKOSTypeWithPrefLabel(self, type_):
    obj= URIRef(self.skosPrefix  % type_)
    vals=[]
    for subject in self.graph.subjects(predicate=self.uriRDFpredicat, object=obj):
      vals.append({'uri' : subject, 'prefLabel' :self.graph.preferredLabel(subject, lang=self.mainLg)} )
    return vals

  def iterateSKOSType(self, type_):
    obj= URIRef(self.skosPrefix  % type_)
    vals=[]
    for subject in self.graph.subjects(predicate=self.uriRDFpredicat, object=obj):
      vals.append(subject)
    return vals
    
  def iterateSKOSTypeWithFilter(self, type_, filter_, subject):
    obj= URIRef(self.skosPrefix  % type_)
    vals=[]
    for val in filter(filter_, self.graph.objects(subject, type_)):
      vals.append(val)
    return vals
    
  def getTopConcept(self, subject):
    vals=[]
    q = prepareQuery(
      """
      SELECT ?lastTerm ?p 
      WHERE {
        ?lastTerm SKOS:broader* ?p.
        ?c SKOS:hasTopConcept ?p.
      }
      """,
      initNs = { "SKOS": SKOS }
    )
    for row in self.graph.query(q, initBindings={'lastTerm': subject}):
      vals.append(row)
    return vals
    

##################################################################################################################
# load the required libraries

from rdflib import Graph, URIRef, Literal, Namespace, RDF
from rdflib.plugins.sparql.processor import prepareQuery
import wikipedia, login, category
import rdflib.plugins.sparql

i = Graph()
# Create the required namespaces
i.bind("ex", "myGraph.org/ex#")

i.load("skos_example.rdf")
#i.load("unescothes.rdf")
#i.load("/home/administrateur/Documents/skos_voc/agrovoc_wb_20120906.rdf")

predicateRDFType = URIRef('http://www.w3.org/1999/02/22-rdf-syntax-ns#type')
objectType = URIRef('http://www.w3.org/2004/02/skos/core#%s' % 'Concept')
altLabelPr = URIRef('http://www.w3.org/2004/02/skos/core#altLabel')

RDFS = Namespace("http://www.w3.org/2000/01/rdf-schema#")
EX = Namespace("http://aifb.uni-karlsruhe.de/WBS/ex#")
SKOS = Namespace("http://www.w3.org/2004/02/skos/core#")
mwSkosImporter = ImportMediawikiSKOSTemplatePage ('en', False)

mainLg = 'en'
pages = {}

#Exemple concept Skills


qres = i.query(
  """
    PREFIX SKOS: <http://www.w3.org/2004/02/skos/core#> 
    SELECT DISTINCT  ?p
    WHERE {
      <http://my.fake.domain/test3> SKOS:broader* ?p.
       ?c SKOS:hasTopConcept ?p.
    }
  """)

for row in qres:
  #~ print("%s Broad %s" % row)
  print repr(row)
  print "eeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeeee"

#!! Necessite le plugin pip install rdflib.plugins.sparql
# et rdflib.plugins.sparql.processor
q = prepareQuery(
    """
    SELECT ?lastTerm ?p 
    WHERE {
      ?lastTerm SKOS:broader* ?p.
       ?c SKOS:hasTopConcept ?p.
    }
  """,
  initNs = { "SKOS": SKOS }
)

lastTerm = rdflib.URIRef("http://my.fake.domain/test3")

for row in i.query(q, initBindings={'lastTerm': lastTerm}):
  print row

#~ 

customLoader = CustomRDFSKOSLoader(mainLg, i)
for subject in i.subjects(predicate=predicateRDFType, object=objectType):
  print subject
  pages[subject] = customLoader.extractSKOSProperties(subject)
  #~ mwSkosImporter.createOrUpdatePage(pages[subject])
  print "------end page ---------------"


print repr(pages)
