<?php


class NSCustomActionsTabs {

	static function displayCustomAction( $obj, &$links ) {
		global $wgLang;
		if ( method_exists ( $obj, 'getTitle' ) ) {
			$title = $obj->getTitle();
		} else {
			$title = $obj->mTitle;
		}
		/*unset($links['views']['form_edit']);
		unset($links['views']['history']);
    unset($links['actions']['move']);
    unset($links['actions']['delete']);*/
    //print_r($links);
		return true;
	}

}
