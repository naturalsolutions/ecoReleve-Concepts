<?php

/**
 * Static class for hooks handled by the Semantic Watchlist extension.
 *
 * @since 0.1
 *
 * @file SemanticWatchlist.hooks.php
 * @ingroup SemanticWatchlist
 *
 * @licence GNU GPL v3+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class NSSetupSchema {



	/**
	 * Schema update to set up the needed database tables.
	 *
	 * @since 0.1
	 *
	 * @param DatabaseUpdater $updater
	 *
	 * @return true
	 */
	public static function onSchemaUpdate( /* DatabaseUpdater */ $updater = null ) {
		global $wgDBtype;
    if ( $wgDBtype == 'mysql' ) {
      $updater->addExtensionUpdate( array(
        'addTable',
        'nss_subscription_log_per_user',
        dirname( __FILE__ ) . '/NS_glossary.sql',
        true
      ) );
      $updater->addExtensionUpdate( array(
        'addTable',
        'nss_subscription_per_user',
        dirname( __FILE__ ) . '/NS_glossary.sql',
        true
      ) );
		}

		return true;
	}

}
