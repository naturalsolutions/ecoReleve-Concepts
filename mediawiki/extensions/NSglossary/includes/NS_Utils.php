<?php
/**
 * Helper functions for the Semantic Forms extension.
 *
 * @ingroup SF
 */

class NSUtils {

	

	public static function createFormTermLink ( &$parser, $specialPageName, $params ) {
		global $wgVersion;

		// Set defaults.
		$inFormName = $inLinkStr = $inLinkType = $inTooltip =$inQueryStr = $inTargetName = '';
		if ( $specialPageName == 'RunQuery' ) {
			$inLinkStr = wfMsg( 'runquery' );
		}
		$classStr = "";
		$inQueryArr = array();
		
		$positionalParameters = false;
		
		// assign params
		// - support unlabelled params, for backwards compatibility
		// - parse and sanitize all parameter values
		foreach ( $params as $i => $param ) {
			
			$elements = explode( '=', $param, 2 );

			// set param_name and value
			if ( count( $elements ) > 1 && !$positionalParameters ) {
				$param_name = trim( $elements[0] );

				// parse (and sanitize) parameter values
				$value = trim( $parser->recursiveTagParse( $elements[1] ) );
			} else {
				$param_name = null;

				// parse (and sanitize) parameter values
				$value = trim( $parser->recursiveTagParse( $param ) );
			}

			if ( $param_name == 'form' ) {
				$inFormName = $value;
			} elseif ( $param_name == 'link text' ) {
				$inLinkStr = $value;
			} elseif ( $param_name == 'link type' ) {
				$inLinkType = $value;
			} elseif ( $param_name == 'query string' ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = self::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $param_name == 'tooltip' ) {
				$inTooltip = Sanitizer::decodeCharReferences( $value );
			} elseif ( $param_name == 'target' ) {
				$inTargetName = $value;
			} elseif ( $param_name == null && $value == 'popup' ) {
				self::loadScriptsForPopupForm( $parser );
				$classStr = 'popupformlink';
			} elseif ( $param_name !== null && !$positionalParameters ) {
				$value = urlencode($value);
				parse_str("$param_name=$value", $arr);
				$inQueryArr = self::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $i == 0 ) {
				$inFormName = $value;
				$positionalParameters = true;
			} elseif ( $i == 1 ) {
				$inLinkStr = $value;
			} elseif ( $i == 2 ) {
				$inLinkType = $value;
			} elseif ( $i == 3 ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = self::array_merge_recursive_distinct( $inQueryArr, $arr );
			} 
		}

		$ad = SFUtils::getSpecialPage( $specialPageName );
		$link_url = $ad->getTitle()->getLocalURL() . "/$inFormName";
		if ( ! empty( $inTargetName ) ) {
			$link_url .= "/$inTargetName";
		}
		$link_url = str_replace( ' ', '_', $link_url );
		$hidden_inputs = "";
		if ( ! empty($inQueryArr) ) {
			// Special handling for the buttons - query string
			// has to be turned into hidden inputs.
			if ( $inLinkType == 'button' || $inLinkType == 'post button' ) {

				$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );

				foreach ( $query_components as $query_component ) {
					$var_and_val = explode( '=', $query_component, 2 );
					if ( count( $var_and_val ) == 2 ) {
						$hidden_inputs .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
					}
				}
			} else {
				$link_url .= ( strstr( $link_url, '?' ) ) ? '&' : '?';
				$link_url .= str_replace( '+', '%20', http_build_query( $inQueryArr, '', '&' ) );
			}
		}
		if ( $inLinkType == 'button' || $inLinkType == 'post button' ) {
			$formMethod = ( $inLinkType == 'button' ) ? 'get' : 'post';
			$str = Html::rawElement( 'form', array( 'action' => $link_url, 'method' => $formMethod, 'class' => $classStr ),
				Html::rawElement( 'button', array( 'type' => 'submit', 'value' => $inLinkStr ), $inLinkStr ) .
				$hidden_inputs
			);
		} else {
			// If a target page has been specified but it doesn't
			// exist, make it a red link.
			if ( ! empty( $inTargetName ) ) {
				$targetTitle = Title::newFromText( $inTargetName );
				if ( is_null( $targetTitle ) || !$targetTitle->exists() ) {
					$classStr .= " new";
				}
			}
			$str = Html::rawElement( 'a', array( 'href' => $link_url, 'class' => $classStr, 'title' => $inTooltip ), $inLinkStr );
		}

		return $str;
	}	
	
  public static function createNewTopicInput (  &$parser, $label, $inValue, $pageName_inAutocompletionSource ,  $pageName_autocompletion_type){
     global $sfgFieldNum;
    $formInputAttrs = array( );
  
    // Now apply the necessary settings and Javascript, depending
    // on whether or not there's autocompletion (and whether the
    // autocompletion is local or remote).
    $input_num = 1;

    $sfgFieldNum++;
    $input_num = $sfgFieldNum;
    // place the necessary Javascript on the page, and
    // disable the cache (so the Javascript will show up) -
    // if there's more than one autocompleted #forminput
    // on the page, we only need to do this the first time
    if ( $input_num == 1 ) {
      if (isset($parser)) $parser->disableCache();
      SFUtils::addJavascriptAndCSS();
    }
    
    
    $inputID = 'input_' . $input_num;
    $formInputAttrs['id'] = $inputID;
    $formInputAttrs['class'] = 'autocompleteInput createboxInput formInput mandatoryField';


    $autocompletion_values = SFUtils::getAutocompleteValues( $pageName_inAutocompletionSource, $pageName_autocompletion_type );

    global $sfgAutocompleteValues;
    $sfgAutocompleteValues[$inputID] = $autocompletion_values;
   
    $formInputAttrs['autocompletesettings'] = $inputID;
    return "\t<span class='mandatoryFieldSpan'><label> $label</label>" . Html::input( 'page_name', $inValue, 'text', $formInputAttrs ) . "</span>\n";

  }
  
  public static  function createRelationTopicInput ($label, $isTopConcept){
    
    global $sfgFieldNum;
     $sfgFieldNum++;
    $groupName = 'relation_type';
    $other_args= array();
    $other_args['possible_values'][]='narrower';
    $other_args['value_labels']['narrower']='narrower';
    if ( ! $isTopConcept) {
      $other_args['possible_values'][]='brother';
      $other_args['value_labels']['brother']='sibling';
    }
    $str  ='<span class="forminput">';
    $str .='<span class="formlabel">'.$label.' : </span>';
    $str .= SFDropdownInput::getHTML( $other_args['possible_values'][0], $groupName,true, false, $other_args );
    $str .='</span>';
    return $str;
  } 
  
  public static  function nsCreateHtmlInputText ($label, $input_name, $inValue,  $inRemoteAutocompletion ,$inAutocompletionSource, $autocompletion_type ) {
        global $sfgFieldNum;
        $formInputAttrs = array( 'size' => 25 );
        $inputID = 'input_' . $sfgFieldNum;
        $formInputAttrs['id'] = $inputID;
        $formInputAttrs['class'] = 'autocompleteInput createboxInput formInput';
        if ( $inRemoteAutocompletion ) {
            $formInputAttrs['autocompletesettings'] = $inAutocompletionSource;
            $formInputAttrs['autocompletedatatype'] = $autocompletion_type;
        } 
        else {
            $autocompletion_values = SFUtils::getAutocompleteValues( $inAutocompletionSource, $autocompletion_type );
            global $sfgAutocompleteValues;
            $sfgAutocompleteValues[$inputID] = $autocompletion_values;
            $formInputAttrs['autocompletesettings'] = $inputID;
        }
        $str  ='<span class="forminput">';
        $str .='<span class="formlabel">'.$label.' : </span>';
        $str .= Html::input($input_name, $inValue, 'text', $formInputAttrs ) ;
        $str .='</span>';
        return  $str;
                
    }


}
