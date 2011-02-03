<?php
/**
 * Template class file
 *
 * See {@link AeTemplate} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Template class
 *
 * This is a basic template class. It uses PHP templates and provides minimum
 * functionality with it. You can use it or any other template system you like.
 *
 * The example of using this class would be something like this:
 * <code> $template = new AeTemplate('templates/index.tpl');
 *
 * $template->assign('foo', 'bar');
 * echo $template; // This is similar to $template->display();</code>
 *
 * The template could be something like the following:
 * <code> <?php if (isset($this->foo): ?>
 * Foo variable value is <?php echo $this->foo; ?>
 * <?php else: ?>
 * Foo variable was not assigned
 * <?php endif; ?></code>
 *
 * You can also use the {@link AeTemplate::get()} method with the default value:
 * <code> Foo varialbe value is <?php echo $this->get('foo', 'unassigned'); ?></code>
 *
 * Finally, you can use the magic getter methods with default values:
 * <code> Foo variable value is <?php echo $this->getFoo('unassigned'); ?></code>
 *
 * Note, however, that using either {@link AeTemplate::get()} or magic getter
 * methods may result into retrieveing internal template properties. That is why,
 * using {@link AeTemplate::assign()} method is advised instead of using {@link
 * AeTemplate::set()} or magic setter methods, because the {@link
 * AeTemplate::assign()} method does not allow assigning the variables if their
 * names are conflicting with the template property names. See {@link
 * AeTemplate::assign()} for the complete list of reserved variable names.
 *
 * Escaping functionality inspired by Savant3 library by: Paul M. Jones <pmjones@ciaweb.net>
 *
 * @author Paul M. Jones <pmjones@ciaweb.net>
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeTemplate extends AeNode
{
    /**
     * Templates directory paths
     * @var array
     */
    protected static $_templateDirectory = array();

    /**
     * Template file extension
     * @var string
     */
    protected static $_templateExtension = 'tpl';

    /**
     * List of escape method callbacks
     * @var array
     */
    protected $_escapeCallback = array('htmlspecialchars');

    /**
     * Name/path to the template file
     * @var string
     */
    protected $_templateFile;

    const CHECK_BOOL  = 1;
    const CHECK_NUM   = 2;
    const CHECK_TEXT  = 3;
    const CHECK_LOOP  = 4;
    const CHECK_ANY   = 5;

    /**
     * Set template directory path
     *
     * @param string $path
     * @param string $path,... any number of paths
     *
     * @return bool
     */
    public static function setTemplateDirectory($path)
    {
        self::$_templateDirectory = (array) @func_get_args();

        return true;
    }

    /**
     * Add template directory path
     *
     * @param string $path
     * @param string $path,... any number of paths
     *
     * @return bool
     */
    public static function addTemplateDirectory($path)
    {
        $args = (array) @func_get_args();
        self::$_templateDirectory = array_merge(self::$_templateDirectory, $args);

        return true;
    }

    /**
     * Set template extension
     *
     * @param string $extension new template extension
     *
     * @return bool
     */
    public static function setTemplateExtension($extension)
    {
        self::$_templateExtension = (string) $extension;

        return true;
    }

    /**
     * Constructor
     *
     * If the global template directory is set, the file is being searched for
     * inside it. No subdirectory checking is done.
     * 
     * If the global template directory is not set, the file is being searched
     * for inside the templates directory of each of the include paths
     * available
     *
     * @throws AeTemplateException #404 if template file not found
     *
     * @param string $template template file name/path
     */
    public function __construct($template)
    {
        // *** Add extension, if none is found
        if (strpos($template, '.') === false) {
            $template .= '.' . self::$_templateExtension;
        }

        $templateDirectory = self::$_templateDirectory;

        if (empty($templateDirectory))
        {
            // *** Search for the template inside the include path
            foreach (explode(PATH_SEPARATOR, get_include_path()) as $path)
            {
                $path = realpath($path);

                if (file_exists($path . SLASH . 'templates' . SLASH . $template)) {
                    $this->_templateFile = realpath($path . SLASH . 'templates' . SLASH . $template);
                    return;
                }
            }

            throw new AeTemplateException('Template not found: ' . $template, 404);
        }

        // *** Search for the template inside template directory
        foreach ($templateDirectory as $directory)
        {
            if (file_exists($directory . SLASH . $template)) {
                $this->_templateFile = realpath($directory . SLASH . $template);
                return;
            }
        }

        throw new AeTemplateException('Template not found: ' . $template, 404);
    }

    /**
     * Set escape callback
     *
     * Sets one or several callbacks to use with the escape method. This method
     * overwrites previous callbacks. Use {@link AeTemplate::addEscapeCallback()}
     * method to add new callbacks without overwriting the existing ones
     *
     * @param string|array|AeCallback $callback
     * @param string|array|AeCallback $callback,...
     *
     * @return AeTemplate self
     */
    public function setEscapeCallback($callback)
    {
        $this->_escapeCallback = (array) @func_get_args();

        return $this;
    }

    /**
     * Add escape callback
     *
     * Adds one or several callbacks to use with the escape method. This method
     * keeps the existing callbacks. Use {@link AeTemplate::setEscapeCallback()}
     * to overwrite existing callbacks
     *
     * @param string|array|AeCallback $callback
     * @param string|array|AeCallback $callback,...
     *
     * @return AeTemplate self
     */
    public function addEscapeCallback($callback)
    {
        $args = (array) @func_get_args();
        $this->_escapeCallback = array_merge($this->_escapeCallback, $args);

        return $this;
    }

    /**
     * Escape value
     *
     * Escapes the value passed, using the escape callbacks set via {@link
     * AeTemplate::setEscapeCallback()} and {@link
     * AeTemplate::addEscapeCallback()}. To use different callbacks just once,
     * you can pass them as a second and following parameters. The existing
     * escape callbacks will be ignored, if this is done
     *
     * @param mixed $value
     * @param string|array|AeCallback $callback,...
     *
     * @return mixed escaped value
     */
    public function escape($value)
    {
        if (func_num_args() > 1) {
            $callbacks = func_get_args();
            $callbacks = array_slice($callbacks, 1);
        } else {
            $callbacks = $this->getEscapeCallback();
        }

        foreach ($callbacks as $callback)
        {
            if ($callback instanceof AeCallback) {
                $value = $callback->call(array($value));
            } else {
                $value = call_user_func($callback, $value);
            }
        }

        return $value;
    }

    /**
     * Get output
     *
     * This method is an alias of {@link AeTemplate::fetch()} method. It is
     * provided as support for direct object echoing, see {@link
     * AeObject::__toString()} for details
     *
     * @return string
     */
    public function toString()
    {
        try {
            return $this->fetch();
        } catch (AeException $e) {
            // *** There can be no exceptions in the type cast method
            return "";
        }
    }

    /**
     * Render template
     *
     * Processes the template with assigned variables and outputs the rendering
     * result
     *
     * @throws AeTemplateException #410 if template file not found
     *
     * @return AeTemplate self
     */
    public function display()
    {
        $template = $this->getTemplateFile();

        // *** One final check
        if (!file_exists($template)) {
            throw new AeTemplateException('Template file is not found', 410);
        }

        include $template;

        return $this;
    }

    /**
     * Process template
     *
     * Processes the template with assigned variables and returns the rendering
     * result.
     *
     * This method trims the whitespaces of the output and adds a
     * newline at the end. This is done so the resulting output will be
     * human-readable, if wrapped inside another template:
     * <code> <html>
     *     <body>
     *         <?php echo $this->output; ?>
     *     </body>
     * </html></code>
     *
     * Assuming, that output is a template with "Hello World" inside it, this
     * will produce:
     * <code> <html>
     *     <body>
     *         Hello World
     *     </body>
     * </html></code>
     *
     * While without whitespaces, this will result in:
     * <code> <html>
     *     <body>
     *         Hello World     </body>
     * </html></code>
     *
     * Note, however, that newlines are not indented automatically, so multiline
     * templates will still either require you to indent them manually or result
     * in a moderately-readable output
     *
     * @throws AeTemplateException #410 if template file is not found
     *
     * @return string
     */
    public function fetch()
    {
        ob_start();
        $this->display();

        // *** Removes whitespaces and ensures correct output position
        return trim(ob_get_clean()) . "\n";
    }

    /**
     * Assign variable
     *
     * Assigns variable to the template. Variables can be assigned using three
     * different methods:
     *  - object properties: if <var>$name</var> parameter is an object, it's
     *                       public properties will be assigned to the template;
     *  - associative array: if <var>$name</var> parameter is an array, it's
     *                       keys and values will be used as variable names and
     *                       values respectively;
     *  - key/value pair:    if <var>$name</var> parameter is a string and
     *                       <var>$value</var> parameter is provided, they will
     *                       be assigned.
     *
     * Any other combination of values will not be assigned and false will be
     * returned.
     *
     * You can also use {@link AeTemplate::assignCheck()} method to assign a
     * variable for a particular purpose and check if the value you want to
     * assign can be used for that purpose.
     *
     * <b>NOTE</b>: The variable name cannot be one of the following:
     * escapeCallback, templateFile, _escapeCallback, _templateFile. These names
     * are reserved for the AeTemplate's property names. If any of them is used,
     * an exception will be thrown
     *
     * @throws AeTemplateException #409 if reserved name is used
     * @throws AeTemplateException #400 on invalid value passed
     *
     * @param object|array|string $name  variable name
     * @param mixed                                $value variable value
     *
     * @return AeTemplate self
     */
    public function assign($name, $value = null)
    {
        if ($name instanceof AeArray || $name instanceof AeString) {
            $name = $name->getValue();
        }

        // *** Assign from object
        if (is_object($name))
        {
            foreach (get_object_vars($name) as $key => $value) {
                $this->assign($key, $value);
            }

            return $this;
        }

        // *** Assign from array
        if (is_array($name))
        {
            foreach ($name as $key => $value) {
                $this->assign($key, $value);
            }

            return $this;
        }

        // *** Regular name => value assign
        if (is_string($name) && $value !== null)
        {
            // *** Only assign if no conflict
            if ($this->propertyExists($name)) {
                throw new AeTemplateException('Cannot assign variable: name is reserved', 409);
            }

            $this->_properties[$name] = $value;
            return $this;
        }

        throw new AeTemplateException('Invalid value passed: expecting string, array or object, ' . AeType::of($name) . ' given', 400);
    }

    /**
     * Check and assign variable
     *
     * Check a variable using <var>$mode</var> and assigns only if check is
     * passed.
     *
     * Check mode can be one of the following:
     * - {@link AeTemplate::CHECK_BOOL}: checks if variable can be used in an if
     *                                   statement
     * - {@link AeTemplate::CHECK_NUM}:  checks if variable can be used as a
     *                                   number
     * - {@link AeTemplate::CHECK_TEXT}: checks if variable can be used as text
     * - {@link AeTemplate::CHECK_LOOP}: (default) checks if variable can be
     *                                   looped through
     * - {@link AeTemplate::CHECK_ANY}:  checks if variable is not NULL
     *
     * This method also supports same formats as {@link AeTemplate::assign()}
     * method does: <var>$name</var> can be an object, an array or a string. If
     * <var>$name</var> is an array or an object, <var>$value</var> is expected
     * to be a check mode instead.
     *
     * <b>NOTE:</b> for the {@link AeTemplate::CHECK_BOOL} variable will be
     *              cast to boolean prior to assignment. For the {@link
     *              AeTemplate::CHECK_TEXT} variable will be cast to string
     *              prior to assignment. For the {@link AeTemplate::CHECK_NUM}
     *              variable will be cast to integer, if it is an instance of
     *              {@link AeInteger}, and cast to float, if it is an instance
     *              of {@link AeFloat}.
     *
     * @throws AeTemplateException #406 if value doesn't pass the check
     *
     * @uses AeTemplate::CHECK_BOOL
     * @uses AeTemplate::CHECK_NUM
     * @uses AeTemplate::CHECK_TEXT
     * @uses AeTemplate::CHECK_LOOP
     * @uses AeTemplate::CHECK_ANY
     *
     * @param object|array|string $name  variable name
     * @param mixed               $value variable value
     * @param int                 $mode  check mode
     *
     * @return AeTemplate self
     */
    public function assignCheck($name, $value = null, $mode = self::CHECK_LOOP)
    {
        if ($name instanceof AeArray || $name instanceof AeString) {
            $name = $name->getValue();
        }

        // *** Assign from object
        if (is_object($name))
        {
            $mode = isset($value) ? $value : self::CHECK_LOOP;

            foreach (get_object_vars($name) as $key => $value) {
                $this->assignCheck($key, $value, $mode);
            }

            return $this;
        }

        // *** Assign from array
        if (is_array($name))
        {
            $mode = isset($value) ? $value : self::CHECK_LOOP;

            foreach ($name as $key => $value) {
                $this->assignCheck($key, $value, $mode);
            }

            return $this;
        }

        // *** Regular name => value assign
        if (is_string($name) && $value !== null)
        {
            switch ($mode)
            {
                case AeTemplate::CHECK_BOOL:
                {
                    if ($value instanceof AeScalar) {
                        $value = $value->toBoolean()->getValue();
                    }

                    if (!is_scalar($value) && !is_boolean((bool) $value)) {
                        throw new AeTemplateException('Boolean check failed for "' . $name . '" variable', 406);
                    }

                    $value = (bool) $value;
                } break;

                case AeTemplate::CHECK_NUM:
                {
                    if ($value instanceof AeNumeric) {
                        $value = $value->getValue();
                    }

                    if (!is_numeric($value)) {
                        throw new AeTemplateException('Numeric check failed for "' . $name . '" variable', 406);
                    }
                } break;

                case AeTemplate::CHECK_TEXT:
                {
                    if ($value instanceof AeScalar) {
                        $value = $value->toString()->getValue();
                    }

                    if (!is_scalar($value) || !is_string((string) $value)) {
                        throw new AeTemplateException('Text check failed for "' . $name . '" variable', 406);
                    }

                    $value = (string) $value;
                } break;

                case AeTemplate::CHECK_ANY:
                {
                    if (is_null($value)) {
                        throw new AeTemplateException('Not null check failed for "' . $name . '" variable', 406);
                    }
                } break;

                case AeTemplate::CHECK_LOOP:
                default:
                {
                    if (!is_array($value) && !($value instanceof Traversable)) {
                        throw new AeTemplateException('Loop check failed for "' . $name . '" variable', 406);
                    }
                } break;
            }

            return $this->assign($name, $value);
        }

        throw new AeTemplateException('Invalid value passed: expecting string, array or object, ' . AeType::of($name) . ' given', 400);
    }

    /**
     * @todo finish method documentation
     *
     * @param object|array|string $name
     * @param mixed               $value
     *
     * @return AeTemplate self
     */
    public function assignEscape($name, $value = null)
    {
        if ($name instanceof AeArray || $name instanceof AeString) {
            $name = $name->getValue();
        }

        // *** Assign from object
        if (is_object($name))
        {
            foreach (get_object_vars($name) as $key => $value) {
                $this->assignEscape($key, $value);
            }

            return $this;
        }

        // *** Assign from array
        if (is_array($name))
        {
            foreach ($name as $key => $value) {
                $this->assignEscape($key, $value);
            }

            return $this;
        }

        // *** Regular name => value assign
        if (is_string($name) && $value !== null)
        {
            // *** Get possible escape callbacks
            $args = @func_get_args();
            $args = array_slice($args, 1);

            // *** Only assign if no conflict
            if ($this->propertyExists($name)) {
                throw new AeTemplateException('Cannot assign variable: name is reserved', 409);
            }

            $this->_properties[$name] = $this->call('escape', $args);
            return $this;
        }

        throw new AeTemplateException('Invalid value passed: expecting string, array or object, ' . AeType::of($name) . ' given', 400);
    }
}

/**
 * Template exception class
 *
 * Template-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeTemplateException extends AeObjectException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Template');
        parent::__construct($message, $code);
    }
}
?>