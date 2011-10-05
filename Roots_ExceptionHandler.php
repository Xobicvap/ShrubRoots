<?php
/**
 *  ShrubRoots Dependency Injection Container
 *  Copyright (C) 2011 Rusty Hamilton (rusty@shrub3.net)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/** \class Roots_ExceptionHandler
 *  Displays ShrubRoots-based exceptions on an HTML error page.
 * 
 * The Roots_ExceptionHandler class displays error messages via an
 * HTML error page (the 'error.html' page located in the same directory
 * as ShrubRoots). This error page displays the message and the version
 * of ShrubRoots. 
 * */
class Roots_ExceptionHandler
{

    /** Array of configuration variables */
    protected $config;
    
    /** Optional; title of application using ShrubRoots. */
    protected $app_name;
    
    /** Optional; version of application using ShrubRoots. */
    protected $app_version;
    
    /** Error file template. Set via config array. */
    protected $error_tpl;

    /** \brief Roots_ExceptionHandler constructor. 
     * 
     * @param Shrub_IConfig $config Configuration object.
     * @param $name Name of application.
     * @param $version Version of application.
     * 
     * Gets array of configuration variables and sets application name
     * and version, if being used. */
    public function __construct(Shrub_IConfig $config,
                                $name = null, 
                                $version = null) {
        $this->config = $config->retrieveConfigArray();
        $this->app_name = $name;
        $this->app_version = $version;
        $this->error_path = $this->setErrorPath();
    }

    /** \brief Sets error file path.
     * 
     * Sets the error file path to whatever is in the config array
     * (if present) or to the directory this file is located in. 
     */
    protected function setErrorPath() {
        $pathtest = array_key_exists('error_path', $this->config);
        
        $path = ($pathtest === true) ?
            $this->config['error_path'] :
            __DIR__;
            
        $path = (stripos(DIRECTORY_SEPARATOR, $path, (strlen($path)-1))) ?
            $path :
            $path.DIRECTORY_SEPARATOR;
        
        $filename = 'error.html';
        if (is_readable($path) && file_exists($path.$filename)) {
            $this->error_tpl = file_get_contents($path.$filename);
        }
        else {
            $this->error_tpl = 
                "<html>\n".
                "     <body>\n".
                "        <p>#NAME</p>\n".
                "        <p>#VERSION</p>\n".
                "        <p>#MESSAGE</p>\n".
                "    </body>\n".
                "</html>";
        }

    }
    
    /** \brief Public method for registering the error and exception handlers
     *         contained in this class.
     * 
     * Registers the processError and processException methods of this class
     * as the error handler and exception handler, respectively.
     */
    public function registerHandlers() {
        set_exception_handler(array('Roots_ExceptionHandler',
                              'processException'));
        set_error_handler(array('Roots_ExceptionHandler',
                          'processError'));
        register_shutdown_function(array('Roots_ExceptionHandler', 
                                   'processFatalError'));
    }

    /** \brief Clears output buffer and starts new output buffering session.
     * 
     * Begins a new output buffering session. If there are no output buffering
     * handlers defined (checked mainly to determine if output buffering has
     * already been enabled), the output buffer is trashed before starting
     * the new output buffering session. */
    protected function resetOutputBuffering() {
        $olh = ob_list_handlers();
        if (empty($olh)) {
            ob_end_clean();
        }
        ob_start();
    }    
    
    /** \brief Handles errors by outputting information about them to
     *         an HTML page.
     *         
     * @param $err_no Error code
     * @param $err_mess Error message.
     * @param $err_file File in which error occurred.
     * @param $err_line Line on which error occurred.
     * 
     * (Please note: I rather religiously try to avoid giving methods more
     * than 3 parameters, but I think in this case it is warranted)
     * In development mode, outputs verbose information about the error
     * in question. In production mode, outputs only that there was an error.
     * (In the future it may log errors to a file.) */
    public function processError($err_no, $err_mess, 
                                 $err_file, $err_line) {
        $message = "ERROR ".$err_no.": ".$err_mess."<br/>".
                   "discovered in file $err_file on line $err_line.";
        $this->outputMessage($message);
        return true;
    }
    
    /** \brief Handler for fatal errors.
     *
     * Should be registered as a shutdown function; obtains last error
     * (if any) and uses processError to output the message. */
    public function processFatalError() {
        $errors = error_get_last();
        if (empty($errors)) {
            return 1;   //i.e. do nothing
        }
        $this->processError($errors['type'], $errors['message'],
                            $errors['file'], $errors['line']);
    }
    
    /** \brief Handler for exceptions.
     *
     * @param Exception $e Exception being processed.
     * 
     * Obtains the message of the exception being triggered and
     * passes it to outputMessage(). */
    public function processException(Exception $e) {
        $message = $e->getMessage();
        $this->outputMessage($message);
    }
    
    /** \brief Obtains and then outputs an error or exception.
     *
     * @param type $message_text Error message text.
     * 
     * Uses getOutput to insert the error message into the error
     * template file, then outputs it. */
    public function outputMessage($message_text) {
        $output = $this->getOutput($message_text);
        $this->resetOutputBuffering();
        echo $output;
        ob_end_flush();
    }
    
    /** \brief Inserts an error message into an error template file.
     *
     * @param string $message_text Error message text.
     * 
     * Uses str_replace to change template tags (#NAME, #VERSION,
     * and #MESSAGE, which should be self-explanatory) into their
     * actual values. */
    protected function getOutput($message_text) {
        $tags = array('#NAME', '#VERSION', '#MESSAGE');
        
        if ($this->getErrorMode() === 'production') {
            $message_text = "Oops! Something went wrong! ".
                            "Please try again later!";
        }
        
        $values = array($this->app_name, 
                    $this->app_version,
                    $message_text);

        return str_replace($tags, $values, $this->error_tpl);
    }
    
    /** \brief Obtains the error mode set in the config file.
     *         Is 'production' by default.
     *
     * If there is no error mode set in the config file, the error
     * mode is set to 'production'. 'Development' mode indicates that
     * error information will be output; 'production' mode simply informs
     * the user there has been an error. */
    protected function getErrorMode() {
        $mode = (array_key_exists('mode', $this->config)) ?
            $this->config['mode'] :
            'production';
        return $mode;
    }
    

    
}