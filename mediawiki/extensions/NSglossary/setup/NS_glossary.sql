
-- Links users to watchlist groups.
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/nss_subscription_per_user (
  upg_subscription_title  VARCHAR(255) NOT NULL,
  upg_user_id             INT(10) unsigned    NOT NULL, 
  PRIMARY KEY  (upg_subscription_title,upg_user_id)
) /*$wgDBTableOptions*/;


-- Links users to watchlist groups.
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/nss_subscription_log_per_user (
  lpg_subscription_title  VARCHAR(255) NOT NULL,
  lpg_user_id             INT(10) unsigned    NOT NULL, 
  lpg_action             VARCHAR(255) NOT NULL, 
  lpg_date_utc            DATETIME NOT NULL
) /*$wgDBTableOptions*/;
