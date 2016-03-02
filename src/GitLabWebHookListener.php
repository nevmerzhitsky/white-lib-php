<?php

class GitLabWebHookListenerException extends Exception {
}

/**
 * GitLab Web Hook
 *
 * This script should be placed within the web root of your desired deploy
 * location. The GitLab repository should then be configured to call it for the
 * "Push events" trigger via the Web Hooks settings page.
 *
 * Each time this script is called, it executes a hook shell script and logs all
 * output to the log file.
 *
 * This hook uses php's exec() function, so make sure it can be executed.
 * See http://php.net/manual/function.exec.php for more info
 *
 * @link Inspired by https://gitlab.com/kpobococ/gitlab-webhook/
 */
class GitLabWebHookListener {

    /*
     * Hook script location. The hook script is a simple shell script that executes
     * the actual git push. Make sure the script is either outside the web root or
     * inaccessible from the web
     * This setting is REQUIRED
     */
    private $_hookFiles;

    /*
     * Log file location. Log file has both this script's and shell script's output.
     * Make sure PHP can write to the location of the log file, otherwise no log
     * will be created!
     */
    private $_logFile;

    /*
     * Hook password. If set, this password should be passed as a GET parameter to
     * this script on every call, otherwise the hook won't be executed.
     * This setting is RECOMMENDED
     */
    private $_password;

    /*
     * Ref name. This limits the hook to only execute the shell script if a push
     * event was generated for a certain ref (most commonly - a master branch).
     * Can also be an array of refs:
     * $ref = array('refs/heads/master', 'refs/heads/develop');
     * This setting does not support the actual refspec, so the refs should match
     * exactly.
     * See http://git-scm.com/book/en/Git-Internals-The-Refspec for more info on
     * the subject of Refspec
     * This setting is OPTIONAL
     */
    private $_ref;

    /**
     * @param string|string[] $hookFile
     * @param string $logFile
     * @param string $password
     * @param string|string[] $ref
     */
    public function __construct ($hookFile, $logFile = null, $password = null, $ref = '*') {
        $this->_hookFiles = (array) $hookFile;
        $this->_logFile = $logFile;
        $this->_password = $password;
        $this->_ref = $ref;
    }

    private function _log ($message) {
        if (empty($this->_logFile)) {
            return;
        }

        $message = sprintf('%s (%s): %s', date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'], $message);

        file_put_contents($this->_logFile, $message . PHP_EOL, FILE_APPEND);
    }

    private function _execCommand ($command) {
        $output = [];

        exec($command, $output);

        foreach ($output as $line) {
            $this->_log('SHELL: ' . $line);
        }
    }

    public function handlePhpInput () {
        // GitLab sends the json as raw post data
        $input = file_get_contents('php://input');

        $this->_handle($input);
    }

    private function _handle ($input) {
        if (isset($this->_password)) {
            if (empty($_REQUEST['p'])) {
                $message = 'Missing hook password';
                $this->_log($message);
                throw GitLabWebHookListenerException($message);
            }

            if ($_REQUEST['p'] !== $this->_password) {
                $message = 'Invalid hook password';
                $this->_log($message);
                throw GitLabWebHookListenerException($message);
            }
        }

        $json = json_decode($input);

        if (!is_object($json) || empty($json->ref)) {
            $message = 'Invalid push event data';
            $this->_log($message);
            throw GitLabWebHookListenerException($message);
        }

        if (isset($this->_ref)) {
            $_refs = (array) $this->_ref;

            if ($this->_ref !== '*' && !in_array($json->ref, $_refs)) {
                $message = 'Ignoring ref ' . $json->ref;
                $this->_log($message);
                return;
            }
        }

        foreach ($this->_hookFiles as $hookFile) {
            $this->_log(sprintf('Launching shell hook script %s...', basename($hookFile)));
            $this->_execCommand('/bin/sh ' . $hookFile);
            $this->_log('Shell hook script finished');
        }

        return true;
    }
}
