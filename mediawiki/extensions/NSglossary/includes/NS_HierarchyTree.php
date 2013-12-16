<?php

class HierarchyTree {
  var $childtree = array();
 
  function __construct () {
		
	}
 /****************************************************************************
   * 
   * name: createBreadCumb
   * Création du breadcrumb complet d'une page
   * @param 
   *  $wgArticle = Article.php Object titre de l'article courrant racine @TODO => controler ce paramètre
   * @return array qui contient : 
   *      - Breadcrumb HTML string qui représente le breadcrumb correctement formaté
   *      - node2open : array contenant la liste des noeuds à ouvrir pour aller jusqu'a fild
   ****************************************************************************/
  function createBreadCumb($article) {
    global $qlg;
    $return = array();
    //page est une catégorie
    if ($article->getTitle()->mNamespace==14) {
      $category_hier=  $this->getParent( $article->getTitle());
      $skosType = 'category';
    }
    else { 
      //Si le titre est un top concept
      $skosType = $this->getSkosType($article->getTitle());
      if ($skosType == 'TopConcept') {
        $category_hier= $this->getParent( $article->getTitle() );
      }
      else {
        //Si non récupération du top concept
        $parentTitle = $this->getTopConcept( $article->getTitle()) ;
        $parent = Title::newFromText( $parentTitle);
        //Si non récupération du top concept
        if ($parentTitle =='') return true ;
        $category_hier= $this->getParent( $parent);
      }
    }
    //Mise en place du breadcrumb des catégories
    $bread = '';
    $node2open = array();
    foreach ( $category_hier as $item ) {
        $titl = $item->cl_to;
        $title = Title::newFromText($titl, NS_CATEGORY);
        $node2open[] = $title->getArticleID();
        $link  = $this->formatPageLink ($item->cl_to, $qlg, true) ;
        $bread .= $link . '>'; 
    }
    //Si le terme est un top concept et n'est pas une catégorie
    //=>breadcrump pour la hiérarchie des concepts (broader)
    if ($skosType != 'TopConcept' &&  $skosType != 'category') {
      $broaderHier= $this->getBroaderTerm ($article->getTitle()) ;
      foreach ( $broaderHier as $item ) {
        if ($item!='' ) {
         $title = Title::newFromText($item);
         $node2open[] = $title->getArticleID();
         $link  = $this->formatPageLink ($item, $qlg, false) ;
         $bread .= $link . '>';
        }
      }
    }
    $return['node2open'] = $node2open;
    //Suppression du dernier > 
    $bread = substr($bread ,0,-1); 
    
    $return['bread'] = '<div class="breadcrumb">'.$bread .'</div>';
    return $return;
  }
   /****************************************************************************
   * 
   * name: createJsonHierarchyTreeFile
   * Création de l'arbe sous la forme de 2 fichiers json 1 par langue
   *  => pour le moment est dans un fichier statique pour des raisons de performances
   *  => @TODO a voir si un seul fichier avec une variable lg est préférable
   * @param 
   * @return true si tout c'est bien passé
   ****************************************************************************/
  function createJsonHierarchyTreeFile() {
    set_time_limit(0);
    global $wgScriptPath,$nsfgIP, $nsgJsPath;
    //Récupération et formatage de l'arbre complet
    $tree= array();
    
    $tree=$this->getChildrenCategoryLinks(Title::newFromText( 'Category:Term category'), 0 );
    //ajout d'une racine
    $fulltree = array();
    
    $dir =$nsgJsPath;
    //Création de l'arbre en français 
    $ftree = $this->formatHierarchyToJsonTree($tree, 'fr');
    $fulltree[0]= array (
        'id' => -999,
        'label' => 'ecoReleve-glossary'
    );
    $fulltree[0]['children'] =$ftree;
    $jsonftree = json_encode ($fulltree);
    $file = 'data.fr.js';
    $r = $this->createJsonVarFile ($dir, $file, $jsonftree) ;
    if (!$r) return false;
    //Création de l'arbre en anglais 
    $ftree = $this->formatHierarchyToJsonTree($tree, 'en');
    $fulltree[0] = array (
        'id' => -999,
        'label' => 'ecoReleve-glossary'
    );
    $fulltree[0]['children'] =$ftree;
    $jsonftree = json_encode ($fulltree);
    $file = 'data.en.js';
    $r = $this->createJsonVarFile ($dir, $file, $jsonftree) ;
    if (!$r) return false;
    
    return true;
  }
     /****************************************************************************
   * 
   * name: createJsonHierarchyTreeFile
   * Création de l'arbe sous la forme de 2 fichiers json 1 par langue
   *  => pour le moment est dans un fichier statique pour des raisons de performances
   *  => @TODO a voir si un seul fichier avec une variable lg est préférable
   * @param 
   * @return true si tout c'est bien passé
   ****************************************************************************/
  function createJsonD3jsFile() {
   global $wgScriptPath,$nsfgIP,$nsgJsPath;
    //Récupération et formatage de l'arbre complet
    $tree= array();
    
    $tree=$this->getChildrenCategoryLinks(Title::newFromText( 'Category:Term category'), 0 );
    //ajout d'une racine
    $fulltree = array();
    //print_r($tree);
    
    $dir =$nsfgIP.'/js/data/';
    //Création de l'arbre en français 
    $ftree = $this->formatHierarchyToJsonD3jsTree($tree, 'fr');
    $fulltree= (Object)array (
        'id' => -999,
        'label' => 'ecoReleve-glossary'
    );
    $fulltree->children =$ftree;
    $fulltree->size=1000;
    $jsonftree = json_encode ($fulltree);
    $jsonftree ='var ctree =' .$jsonftree .';';
    $file = 'flare.json';
     try {
      $fp = fopen($dir.$file, 'w');
      fwrite($fp, $jsonftree);
      fclose($fp);
      return true;
    }
    catch (Exception $e) {
      print 'Exception reçue : '.  $e->getMessage().'\n';
      return false;
    }
    return true;
  }
  
  function createJsonVarFile ($dir, $file, $content) {
    try {
      $jsonftree ='var data =' .$content .';';
      $fp = fopen($dir.$file, 'w');
      fwrite($fp, $jsonftree);
      fclose($fp);
      return true;
    }
    catch (Exception $e) {
      print 'Exception reçue : '.  $e->getMessage().'\n';
      return false;
    }
  }
  
  /****************************************************************************
   * 
   * name: getChildrenCategoryLinks
   * @type = Recursive function
   * @param 
   *  $title = Title.php Object titre de la catégorie racine
   *  $level = Int Niveau de la profondeur de la hiérarchie @TODO => controler ce paramètre
   * @return array sous forme d'arbre contenant la hiérarchie d'une catégorie. 
   *  Chaque élément est représenté sous la forme d'objet. 
   ****************************************************************************/
  function getChildrenCategoryLinks( $title, $level) {
    $dbr = wfGetDB( DB_SLAVE );
    //Récupération des pages/Catégorie liées à la catégorie racine
    $tables = array( 'page', 'categorylinks' );
    $fields = array( 'page_id', 'page_namespace', 'page_title',
      'page_is_redirect', 'page_len', 'page_latest', 'cl_to',
      'cl_from' );
    $where = array();
    $joins = array();
    $options = array( 'ORDER BY' => 'cl_type, cl_sortkey' );
    $joins['categorylinks'] = array( 'JOIN', 'cl_from = page_id' );
    $where['cl_to'] = $title->getDBkey();
    $options['USE INDEX']['categorylinks'] = 'cl_sortkey';
    $res = $dbr->select( $tables, $fields, $where, __METHOD__, $options, $joins );
    $i =0;
    foreach ( $res as $row ) {
      //Si c'est une catégorie
      if ($row->page_namespace == 14) {
          $page = Title::newFromText( $row->page_title, NS_CATEGORY );
          $row->type='cat';
      }
      else $page =  Title::newFromText( $row->page_title );
      
      //Si le fils est une catégorie alors appel récursif à la fonction
      if ($row->page_namespace == 14)  {
        $child = $this->getChildrenCategoryLinks( $page,  $level +1);
      }
      elseif($this->getSkosType($page) == 'TopConcept') { 
      //Si le fils est un top concept alors on récupère ces fils au travers de la fonction getChildOfBroaderTerm
        $child = $this->getChildOfBroaderTerm($page);  
        $row->type='topConcept';
      }  
      $tree[$title->getArticleID()][$i]['data'] = $row;
      //Si la catégorie à au moins un fils, ajout à l'arbre de la hiérachie de ces fils
      if (isset($child))  {
        if ($child !='empty') $tree[$title->getArticleID()][$i]['children'] = $child;
      }
      $i++;
    }
    //Si la catégorie n'a aucun fils alors on renvoie la valeur 'empty'
    if (!isset($tree)) return 'empty';
    return $tree;
  }


  /****************************************************************************
   * 
   * name: formatHierarchyToJsonD
   *  fonction qui renovie un arbre formaté de façon à être sérialisé en json
   * @type = Recursive function
   * @param
   *  $tree = array hiérarhie d'une catégorie
   * @return array sous forme d'arbre contenant la hiérarchie d'une catégorie. 
   *  Chaque élément est représenté sous la forme d'un lien pointant vers la page correspondante. 
   ****************************************************************************/
  function formatHierarchyToJsonD3jsTree ($tree, $lg = 'en') {
    foreach ( $tree as $key => $subtree ) {
      foreach ( $subtree as $key => $item ) {
        $fdata = array();
        $data = $item['data'];
        $fdata['id'] = $data->page_id;
        $fdata['name'] = $data->page_title;
        if($data->page_namespace == 14) {
          $fdata['type'] ='category';
        }
        else {
          $fdata['type'] =$data->type;
        }
        if (isset($item['children'])) {
          $fdata['size'] = count($item['children'])*1000;
          $fdata['children'] = $this->formatHierarchyToJsonD3jsTree ($item['children'],  $lg);
        }
        else {
          $fdata['size'] = 1*1000;
        }
        $ftree[] = $fdata;
      }
    }
    return $ftree;
  }

  /****************************************************************************
   * 
   * name: formatHierarchyToJsonTree
   *  fonction qui renovie un arbre formaté de façon à être sérialisé en json
   * @type = Recursive function
   * @param
   *  $tree = array hiérarhie d'une catégorie
   * @return array sous forme d'arbre contenant la hiérarchie d'une catégorie. 
   *  Chaque élément est représenté sous la forme d'un lien pointant vers la page correspondante. 
   ****************************************************************************/
  function formatHierarchyToJsonTree ($tree, $lg = 'en') {
    foreach ( $tree as $key => $subtree ) {
      foreach ( $subtree as $key => $item ) {
        $fdata = array();
        $data = $item['data'];
        $fdata['id'] = $data->page_id;
        $fdata['page_title'] = $data->page_title;
        if($data->page_namespace == 14) $isCat=true;
        else  $isCat=false;
        if (! isset($data->type)) print_r($data);
        $fdata['label'] = $this->formatPageLink ($data->page_title, $lg, $isCat, 'tree-item', true, $data->type);
        if (isset($item['children'])) {
          $fdata['children'] = $this->formatHierarchyToJsonTree ($item['children'],  $lg);
        }
        $ftree[] = $fdata;
      }
    }
    return $ftree;
  }


  /****************************************************************************
   * 
   * name: getParent
   *  Returns a string with an HTML representation of the parents of the given category.
   * @type = Recursive function
   * @param
   *  $title Title titre de la page fils dont on veux récupérer les parents
   * @return  a string with an HTML representation of the parents of the given category.
   ****************************************************************************/
  function getParent( $title) {
    $dbr = wfGetDB( DB_SLAVE );
    $categorylinks = $dbr->tableName( 'categorylinks' );
    $sql = "SELECT * 
        FROM $categorylinks
        WHERE cl_from = " . $title->getArticleID() . "
        AND NOT cl_to IN ('Term_category_type' ,'Term_category', 'Term', 'Type_compartment', 'Compartment', 'TopConcept')
        ORDER BY cl_to";

    $res = $dbr->query( $sql, __METHOD__ );
    $s = array();
    foreach ( $res as $row ) {
      if ( is_null($row->cl_to)) return $s;
      else {
          $page = Title::newFromText( 'Category:'.$row->cl_to );
          $s = $this->getParent( $page);
      }
      $s[] = $row;
    }
    return $s;
  }

  /****************************************************************************
   * 
   * name: getSkosType
   *  Fonction qui revoie la valeur de la propriété sémantique type d'une page
   * Peut être de trois valeur = Schema/Collection/TopConcept/Concept
   * @param
   *  $title String d'une page
   * @return boolean sous la forme d'une chaine de caractère
   ****************************************************************************/
  function getSkosType($title) {
      $params = array ("[[$title]]", "?Skos:type=", "mainlabel=-");
      $result = SMWQueryProcessor::getResultFromFunctionParams( $params, SMW_OUTPUT_WIKI );
      return $result;
  }
  
  /****************************************************************************
   * 
   * name: getTopConcept
   *  Fonction renvoyant le nom du top concept associé à la page désiré
   * @param
   *  $title String d'une page
   * @return nom du top concept associé à la page désiré
   ****************************************************************************/
  function getTopConcept($title) {
      $params = array ("[[$title]]", "?Skos:hasTopConcept=", "mainlabel=-", "link=none");
      $result = SMWQueryProcessor::getResultFromFunctionParams( $params, SMW_OUTPUT_WIKI );
      return $result;
  }


  /****************************************************************************
   * 
   * name: inconnu
   *  Fonction qui renvoie le terme parent d'une page identifiée par $title
   * @param
   *  $title String d'une page
   * @return nom de la page parent (relation Skos:broader)
   ****************************************************************************/
  function getBroaderTerm ($title) {
    $params = array ("[[$title]]", "?Skos:broader=", "mainlabel=-", "link=none");
    $result = SMWQueryProcessor::getResultFromFunctionParams( $params, SMW_OUTPUT_WIKI );
    $s = array();
   
    if ( !is_null($result) &&  $result !='') {
        $page = Title::newFromText( $result );
        $s = $this->getBroaderTerm($page);
    }
    $s[] = $result;
    return $s;
    // return $result;
  }
  
  
  /****************************************************************************
   * name: getChildOfBroaderTerm
   *  Fonction récursive qui parcours les fils d'un terme (relation : Skos:broader)
   * @type = Recursive function
   * @param
   *  $title String d'une page
   * @return
   *  array  contenant la hiérarchie d'une page basée sur la relation Skos:broader 
   ****************************************************************************/
  function getChildOfBroaderTerm ($title) {
    $params = array ("[[Skos:broader::$title]]","link=none");
    $result = SMWQueryProcessor::getResultFromFunctionParams( $params, SMW_OUTPUT_WIKI );
    $results = explode(',', $result);
    $i = 0;
    foreach ($results as $row) {
      if ( !is_null($row) &&  $row !='') {
        $page =  Title::newFromText($row);
        $artId = $page->getArticleID();
        $data = (object) array (
          'page_id'=>$artId, 'page_namespace'=>$title->getNamespace(), 'page_title'=>$row,'type'=>'term',
        );
        $tree[$artId ][$i]['data'] = $data;
        //$s[$title->getArticleID()][] = getChildren( $page,  $level +1);
        $child = $this->getChildOfBroaderTerm( $page);    
        if ($child !='empty') $tree[$artId][$i]['children'] = $child;
        $i++;
      }
    }
    if (!isset($tree)) return 'empty';
    return $tree;
  }
  


  /****************************************************************************
   * name: formatPageLink
   * Fonction qui permet le formatage des liens de type Category/Top concept/Terme
   * @param
   *  $title = nom de la page à charger
   *  $lg = langue de l'utilisateur
   *  $isCat = Spécifie si la page est ou non une catégorie
   *  $cssClass = class css à utilisé pour le span qui entoure le lien
   *  $withImgType = Spécifie si on ajoute ou non une image devant le lien
   *  $type = Category/topConcept/term @TODO => redondance avec le param 
   * @return  $link  lien formaté de la page pointant vers le $title spécifié
   * 
   ****************************************************************************/
  function formatPageLink ($title, $lg='en', $isCat=false, $cssClass= 'breadcrumb-item', $withImgType = false, $type = 'term') {
    global $wgScriptPath,$moduleGlossaryName ;
    if ($isCat) $page = Title::newFromText( $title, NS_CATEGORY );
    else $page = Title::newFromText( $title);

    //Récupération des données sémantique de la page
    $semdata = smwfGetStore()->getSemanticData( SMWDIWikiPage::newFromTitle( $page ));
    
    //Récupération des valeurs de la propriétés prefered label selon la langue
    $labelProp = 'Skos:prefLabel';
      
    $property = new SMWDIProperty($labelProp);
    $values =  NSSMWData::getStringPropertyValues($semdata, $property);
    $valueen;
    foreach ($values as $val) {
    	$data = explode(':', $val);
    	if ( $data[0] == $lg) $value= $data[1] ;
    	if ( $data[0] == 'en') $valueen= $data[1] ;
    }
    //Si aucune valeur n'a été récupéré => récupération du label en
    if (is_null($value) || $value=='') $value = $valueen;
    
    //Récupération du type pour rajouter le picto
    $img = '';
    if ($withImgType) {
      $dir = dirname( __FILE__ ) . '/';  
      //Si c'est une catégorie
      if ($isCat) {
        $img ="<img src ='".$wgScriptPath."/extensions/$moduleGlossaryName/img/ico-category.png' alt='picto-category'/>";
      }
      elseif ($type == 'topConcept') {
        $img ="<img src ='".$wgScriptPath."/extensions/$moduleGlossaryName/img/ico-top_concept.png' alt='picto-topconcept'/>";
      }
      else {
        $img ="<img src ='".$wgScriptPath."/extensions/$moduleGlossaryName/img/ico-term.png' alt='picto-term'/>";
      }

    }
    $link ="<span class='".$cssClass."'>".$img ."<a href='".$page->getFullURL()."'>".$value."</a></span>";

    return  $link ; 
  }

  
}
