<?php

/**
 * Prints details about a given migration.
 * 
 * @author Russell Stringer <rstring3@jhu.edu>
 */
class MpmDetailsController extends MpmController {
    
    const MISSING_REQUIRED_ARG = 'missing_arg';
    const ARG_MISSING_MIGRATION = 'missing_migration';
    const MIGRATION_NOT_EXIST = 'not_exist';
    const MIGRATION_FILE_MISSING = 'file_missing';
    
    private $_errorMessages = array(
        self::MISSING_REQUIRED_ARG => 'ERROR: The %s flag is required',
        self::ARG_MISSING_MIGRATION => 'ERROR: You must provide a migration number after the -m flag',
        self::MIGRATION_NOT_EXIST => 'ERROR: Migration ID %s does not exist. Use the list option to view available migration IDs',
        self::MIGRATION_FILE_MISSING => 'ERROR: Migration file %s is missing from directory %s'
    );
    
    private $_error;
    
    private $_errorMessageReplacements = array();
    
    public function doAction()
	{
        $this->_parseArgs();
        
        if ($this->_isErrorInArgs()){
            $this->_printError();
            return;
        }
        
        $migrationId = $this->arguments['-m'];
        
        $migration = MpmMigrationHelper::getMigrationObject($migrationId);
        
        if (!$migration){
            $this->_error = self::MIGRATION_NOT_EXIST;
            $this->_errorMessageReplacements[] = $migrationId;
            $this->_printError();
            return;
        }
        
        $filename = MpmStringHelper::getFilenameFromTimestamp($migration->timestamp);
        $classname = 'Migration_' . str_replace('.php', '', $filename);
        
        if (!file_exists(MPM_DB_PATH . $filename)){
            $this->_error = self::MIGRATION_FILE_MISSING;
            $this->_errorMessageReplacements[] = $filename;
            $this->_errorMessageReplacements[] = MPM_DB_PATH;
        }
        
    }
    
    public function displayHelp() {
        $obj = MpmCommandLineWriter::getInstance();
		$obj->addText('./migrate.php details [-m migration #]');
		$obj->addText(' ');
		$obj->addText('This command is used to display details of a given migration number.');
		$obj->addText(' ');
		$obj->addText('You must specify a migration # (as provided by the list command)');
		$obj->addText(' ');
		$obj->addText('The details command will display the timestamp, up/down status of the migration, and any associated @migration docblock comments');
		$obj->addText(' ');
		$obj->addText('Example:');
		$obj->addText('./migrate.php details -m 14', 4);
		$obj->write();
    }
    
    private function _isErrorInArgs()
    {
        if (!array_key_exists('-m', $this->arguments)){
            $this->_error = self::MISSING_REQUIRED_ARG;
            $this->_errorMessageReplacements[] = '-m';
    		return true;
        }
        
        if (!is_numeric($this->arguments['-m'])){
            $this->_error = self::ARG_MISSING_MIGRATION;
    		return true;
        }
    }
    
    private function _printError()
    {
        $writer = MpmCommandLineWriter::getInstance();
        
        $msg = vsprintf($this->_errorMessages[$this->_error], $this->_errorMessageReplacements);
        
        $writer->addText($msg);
        $writer->addText(' ');
        $this->displayHelp();
    }
    
}