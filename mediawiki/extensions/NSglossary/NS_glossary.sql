

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données: `wiki_test_tdwg`
--

-- --------------------------------------------------------

--
-- Structure de la table `nss_subscription_log_per_user`
--

DROP TABLE IF EXISTS `nss_subscription_log_per_user`;
CREATE TABLE IF NOT EXISTS `nss_subscription_log_per_user` (
  `lpg_subscription_title` varbinary(255) NOT NULL,
  `lpg_user_id` int(10) unsigned NOT NULL,
  `lpg_action` varbinary(255) NOT NULL,
  `lpg_date_utc` datetime NOT NULL,
  `PK` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

-- --------------------------------------------------------

--
-- Structure de la table `nss_subscription_per_user`
--

DROP TABLE IF EXISTS `nss_subscription_per_user`;
CREATE TABLE IF NOT EXISTS `nss_subscription_per_user` (
  `upg_subscription_title` varbinary(255) NOT NULL,
  `upg_user_id` int(10) unsigned NOT NULL,
  `PK` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
