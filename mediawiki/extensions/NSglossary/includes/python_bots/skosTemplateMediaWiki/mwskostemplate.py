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
    self.templateCommonConcept=u"""{{CommonConcept
      |label=%s
      |definition=%s
      |alternative labels=%s
      |notes=%s
      |references=%s
      |identifier=%s
      }}"""
    self.templateDefinitionTermeSimple=u"""{{SimpleTermDefinition
      |hasTopConcept=%s
      |broader=%s
      |order=%s
      }}"""
    self.templateDefinitionCategory=u"""{{CategoryConceptDefinition
      |broader=%s
      }}"""
    self.templateDefinitionTopConcept=u"""{{TopConceptDefinition
      |compartment=%s
      }}"""
    self.templateConceptRelation=u"""{{Concept relation
      |relation=%s
      |internal-page=%s
      |uri=%s
      }}"""
    self.templateConceptTranslation=u"""{{Concept translation
      |language=%s
      |label=%s
      |definition=%s
      |alternative labels=%s
      |notes=%s
      }}"""
       
      
    if lg is None:
      self.mainLg = 'en'
    else : 
      self.mainLg = lg
    self.login()

  def importSkosFile (self, pages) : 
    self.login()
    for pages in pages:
      try:
        self.createOrUpdatePage(page)
      except KeyError:
        continue
    wikipedia.stopme()
    
  def extractValuesAndCreateTemplate(self, page, loader):
    #Test page type : Collection/Concept/Scheme
    pageType = type(page).__name__
    
    """****************************************************************
            GENERIC TEMPLATE
    ****************************************************************"""
    text = self.templateCommonConcept % (
      page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) if not page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'], self.mainLg) == '' else page.prefLabel, 
      page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], self.mainLg) if not page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], self.mainLg) == '' else page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], None) ,  
      page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], self.mainLg), 
      page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['note'], self.mainLg), 
      '',
      page.uri
    )
    
    lgs= page.associatedPropertiesLg.getAllLgValue(None);
    if self.mainLg in lgs: 
      lgs.remove(self.mainLg)
    if None in lgs: 
      lgs.remove(None)
    for lg in lgs:
      text += self.templateConceptTranslation % (
        lg,
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['prefLabel'],lg),
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['definition'], lg), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['altLabel'], lg), 
        page.associatedPropertiesLg.getAssociatedPropertiesLgValue(['note'],lg)
      )
      
    """****************************************************************
            SPECIFIC TEMPLATE Collection/Topconcept/Concept
    ****************************************************************"""
    if (pageType == 'Concept') :
      if (page.isTopConcept == 'yes'):
        text += self.templateDefinitionTopConcept % (
          self._getCategory(page, loader),
        )
      else:
        text += self.templateDefinitionTermeSimple % (
          self._getTopConcept(page, loader),
          self._getBroaders(page, loader),
          ''
        )
        """****************************************************************
            RELATION TEMPLATE
        ****************************************************************"""
      for relate in page.related:
        if ((relate != self._getPreferedlabelForRelatedConcept(relate,loader))):
          text += self.templateConceptRelation%('skos:related',self._getPreferedlabelForRelatedConcept(relate,loader),'')
        else:
          text += self.templateConceptRelation%('skos:related','',relate)
      for syno in page.synonyms:
        if ((syno != self._getPreferedlabelForRelatedConcept(syno,loader))):
          text += self.templateConceptRelation%('skos:exactMatch',self._getPreferedlabelForRelatedConcept(syno,loader),'')
        else:
          text += self.templateConceptRelation%('skos:exactMatch','',syno)
    else : 
      text += self.templateDefinitionCategory % (
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
        try:
          print  u' exists already, I did not change it.'
        except IndexError:
          pass
      else: # create the text for the page and load it up
        try:
          mwpage.put(self.extractValuesAndCreateTemplate(page, loader).decode('utf8'), self.importMessage)
          txt = u'Added page ' 
          print txt
        except IndexError:
          pass
    except IndexError:
      raise

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
