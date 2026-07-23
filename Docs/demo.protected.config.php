<?php
###############################
### GDO6 Configuration File ###
###############################
if (defined('GDO_CONFIGURED')) return;
define('GDO_CONFIGURED', env('GDO_CONFIGURED', '1'));

############
### Site ###
############
define('GDO_SITENAME', env('GDO_SITENAME', 'LinkUUp'));
define('GDO_SEO_URLS', env('GDO_SEO_URLS', true));
define('GDO_SITECREATED', env('GDO_SITECREATED', '2021-04-18 17:12:24.305'));
define('GDO_LANGUAGE', env('GDO_LANGUAGE', 'en'));
define('GDO_TIMEZONE', env('GDO_TIMEZONE', 'UTC'));
define('GDO_THEMES', env('GDO_THEMES', 'lup,material,default'));
define('GDO_MODULE', env('GDO_MODULE', 'LinkUUp'));
define('GDO_METHOD', env('GDO_METHOD', 'Welcome'));
define('GDO_IPC', env('GDO_IPC', 'db'));
define('GDO_IPC_DEBUG', env('GDO_IPC_DEBUG', false));

############
### HTTP ###
############
define('GDO_DOMAIN', env('GDO_DOMAIN', 'lup.giz.org'));
define('GDO_SERVER', env('GDO_SERVER', 'apache2.4'));
define('GDO_PROTOCOL', env('GDO_PROTOCOL', 'http'));
define('GDO_WEB_ROOT', env('GDO_WEB_ROOT', '/backend/'));

#############
### Files ###
#############
define('GDO_CHMOD', env('GDO_CHMOD', 0770));

###############
### Logging ###
###############
define('GDO_CONSOLE_VERBOSE', env('GDO_CONSOLE_VERBOSE', true));
define('GDO_ERROR_LEVEL', env('GDO_ERROR_LEVEL', 0x37ff));
define('GDO_ERROR_STACKTRACE', env('GDO_ERROR_STACKTRACE', true));
define('GDO_ERROR_DIE', env('GDO_ERROR_DIE', true));
define('GDO_ERROR_MAIL', env('GDO_ERROR_MAIL', true));

################
### Database ###
################
define('GDO_SALT', env('GDO_SALT', 'KPqjf0orkxQMNMkP'));
define('GDO_DB_ENABLED', env('GDO_DB_ENABLED', 1));
define('GDO_DB_HOST', env('GDO_DB_HOST', 'localhost'));
define('GDO_DB_USER', env('GDO_DB_USER', 'lup'));
define('GDO_DB_PASS', env('GDO_DB_PASS', 'lup'));
define('GDO_DB_NAME', env('GDO_DB_NAME', 'lup'));
define('GDO_DB_DEBUG', env('GDO_DB_DEBUG', false));

#############
### Cache ###
#############
define('GDO_MEMCACHE', env('GDO_MEMCACHE', false));
define('GDO_MEMCACHE_HOST', env('GDO_MEMCACHE_HOST', '127.0.0.1'));
define('GDO_MEMCACHE_PORT', env('GDO_MEMCACHE_PORT', 61221));
define('GDO_MEMCACHE_TTL', env('GDO_MEMCACHE_TTL', 1800));

###############
### Cookies ###
###############
define('GDO_SESS_NAME', env('GDO_SESS_NAME', 'LUP'));
define('GDO_SESS_DOMAIN', env('GDO_SESS_DOMAIN', 'lup.giz.org'));
define('GDO_SESS_TIME', env('GDO_SESS_TIME', 172800));
define('GDO_SESS_JS', env('GDO_SESS_JS', true));
define('GDO_SESS_HTTPS', env('GDO_SESS_HTTPS', false));

############
### Mail ###
############
define('GDO_ENABLE_EMAIL', env('GDO_ENABLE_EMAIL', false));
define('GDO_BOT_NAME', env('GDO_BOT_NAME', 'GDO6 support'));
define('GDO_BOT_EMAIL', env('GDO_BOT_EMAIL', 'support@localhost'));
define('GDO_ADMIN_EMAIL', env('GDO_ADMIN_EMAIL', 'administrator@localhost'));
define('GDO_ERROR_EMAIL', env('GDO_ERROR_EMAIL', 'administrator@localhost'));
define('GDO_DEBUG_EMAIL', env('GDO_DEBUG_EMAIL', true));
