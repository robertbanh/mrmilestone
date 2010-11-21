CREATE TABLE `followers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `total` int(10) unsigned NOT NULL DEFAULT '0',
  `target` int(10) unsigned NOT NULL DEFAULT '100',
  `lastTarget` int(10) unsigned NOT NULL DEFAULT '0',
  `reminder` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `updateDt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastTargetDt` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`total`,`target`,`reminder`,`active`,`updateDt`),
  KEY `lastTarget` (`lastTarget`),
  KEY `lastTargetDt` (`lastTargetDt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1

CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `screenName` varchar(16) NOT NULL,
  `createDt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `screenName` (`screenName`,`createDt`,`active`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1