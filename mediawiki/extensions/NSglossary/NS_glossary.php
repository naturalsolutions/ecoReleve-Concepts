<?php

$moduleGlossaryName= 'NSglossary';
$nsfgIP = dirname( __FILE__ );

//Variable de configuration 
$qlg = 'fr';
$wgidPath = array();

//Path where js files will be stored. Need a directory with www-data write permission
$nsgJsPath =$nsfgIP.'/js/data/';
//Path where cookies files will be stored
$nsgCookiePath = '/tmp/';



if ( ! defined( 'MEDIAWIKI' ) )
        die();
 
$wgExtensionCredits['parserhook'][] = array(
        'name' => 'NS glossary extension',
        'author' => 'Natural Solution',
        'url' => 'http://www.mediawiki.org/wiki/Extension:CategoryBreadcrumb',
        'version' => '2.1',
        'description' => 'Glossary functions : Unified breadcrumb; action; property, .....',
);
/**
 * Register ResourceLoader modules
 */
$commonModuleInfo = array(
	'localBasePath' => dirname( __FILE__ ) . '/js/jqtree',
	'remoteExtPath' => $moduleGlossaryName.'/js/jqtree',
);
$commonModuleInfoCSS = array(
	'localBasePath' => dirname( __FILE__ ) . '/css',
	'remoteExtPath' => $moduleGlossaryName.'/css',
);
/*
		'jquery-1.8.2.min.js',*/
/*
 *   // JavaScript and CSS styles. To combine multiple files, just list them as an array.
        'scripts' => array( 'js/ext.myExtension.core.js', 'js/ext.myExtension.foobar.js' ),
        'styles' => 'css/ext.myExtension.css',
 
        // When your module is loaded, these messages will be available through mw.msg()
        'messages' => array( 'myextension-hello-world', 'myextension-goodbye-world' ),
 
        // If your scripts need code from other modules, list their identifiers as dependencies
        // and ResourceLoader will make sure they're loaded before you.
        // You don't need to manually list 'mediawiki' or 'jquery', which are always loaded.
        'dependencies' => array( 'jquery.ui.datepicker' ),
 
        // You need to declare the base path of the file paths in 'scripts' and 'styles'
        'localBasePath' => dirname( __FILE__ ),
        // ... and the base from the browser as well. For extensions this is made easy,
        // you can use the 'remoteExtPath' property to declare it relative to where the wiki
        // has $wgExtensionAssetsPath configured:
        'remoteExtPath' => 'MyExtension'
        * */
$wgResourceModules['jqtree'] = array(
  'scripts' => array( 'js/jqtree/jquery.mockjax.js', 'js/jqtree/tree.jquery.js' ,'js/jqtree/jquery.cookie.js'),
  
	// If your scripts need code from other modules, list their identifiers as dependencies
		// and ResourceLoader will make sure they're loaded before you.
		// You don't need to manually list 'mediawiki' or 'jquery', which are always loaded.
 //'dependencies' => array( 'jquery-1.8.2.min.js', ),
 'localBasePath' => dirname( __FILE__ ),
) + $commonModuleInfo;

$wgResourceModules['jqtree.css'] = array(
	'styles' => 'jqtree.css',
) + $commonModuleInfoCSS;

 
  

// Allow translation of the parser function name
$wgExtensionMessagesFiles['BCExtensionMagic'] = $nsfgIP . '/NS_glossary.i18n.php';

//Appel des classes
$wgAutoloadClasses['NSSetupSchema'] = $nsfgIP . '/setup/NS_SetupSchema.php';

$wgAutoloadClasses['NSSMWData'] = $nsfgIP . '/includes/NS_SMWfunctions.php';
$wgAutoloadClasses['HierarchyTree'] = $nsfgIP . '/includes/NS_HierarchyTree.php';
$wgAutoloadClasses['NSCallApi'] =  $nsfgIP  . '/includes/API/NS_CallApi.php';
$wgAutoloadClasses['NSUtils'] = $nsfgIP . '/includes/NS_Utils.php';

$wgAutoloadClasses['NSSubscription']= $nsfgIP . '/hooks/NS_Subscription.php';
$wgAutoloadClasses['BCHookFunctions'] = $nsfgIP. '/hooks/NS_BC_HookFunctions.php';
$wgAutoloadClasses['NSParserFunctions'] =  $nsfgIP  . '/hooks/NS_ParserFunctions.php';
$wgAutoloadClasses['NSCustomActionsTabs'] = $nsfgIP . '/hooks/NS_CustomActionsTab.php';
$wgAutoloadClasses['CategoryTermCategoryPage'] = $nsfgIP . '/hooks/NS_CustomPageRender.php';

$wgAutoloadClasses['NSCustomTermFormStart'] = $nsfgIP . '/specials/NS_CustomTermFormStart.php';
$wgAutoloadClasses['NSHierachySpecialPage'] = $nsfgIP . '/specials/NS_HierarchyAction.php';
$wgAutoloadClasses['NSSubscriptionLog'] = $nsfgIP . '/specials/NS_SubscriptionLog.php';
$wgAutoloadClasses['NSSpecialExport']= $nsfgIP . '/specials/NS_SpecialExport.php';
 
 


 
// Allow translation of the parser function name
$wgExtensionMessagesFiles['CustomTermForm'] = $nsfgIP . '/languages/NSF_Messages.php';

 $wgHooks['LoadExtensionSchemaUpdates'][] = 'NSSetupSchema::onSchemaUpdate';
 
// hook into SkinTemplate.php
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'BCHookFunctions::bcCustomBreadCrumbsDisplay';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'BCHookFunctions::bcCategoryTreeSkinTemplateOutputPageBeforeExec';
//Called after parse, before the HTML is added to the output
//$wgHooks['OutputPageParserOutput'][] = 'BCHookFunctions::bcbctreeParserOutput';
// Specify the function that will initialize the parser function.
$wgHooks['ParserFirstCallInit'][] = 'NSParserFunctions::registerFunctions';
//Configuration des tabulations de l'espace de nom ecoOntoGLossary
$wgHooks['SkinTemplateNavigation'][] = 'NSCustomActionsTabs::displayCustomAction';
//$wgHooks['ParserAfterTidy'][] = 'NSCustomPageRender::renderCategoryPage';
# $wgHooks['SkinTemplateTabs'][] = 'efCategoryTreeInstallTabs';
$wgHooks['ArticleFromTitle'][] = 'efCategoryTermFromTitle';
 
 
$wgHooks['ParserBeforeStrip'][] = 'BCHookFunctions::onParserBeforeStrip';


$wgHooks['GetPreferences'][] = 'NSSubscription::onGetPreferences';
$wgHooks['UserSaveOptions'][] = 'NSSubscription::onUserSaveOptions';
$wgHooks['PersonalUrls'][] = 'NSSubscription::onPersonalUrls';



//Special Pages
$wgSpecialPages['CustomTermFormStart'] = 'NSCustomTermFormStart';
$wgSpecialPages['ManageHierarchy'] = 'NSHierachySpecialPage';
$wgSpecialPages['ExportConceptData']  = 'NSSpecialExport';
$wgSpecialPages['SubscriptionShowLog']  = 'NSSubscriptionLog';
//$wgHooks['GetPreferences'][] = 'SWLHooks::onGetPreferences';

/**
 * ArticleFromTitle hook, override category page handling
 *
 * @param $title Title
 * @param $article Article
 * @return bool
 */
function efCategoryTermFromTitle( $title, &$article ) {

	if ( $title->getNamespace() == NS_CATEGORY ) {
		$article = new CategoryTermCategoryPage( $title );
	}
	return true;
}

