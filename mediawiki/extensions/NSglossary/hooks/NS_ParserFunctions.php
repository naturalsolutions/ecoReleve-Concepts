<?php
/**
 * Parser functions for Custom NS Forms.
 * {{#example:Skos:preferedLabel|gogog}}
 * {{#termforminput:form=|size=|default value=|button text=|query string=
 * |autocomplete on category=|autocomplete on namespace=
 * |remote autocompletion|...additional query string values...}}
 */

class NSParserFunctions {

	// static variable to guarantee that Javascript for autocompletion
	// only gets added to the page once
	static $num_autocompletion_inputs = 0;

	//Enregistrement des fonction parseur
	static function registerFunctions( &$parser ) {

		global $wgOut;
		$parser->setFunctionHook( 'nstermform', array( 'NSParserFunctions', 'renderTermFormInput' ));
		$parser->setFunctionHook( 'nsvocform', array( 'NSParserFunctions', 'renderVocabularyFormInput' ));
		$parser->setFunctionHook( 'nsactionlink', array( 'NSParserFunctions', 'renderActionLink' ));
    $parser->setFunctionHook( 'ucword', array( 'NSParserFunctions', 'renderUcword' ));
		//$parser->setFunctionHook( 'flatCategoryHierarchy', array( 'NSParserFunctions', 'renderFlatCategoryHierarchy' ) );
		$parser->setFunctionHook( 'd3jstree',array( 'NSParserFunctions',  'renderD3jsTree'));
		$parser->setFunctionHook( 'forcedirected',array( 'NSParserFunctions',  'renderForcedirected'));
		
  //  $parser->setFunctionHook( 'categoryhier',  array( 'NSParserFunctions',  'categoryhier'));
		// load jQuery on MW 1.16
		if ( is_callable( array( $wgOut, 'includeJQuery' ) ) ) {
			$wgOut->includeJQuery();
		}

		return true;
	}
	
 	static function renderForcedirected ( &$parser, $param1 = '', $param2 = '' ) {
    global $wgOut, $wgScriptPath,$moduleGlossaryName ;
    $nsfgIP = dirname( __FILE__ );
    $params = func_get_args();
    // The input parameters are wikitext with templates expanded.
    // The output should be wikitext too.
   // print_r($params);
  $output='';
		# grab all known options from the request. Normalization is done by the CategoryTree class
    global $wgServer  ;
    $title = $parser->getTitle();
    $fs_url = $title->getLocalURL();
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/data/flare.json';
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/d3/d3.js';
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/d3/d3.geom.js';
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/d3/d3.layout.js';
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/force-collapsible.js';
   	foreach ( $scripts as $js ) {
			$wgOut->addScriptFile( $js );
		}
			$css_text = <<<END
  <style type="text/css">
circle.node {
  cursor: pointer;
  stroke: #000;
  stroke-width: .5px;
}
line.link {
  fill: none;
  stroke: #9ecae1;
  stroke-width: 1.5px;
}
#currentNode {
  font-size:16px;
}
    </style>
END;
		$wgOut->addStyle($css_text);
    return $output;
  } 
  
 	static function renderD3jsTree ( &$parser, $param1 = '', $param2 = '' ) {
    global $wgOut, $wgScriptPath,$moduleGlossaryName ;
    $nsfgIP = dirname( __FILE__ );
    $params = func_get_args();
    // The input parameters are wikitext with templates expanded.
    // The output should be wikitext too.
   // print_r($params);
  $output='';
		# grab all known options from the request. Normalization is done by the CategoryTree class
    global $wgServer  ;
    $title = $parser->getTitle();
    $fs_url = $title->getLocalURL();
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/d3/d3.js';
    $scripts[] = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js/d3jstree/d3/d3.layout.js';
   	foreach ( $scripts as $js ) {
			$wgOut->addScriptFile( $js );
		}
			$javascript_text = <<<END

    <script type="text/javascript">

var m = [20, 120, 20, 120],
    w = 1280 - m[1] - m[3],
    h = 800 - m[0] - m[2],
    i = 0,
    root;

var tree = d3.layout.tree()
    .size([h, w]);

var diagonal = d3.svg.diagonal()
    .projection(function(d) { return [d.y, d.x]; });

var vis = d3.select("#body").append("svg:svg")
    .attr("width", w + m[1] + m[3])
    .attr("height", h + m[0] + m[2])
  .append("svg:g")
    .attr("transform", "translate(" + m[3] + "," + m[0] + ")");

d3.json("$wgScriptPath/extensions/$moduleGlossaryName/js/d3jstree/flare.json", function(json) {
  root = json;
  root.x0 = h / 2;
  root.y0 = 0;

  function toggleAll(d) {
    if (d.children) {
      d.children.forEach(toggleAll);
      toggle(d);
    }
  }

  // Initialize the display to show a few nodes.
  root.children.forEach(toggleAll);
  toggle(root.children[1]);
  toggle(root.children[1].children[2]);

  update(root);
});

function update(source) {
  var duration = d3.event && d3.event.altKey ? 5000 : 500;

  // Compute the new tree layout.
  var nodes = tree.nodes(root).reverse();

  // Normalize for fixed-depth.
  nodes.forEach(function(d) { d.y = d.depth * 180; });

  // Update the nodes…
  var node = vis.selectAll("g.node")
      .data(nodes, function(d) { return d.id || (d.id = ++i); });

  // Enter any new nodes at the parent's previous position.
  var nodeEnter = node.enter().append("svg:g")
      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
      .on("click", function(d) { toggle(d); update(d); });

  nodeEnter.append("svg:circle")
      .attr("r", 1e-6)
      .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

  nodeEnter.append("svg:text")
      .attr("x", function(d) { return d.children || d._children ? -10 : 10; })
      .attr("dy", ".35em")
      .attr("text-anchor", function(d) { return d.children || d._children ? "end" : "start"; })
      .text(function(d) { return d.name; })
      .style("fill-opacity", 1e-6);

  // Transition nodes to their new position.
  var nodeUpdate = node.transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

  nodeUpdate.select("circle")
      .attr("r", 4.5)
      .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

  nodeUpdate.select("text")
      .style("fill-opacity", 1);

  // Transition exiting nodes to the parent's new position.
  var nodeExit = node.exit().transition()
      .duration(duration)
      .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
      .remove();

  nodeExit.select("circle")
      .attr("r", 1e-6);

  nodeExit.select("text")
      .style("fill-opacity", 1e-6);

  // Update the links…
  var link = vis.selectAll("path.link")
      .data(tree.links(nodes), function(d) { return d.target.id; });

  // Enter any new links at the parent's previous position.
  link.enter().insert("svg:path", "g")
      .attr("class", "link")
      .attr("d", function(d) {
        var o = {x: source.x0, y: source.y0};
        return diagonal({source: o, target: o});
      })
    .transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition links to their new position.
  link.transition()
      .duration(duration)
      .attr("d", diagonal);

  // Transition exiting nodes to the parent's new position.
  link.exit().transition()
      .duration(duration)
      .attr("d", function(d) {
        var o = {x: source.x, y: source.y};
        return diagonal({source: o, target: o});
      })
      .remove();

  // Stash the old positions for transition.
  nodes.forEach(function(d) {
    d.x0 = d.x;
    d.y0 = d.y;
  });
}

// Toggle children.
function toggle(d) {
  if (d.children) {
    d._children = d.children;
    d.children = null;
  } else {
    d.children = d._children;
    d._children = null;
  }
}

    </script>

END;
		$wgOut->addScript( $javascript_text );
    return $output;
  } 
  
  static function getParentCategory ($cl_from, $result){
    print '<br/>';
    $dbr = wfGetDB( DB_SLAVE );
    $sql = 'SELECT `cl_from`,  cl_to as  parent_title, page.page_id  as parent_id FROM `categorylinks` JOIN page ON 	cl_to = page_title  
      WHERE cl_from = '.$cl_from;
    $res = $dbr->query( $sql, __METHOD__ );
    foreach ( $res as $row ) {
			$result= $row->parent_title .'>'.$result;
      print 'cl_from'.$row->cl_from;
      //if ()getParentCategory ($row->cl_from, $result);
		}
    return $result;
  }
  
	static function renderTermFormInput ( &$parser ) {
    
    //Test les droits d'utilisateurs
    // Pour accéder à la fontionnalité l'utilisateur doit disposer des droits : createpage
    global $wgUser;

    $user_can_createpage = $wgUser->isAllowed( 'createpage' );
		if (!$user_can_createpage) return $parser->insertStripItem( "Not allowed", $parser->mStripState );
   
   
    //Si l'utilisateur à les bons privilèges
    //Création du formulaire
		$params = func_get_args();
		array_shift( $params ); // don't need the parser
		// set defaults
		$inFormName = $inValue = $inButtonStr = $inQueryStr = '';
		$inQueryArr = array();
		$positionalParameters = false;
		$inAutocompletionSource = '';
		$inRemoteAutocompletion = false;
    $isTopConcept = false;
		$inSize = 25;
		$classStr = "";
		$str ="";
    //Paramètre qui permet de spécifier si le terme est nouveau ou relié à un terme existant
    $islinked=false;
		// assign params - support unlabelled params, for backwards compatibility
		foreach ( $params as $i => $param ) {
			$elements = explode( '=', $param, 2 );

			//set param_name and value
			if ( count( $elements ) > 1 && !$positionalParameters ) {
				$param_name = trim( $elements[0] );

				// parse (and sanitize) parameter values
				$value = trim( $parser->recursiveTagParse( $elements[1] ) );
			} else {
				$param_name = null;

				// parse (and sanitize) parameter values
				$value = trim( $parser->recursiveTagParse( $param ) );
			}
      
      if ( $param_name == 'form' )
				$inFormName = $value;
			elseif ( $param_name == 'size' )
				$inSize = $value;
      elseif ( $param_name == 'isTopConcept' )
				$isTopConcept = true;
			elseif ( $param_name == 'default value' )
				$inValue = $value;
			elseif ( $param_name == 'button text' )
				$inButtonStr = $value;
			elseif ( $param_name == 'query string' ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $param_name == null && $value == 'linkedterm' ) {
				$islinked=true;
			} elseif ( $param_name == null && $value == 'popup' ) {
				SFUtils::loadScriptsForPopupForm( $parser );
				$classStr = 'popupforminput';
			} elseif ( $param_name !== null && !$positionalParameters ) {
				$value = urlencode($value);
				parse_str("$param_name=$value", $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $i == 0 ) {
				$inFormName = $value;
				$positionalParameters = true;
			} elseif ( $i == 1 ) {
				$inSize = $value;
			} elseif ( $i == 2 ) {
				$inValue = $value;
			} elseif ( $i == 3 ) {
				$inButtonStr = $value;
			} elseif ( $i == 4 ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			}
		}

		$fs = SFUtils::getSpecialPage( 'CustomTermFormStart' );
		$fs_url = $fs->getTitle()->getLocalURL();
		
    $str .='<h2>'.wfMsg('nsf_termformstart_linkedterm_title').'</h2>';
    $str .= <<<END
			<form name="createbox" action="$fs_url" method="get" class="$classStr">
			<p>

END;
    $str.=NSUtils::createNewTopicInput( $parser,  wfMsg( 'nsf_termformstart_linkedterm_pageNameLabel'), null, NSCustomTermFormStart::$term_inAutocompletionSource, NSCustomTermFormStart::$term_autocompletion_type);
    if ($islinked)  {
      $str.=NSUtils::createRelationTopicInput(wfMsg( 'nsf_termformstart_linkedterm_relationTypeLabel'), $isTopConcept);
      $str .= Html::hidden( 'action','linkedterm');
      $str .= Html::hidden( 'isTopConcept',$isTopConcept);
    }
    else {
       $str .= Html::hidden( 'action','newterm');
    }
    
		// if the form start URL looks like "index.php?title=Special:FormStart"
		// (i.e., it's in the default URL style), add in the title as a
		// hidden value
		if ( ( $pos = strpos( $fs_url, "title=" ) ) > - 1 ) {
			$str .= Html::hidden( "title", urldecode( substr( $fs_url, $pos + 6 ) ) );
		}
		if ( $inFormName == '' ) {
			$str .= SFUtils::formDropdownHTML();
		} else {
			$str .= Html::hidden( "form", $inFormName );
		}

		// Recreate the passed-in query string as a set of hidden variables.
		if ( !empty( $inQueryArr ) ) {
			// query string has to be turned into hidden inputs.

			$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );

			foreach ( $query_components as $query_component ) {
				$var_and_val = explode( '=', $query_component, 2 );
				if ( count( $var_and_val ) == 2 ) {
					$str .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
				}
			}
		}

		$button_str = ( $inButtonStr != '' ) ? $inButtonStr : wfMsg( 'sf_formstart_createoredit' );
		$str .= <<<END
			<input type="submit" value="$button_str" /></p>
			</form>

END;
		if ( ! empty( $inAutocompletionSource ) ) {
			$str .= "\t\t\t" .
				Html::element( 'div',
					array(
						'class' => 'page_name_auto_complete',
						'id' => "div_$input_num",
					),
					// it has to be <div></div>, not
					// <div />, to work properly - stick
					// in a space as the content
					' '
				) . "\n";
		}

		// hack to remove newline from beginning of output, thanks to
		// http://jimbojw.com/wiki/index.php?title=Raw_HTML_Output_from_a_MediaWiki_Parser_Function
		return $parser->insertStripItem( $str, $parser->mStripState );
	}
	
  static function renderFlatCategoryHierarchy ( &$parser) {
    $params = func_get_args();
		array_shift( $params ); // don't need the parser
    $title = Title::newFromText('Activity taxon');
    //$str = CategoryTree::renderParents('Activity taxon');
    print 'toto';
    //print_r($str);
    return $parser->insertStripItem( $str, $parser->mStripState );
  }
  
  static function renderVocabularyFormInput (&$parser) {
      //Test les droits d'utilisateurs
    // Pour accéder à la fontionnalité l'utilisateur doit disposer des droits : createpage
    global $wgUser;

    $user_can_createpage = $wgUser->isAllowed( 'createpage' );
		if (!$user_can_createpage) return $parser->insertStripItem( "Not allowed", $parser->mStripState );
   
   
    //Si l'utilisateur à les bons privilèges
    //Création du formulaire
		$params = func_get_args();
		array_shift( $params ); // don't need the parser
		// set defaults
		$inFormName = $inValue = $inButtonStr = $inQueryStr = '';
		$inQueryArr = array();
		$positionalParameters = false;
		$inAutocompletionSource = '';
		$inRemoteAutocompletion = false;
		$inSize = 25;
		$classStr = "";
		$str ="";
		// assign params - support unlabelled params, for backwards compatibility
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
      
      
			if ( $param_name == 'form' )
				$inFormName = $value;
			elseif ( $param_name == 'size' )
				$inSize = $value;
			elseif ( $param_name == 'default value' )
				$inValue = $value;
			elseif ( $param_name == 'button text' )
				$inButtonStr = $value;
			elseif ( $param_name == 'query string' ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $param_name == null && $value == 'popup' ) {
				SFUtils::loadScriptsForPopupForm( $parser );
				$classStr = 'popupforminput';
			} elseif ( $param_name !== null && !$positionalParameters ) {
				$value = urlencode($value);
				parse_str("$param_name=$value", $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			} elseif ( $i == 0 ) {
				$inFormName = $value;
				$positionalParameters = true;
			} elseif ( $i == 1 ) {
				$inSize = $value;
			} elseif ( $i == 2 ) {
				$inValue = $value;
			} elseif ( $i == 3 ) {
				$inButtonStr = $value;
			} elseif ( $i == 4 ) {
				// Change HTML-encoded ampersands directly to
				// URL-encoded ampersands, so that the string
				// doesn't get split up on the '&'.
				$inQueryStr = str_replace( '&amp;', '%26', $value );
				
				parse_str($inQueryStr, $arr);
				$inQueryArr = SFUtils::array_merge_recursive_distinct( $inQueryArr, $arr );
			}
		}

		$fs = SFUtils::getSpecialPage( 'CustomTermFormStart' );
		$fs_url = $fs->getTitle()->getLocalURL();
		$str = <<<END
			<form name="createbox" action="$fs_url" method="get" class="$classStr">
			<p>

END;
  //(  &$parser, $label, $inValue, $pageName_inAutocompletionSource ,  $pageName_autocompletion_type){
    $str.=NSUtils::createNewTopicInput( $parser,  wfMsg( 'nsf_termformstart_newTopConcept_pageNameLabel'), null, NSCustomTermFormStart::$voc_inAutocompletionSource, NSCustomTermFormStart::$voc_autocompletion_type);

    $str .= Html::hidden( 'action','newTopConcept');
  
    
		// if the form start URL looks like "index.php?title=Special:FormStart"
		// (i.e., it's in the default URL style), add in the title as a
		// hidden value
		if ( ( $pos = strpos( $fs_url, "title=" ) ) > - 1 ) {
			$str .= Html::hidden( "title", urldecode( substr( $fs_url, $pos + 6 ) ) );
		}
		if ( $inFormName == '' ) {
			$str .= SFUtils::formDropdownHTML();
		} else {
			$str .= Html::hidden( "form", $inFormName );
		}

		// Recreate the passed-in query string as a set of hidden variables.
		if ( !empty( $inQueryArr ) ) {
			// query string has to be turned into hidden inputs.

			$query_components = explode( '&', http_build_query( $inQueryArr, '', '&' ) );

			foreach ( $query_components as $query_component ) {
				$var_and_val = explode( '=', $query_component, 2 );
				if ( count( $var_and_val ) == 2 ) {
					$str .= Html::hidden( urldecode( $var_and_val[0] ), urldecode( $var_and_val[1] ) );
				}
			}
		}

		$button_str = ( $inButtonStr != '' ) ? $inButtonStr : wfMsg( 'sf_formstart_createoredit' );
		$str .= <<<END
			<input type="submit" value="$button_str" /></p>
			</form>

END;
		if ( ! empty( $inAutocompletionSource ) ) {
			$str .= "\t\t\t" .
				Html::element( 'div',
					array(
						'class' => 'page_name_auto_complete',
						'id' => "div_$input_num",
					),
					// it has to be <div></div>, not
					// <div />, to work properly - stick
					// in a space as the content
					' '
				) . "\n";
		}

		// hack to remove newline from beginning of output, thanks to
		// http://jimbojw.com/wiki/index.php?title=Raw_HTML_Output_from_a_MediaWiki_Parser_Function
		return $parser->insertStripItem( $str, $parser->mStripState );
  }

 
  static function renderActionLink (&$parser, $action) {
    $params = func_get_args();
    array_shift( $params ); // don't need the parser
    $positionalParameters = false;
    $permission = $action;
    $label = wfMsg( 'nsf_actionname_'.$action ); 
    $type = 'Term';
    foreach ( $params as $i => $param ) {
     // print_r($param);
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
      if ($param_name == 'permission_name')  {
        $permission = $value;
      }
 
      if ($param_name == 'label') $label =  $value;
      
      if ($param_name == 'type') $type =  $value;
      
    } 
		//Création de l'url complete de la page courrante.
    global $wgServer;
		global $wgUser;
    $title = $parser->getTitle();
    $page_url = $title->getLocalURL();
    //Gestion des permission, seule l'opération voir l'historique est accéssible à tous le monde
    if ( $permission !== ''){
      $user_can = $wgUser->isAllowed( $permission);
      if (!$user_can) return "";
    }
    //Création du lien permettant l'action
    if ($action == 'move') {
      $mp = SFUtils::getSpecialPage( 'MovePage' );
      $mp_url = $mp->getTitle()->getLocalURL();
      $url = $wgServer.''.$mp_url.'/'.$title->getPartialURL();
    }
    else {
      $url = $wgServer.''.$page_url.'?action='.$action;
    }
    
    //Création du label de l'action
    $actionLabel = $label. ' '. wfMsg( 'nsf_elementype_name_'.$type ); 
     $output ='['.$url.' '.$actionLabel.']<br/>';
    return $output;
  }


  static function renderUcword (&$parser, $word) {
    $output = ucwords($word);
    return $output;
  }
  
 	static function categoryhier ( &$parser) {
    $output = 'blblblblblbbl';
    return $output;
  }
}
