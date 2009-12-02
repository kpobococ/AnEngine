<?php

class AeRequest extends AeObject
{
    /**
     *
     * @var string
     */
    protected $_site = null;
    protected $_directory = null;
    protected $_virtual = null;
    protected $_file = null;
    protected $_get = null;

    protected $_name = null;
    protected $_rewrite = false;
    protected $_extensions = array();

    // TODO: remove after debug finished
    public $timer;

    public function __construct($rewrite = false, $name = null, $extensions = array())
    {
        $this->setRewrite($rewrite);
        $this->setName($name);
        $this->setExtensions($extensions);

        $this->_parseRequest();
    }

    public function isClean()
    {
        $clean = '';

        if ($this->_directory !== false) {
            $clean = '/' . $this->_directory;
        }
    }

    public function toString()
    {
        if (empty($this->_site)) {
            return '';
        }

        $return = $this->_site;

        if (!empty($this->_directory)) {
            $return .= $this->_directory . '/';
        }

        if (!empty($this->_virtual)) {
            $return .= $this->_virtual . '/';
        }

        if (!empty($this->_file)) {
            $return .= $this->_file;
        }

        if (!empty($this->_get)) {
            $return .= '?' . $this->_get;
        }

        return $return;
    }

    protected function _parseRequest()
    {
        // TODO: remove after debug finished
        $this->timer = new AeTimer();

        if ($this->_site !== null) {
            return $this;
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $this->_site = false;
            throw new AeRequestException('Request parser does not support command line mode', 405);
        }

        // *** Protocol, host and port
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';

        if ($protocol == 'https') {
            $port = $_SERVER['SERVER_PORT'] == '443' ? '' : ':' . $_SERVER['SERVER_PORT'];
        } else {
            $port = $_SERVER['SERVER_PORT'] == '80'  ? '' : ':' . $_SERVER['SERVER_PORT'];
        }

        $this->_site = $protocol . '://' . $_SERVER['HTTP_HOST'] . $port . '/';

        // *** Parse directory using script name
        $script_name = substr($_SERVER['SCRIPT_NAME'], 1);
        $bits        = explode('/', $script_name);

        // TODO: fix issue with index.php, when rewrite is set to false but is actually used
        if ($this->_rewrite === true)
        {
            // *** Remove script file name from URL
            if (strpos($script_name, '/') !== false) {
                array_pop($bits);
            } else {
                $bits = array();
            }
        }

        foreach ($bits as $i => $bit)
        {
            if (empty($bit)) {
                unset($bits[$i]);
            }
        }

        $script_name = implode('/', $bits);

        if (!empty($script_name)) {
            $this->_directory = $script_name;
        }

        $virtual = '';
        $get     = '';

        @list ($virtual, $get) = explode('?', $_SERVER['REQUEST_URI'], 2);

        // *** Parse virtual directory
        if (!isset($this->_name) || !isset($_GET[$this->_name]))
        {
            $bits = explode('/', $virtual);

            foreach ($bits as $i => $bit)
            {
                if (empty($bit)) {
                    unset($bits[$i]);
                }
            }

            $virtual = implode('/', $bits);
            $virtual = preg_replace('#^/?' . $script_name . '/?#', '', $virtual);
        } else {
            $virtual = $_GET[$this->_name];
        }

        // *** Parse file
        $last = strrpos($virtual, '/');
        $file = substr($virtual, $last > 0 ? $last + 1 : 0);

        if (strpos($file, '.') > 0)
        {
            $vir = substr($virtual, 0, $last);
            $ext = strtolower(substr(strrchr($file, '.'), 1));

            if (empty($this->_extensions) || in_array($ext, $this->_extensions)) {
                $this->_virtual = $vir;
                $this->_file    = $file;
            } else {
                $this->_virtual = $virtual;
            }
        } else {
            $this->_virtual = $virtual;
        }

        // *** Parse get
        // TODO: add GET parsing (including cleanup)
        // TODO: after GET is parsed, add new parameters to GET and REQUEST

        return $this;
    }
}

/**
 * Request exception class
 *
 * Request-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeRequestException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Request');
        parent::__construct($message, $code);
    }
}
?>