<?php
/**
 * This file houses the MpmMigration class.
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 * @license    http://www.opensource.org/licenses/bsd-license.php  The New BSD License
 * @link       http://code.google.com/p/mysql-php-migrations/
 */

/**
 * The MpmMigration is an abstract template class used as the parent to all migration classes.
 *
 * @package    mysql_php_migrations
 * @subpackage Controllers
 */
abstract class MpmMigration
{
    private $_databaseChanged = false;
    
    private $_origDatabase;
    
	/**
	 * Migrates the database up.
	 * 
	 * @param PDO $pdo a PDO object
	 *
	 * @return void
	 */
	abstract public function up(PDO &$pdo);
	
	/** 
	 * Migrates down (reverses changes made by the up method).
	 *
	 * @param PDO $pdo a PDO object
	 *
	 * @return void
	 */
	abstract public function down(PDO &$pdo);
    
    /**
     * Use this method to select a different database from the one specified in the migration.php database config file
     * 
     * @param PDO $pdo
     * @param string $database_name     - The name of the database to switch to
     */
    protected function selectDatabase(PDO $pdo, $database_name)
    {
        if ( !$this->_databaseChanged ) {
            $stmt = $pdo->prepare("SELECT DATABASE()");
            $stmt->execute();
            $row = $stmt->fetch();
            
            $this->_origDatabase = $row[0];
        }
        
        $query = "USE " . $database_name;
        $pdo->exec($query);
        
        $this->_databaseChanged = true;
        
    }
    
    /**
     * Use this method to restore PDO to the original database (so that migration.php won't break)
     * @param PDO $pdo 
     */
    public function restoreDatabase(PDO $pdo)
    {
        if ( $this->_databaseChanged ) {
            $this->selectDatabase($pdo, $this->_origDatabase);
        }
        
    }
    
}
