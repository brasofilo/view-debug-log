<?php
/**
 * Do the thing
 *
 * @package View-Debug-Log
 */

# Busted!
!defined( 'ABSPATH' ) AND exit(
        "<pre>Hi there! I'm just part of a plugin,
            <h1>&iquest;what exactly are you looking for?" );


/**
 * Handles log file operations
 */
class B5F_VDL_Logger {
    /**
     * @var string Path to the debug log file
     */
    public $logpath;

    /**
     * @var string Default log file name
     */
    private $logfile = 'debug.log';

    /**
     * Constructor
     */
    public function __construct() {
        $this->logpath = realpath($_SERVER["DOCUMENT_ROOT"] . '/..') . '/' . $this->logfile;
    }

    /**
     * Get formatted file size
     *
     * @param int $size File size in bytes
     * @return string Formatted size with unit
     */
    public function format_size($size) {
        $units = explode(' ', 'B KB MB GB TB PB');
        $mod = 1024;
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }
        $endIndex = strpos($size, ".") + 3;
        return substr($size, 0, $endIndex) . ' ' . $units[$i];
    }

    /**
     * Clear the log file
     *
     * @return bool True if file was deleted, false otherwise
     */
    public function clear_log() {
        if (file_exists($this->logpath)) {
            return unlink($this->logpath);
        }
        return false;
    }

    /**
     * Get log file size
     *
     * @return int File size in bytes
     */
    public function get_log_size() {
        return is_readable($this->logpath) ? filesize($this->logpath) : 0;
    }

    /**
     * Get log file contents
     *
     * @return string|false File contents or false on failure
     */
    public function get_log_contents() {
        if (!is_readable($this->logpath)) {
            return false;
        }

        $handle = fopen($this->logpath, 'r');
        $content = stream_get_contents($handle);
        fclose($handle);
        return $content;
    }

    /**
     * Daily maintenance task
     */
    public function daily_maintenance() {
        $max_size = 10485760 * 5; // 50MB
        if (is_readable($this->logpath)) {
            $size = filesize($this->logpath);
            if ($size > $max_size) {
                $this->clear_log();
            }
        }
    }
}
