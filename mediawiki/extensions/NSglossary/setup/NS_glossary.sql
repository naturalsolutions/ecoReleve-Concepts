
-- Links users to watchlist groups.
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/nss_subscription_log_per_user (
  `lpg_subscription_title` varbinary(255) NOT NULL,
  `lpg_user_id` int(10) unsigned NOT NULL,
  `lpg_action` varbinary(255) NOT NULL,
  `lpg_date_utc` datetime NOT NULL,
  `PK` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`)
) /*$wgDBTableOptions*/;

-- --------------------------------------------------------


-- Links users to watchlist groups.

CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/nss_subscription_per_user (
  `upg_subscription_title` varbinary(255) NOT NULL,
  `upg_user_id` int(10) unsigned NOT NULL,
  `PK` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`)
) /*$wgDBTableOptions*/;

