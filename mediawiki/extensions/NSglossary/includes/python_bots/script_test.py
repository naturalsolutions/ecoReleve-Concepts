activate_this = './bin/activate_this.py'
execfile(activate_this, dict(__file__=activate_this))

import sys
sys.path.append('./pywikipedia')

# load the required libraries
from rdflib import Graph, URIRef, Literal, Namespace, RDF
import wikipedia, login, category

# note that you need to setup the appropriate family file
family = "ecoreleveglossary"

i = Graph()
# Create the required namespaces
i.bind("ex", "http://aifb.uni-karlsruhe.de/WBS/ex#")
RDF = Namespace("http://www.w3.org/1999/02/22-rdf-syntax-ns#")
RDFS = Namespace("http://www.w3.org/2000/01/rdf-schema#")
EX = Namespace("http://aifb.uni-karlsruhe.de/WBS/ex#")
# Load the file. If loaded like this, python needs to be able
# to find the file, e.g. put it in the same directory
i.load("elements.rdf")

# login to the wiki
ex = wikipedia.Site('en')
mgr = login.LoginManager('123456', False, ex)
mgr.login()

# iterates through everything that has the type Element
# (note, only explicit assertions -- rdflib does not do reasoning here!)
for s in i.subjects(RDF["type"], EX["Element"]):
  for name in i.objects(s, RDFS["label"]):  # reads the label
    # gets the page with that name
    page = wikipedia.Page(ex,name)
    if page.exists(): # if the page already exists
      print name + " exists already, I did not change it."
    else: # create the text for the page and load it up
      text = "'''" + name + "''' "
      for symbol in i.objects(s, EX["elementSymbol"]):
        text += "(Chemical symbol: " + symbol + ") "
      for number in i.objects(s, EX["elementNumber"]):
        text += "is element number " + number + " in the [[element table]]."
      text += "\n\n[[Category:Element]]"
      # Now we have created the text, let's upload it
      page.put(text, 'Added from ontology')
      print "Added page " + name

wikipedia.stopme()
# close the Wikipedia library
print "Script ended."
