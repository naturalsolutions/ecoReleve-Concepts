<?php

/**
 * Semantic watchlist page listing changes to watched properties.
 * 
 * @since 0.1
 * 
 * @file SemanticWatchlist.php
 * @ingroup SemanticWatchlist
 * 
 * @licence GNU GPL v3 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NSSubscriptionLog extends SpecialPage {
	
	/**
	 * MediaWiki timestamp of when the watchlist was last viewed by the current user.
	 * 
	 * @since 0.1
	 * 
	 * @var integer
	 */
	protected $lastViewed;
	
	/**
	 * Constructor.
	 * 
	 * @since 0.1
	 */

	public function __construct() {
		parent::__construct( 'SubscriptionShowLog' );
	}
	/**
	 * @see SpecialPage::getDescription
	 * 
	 * @since 0.1
	public function getDescription() {
		return wfMsg( 'special-' . strtolower( $this->getName() ) );
	}
	 */
	
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 * 
	 * @since 0.1
	public function setHeaders() {
		global $wgOut;
		$wgOut->setArticleRelated( false );
		$wgOut->setRobotPolicy( 'noindex,nofollow' );
		$wgOut->setPageTitle( $this->getDescription() );
	}	
	 */
	
	/**
	 * Main method.
	 * 
	 * @since 0.1
	 * 
	 * @param string $arg
	 */
	public function execute( $subPage ) {
		global $wgOut, $wgUser;
		
		$wgOut->setPageTitle(wfMsg( 'special-subscription-log' ) );
    
		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $wgUser ) ) {
			$this->displayRestrictionError();
			return;
		}
    
		$log = $this->getUserLog($wgUser->getId());
    
    if (count($log) >0 ) {
      $wgOut->addHTML('<table class="sortable wikitable smwtable jquery-tablesorter">');
      $wgOut->addHTML('<thead><tr><th class="Subscription headerSort" title="Sort ascending">Subscription</th><th class="Status headerSort" title="Sort ascending">Status</th><th class="Date-(UTC) headerSort" title="Sort ascending">Date-(UTC)</th></tr></thead>');
      $wgOut->addHTML('<tbody>');
      foreach ($log as $l) {
        $wgOut->addHTML('<tr>');
        $wgOut->addHTML('<td>'.$l->lpg_subscription_title.'</td>');
        $wgOut->addHTML('<td>'.$l->lpg_action.'</td>');
        $wgOut->addHTML('<td>'.$l->lpg_date_utc.'</td>');
       // [lpg_action] => update [lpg_date_utc] => 2013-06-04 12:29:42 [lpg_subscription_title] => EReleve_Thesaurus ) 
        $wgOut->addHTML('</tr>');
      }
      $wgOut->addHTML('</tbody>');
      $wgOut->addHTML('</table>');
    }
    else {
      $wgOut->addHTML('No log to display');  
    }
		
	}
	
	/**
	 * Register the user viewed the watchlist,
	 * so we know that following chnages should
	 * result into notification emails is desired.
	 * 
	 * @since 0.1
	 * 
	 * @param User $user
	 */
	protected function getUserLog( $userId ) {
    $log = array();
    try {
      $dbr = wfGetDB( DB_SLAVE );
      $res = $dbr->select(
        'nss_subscription_log_per_user',  
        array( 'lpg_action', 'lpg_date_utc', 'lpg_subscription_title' ),
        'lpg_user_id = '.$userId,
        __METHOD__,
        array( 'ORDER BY' => 'lpg_date_utc DESC')
      );        
      foreach ( $res as $row ) {
        $log[] = $row;
      }
    }
    catch ( Exception $e ){
        echo "<b>error</b>: ".$e->getMessage();
    }
    return $log;
	}

	
}
