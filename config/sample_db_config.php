<?php
/**
 * Copy this file to be "db_config.php" and fill in the fields below
 */

$db_config = (object) array();
$db_config->host = 'localhost';
$db_config->port = '3306';
$db_config->user = '';
$db_config->pass = '';
$db_config->name = 'jhu_epp';
$db_config->db_path = '/var/web/apps.ep.jhu.edu/db-migrations/';
$db_config->method = 1;
$db_config->migrations_table = 'mpm_migrations';
