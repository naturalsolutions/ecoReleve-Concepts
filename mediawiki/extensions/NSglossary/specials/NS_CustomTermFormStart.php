<?php
/**
 * Displays a form for entering the title of a page, which then redirects
 * to the form for creating/editing the page.
 *
 * @author Yaron Koren
 * @author Jeffrey Stuckman
 * @file
 * @ingroup SF
 */

/**
 * @ingroup SFSpecialPages
 */
class NSCustomTermFormStart extends SpecialPage {


  //Définition des constantes
	static $term_templateName = 'Definition term simple';
  static $term_formName = 'Definition term simple';
  
	static $voc_templateName = 'Definition term simple';
  static $voc_termFormName = 'Definition_type';
  
  static $term_inAutocompletionSource = 'Term';
  static $term_autocompletion_type = 'category';
  static $voc_inAutocompletionSource = 'TopConcept';
  static $voc_autocompletion_type = 'category';
  
    /**
     * Constructor
     */
    function __construct() {
        parent::__construct( 'CustomTermFormStart' );
    }

    function execute( $query ) {
        global $wgOut, $wgRequest;

        $this->setHeaders();
        
        //Récupération des variables du formulaire
        $form_name = $wgRequest->getVal( 'form' );
        $target_namespace = $wgRequest->getVal( 'namespace' );
        $super_page = $wgRequest->getVal( 'super_page' );
        $params = $wgRequest->getVal( 'params' );
       
       // If the query string did not contain a form name, try the URL
        if ( ! $form_name ) {
            $queryparts = explode( '/', $query, 2 );
            $form_name = isset( $queryparts[0] ) ? $queryparts[0] : '';
            // If a target was specified, it means we should
            // redirect to 'FormEdit' for this target page.
            if ( isset( $queryparts[1] ) ) {
                $target_name = $queryparts[1];
                $this->doRedirect( $form_name, $target_name, $params );
            }

            // Get namespace from the URL, if it's there.
            if ( $namespace_label_loc = strpos( $form_name, "/Namespace:" ) ) {
                $target_namespace = substr( $form_name, $namespace_label_loc + 11 );
                $form_name = substr( $form_name, 0, $namespace_label_loc );
            }
        }

        // Remove forbidden characters from the form name.
        $forbidden_chars = array( '"', "'", '<', '>', '{', '}', '(', ')', '[', ']', '=' );
        $form_name = str_replace( $forbidden_chars, "", $form_name );

        // Get title of form.
        $form_title = Title::makeTitleSafe( SF_NS_FORM, $form_name );

        // Handle submission of this form.
        //Cas avec redirection => pas de création de formulaire
        //Cas ou le form est appelé avec un parse {{#forminput {{#formlink .....
        $form_submitted = $wgRequest->getCheck( 'page_name' );
        if ( $form_submitted ) {
          //récupération de l'action
          $action = $wgRequest->getVal( 'action' );
          //Récupération du nom de la page à créer
          $page_name = $wgRequest->getVal( 'page_name' );
          $cparams = array();
          switch ($action) {
            case 'linkedterm' : 
              //Récupération des variables spécifiques au formulaire custom
              $cparams['relatedTopic'] = $wgRequest->getVal( 'related_topic' );
              $cparams['relationType'] = $wgRequest->getVal( 'relation_type' );   
              $cparams['isTopConcept'] = $wgRequest->getVal( 'isTopConcept' );   
              $test = $wgRequest->getVal( 'test');
              //Création des paramètres de la requête = valeur par défaut du nouveau terme   
              if ((  $cparams['relatedTopic'] !== '' )  && ( $cparams['relationType'] !== '')){
                $queryParams = self::buildQueryString ($cparams, $page_name, $action);
                //Ajout des valeurs par défaut aux paramètres
                if (!empty($params))  $params = $params .'&'. $queryParams;
                else  $params =  $queryParams;
              }
            break;
            case 'newterm' :
              //Récupération des variables spécifiques au formulaire custom
              $cparams['vocabulary'] = $wgRequest->getVal( 'vocabulary' );
              $queryParams = self::buildQueryString ($cparams, $page_name, $action);
              //Ajout des valeurs par défaut aux paramètres
              if (!empty($params))  $params = $params .'&'. $queryParams;
              else  $params =  $queryParams;
            break; 
            case 'newTopConcept' :
              $cparams['term_category'] = $wgRequest->getArray('term_category');

              $queryParams = self::buildQueryString($cparams, $page_name, $action);
              //Ajout des valeurs par défaut aux paramètres
              if (!empty($params))  $params = $params .'&'. $queryParams;
              else  $params =  $queryParams;
            break;
          }
          


          // This form can be used to create a sub-page for an
          // existing page
          if ( !is_null( $super_page ) && $super_page !== '' ) {
            $page_name = "$super_page/$page_name";
          }
          
          if ( $page_name !== '' ) {
            // Append the namespace prefix to the page name,
            // if this namespace was not already entered.
            if (( strpos( $page_name, $target_namespace . ':' ) === false) && (!is_null( $target_namespace )) ) {
              $page_name = $target_namespace . ':' . $page_name;
            }
            // If there was no page title, it's probably an
            // invalid page name, containing forbidden
            // characters - in that case, display an error
            // message.
            $page_title = Title::newFromText( $page_name );
            if ( !$page_title ) {
              $wgOut->addHTML( htmlspecialchars( wfMsg( 'sf_formstart_badtitle', $page_name ) ) );
              return;
            } else {
                SFFormStart::doRedirect( $form_name, $page_name, $params );
              return;
            }
          }
        }

        if ( ( !$form_title || !$form_title->exists() ) && ( $form_name !== '' ) ) {
            $text = Html::rawElement( 'p', array( 'class' => 'error' ), wfMsgExt( 'sf_formstart_badform', 'parseinline', SFUtils::linkText( SF_NS_FORM, $form_name ) ) ) . "\n";
        } else {
            
            SFUtils::addJavascriptAndCSS();
            $text='';            
            /**Création du formulaire create a linked term*/
            $text .= self::getFormCreateLinkedTerm('newterm', self::$term_templateName, $target_namespace, $super_page, $params);
            /**Création du formulaire create a linked term*/
            $text .= self::getFormCreateLinkedTerm('linkedterm', self::$term_templateName, $target_namespace, $super_page, $params);
            /**Création du formulaire create a linked term*/
            $text .= self::getFormCreateLinkedTerm('newTopConcept', self::$voc_templateName, $target_namespace, $super_page, $params);
          
           
        }
        $wgOut->addHTML( $text );
    }

    static function getFormCreateLinkedTerm($action, $form_name, $target_namespace, $super_page, $params) {
      
      global $parser;
      
      //Message de explicatifs des formulaires
      $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_termformstart_'.$action.'_title', $form_name ) ).'</h2>';
      $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_termformstart_'.$action.'_docu', $form_name ) ).'</p>';
      $text = <<<END
<form action="" method="post" id ="$action">
$description
END;
    
      switch ($action) {
        case 'linkedterm' : 
          $groupName = 'relation_type';
          $inRemoteAutocompletion= false;
          $text .= NSUtils::createNewTopicInput( $parser,  wfMsg( 'nsf_termformstart_'.$action.'_pageNameLabel'), null, self::$term_inAutocompletionSource, self::$term_autocompletion_type);
          $text .= NSUtils::createRelationTopicInput(wfMsg( 'nsf_termformstart_'.$action.'_relationTypeLabel'));
          $text .= NSUtils::nsCreateHtmlInputText( wfMsg( 'nsf_termformstart_'.$action.'_linkedTermName'), "related_topic", null,  $inRemoteAutocompletion ,self::$term_inAutocompletionSource, self::$term_autocompletion_type);
          $text .= "\t</p>\n";
        break;
        case 'newterm' : 
          $inRemoteAutocompletion= false;

          $text .= NSUtils::createNewTopicInput( $parser,  wfMsg( 'nsf_termformstart_'.$action.'_pageNameLabel'), null,   self::$term_inAutocompletionSource, self::$term_autocompletion_type);
          $text .= "\t</p>\n";

          $text .= NSUtils::nsCreateHtmlInputText( 'Vocabulary', "vocabulary", null,   $inRemoteAutocompletion ,self::$voc_inAutocompletionSource, self::$voc_autocompletion_type);
          $text .= "\t</p>\n";
          
        break;
        case 'newTopConcept' : 
          $inRemoteAutocompletion= false;
          $other_args = array();
          $other_args['top category'] = 'term_category';
          $text .= SFCategoriesInput::getHTML( null, 'term_category', true, false, $other_args );
          $text .= NSUtils::createNewTopicInput( $parser,  htmlspecialchars(wfMsg( 'nsf_termformstart_'.$action.'_pageNameLabel')), null,  self::$voc_inAutocompletionSource, self::$voc_autocompletion_type);
          $text .= "\t</p>\n";          
        break;
      }
      
      $text .= Html::hidden( 'form',$form_name);
      $text .= Html::hidden( 'namespace', $target_namespace );
      $text .= Html::hidden( 'super_page', $super_page );
      $text .= Html::hidden( 'params', $params );
      $text .= Html::hidden( 'action',$action);
      $text .= "\n\t" . Html::input( null, wfMsg( 'sf_formstart_createoredit' ), 'submit' ) . "\n";
      $text .= "\t</form>\n";
      return $text;
    }
    /**
     * Helper function - returns a URL that includes Special:FormEdit.
     */
    static function getFormEditURL( $formName, $targetName) {
        $fe = SFUtils::getSpecialPage( 'FormEdit' );
        // Special handling for forms whose name contains a slash.
        if ( strpos( $formName, '/' ) !== false ) {
            return $fe->getTitle()->getLocalURL( array( 'form' => $formName, 'target' => $targetName ) );
        }
        return $fe->getTitle( "$formName/$targetName" )->getLocalURL();
    }


        
    private function buildQueryString ($params,  $page_name, $action) {
      
      //Propriétés dont les valeurs sont présentes dans $params
      $queryParam=array();
      //Propriétés dont les valeurs doivent être récupérées avec une requete semantique
      $propList = array();
      
      switch ($action) {
        //Cas création d'un terme lié à un terme existant
        case 'linkedterm' : 
          $queryParam[]= self::$term_templateName.'[isTopConcept]=No';
          $relatedTopicName = $params['relatedTopic'];
          $relationType = $params['relationType'];
          $queryParam[]= self::$term_templateName.'[prefered term]='.$page_name;
          //Si le terme où se situait le formulaire est un top concept alors $params['isTopConcept'] = True
          if ($params['isTopConcept']) {
             $queryParam[]= self::$term_templateName.'[hasTopConcept]='.$relatedTopicName;
          }
          else {
            $propList = array('hasTopConcept'=>'Skos:hasTopConcept');
          }
          $broaderTopic = '';
          if ($relationType == 'brother') {
            $propList['broader'] = 'Skos:broader';
          }
          elseif ($relationType == 'narrower') {
            $queryParam[]= self::$term_templateName.'[broader]='.$relatedTopicName;
          }
          $valuesHtml = self::getRelatedTopicProperty($relatedTopicName, $propList);
      
          foreach ( $propList as $propertyLabel=> $propertyName ) {
              if (isset($valuesHtml[$propertyName])) $queryParam[]= self::$term_templateName.'['.$propertyLabel.']='.$valuesHtml[$propertyName];
          }
          
          if (($relationType == 'brother') && (isset($valuesHtml['Skos:broader']))){ $broaderTopic = $valuesHtml['Skos:broader'] ;}
          else {$broaderTopic = $relatedTopicName; }
                
          $order = self::getMaxOrder($broaderTopic);
          $queryParam[]= self::$term_templateName.'[order]='.$order;
           
        break;
        case 'newterm' : 
          $vocabulary = $params['vocabulary'];
          $queryParam[]= self::$term_templateName.'[prefered term]='.$page_name;
          $queryParam[]= self::$term_templateName.'[vocabulary]='.$vocabulary;
          $queryParam[]= self::$term_templateName.'[broader]='.$vocabulary;
          $queryParam[]= self::$term_templateName.'[order]=1';
          $order = self::getMaxOrder($vocabulary);
          $queryParam[]= self::$term_templateName.'[order]='.$order;
        break;
        case 'newTopConcept' :
          $term_category = $params['term_category'];
          unset($term_category['is_list']);
          $listterm_category = implode('&', $term_category);
          $queryParam[]= self::$voc_templateName.'[prefered term]='.$page_name;
          $queryParam[]= self::$voc_templateName.'[term_category]='.$listterm_category;
          $queryParam[]= self::$term_templateName.'[isTopConcept]=Yes';
          
        break;
      }
      
      $listParams = implode('&', $queryParam);
      
      return $listParams;
    }
    
    
    /****************************
     * * Fonction qui permet de récupérer les paramètres 'Skos:broader', 'Skos:hasTopConcept' d'une page
     * * @TODO gestion des propriétés mutlivaluées
     * *******************************/
    public function getRelatedTopicProperty ($relatedTopicName, $propList) {

        //Création de la page a partir de son titre
        $title = Title::newFromText($relatedTopicName);
        
        $valuesHtml=array();
        //Récupération de l'ensemble des propriétés sémantique d'une page
        $semdata = smwfGetStore()->getSemanticData( SMWDIWikiPage::newFromTitle($title));
        
        //Extraction des valeurs des proprétés souhaitées
        foreach ( $propList as $propertyLabel=> $propertyName ) {
            $propertyDi =  SMWDIProperty::newFromUserLabel($propertyName);
            $propvalues = $semdata->getPropertyValues($propertyDi);
            
            foreach ( $propvalues as $dataItem ) {
                $dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $propertyDi );
                if ( $dataValue->isValid() ) {
                    $valuesHtml[$propertyName] = $dataValue->getText ();
                }
            }
        }
        return $valuesHtml;
    }
    
    
    /****************************
     * * Fonction qui permet de récupérer la valeur maximale de la propriété ordre
     * *******************************/
    private function getMaxOrder ($broaderTopic) {
       
        $queryString = array ('[[Skos:broader::'.$broaderTopic.']]', '?Order', 'format=max');

        $result = NSUtils::runQuery($queryString);
        $order =  $result+1;
        return  $order;
    }
}
