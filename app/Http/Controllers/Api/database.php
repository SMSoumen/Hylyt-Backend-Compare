<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = 'knva';
$db['default']['password'] = 'pfD52dVA7TvNNJHd';
$db['default']['database'] = 'knv_operations';
$db['default']['dbdriver'] = 'mysql';
$db['default']['dbprefix'] = '';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

$active_group = 'default';
$active_record = TRUE;

$db['inventory']['hostname'] = 'localhost';
$db['inventory']['username'] = 'knva';
$db['inventory']['password'] = 'pfD52dVA7TvNNJHd';
$db['inventory']['database'] = 'knv_inventory';
$db['inventory']['dbdriver'] = 'mysql';
$db['inventory']['dbprefix'] = '';
$db['inventory']['pconnect'] = TRUE;
$db['inventory']['db_debug'] = TRUE;
$db['inventory']['cache_on'] = FALSE;
$db['inventory']['cachedir'] = '';
$db['inventory']['char_set'] = 'utf8';
$db['inventory']['dbcollat'] = 'utf8_general_ci';
$db['inventory']['swap_pre'] = '';
$db['inventory']['autoinit'] = TRUE;
$db['inventory']['stricton'] = FALSE;

$active_group = 'default';
$active_record = TRUE;

$db['sales']['hostname'] = 'localhost';
$db['sales']['username'] = 'knva';
$db['sales']['password'] = 'pfD52dVA7TvNNJHd';
$db['sales']['database'] = 'knv_sales';
$db['sales']['dbdriver'] = 'mysql';
$db['sales']['dbprefix'] = '';
$db['sales']['pconnect'] = TRUE;
$db['sales']['db_debug'] = TRUE;
$db['sales']['cache_on'] = FALSE;
$db['sales']['cachedir'] = '';
$db['sales']['char_set'] = 'utf8';
$db['sales']['dbcollat'] = 'utf8_general_ci';
$db['sales']['swap_pre'] = '';
$db['sales']['autoinit'] = TRUE;
$db['sales']['stricton'] = FALSE;

$db['logs']['hostname'] = 'localhost';
$db['logs']['username'] = 'knva';
$db['logs']['password'] = 'pfD52dVA7TvNNJHd';
$db['logs']['database'] = 'knv_logs';
$db['logs']['dbdriver'] = 'mysql';
$db['logs']['dbprefix'] = '';
$db['logs']['pconnect'] = TRUE;
$db['logs']['db_debug'] = TRUE;
$db['logs']['cache_on'] = FALSE;
$db['logs']['cachedir'] = '';
$db['logs']['char_set'] = 'utf8';
$db['logs']['dbcollat'] = 'utf8_general_ci';
$db['logs']['swap_pre'] = '';
$db['logs']['autoinit'] = TRUE;
$db['logs']['stricton'] = FALSE;

$db['accounts']['hostname'] = 'localhost';
$db['accounts']['username'] = 'knva';
$db['accounts']['password'] = 'pfD52dVA7TvNNJHd';
$db['accounts']['database'] = 'knv_accounts';
$db['accounts']['dbdriver'] = 'mysql';
$db['accounts']['dbprefix'] = '';
$db['accounts']['pconnect'] = TRUE;
$db['accounts']['db_debug'] = TRUE;
$db['accounts']['cache_on'] = FALSE;
$db['accounts']['cachedir'] = '';
$db['accounts']['char_set'] = 'utf8';
$db['accounts']['dbcollat'] = 'utf8_general_ci';
$db['accounts']['swap_pre'] = '';
$db['accounts']['autoinit'] = TRUE;
$db['accounts']['stricton'] = FALSE;
/* End of file database.php */
/* Location: ./application/config/database.php */