<?php
/**
 * Parser functions for Custom NS Forms.
 * {{#example:Skos:preferedLabel|gogog}}
 * {{#termforminput:form=|size=|default value=|button text=|query string=
 * |autocomplete on category=|autocomplete on namespace=
 * |remote autocompletion|...additional query string values...}}
 */

class BCHookFunctions {
   
 public static function onParserBeforeStrip( &$parser, &$text, &$strip_state ){
    global $wgOut;
    $wgOut->addModuleStyles( 'jqtree.css' );
    $wgOut->addModules( 'jqtree' );
    return true;
  }
  

  public static function bcCategoryTreeSkinTemplateOutputPageBeforeExec ($skin, $tpl) {
      global $wgOut, $wgScriptPath,$wgTitle, $wgidPath,$qlg, $moduleGlossaryName,$nsgHtmlPath, $moduleGlossaryName,$nsfgIP, $wgLang;
      
      if ($wgLang->getCode()) {
      	$qlg = $wgLang->getCode();
     	}
      /**********
      **Ouverture de l'arbre => récupération de la suite des noeuds à ouvrir
      * */
    /*  if (($wgTitle->getNamespace()!=0 ) &&  ($wgTitle->getNamespace()!=14 )) {
        return true;
      }*/
     	$jsBasePath = $wgScriptPath.'/extensions/'.$moduleGlossaryName.'/js';
      if ((! isset($nsgHtmlPath)) && ($nsgHtmlPath == '')) {
      	$jsDataPath = $jsBasePath.'/data/';
      }
      else {
      	$jsDataPath = $nsgHtmlPath;
      }
      
        $html='';
        //@TODO
        $html = '<script src="'.$jsBasePath.'/jqtree/jquery.mockjax.js?303"></script>
                  <script src="'.$jsBasePath.'/jqtree/tree.jquery.js?303"></script>
                  <script src="'.$jsBasePath.'/jqtree/jquery.cookie.js?303"></script>';
        $html .= '<script src="'.$jsDataPath.'data.'.$qlg.'.js"></script>';
        
        //Script js qui permet de : créer l'arbre à partir des données contenus dans le fichier data.js
        //Ouvir l'arbre au bon endroit au travers de la variable $wgidPath  qui contient la hiéréchie des noeuds
        //Changer le style de la page courante
        $selectNode = 'var node;';
        if ($wgidPath) {
          foreach ($wgidPath as $artId) {
              $selectNode .= 'node = $tree.tree(\'getNodeById\', '.$artId.');$tree.tree(\'openNode\', node, true); ';
          }
        }
        $html .= '<script type="text/javascript">
            $(function() {
                 var $tree = $(\'#treeport\');
                // set autoEscape to false
                $(\'#treeport\').tree({
                    data: data,
                    autoEscape: false,
                    autoOpen:0,
                });
              '.$selectNode.'
               var currentNode = $tree.tree(\'getNodeById\', '.$wgTitle->getArticleID().');
               if(currentNode){
                 var currentlabel = currentNode.name;
                 var newlabel = \'<span class="currentPage">\'+currentlabel+\'</span>\';
                 $tree.tree( \'updateNode\', currentNode,{label: newlabel,});
               }
            });   
        </script>';
        $html .= '<div id="treeport" ></div>';
        if ( $html ) {
          $tpl->data['sidebar']['categorytree-portlet'] = $html;
        }

    return true;
  }

  //Fonction qui renvoie le breadcrumb total d'un term ou d'une catégorie
  public static function bcCustomBreadCrumbsDisplay(&$q, &$p) {
    global $wgOut, $wgArticle, $qlg, $wgidPath;
    $hierarchyTree = new HierarchyTree();
    if ($wgArticle == null) return true;    
    if (($wgArticle->getTitle()->mNamespace != 0) && ($wgArticle->getTitle()->mNamespace != 14)) return true;
    //Création du breadcrumb
    $bread = $hierarchyTree->createBreadCumb($wgArticle);
    $wgidPath = $bread['node2open'];
    $combine = $bread['bread'].$wgOut->mBodytext;
    $p->setRef( 'bodytext', $combine );
    return true;
  }

  
}
