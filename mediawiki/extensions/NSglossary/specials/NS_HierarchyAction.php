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
class NSHierachySpecialPage extends SpecialPage {


  /**
   * Constructor
   */
  function __construct() {
      parent::__construct( 'ManageHierarchy' );
  }

  function execute( $query ) {
    global $wgOut, $wgRequest;
    if ($wgRequest->wasPosted()) {
      $form_name = $wgRequest->getVal( 'form' );
      if ($form_name == 'RefreshD3js') {
        $this->RefreshD3js();
      }
      else {
        $this->refreshHierarchyData();
      }
    }
    else if ($query) {
    
    }
    else {
      $this->displayForm();
    }   
  }


  function displayForm() {
    global $wgOut;
    $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_title' ) ).'</h2>';
    $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_docu') ).'</p>';
    //Création du formulaire
    $text = '<form action="" method="post" id ="RefreshHierachy">';
    $text .=$description;
    $text .= Html::hidden( 'form','RefreshHierachy');
    $text .= "\n\t" . Html::input( null, wfMsg( 'sf_formManageHierachy_refreshdata' ), 'submit' ) . "\n";
    $text .= "\t</form>\n";
        $description = '<h2>D3js</h2>';
    $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_docu') ).'</p>';
    //Création du formulaire
    $text .= '<form action="" method="post" id ="RefreshD3js">';
    $text .=$description;
    $text .= Html::hidden( 'form','RefreshD3js');
    $text .= "\n\t" . Html::input( null, wfMsg( 'sf_formManageHierachy_refreshdata' ), 'submit' ) . "\n";
    $text .= "\t</form>\n";
    $wgOut->addHTML( $text ); 
  }
  
  function refreshHierarchyData() {
    global $wgOut;
   /**********
    **Création de l'arbe => pour le moment est dans un fichier statique pour des raisons de performances
    * */
    $hierarchyTree = new HierarchyTree();
    $ret = $hierarchyTree->createJsonHierarchyTreeFile();
    if ($ret) {
      $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_title' ) ).'</h2>';
      $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_ok') ).'</p>';
    }
    else {
      $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_title' ) ).'</h2>';
      $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_error') ).'</p>';
    }
    $wgOut->addHTML( $description ); 
  }
  
   function RefreshD3js() {
    global $wgOut;
   /**********
    **Création de l'arbe => pour le moment est dans un fichier statique pour des raisons de performances
    * */
    $hierarchyTree = new HierarchyTree();
    $ret = $hierarchyTree->createJsonD3jsFile();
    if ($ret) {
      $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_title' ) ).'</h2>';
      $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_ok') ).'</p>';
    }
    else {
      $description = '<h2>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_title' ) ).'</h2>';
      $description .= '<p>'.htmlspecialchars( wfMsg( 'nsf_formManageHierachy_refreshdata_error') ).'</p>';
    }
    $wgOut->addHTML( $description ); 
  }
}
