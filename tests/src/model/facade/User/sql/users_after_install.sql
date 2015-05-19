-- Adminer 4.0.3 MySQL dump

INSERT INTO `facebook` (`id`, `access_token`, `mail`, `name`, `birthday`, `gender`, `hometown`, `link`, `location`, `locale`, `username`) VALUES
  ('fb123456789',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

INSERT INTO `page_config_settings` (`id`, `language`) VALUES
  (1,	NULL);

INSERT INTO `page_design_settings` (`id`, `color`, `layout_boxed`, `container_bg_solid`, `header_fixed`, `footer_fixed`, `sidebar_closed`, `sidebar_fixed`, `sidebar_reversed`, `sidebar_menu_hover`, `sidebar_menu_light`) VALUES
  (1,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

INSERT INTO `role` (`id`, `name`) VALUES
  (1,	'guest'),
  (2,	'signed'),
  (3,	'user'),
  (4,	'dealer'),
  (5,	'admin'),
  (6,	'superadmin');

INSERT INTO `twitter` (`id`, `access_token`, `name`, `screen_name`, `url`, `location`, `description`, `statuses_count`, `lang`) VALUES
  ('tw123456789',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	NULL);

INSERT INTO `user` (`id`, `page_config_settings_id`, `page_design_settings_id`, `required_role_id`, `facebook_id`, `twitter_id`, `mail`, `hash`, `recovery_token`, `recovery_expiration`) VALUES
  (1,	NULL,	NULL,	NULL,	NULL,	NULL,	'admin',	'$2y$10$gYjoL8Tmo65r2gGPZxCbt.65QL3.YOhhr6Q2MHv8AqgCn7J62iRAy',	NULL,	NULL),
  (2,	NULL,	NULL,	NULL,	NULL,	NULL,	'superadmin',	'$2y$10$fYzlmzUkdY5LMum19yytkub0vm6BUCds5zcjvUFUrvZGHdBpZbGpC',	NULL,	NULL),
  (3,	1,	1,	NULL,	'fb123456789',	'tw123456789',	'user.mail@domain.com',	'$2y$10$x5YtSMkPLjbA8zq/oG858emzLg7PCfFJtpxeI5Lqc9a1MG3Xg8O.2',	NULL,	NULL);

INSERT INTO `user_role` (`user_id`, `role_id`) VALUES
  (1,	5),
  (2,	6),
  (3,	3);