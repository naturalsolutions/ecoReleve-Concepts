<?php
require_once(  dirname( __FILE__ ).'/../Wikimate/globals.php');
/**
 * This special page (Special:ExportRDF) for MediaWiki implements an OWL-export of semantic data,
 * gathered both from the annotations in articles, and from metadata already
 * present in the database.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class NSSpecialExport extends SpecialPage {
	
	/// Export controller object to be used for serializing data
	protected $export_controller;
  protected $limit = 500;
  protected $from = 0;
  protected $until;


	public function __construct() {
		parent::__construct( 'ExportConceptData' );
	}

	public function execute( $page ) {
		global $wgOut, $wgRequest, $wgScriptPath, $wgServer;
		global $wgUser;
		
		$wgOut->setPageTitle( 'Première version exporter : un concept');
    $pages =false;
		// see if we can find something to export:
		$page = is_null( $page ) ? $wgRequest->getVal( 'page' ) : rawurldecode( $page );
		if ( !is_null( $page ) || $wgRequest->getCheck( 'page' ) ) {
			$page = is_null( $page ) ? $wgRequest->getCheck( 'text' ) : $page;
      $pDataItem = SMWDIWikiPage::newFromTitle(Title::newFromText($page) ); 
      
      $pages = array( $page );
      $p =  new SMWDIProperty('SubscribTo');
      $semdata = smwfGetStore()->getSemanticData( $pDataItem); // advise store to retrieve only core things
      $subscribTo =  HierarchyTree::getStringPropertyValue( $semdata,$p);
      $initialized =  HierarchyTree::getStringPropertyValue( $semdata,new SMWDIProperty('Initialized'));
      $proper =  NSUtils::getStringPropertyValues( $semdata,new SMWDIProperty('ExportProperty'));
      //Si l'abonnement a déjà été initialisé alors => récupération de la dernière date
      if ($initialized) {
        $lastSynchroDate =  HierarchyTree::getStringPropertyValue( $semdata,new SMWDIProperty('LastSynchro'));
        $q = '[['.$subscribTo.']]+[[Modification_date::>'.$lastSynchroDate.']]';
      }
      else {
        $q = '[['.$subscribTo.']]';
      }
      $api_url = '192.168.1.96/ecoReleve-glossary/api.php';
 
      $username = $wgUser->mName;
      $password = $wgUser->mPassword;
      
      //Modification de la page du subscriber 
      try  {
          $wiki = new Wikimate($api_url);
          if ($wiki->login($username,$password)) {
              $page = $wiki->getPage('Subscription');
              $sections = $page->getAllSections(false, 2);
              $modifLog = $sections['Modification log'];
              $page->setText("==Status==\n\n[[Initialized::true]]\n\n", 2 );
              $today = date("Y-m-d H:i:s"); 
              $modifLog = str_replace ( '==Modification log==' , '',$modifLog);
              $page->setText("==Modification log==\n\n".$modifLog."{{#subobject:| isSubobjectOf={{PAGENAME}}| status=update| LastQuery=".$today."}}\n\n",3  );
            }
          else {
              $error = $wiki->getError();
              echo "<b>Wikimate error</b>: ".$error['login'];
          }
      }
      catch ( Exception $e ){
          echo "<b>Wikimate error</b>: ".$e->getMessage();
      }
      //Construction de la requête
      //@TODO  : construction dynamique en fonction des champs sélectionnés
      
      $wsCall = $this->buildWSQueryCall($wgServer.$wgScriptPath.'/index.php?title=Special%3AAsk&',$proper, $q);
      $collectionWS = file_get_contents($wsCall);
     // print_r($collectionWS );
      header( "Content-type: application/rdf+xml; charset=UTF-8" );
      $wgOut->disable();
      print $collectionWS;
      return ;
		}
		// Nothing exported yet; show user interface:
		$this->showForm();
	}

	/**
	 * Create the HTML user interface for this special page.
	 */
	protected function showForm() {
		global $wgOut, $wgUser, $smwgAllowRecursiveExport, $smwgExportBacklinks, $smwgExportAll;

		$html = '<form name="tripleSearch" action="" method="POST">' . "\n" .
                '<p>' . wfMessage( 'smw_exportrdf_docu' )->text() . "</p>\n" .
                '<input type="hidden" name="postform" value="1"/>' . "\n" .
                '<textarea name="pages" cols="40" rows="10"></textarea><br />' . "\n";
		
		if ( $wgUser->isAllowed( 'delete' ) || $smwgAllowRecursiveExport ) {
			$html .= '<input type="checkbox" name="recursive" value="1" id="rec">&#160;<label for="rec">' . wfMessage( 'smw_exportrdf_recursive' )->text() . '</label></input><br />' . "\n";
		}

		if ( $wgUser->isAllowed( 'delete' ) || $smwgExportBacklinks ) {
			$html .= '<input type="checkbox" name="backlinks" value="1" default="true" id="bl">&#160;<label for="bl">' . wfMessage( 'smw_exportrdf_backlinks' )->text() . '</label></input><br />' . "\n";
		}

		if ( $wgUser->isAllowed( 'delete' ) || $smwgExportAll ) {
			$html .= '<br />';
			$html .= '<input type="text" name="date" value="' . date( DATE_W3C, mktime( 0, 0, 0, 1, 1, 2000 ) ) . '" id="date">&#160;<label for="ea">' . wfMessage( 'smw_exportrdf_lastdate' )->text() . '</label></input><br />' . "\n";
		}

		$html .= '<br /><input type="submit"  value="' . wfMessage( 'smw_exportrdf_submit' )->text() . "\"/>\n</form>";
		
		$wgOut->addHTML( $html );
	}
	
	/**
	 * Prepare $wgOut for printing non-HTML data.
	 */
	protected function startRDFExport() {
		global $wgOut, $wgRequest;

		$syntax = $wgRequest->getText( 'syntax' );

		if ( $syntax === '' ) {
			$syntax = $wgRequest->getVal( 'syntax' );
		}

		$wgOut->disable();
		ob_start();

		if ( $syntax == 'turtle' ) {
			$mimetype = 'application/x-turtle'; // may change to 'text/turtle' at some time, watch Turtle development
			$serializer = new SMWTurtleSerializer();
		} else { // rdfxml as default
			// Only use rdf+xml mimetype if explicitly requested (browsers do
			// not support it by default).
			// We do not add this parameter to RDF links within the export
			// though; it is only meant to help some tools to see that HTML
			// included resources are RDF (from there on they should be fine).
			$mimetype = ( $wgRequest->getVal( 'xmlmime' ) == 'rdf' ) ? 'application/rdf+xml' : 'application/xml';
			$serializer = new SMWRDFXMLSerializer();
		}

		header( "Content-type: $mimetype; charset=UTF-8" );

		$this->export_controller = new SMWExportController( $serializer );
	}
	
	/**
	 * Export the given pages to RDF.
	 * @param array $pages containing the string names of pages to be exported
	 */
	protected function exportPages( $pages ) {
		global $wgRequest, $smwgExportBacklinks, $wgUser, $smwgAllowRecursiveExport;

    //print_r($pages);
		// Effect: assume "no" from missing parameters generated by checkboxes.
		$postform = $wgRequest->getText( 'postform' ) == 1;

		$recursive = 0;  // default, no recursion
		$rec = $wgRequest->getText( 'recursive' );

		if ( $rec === '' ) {
			$rec = $wgRequest->getVal( 'recursive' );
		}

		if ( ( $rec == '1' ) && ( $smwgAllowRecursiveExport || $wgUser->isAllowed( 'delete' ) ) ) {
			$recursive = 1; // users may be allowed to switch it on
		}

		$backlinks = $smwgExportBacklinks; // default
		$bl = $wgRequest->getText( 'backlinks' );

		if ( $bl === '' ) {
			// TODO: wtf? this does not make a lot of sense...
			$bl = $wgRequest->getVal( 'backlinks' );
		}

		if ( ( $bl == '1' ) && ( $wgUser->isAllowed( 'delete' ) ) ) {
			$backlinks = true; // admins can always switch on backlinks
		} elseif ( ( $bl == '0' ) || ( '' == $bl && $postform ) ) {
			$backlinks = false; // everybody can explicitly switch off backlinks
		}
    /*print_r($backlinks);
    $backlinks=false;*/
		$date = $wgRequest->getText( 'date' );
		if ( $date === '' ) {
			$date = $wgRequest->getVal( 'date' );
		}

		if ( $date !== '' ) {
			$timeint = strtotime( $date );
			$stamp = date( "YmdHis", $timeint );
			$date = $stamp;
		}

		$this->startRDFExport();
		$this->export_controller->enableBacklinks( $backlinks );
		$this->export_controller->printPages( $pages, $recursive, $date );
	}
	/**
	 * Retrieve a copy of the semantic data for a wiki page, possibly filtering
	 * it so that only essential properties are included (in some cases, we only
	 * want to export stub information about a page).
	 * We make a copy of the object since we may want to add more data later on
	 * and we do not want to modify the store's result which may be used for
	 * caching purposes elsewhere.
	 */
	protected function NSGetSemanticData( SMWDIWikiPage $diWikiPage, $core_props_only) {
     $p =  new SMWDIProperty('SubscribTo');
  
		$semdata = smwfGetStore()->getSemanticData( $diWikiPage); // advise store to retrieve only core things
	
     $v =  HierarchyTree::getStringPropertyValue( $semdata,$p);
     
    print "\n**************ssssssssssssssssssssssssssssss***************************";
    print_r($v );
  print "\n**************ssssssssssssssssssssssssssssss***************************";
  if ( $core_props_only ) { // be sure to filter all non-relevant things that may still be present in the retrieved
			$result = new SMWSemanticData( $diWikiPage );
      print "\n*****************************************";
			foreach ( array( '_URI', '_TYPE', '_IMPO' ,'SubscribTo' ) as $propid ) {
        print "\n*****************************************".$propid;
				$prop = new SMWDIProperty( $propid );
        print_r($prop );
				$values = $semdata->getPropertyValues( $prop );
        print_r($values );
				foreach ( $values as $dv ) {
					$result->addPropertyObjectValue( $prop, $dv );
				}
			}
		} else {
      print "\n*****************************************";
			$result = clone $semdata;
		}
		return $result;
	}

  protected function buildWSQueryCall($baseurl, $properties, $q) {
    $urlArgs = array();
    $urlArgs['q'] = $q;
    /*$urlArgs['p'] = array(
      '?Skos:hasTopConcept=topConcept',
      '?Skos:preferedLabel=preferedLabel',
      '?Skos:preferedLabel@fr=preferedLabel@fr',
      '?Skos:definition=definition',
      '?Skos:definition@fr=definition@fr',
      '?Skos:broader=broader',
      '?Modification date'
    );*/
    foreach ($properties as $p ) {
      $proClean = str_replace('Property:', '?', $p);
      $urlArgs['p'][] = $proClean;
    };
    $urlArgs['p[format]'] = 'rdf';
    $wsCall =  wfArrayToCGI($urlArgs);
    return $baseurl.$wsCall;
	}
  
}
