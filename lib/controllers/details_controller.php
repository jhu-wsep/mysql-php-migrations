<?php

/**
 * Prints details about a given migration.
 * 
 * @author Russell Stringer <rstring3@jhu.edu>
 */
class MpmDetailsController extends MpmController
{
    const MISSING_REQUIRED_ARG   = 'missing_arg';
    const ARG_MISSING_MIGRATION  = 'missing_migration';
    const MIGRATION_NOT_EXIST    = 'not_exist';
    const MIGRATION_FILE_MISSING = 'file_missing';

    private $_errorMessages = array(
        self::MISSING_REQUIRED_ARG => 'ERROR: The %s flag is required',
        self::ARG_MISSING_MIGRATION => 'ERROR: You must provide a migration number after the -m flag',
        self::MIGRATION_NOT_EXIST => 'ERROR: Migration ID %s does not exist. Use the list option to view available migration IDs',
        self::MIGRATION_FILE_MISSING => 'ERROR: Migration file %s is missing from directory %s'
    );
    private $_error;
    private $_errorMessageReplacements = array();

    /**
     * Collects and prints details about the specified migration.
     */
    public function doAction()
    {
        $this->_parseArgs();

        if ($this->_isErrorInArgs()) {
            $this->_printError();
            return;
        }

        $migrationId = $this->arguments['-m'];

        $migration = MpmMigrationHelper::getMigrationObject($migrationId);

        if (!$migration) {
            $this->_error                      = self::MIGRATION_NOT_EXIST;
            $this->_errorMessageReplacements[] = $migrationId;
            $this->_printError();
            return;
        }

        $filename = MpmStringHelper::getFilenameFromTimestamp($migration->timestamp);
        $location = MPM_DB_PATH.$filename;
        if (!file_exists($location)) {
            $this->_error                      = self::MIGRATION_FILE_MISSING;
            $this->_errorMessageReplacements[] = $filename;
            $this->_errorMessageReplacements[] = MPM_DB_PATH;
            $this->_printError();
            return;
        }

        $classname = 'Migration_'.str_replace('.php', '', $filename);

        require_once($location);
        $reflect = new ReflectionClass($classname);
        $comment = $this->_getMigrationComment($reflect);

        $writer = MpmCommandLineWriter::getInstance();

        $writer->addText("Migration # $migrationId", 4);
        $writer->addText(' ');
        $writer->addText("Timestamp: {$migration->timestamp}", 4);
        $writer->addText("Migration Location: $location", 4);

        if ($migration->active == 1) {
            $writer->addText("This migration has been applied", 4);
        } else {
            $writer->addText("This migration has not been applied", 4);
        }

        if ($migration->is_current == 1) {
            $writer->addText("This migration is the current one", 4);
        }

        $writer->addText(' ');

        $writer->addText("Migration Details: $comment", 4);
        $writer->write();
    }

    /**
     * Write command help to the output. 
     */
    public function displayHelp()
    {
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

    /**
     * Grabs and parses the migration classes docblock comment using reflection
     * to find an @migration tag.
     * 
     * @return string The text of the @migration tag from the migration's docblock
     */
    private function _getMigrationComment($reflection)
    {
        $lines   = explode(PHP_EOL, $reflection->getDocComment());
        $comment = '';

        for ($i = 1; $i < sizeof($lines); $i++) {
            $line = preg_replace('/^\s+\*\s+/', '', $lines[$i]); // Trim the left side

            if (strpos($line, '@migration') === 0) {
                $comment = trim(substr($line, 10));
            } else if (strpos($line, '@') === 0) {
                continue;
            } else if (strpos($line, '*/')) {
                continue;
            } else {
                $comment .= ' '.trim($line);
            }
        }

        return $comment;
    }

    /**
     * Checks the CLI arguments for specific errors. Sets the appropriate error
     * conditions if an error occured.
     * 
     * @return boolean true on error
     */
    private function _isErrorInArgs()
    {
        if (!array_key_exists('-m', $this->arguments)) {
            $this->_error                      = self::MISSING_REQUIRED_ARG;
            $this->_errorMessageReplacements[] = '-m';
            return true;
        }

        if (!is_numeric($this->arguments['-m'])) {
            $this->_error = self::ARG_MISSING_MIGRATION;
            return true;
        }
    }

    /**
     * Helper for printing error messages
     * 
     * @todo Move this up to the MpmController class so other controllers can
     * take advantage of it.
     */
    private function _printError()
    {
        $writer = MpmCommandLineWriter::getInstance();

        $msg = vsprintf($this->_errorMessages[$this->_error],
            $this->_errorMessageReplacements);

        $writer->addText($msg);
        $writer->addText(' ');
        $this->displayHelp();
    }
}
