<?php

class NSSMWData {
  /****************************************************************************
   * 
   * name: getStringPropertyValue
   *  Fonction renvoyant la valeur sous forme d'une string (medawiki) d'une propriété 
   * @param
   *  $semdata = entrepot sémantique contenant les valeur d'un objet
   *  $property = propriété d'intéret
   * @return (String)
   *    Valeur de $property
   ****************************************************************************/

  function getStringPropertyValue  (SMWSemanticData $semdata,SMWDIProperty $property) {
    $propvalues = $semdata->getPropertyValues($property)  	;
    foreach ( $propvalues as $dataItem ) {
      $dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $property );
      if ( $dataValue->isValid() ) {
        return $dataValue->getWikiValue ();
      }
    }
    return '';
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

    $v =  $this->getStringPropertyValue( $semdata,$p);

    if ( $core_props_only ) { // be sure to filter all non-relevant things that may still be present in the retrieved
      $result = new SMWSemanticData( $diWikiPage );
      foreach ( array( '_URI', '_TYPE', '_IMPO' ,'SubscribTo' ) as $propid ) {
        $prop = new SMWDIProperty( $propid );
        //print_r($prop );
        $values = $semdata->getPropertyValues( $prop );
        //print_r($values );
        foreach ( $values as $dv ) {
          $result->addPropertyObjectValue( $prop, $dv );
        }
      }
    } 
    else {
      $result = clone $semdata;
    }
    return $result;
  }

  public function buildWSQueryCall($baseurl,$q, $properties,  $extraproperties, $format = 'rdf') {
    $urlArgs = array();
    $urlArgs['q'] = $q;
    foreach ($properties as $p ) {
      $proClean = str_replace('Property:', '?', $p);
      $urlArgs['p'][] = $proClean;
    };
    
    foreach ($extraproperties as $epname => $epval) {
      $urlArgs['p['.$epname.']'] = $epval;
    };
    $urlArgs['p[format]'] = $format;
    $wsCall =  wfArrayToCGI($urlArgs);
    //print "<br/>$wsCall<br/>*******\n";
    return $baseurl.$wsCall;
	}
  	
  
  public function runQuery ($queryString) {
    $result = SMWQueryProcessor::getResultFromFunctionParams( $queryString, SMW_OUTPUT_WIKI );
    return  $result;
  }
  
  /****************************************************************************
   * 
   * name: getStringPropertyValue
   *  Fonction renvoyant la valeur sous forme d'une string (medawiki) d'une propriété 
   * @param
   *  $semdata = entrepot sémantique contenant les valeur d'un objet
   *  $property = propriété d'intéret
   * @return (String)
   *    Valeur de $property
   ****************************************************************************/

  static function getStringPropertyValues (SMWSemanticData $semdata,SMWDIProperty $property) {
    $vala = array();
    $propvalues = $semdata->getPropertyValues($property)  	;
    foreach ( $propvalues as $dataItem ) {
      $dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $property );
      if ( $dataValue->isValid() ) {
        $vala[]= $dataValue->getWikiValue ();
      }
    }
    return $vala;
  }
  
}
