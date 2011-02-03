<?php
/**
 * Request class file
 *
 * See {@link AeRequest} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Request class
 *
 * This class cleans and parses human-friendly URL input. If you are looking for
 * input filtering, take a look at {@link AeInput} class.
 *
 * Let's assume you have a script available at http://example.com/, that uses
 * MOD_REWRITE:
 * <code> $request = new AeRequest(true);
 *
 * if (!$request->isClean()) {
 *     header('Location: ' . $request);
 * }
 *
 * echo $request->site;
 * echo $request->directory;
 * echo $request->virtual;
 * echo $request->file;</code>
 *
 * Let's see what the above code will produce for several different ULRs ( =>
 * means redirect):
 * <pre> http://example.com/:
 * http://example.com/
 * null
 * null
 * null
 *
 * http://example.com/index.html:
 * http://example.com/
 * null
 * null
 * index.html
 *
 * http://example.com/user/list/:
 * http://example.com/
 * null
 * user/list
 * null
 *
 * http://example.com////user/////list => http://example.com/user/list/
 * http://example.com/user/list/index.html/ => http://example.com/user/list/index.html</pre>
 *
 * You can also provide the array of recognized extensions:
 * <code> $request = new AeRequest(true, null, array('html', 'xml', 'json'));</code>
 *
 * The script above will result in all other extensions to be treated as
 * directories instead of files, for example:
 * <pre> http://example.com/user/list/index.php => http://example.com/user/list/index.php/</pre>
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeRequest extends AeObject
{
    /**
     * Site protocol, domain and port:
     * <pre>http://example.com/</pre>
     * @var string
     */
    protected $_site = null;

    /**
     * Actual script subdirectory:
     * <pre>subdir</pre>
     * @var string or null, if script is inside the domain's document root
     */
    protected $_directory = null;

    /**
     * Virtual path, passed using MOD_REWRITE:
     * <pre>user/list</pre>
     * @var string or null, if no path has been passed
     */
    protected $_virtual = null;

    /**
     * Script file:
     * <pre>index.html</pre>
     * @var string or null, if no file has been specified
     */
    protected $_file = null;

    /**
     * Script get:
     * <pre>foo=foo&bar=bar&baz=baz</pre>
     * @var string or null, if no get has been specified
     */
    protected $_get = null;

    /**
     * Variable that all the virtual data is passed to by MOD_REWRITE
     * @var string or null, if not set
     */
    protected $_name = null;

    /**
     * Is MOD_REWRITE used. If this is false, it is assumed that you are using
     * the http://example.com/index.php/foo/bar/ URL structure
     * @var bool
     */
    protected $_rewrite = false;

    /**
     * An array of allowed file extensions. If this is empty, every trailing URL
     * part containing a dot and some text afterwards is treated as a file name
     * @var array
     */
    protected $_extensions = array();

    /**
     * Constructor
     *
     * See property documentation for more details on accepted parameters.
     *
     * @param bool   $rewrite    is MOD_REWRITE used or not. Default: false
     * @param string $name       virtual data holding variable. Default: null
     * @param array  $extensions an array of allowed file extensions
     */
    public function __construct($rewrite = false, $name = null, $extensions = array())
    {
        $this->setRewrite($rewrite);
        $this->setName($name);
        $this->setExtensions($extensions);

        $this->_parseRequest();
    }

    /**
     * Is request URI clean
     *
     * This method returns true if the request URI is valid, i.e. does not
     * contain double slashes, has a trailing slash for all the directory names
     * and does not have a trailing slash for file names.
     *
     * @return bool
     */
    public function isClean()
    {
        $clean = '/' . preg_replace('#^' . $this->_site . '#', '', $this->toString());

        return $clean === $_SERVER['REQUEST_URI'];
    }

    /**
     * String type cast support method
     *
     * This returns a clean version of the request made
     *
     * @return string
     */
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

    /**
     * Parse request
     *
     * Parses request URI and assigns class properties. Also assigns all the GET
     * parameters to REQUEST according to php.ini settings
     *
     * @return AeRequest self
     */
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

            $order = @ini_get('variables_order');

            if (version_compare(PHP_VERSION, '5.3.0', '>='))
            {
                $_order = @ini_get('request_order');

                if (!empty($_order)) {
                    $order = $_order;
                }
            }

            $order = strtoupper($order);

            if (strpos($order, 'G') !== false)
            {
                $length = strlen($order);
                $req    = array();

                for ($i = 0; $i < $length; $i++)
                {
                    $char = $order[$i];

                    switch ($char)
                    {
                        case 'G': {
                            array_merge($req, $_GET);
                        } break;

                        case 'P': {
                            array_merge($req, $_POST);
                        } break;

                        case 'C': {
                            array_merge($req, $_COOKIE);
                        }
                    }
                }

                $_REQUEST = $req;
            }
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