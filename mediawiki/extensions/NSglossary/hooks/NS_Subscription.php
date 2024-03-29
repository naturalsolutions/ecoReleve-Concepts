<?php

class NSSubscription {

    
    /**
     * Adds the preferences of Semantic Watchlist to the list of available ones.
     *
     * @since 0.1
     *
     * @param User $user
     * @param array $preferences
     *
     * @return true
     */
	public static function onGetPreferences( User $user, array &$preferences ) {
	global $wgServer,$wgScriptPath;

		// Intro text
		$preferences['nsg_subscription_intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
        'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMessage( 'prefs-subscription-intro-text' )->parseAsBlock() ) ),
				'section' => 'nsg',
				'raw' => 1,
				'rawrow' => 1,
			);
    
		//Get all subscription
    $q = '[[Category:Thesaurus subscription]]';
		
		$wsCall = NSSMWData::buildWSQueryCall($wgServer.$wgScriptPath.'/index.php?title=Special%3AAsk&',$q, array(), array(),'json');
    $collectionWS =  file_get_contents($wsCall);
    $collectionData = json_decode($collectionWS);
    if (!$collectionData) return true;
		foreach ( $collectionData->results as /* SWLGroup */ $group ) {
      $n = str_replace(' ', '_', $group->fulltext);
			$preferences['nsg_subscription_' . $n] = array(
				'type' => 'toggle',
				'label' =>  $group->fulltext,
				'section' => 'nsg/subscription',
			);
		}
    
		return true;
	}

	/**
	 * Called just before saving user preferences/options.
	 * Find the watchlist groups the user watches, and update the swl_users_per_group table.
	 *
	 * @since 0.1
	 *
	 * @param User $user
	 * @param array $options
	 *
	 * @return true
	 */
	public static function onUserSaveOptions( User $user, array &$options ) {
    
    //Get actual subscription
    $dbr = wfGetDB( DB_SLAVE );
    $res = $dbr->select(
        'nss_subscription_per_user',  
        array( 'upg_user_id', 'upg_subscription_title' ),
        'upg_user_id = '. $user->getId() ,
        __METHOD__,
        array( )
    );   
    $actualRes= array();     
    foreach ( $res as $row ) {
      $actualRes[$row->upg_subscription_title] = 'false';
    }
    
		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin();
		
		foreach ( $options as $name => $value ) {
			if ( strpos( $name, 'nsg_subscription_' ) === 0 && $value ) {
        $subName = str_replace('nsg_subscription_', '', $name);
        //Si la clé n'existe pas
        if (!array_key_exists($subName, $actualRes)) {
          $actualRes[$subName] = true;
          NSSubscription::addSubToUser($dbw, $subName, $user) ;
        }
        else {
          $actualRes[$subName] = true;
        }
			}
		}
    
    //Suppression des données de log des subscriptions désactivées
    foreach ( $actualRes as $subName => $val ) {
      if ($val === 'false') {
        NSSubscription::delSubToUser($dbw,$subName, $user);
      }
    }
		
		$dbw->commit();
		return true;
	}




	/**
	 * Adds a link to Admin Links page.
	 *
	 * @since 0.1
	 *
	 * @return true
	public static function addToAdminLinks( &$admin_links_tree ) {
	    $displaying_data_section = $admin_links_tree->getSection( wfMsg( 'adminlinks_browsesearch' ) );

	    // Escape if SMW hasn't added links.
	    if ( is_null( $displaying_data_section ) ) return true;
	    $smw_docu_row = $displaying_data_section->getRow( 'smw' );

	    $smw_docu_row->addItem( AlItem::newFromSpecialPage( 'WatchlistConditions' ) );

	    return true;
	}
	 */

	/**
	 * Called after the personal URLs have been set up, before they are shown.
	 * https://secure.wikimedia.org/wikipedia/mediawiki/wiki/Manual:Hooks/PersonalUrls
	 *
	 * @since 0.1
	 *
	 * @param array $personal_urls
	 * @param Title $title
	 *
	 * @return true
   * 
	 */
	public static function onPersonalUrls( array &$personal_urls, Title &$title ) {
	
			global $wgUser;

			// Find the watchlist item and replace it by itself and the semantic watchlist.
			if ( $wgUser->isLoggedIn() ) {
				$keys = array_keys( $personal_urls );
        
				$watchListLocation = array_search( 'watchlist', $keys );
				$watchListItem = $personal_urls[$keys[$watchListLocation]];

				$url = SpecialPage::getTitleFor( 'SubscriptionShowLog' )->getLinkUrl();
				$semanticWatchlist = array(
					'text' => 'sub log',
					'href' => $url,
					'active' => ( $url == $title->getLinkUrl() )
				);

				array_splice( $personal_urls, $watchListLocation, 1, array( $watchListItem, $semanticWatchlist ) );
			}
		

		return true;
	}
	/**
	 * ArticleEditUpdates : Executes when edit updates (mainly link tracking) are made after an article has been changed
	 * This function reinitialize the subscription

	 */
	public static function onArticleEditUpdatesReinitializeSub( &$article, &$editInfo, $changed ) {
		//Test if page is link to the Category Thesaurus_subscription
		$cat = $article->getTitle()->getParentCategories();
		if ( array_key_exists('Category:Thesaurus_subscription', $cat)) {
			$dbw = wfGetDB( DB_MASTER );
			$dbw->begin();
			$dbw->delete(
				'nss_subscription_log_per_user',
				array('lpg_subscription_title' =>  $article->getTitle()->getSubjectPage())
			);
			$dbw->commit();
		}
		return $article;
  }
  
  private static function addSubToUser($dbw, $subName, $user) {
		$dbw->insert(
			'nss_subscription_per_user',
			array(
				'upg_user_id' => $user->getId(),
				'upg_subscription_title' =>  str_replace('nsg_subscription_', '', $subName) 
			)
    );
	}
	
	private static function delSubToUser($dbw, $subName, $user) {
		$dbw->delete(
			'nss_subscription_log_per_user',
			array('lpg_subscription_title' =>  $subName,  'lpg_user_id' => intval($user->getId()) )
		);
		$dbw->delete(
			'nss_subscription_per_user',
			array('upg_subscription_title' =>  $subName,  'upg_user_id' => intval($user->getId()) )
		);
	}
}
