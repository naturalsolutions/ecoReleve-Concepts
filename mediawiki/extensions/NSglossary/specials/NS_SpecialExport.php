<?php
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
    
    
    ///------------------------------------------------------------------
    //      LOGGIN 
    ///------------------------------------------------------------------
    //@TODO => Si pas de paramètre username et password mais que l'utilisateur est loggé
		//Test si l'utilisateur est loggé et qu'il a la permission
    if ( !$wgUser->isLoggedIn() ) { 
      //$wgOut->addHTML('NOT permitted');
      //return ;
    }      
    //Récupération des données utilisateur  passés en paramètres de la requête
    //@TODO : passer les données en variable
    $username = $wgRequest->getText( 'username' );
    $password = $wgRequest->getText( 'password' ) ;
    $userId =  User::idFromName ($username);

     //Login
    try {
      $token = NSCallApi::login($username,  $password);
      NSCallApi::login($username,  $password, $token);
      $log = 'success';
      
    } 
    catch (Exception $e) {
      die("FAILED: " . $e->getMessage());
    }
    
		$wgOut->setPageTitle( 'Première version exporter : un concept');
    $pages =false;
		// see if we can find something to export:
		$page = is_null( $page ) ? $wgRequest->getVal( 'page' ) : rawurldecode( $page );
    $page = is_null( $page ) ? $wgRequest->getCheck( 'text' ) : $page;
    $page = is_null( $page ) ? $wgRequest->getCheck( 'page' ) : $page;

		if ( $page != '') {
      //Get semantic properties associated to subscription & build query
      try  {
        ///------------------------------------------------------------------
        //      Subscription data 
        ///------------------------------------------------------------------
        //@TODO : Vérifier que la subscription existe bien 
        $r = $this->getUserSubscription($userId, $page);
        if (!$r) {
          $wgOut->addHTML('You are not subscriber to this subscription');
          return ;
        }
               
        $pDataItem = SMWDIWikiPage::newFromTitle(Title::newFromText($page) ); 
        $p =  new SMWDIProperty('SubscribTo');
        $semdata = smwfGetStore()->getSemanticData( $pDataItem); // advise store to retrieve only core things
        $subscribTo =  NSSMWData::getStringPropertyValue( $semdata,$p);
        $proper =  NSSMWData::getStringPropertyValues( $semdata,new SMWDIProperty('ExportProperty'));
       
       if ($subscribTo === '')  {
          $wgOut->addHTML('Subscription page whitout SubscribTo property');
          return ;
        }
        ///------------------------------------------------------------------
        //      USER subscriber LOG 
        ///------------------------------------------------------------------
        $dbr = wfGetDB( DB_SLAVE );

        $res = $dbr->select(
          'nss_subscription_log_per_user',  
          array( 'lpg_action', 'lpg_date_utc' ),
          'lpg_user_id = '.$userId.' AND lpg_subscription_title =\''.$page.'\'',
          __METHOD__,
          array( 'ORDER BY' => 'lpg_date_utc DESC',  'LIMIT' =>1)
        );        
        $lastSynchroDate = NULL;
        //Si aucune données => alors la subscription n'a pas été initialisée
        if ( 	$res->numRows() == 0 ) {
          $q = '[['.$subscribTo.']]';
          $action='initialize';
        }
        else {
          $row  = $res->fetchObject();
          $lastSynchroDate =  $row->lpg_date_utc;
          $q = '[['.$subscribTo.']][[Modification_date::>'.$lastSynchroDate.']]';
          $action='update';
        }
      }
      catch ( Exception $e ){
          echo "<b>error</b>: ".$e->getMessage();
      }
      
      //Count results
      //Si nombre de résultat est supérieur à 100 alors le renvoie une 
      $params[] = new SMWPrintRequest( SMWPrintRequest::PRINT_THIS, "" );
      $params = SMWQueryProcessor::getProcessedParams( array(), array() );
      $printouts[] = new SMWPrintRequest(SMWPrintRequest::PRINT_PROP, 'has Connection', SMWPropertyValue::makeUserProperty('has Connection')); 
      $queryobj = SMWQueryProcessor::createQuery($q, $params,'self::INLINE_QUERY', 'count');
      $resultsCount = smwfGetStore()->getQueryResult($queryobj); 
      
      $allowedLg = $this->getAllowedLanguage();
      
      $params = array('limit'=>100,'offset'=>0 );
        
      $wsCall = NSSMWData::buildWSQueryCall($wgServer.$wgScriptPath.'/index.php?title=Special%3AAsk&', $q,$proper,$params);
      $resultsXML = file_get_contents($wsCall);
      foreach ($allowedLg AS $lg ) {
        $resultsXML = str_replace('>'.$lg.':',' xml:lang="'.$lg.'">', $resultsXML);
      }
      
      $rdfresults = '';
      for ($i=100; $i<$resultsCount+100; $i = $i+100) {
        $params = array('limit'=>100,'offset'=>$i );
        
        $wsCall = NSSMWData::buildWSQueryCall($wgServer.$wgScriptPath.'/index.php?title=Special%3AAsk&', $q,$proper,$params);
        $resultsCall= file_get_contents($wsCall);
        foreach ($allowedLg AS $lg ) {
          $resultsCall = str_replace('>'.$lg.':',' xml:lang="'.$lg.'">', $resultsCall);
        }
      
        //$resultsXML = $resultsXML . file_get_contents($wsCall);
        // print_r($resultsXML);
        $dom = new DomDocument;
        $dom->loadXml($resultsCall);
        $xph = new DOMXPath($dom);
        $xph->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

        foreach($xph->query('/rdf:RDF') as $attribute) {
            //print_r($attribute->nodeValue);
            $rdfresults .= $this->get_inner_html( $attribute );
        }
      }
      //Ajout des logs delete/Move
      if ($action=='update' ) {
        $logsEvents = $this->getMoveDeleteAction($lastSynchroDate) ; 
        $rdfresults .= implode('', $logsEvents);
      }
    
    
      $resultsXML = str_replace('</rdf:RDF>', $rdfresults .'</rdf:RDF>', $resultsXML);
      
      //Modification log de l'appel à la subscription
      $this->logUserSubscription($userId, $page, $action) ;
      
      header( "Content-type: application/rdf+xml; charset=UTF-8" );
      $wgOut->disable();
      print $resultsXML;
      return ;
		}
		// Nothing exported yet; show user interface:
		$this->showForm();
	}
  function get_inner_html( $node ) {
    $innerHTML= '';
    $children = $node->childNodes;
    foreach ($children as $child) {
        $innerHTML .= $child->ownerDocument->saveXML( $child );
    }

    return $innerHTML;
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
	
  protected function getMoveDeleteAction($lastSynchroDate = NULL) {
    $dbr = wfGetDB( DB_SLAVE );
    //Récupération des pages/Catégorie liées à la catégorie racine
    $tables = array( 'logging' );
    $fields = array( 'log_id','log_type','log_action','log_timestamp','log_user','log_user_text','log_namespace',
      'log_title','log_page','log_comment','log_params','log_deleted');
    $where = array();
    $where['log_action'] =  array('move', 'delete', 'restore');
    
    if ($lastSynchroDate) $where[] = 'log_timestamp > '. wfTimestamp( TS_MW, $lastSynchroDate);
    $joins = array();
    $options = array();
    
    $res = $dbr->select( $tables, $fields, $where, __METHOD__, $options, $joins );
    $i =0;
    $event = array();
    $resolverURL =  SpecialPage::getTitleFor( 'URIResolver' )->getFullURL().'/';
    foreach ( $res as $row ) {
      global $smwgNamespace; // complete namespace for URIs (with protocol, usually http://)
      global $wgCanonicalNamespaceNames;
      $titleName = $row->log_title ;
      
      if (isset( $wgCanonicalNamespaceNames[$row->log_namespace])) $titleName =  $wgCanonicalNamespaceNames[$row->log_namespace].':'.$titleName ;
      $logEntry = DatabaseLogEntry::newFromRow($row);
      $title = Title::newFromText( $titleName);
      $logParams =  $logEntry->getParameters();

      if ( '' == $smwgNamespace ) {
        $resolver = SpecialPage::getTitleFor( 'URIResolver' );
        $smwgNamespace = $resolver->getFullURL() . '/';
      } elseif ( $smwgNamespace[0] == '.' ) {
        $resolver = SpecialPage::getTitleFor( 'URIResolver' );
        $smwgNamespace = "http://" . substr( $smwgNamespace, 1 ) . $resolver->getLocalURL() . '/';
      }
      $text = '<swivt:Subject rdf:about="'.$smwgNamespace.$title->getPrefixedURL() ; 
      $text .='" action="'.$row->log_action.'">';
      $text .= '<swivt:creationDate rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">'.wfTimestamp( TS_ISO_8601, $row->log_timestamp) .'</swivt:creationDate>';
      if ($row->log_action =='move' &&  ( isset($logParams['4::target'] ))) {
         $text .= '<target rdf:about="http://192.168.1.96/html/ecoReleve-glossary/wiki/Special:URIResolver/'.$logParams['4::target'].'"/>';
      }
      $text .= '</swivt:Subject>';
      $event[] = $text;
      
    }
    return $event;
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
	
  public static function getUserSubscription($userId, $subTitle) {

    $dbr = wfGetDB( DB_SLAVE );

    $res = $dbr->select(
            'nss_subscription_per_user',                                   // $table
            array( 'upg_subscription_title', 'upg_user_id' ),            // $vars (columns of the table)
            'upg_user_id = '.$userId,                              // $conds
            __METHOD__,                                   // $fname = 'Database::select',
            array()        // $options = array()
    );        
 
    foreach ( $res as $row ) {
      if (strtolower($row->upg_subscription_title)== strtolower($subTitle)) return true;
    }
    return false;
		
	}
	
  
  public function getAllowedLanguage() {
    $proptitle = Title::makeTitleSafe( SMW_NS_PROPERTY, 'Dcterms:language');
    if ( $proptitle === null ) {
      return;
    }
    $store = smwfGetStore();
    // this returns an array of objects
    $allowed_values = SFUtils::getSMWPropertyValues( $store, $proptitle, "Allows value" );
    return $allowed_values;
  }
  public function logUserSubscription($userId, $subTitle, $action) {

    $dbw = wfGetDB( DB_SLAVE );
		$dbw->begin();
/*lpg_subscription_title
lpg_user_id
lpg_action
lpg_date_utc**/
    date_default_timezone_set("UTC");
    $today = date("Y-m-d H:i:s", time()); 
    $dbw->insert(
      'nss_subscription_log_per_user',
      array(
        'lpg_subscription_title' => $subTitle,
        'lpg_user_id' =>  $userId,
        'lpg_action' => $action,
        'lpg_date_utc' =>  $today,
      )
    );
		$dbw->commit();

		return true;
		
	}

}
