-- Adminer 4.0.3 MySQL dump

INSERT INTO `role` (`id`, `name`) VALUES
  (1,	'guest'),
  (2,	'signed'),
  (3,	'user'),
  (4,	'dealer'),
  (5,	'admin'),
  (6,	'superadmin');

INSERT INTO `user` (`id`, `page_config_settings_id`, `page_design_settings_id`, `required_role_id`, `facebook_id`, `twitter_id`, `mail`, `hash`, `recovery_token`, `recovery_expiration`) VALUES
  (1,	NULL,	NULL,	NULL,	NULL,	NULL,	'admin',	'$2y$10$JlRd6Mnnk/5szE4bHOnYHeuNvFTUoX2S5Zbpwvbst07lDHqTZGrr2',	NULL,	NULL),
  (2,	NULL,	NULL,	NULL,	NULL,	NULL,	'superadmin',	'$2y$10$gvuV.MFtGvzdSg5hLCbyGuCS5dqreeHNEe2YA8sbQw.kpjruZdnLO',	NULL,	NULL),
  (3,	NULL,	NULL,	NULL,	NULL,	NULL,	'user1@domain.com',	'$2y$10$iSNWd8hvJdxRaNjow8pbv.JpGXjZpQAxZW1R0hQ7u4H/R1zz1qRqi',	NULL,	NULL),
  (4,	NULL,	NULL,	NULL,	NULL,	NULL,	'user2@domain.com',	'$2y$10$agJa36dwTZpy8feK7WagTevRZ3pzvlJPLGM9..dI658pxdEwUsAQO',	NULL,	NULL),
  (5,	NULL,	NULL,	NULL,	NULL,	NULL,	'user3@domain.com',	'$2y$10$e75kDa3nLIwQFnDr/XwQxuZpb/5bPAoZkbM8/jsMZfRlbpOIG/l5m',	NULL,	NULL),
  (6,	NULL,	NULL,	NULL,	NULL,	NULL,	'user4@domain.com',	'$2y$10$4wfejRe0XmMEVNwwc.pYGurbZy8vPzo2K0WNdN7rNR6awflBFGQ7e',	NULL,	NULL),
  (7,	NULL,	NULL,	NULL,	NULL,	NULL,	'user5@domain.com',	'$2y$10$bD1XcBxX1xr3/GFDWBVtsuW74D6bqcm2indag7uahhb7YXYVZCslG',	NULL,	NULL),
  (8,	NULL,	NULL,	NULL,	NULL,	NULL,	'user6@domain.com',	'$2y$10$g9wUaVSGpyrx1uYisfpYXuuxeIt8eru5ixnudjrWjhvfF1FSrZ0Sy',	NULL,	NULL),
  (9,	NULL,	NULL,	NULL,	NULL,	NULL,	'dealer1@domain.com',	'$2y$10$g4Ipfb4cLQEMZ/a2.We7vep/qJY5qG8VpRITpS8mh41mVLIenZDtO',	NULL,	NULL),
  (10,	NULL,	NULL,	NULL,	NULL,	NULL,	'dealer2@domain.com',	'$2y$10$pd2Z9KFbJ8c/eloU01CT/.9mVO4jwSAnGvA3oSB0nqyl97kKYXAxi',	NULL,	NULL),
  (11,	NULL,	NULL,	NULL,	NULL,	NULL,	'dealer3@domain.com',	'$2y$10$moPKFwT.gji1KIDCbgg6VOlkaf72Cvc60OcidzSaPxCtd4w63q8iK',	NULL,	NULL),
  (12,	NULL,	NULL,	NULL,	NULL,	NULL,	'admin1@domain.com',	'$2y$10$JX2Ar/kfqwaqjFeEOUXgU.9kIStaZyWxBdScvZJ2M09luJqG.h9PW',	NULL,	NULL),
  (13,	NULL,	NULL,	NULL,	NULL,	NULL,	'admin2@domain.com',	'$2y$10$aCLJEn.RNsKJQy2P4iIrK.i8DS4PjTM8oYo0SsFFwv/VinXDI7iiq',	NULL,	NULL);

INSERT INTO `user_role` (`user_id`, `role_id`) VALUES
  (1,	5),
  (2,	6),
  (3,	3),
  (4,	3),
  (5,	3),
  (6,	3),
  (7,	3),
  (8,	3),
  (9,	4),
  (10,	4),
  (11,	4),
  (12,	5),
  (13,	5);