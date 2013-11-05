# -*- coding: utf-8

import sys
sys.path.append('../pywikipedia')

import wikipedia, login, category
from rdflib import Graph, URIRef, Literal, Namespace, RDF




class ImportMediawikiSKOSTemplatePage():
  def __init__(self, lg, overwrite, importMessage ='Added from ImportMediawikiSKOSTemplatePage'):
    family = "ecoreleveglossary"
    self.mwSite = wikipedia.Site('en')
    self.importMessage= importMessage
    self.overwrite = overwrite
    self.page = None
    self.templateDefinitionTermeSimple=u"""{{Definition term simple
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
    self.templateDefinitionTermCategory=u"""{{Definition_term_category
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
      }}"""
    if lg is None:
      self.mainLg = 'en'
    else : 
      self.mainLg = lg
    self.login()

  def importSkosFile (self, pages) : 
    self.login()
    for pages in pages:
      self.createOrUpdatePage(page)
    wikipedia.stopme()
    
  def extractValuesAndCreateTemplate(self, page, loader):
    #Test page type : Collection/Concept/Scheme
    pageType = type(page).__name__
    #text = self.templateDefinitionTermeSimple % ('isTopConcept', 'hasTopConcept', prefered, prefered fr, prefered ru, sy, sy fr, sy ru, def, def fr, def ru,
    #  ref, broader, order,  category, initialid)
    if (pageType == 'Concept') :
      text = self.templateDefinitionTermeSimple % (
        page.isTopConcept, 
        self._getTopConcept(page, loader),
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) if not page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) == '' else page.prefLabel, 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], 'ru'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'en'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'ru'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'en'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'ru'), 
        '', 
        self._getBroaders(page, loader),
        '',
        self._getCategory(page, loader),
        page.uri
      )
    else : 
      text = self.templateDefinitionTermCategory % (
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) if not page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) == '' else page.prefLabel, 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], 'ru'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'en'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], 'ru'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'en'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'fr'), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], 'ru'), 
        '', 
        'Term category' if self._getCategory(page, loader) =='' or pageType == 'ConceptScheme' else self._getCategory(page, loader)
      )
    return text.encode('utf8', 'ignore')
    
  def login(self):
    # login to the wiki
    mgr = login.LoginManager('123456', False, self.mwSite)
    mgr.login()
  
  def createOrUpdatePage(self, page, loader):
    try:
      prefix = u'Category:' if not (type(page).__name__ == 'Concept') else u''
      name = page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) if not page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) == '' else page.prefLabel
      mwpage = wikipedia.Page( self.mwSite,prefix+name)
      if mwpage.exists() and self.overwrite == False: # if the page already exists
        print prefix + name.encode('utf8') + u' exists already, I did not change it.'
      else: # create the text for the page and load it up
        mwpage.put(self.extractValuesAndCreateTemplate(page, loader).decode('utf8'), self.importMessage)
        txt = u'Added page ' + prefix + name.encode('utf8')
        print txt
    except IndexError:
      print IndexError

  def _getTopConcept(self, page, loader):
    if (page.isTopConcept == 'no') and (page.hasTopConcept):
      return self._getPreferedlabelForRelatedConcept(page.hasTopConcept[0], loader)
    else:
      return ''

  def _getBroaders(self,  page, loader):
    for broader in page.broader :
      return self._getPreferedlabelForRelatedConcept(broader, loader)
    return ''

  def _getCategory(self, page, loader) :
    #Return a value only If page is a TopConcept or if page is not a topConcept but don't have one
    if type(page).__name__ == 'Concept' : 
      if (page.isTopConcept == 'no') and (page.hasTopConcept): 
        return ''

    cat = {}
    if page.collections : #if page is in collection
      cat =  page.collections 
    else : #else the schema = the category
      cat =  page.schemes 
        
    for s in cat : 
      scheme = loader[s]
      prefLabel = scheme.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) 
      if not prefLabel == '' : 
        return prefLabel 
    return ''
  
  def _getPreferedlabelForRelatedConcept(self, elURI, loader) : 
    try:
      el = loader[loader.normalise_uri(elURI)]
      if (not el.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) == ''):
        return el.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) 
      else:
        return el.prefLabel
    except KeyError:
      return elURI
