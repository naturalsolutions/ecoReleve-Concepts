<?php 
require_once('./NS_HierarchyTree.php');
require_once ( getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . "/maintenance/commandLine.inc"
	: '../../../maintenance/commandLine.inc' );

$hierarchyTree = new HierarchyTree();
$ret = $hierarchyTree->createJsonHierarchyTreeFile();
