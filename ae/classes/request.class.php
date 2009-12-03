<?php
/**
 * @todo write documentation
 */
class AeRequest extends AeObject
{
    protected $_site = null;
    protected $_directory = null;
    protected $_virtual = null;
    protected $_file = null;
    protected $_get = null;

    protected $_name = null;
    protected $_rewrite = false;
    protected $_extensions = array();

    public function __construct($rewrite = false, $name = null, $extensions = array())
    {
        $this->setRewrite($rewrite);
        $this->setName($name);
        $this->setExtensions($extensions);

        $this->_parseRequest();
    }

    public function isClean()
    {
        $clean = '/' . preg_replace('#^' . $this->_site . '#', '', $this->toString());

        return $clean === $_SERVER['REQUEST_URI'];
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
        // *** Already parsed?
        if ($this->_site !== null) {
            return $this;
        }

        // *** Check for command line mode
        if (!isset($_SERVER['HTTP_HOST'])) {
            $this->_site = false;
            throw new AeRequestException('Request parser does not support command line mode', 405);
        }

        // *** Protocol, host and port
        /*
         * Apparently, this detection will fail on lighttpd, and there is no way
         * to tell whether a https connection was used for a request on that
         * server, so I'm all out of ideas.
         */
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

        foreach ($bits as $i => $bit)
        {
            if (empty($bit)) {
                unset($bits[$i]);
            }
        }

        // *** Remove script file name from URL
        $real_file = array_pop($bits);
        $real_dir  = implode('/', $bits);

        $virt_dir = '';
        $get      = '';

        // *** Store GET for later parsing
        @list ($virt_dir, $get) = explode('?', $_SERVER['REQUEST_URI'], 2);

        // *** Parse virtual directory
        if (isset($this->_name) && isset($_GET[$this->_name])) {
            $virt_dir = $_GET[$this->_name];
        }

        $bits = explode('/', $virt_dir);

        foreach ($bits as $i => $bit)
        {
            if (empty($bit)) {
                unset($bits[$i]);
            }
        }

        $virt_dir = implode('/', $bits);

        if (!isset($this->_name) || !isset($_GET[$this->_name])) {
            // *** Remove real directory from the virtual part
            $virt_dir = preg_replace('#^/?' . $real_dir . '/?#', '', $virt_dir, 1);
        }

        /*
         * Since the class settings may be incorrect (although this case is
         * not fully supported), only allow removing the script name from
         * the URL, not require it to be there. In this case, if it is in
         * the URL, it will be removed from the virtual part, but if it is
         * not, it will not break the parsing
         */
        if ($this->_rewrite !== true) {
            $count    = 0;
            $virt_dir = preg_replace('#^/?(?:' . $real_file . '/?){1}#', '', $virt_dir, 1, $count);
        }

        if (!empty($virt_dir) || !isset($count) || $count != 1)
        {
            if ($this->_rewrite !== true && isset($count) && $count == 1) {
                $real_dir = empty($real_dir) ? $real_file : $real_dir . '/' . $real_file;
            }

            $last      = strrpos($virt_dir, '/');
            $virt_file = substr($virt_dir, $last > 0 ? $last + 1 : 0);
            $virt_dir  = substr($virt_dir, 0, $last);
        } else {
            $virt_file = $real_file;
        }

        // *** Parse file
        if (!empty($virt_file) && strpos($virt_file, '.') > 0) {
            $ext = strtolower(substr(strrchr($virt_file, '.'), 1));
        }

        if (!isset($ext) || (!empty($this->_extensions) && !in_array($ext, $this->_extensions))) {
            $virt_dir  = empty($virt_dir) ? $virt_file : $virt_dir . '/' . $virt_file;
            $virt_file = null;
        }

        $this->_directory = empty($real_dir) ? null : $real_dir;
        $this->_virtual   = empty($virt_dir) ? null : $virt_dir;
        $this->_file      = $virt_file;

        // *** Parse get
        if ($get !== null) {
            $this->_get = $get;

            // *** This overwrites the whole GET array, leaving only new params
            parse_str($get, $_GET);

            // TODO: also assign new vars to REQUEST properly
        }

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