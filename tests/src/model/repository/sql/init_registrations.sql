-- Adminer 4.0.3 MySQL dump

INSERT INTO `role` (`id`, `name`) VALUES
  (1,	'guest'),
  (2,	'signed'),
  (3,	'user'),
  (4,	'dealer'),
  (5,	'admin'),
  (6,	'superadmin');

INSERT INTO `registration` (`id`, `role_id`, `mail`, `hash`, `facebook_id`, `facebook_access_token`, `twitter_id`, `twitter_access_token`, `verification_token`, `verification_expiration`) VALUES
  (1,	3,	'for.registration@mail.com',	'$2y$10$dL/uQ8kNcaBhbdkk3FQlbOy0IhxkrDYZQTUIMxodp8AHj3BqGILtu',	NULL,	NULL,	NULL,	NULL,	'kbmdtj5pzx0jux1qlyvaaujtfnumyus1',	'2015-03-12 10:47:54');

