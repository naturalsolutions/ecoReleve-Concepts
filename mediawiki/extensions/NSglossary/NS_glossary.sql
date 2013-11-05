
-- Links users to watchlist groups.
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/nss_subscription_per_user (
  upg_subscription_title  VARCHAR(255) NOT NULL, 
  user_id             INT(10)  NOT NULL, -- Foreign key: user.user_id
  PRIMARY KEY  (upg_subscription_title,user_id)
) /*$wgDBTableOptions*/;


/*
CREATE TABLE IF NOT EXISTS nss_subscription (
  group_id                 SMALLINT unsigned   NOT NULL auto_increment PRIMARY KEY,
  group_name               VARCHAR(255)        NOT NULL,
  -- No need to have this stuff relational, so keep it simple.
  -- These fields keep multiple values, | separated.
  group_categories         BLOB                NOT NULL, -- Category names
  group_namespaces         BLOB                NOT NULL, -- Namespace IDs
  group_properties         BLOB                NOT NULL, -- Property names
  group_concepts           BLOB                NOT NULL, -- Concept names
  group_custom_texts       BLOB                NULL -- Custom Texts
) *//*$wgDBTableOptions*/;


