<?php
/**
 * Document class file
 *
 * See {@link AeDocument} class documentation.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */

/**
 * Document class
 *
 * Document class is used to simplify document header generation, script and
 * stylesheet inclusion etc. Let's assume you want to generate a simple XHTML
 * page with title and a favicon:
 * <code> $document = AeDocument::getInstance('Simple page', array(
 *     'doctype' => AeDocument::TYPE_XHTML_10_STRICT,
 *     'icon'    => 'path/to/favicon.ico'
 * ));
 *
 * echo $document->getType() .
 * '&lt;html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
 *     ' . $document->getHead(8);</code>
 *
 * The code above will result in the following html being generated:
 * <pre> &lt;?xml version="1.0" encoding="utf-8"?&gt;
 * &lt;!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
 *                       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"&gt;
 * &lt;html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"&gt;
 *     &lt;head&gt;
 *         &lt;meta http-equiv="Content-Type" content="text/html; charset=utf-8" /&gt;
 *         &lt;title&gt;Simple page&lt;/title&gt;
 *         &lt;link rel="shortcut icon" href="path/to/favicon.ico" type="image/x-icon" /&gt;
 *     &lt;/head&gt;</pre>
 *
 * Note how the head block is indented. If you review the last line of the code,
 * you'll notice that a number 8 is passed to a call to {@link
 * AeDocument::getHead() getHead()}. This number specifies how much space should
 * be left before each tag inside the head tag. The head tag itself, although
 * being autogenerated, is moved 4 spaces ahead of it's contents. You can also
 * disable this functionality by passing 0 to the method. You can also disable
 * head tag auto-generating completely by passing false as the fourth method
 * argument. See {@link AeDocument::getHead() method documentation} for more
 * detailed description of the function's options.
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Framework
 */
class AeDocument extends AeObject
{
    // *** Title modes
    const TITLE_REGULAR = 1;
    const TITLE_REVERSE = 2;

    /**
     * XHTML 1.1 doctype declaration
     */
    const TYPE_XHTML_11 = 1;

    /**
     * XHTML 1.0 Strict doctype declaration
     */
    const TYPE_XHTML_10_STRICT = 2;

    /**
     * XHTML 1.0 Transitional doctype declaration
     */
    const TYPE_XHTML_10_TRANSITIONAL = 3;

    /**
     * XHTML 1.0 Frameset doctype declaration
     */
    const TYPE_XHTML_10_FRAMESET = 4;

    /**
     * HTML 4.01 Strict doctype declaration
     */
    const TYPE_HTML_401_STRICT = 5;

    /**
     * HTML 4.01 Transitional doctype declaration
     */
    const TYPE_HTML_401_TRANSITIONAL = 6;

    /**
     * HTML 4.01 Frameset doctype declaration
     */
    const TYPE_HTML_401_FRAMESET = 7;

    /**
     * HTML 5
     */
    const TYPE_HTML_5 = 8;

    /**
     * Document title
     * @var array
     */
    protected $_title;

    /**
     * Document head tags
     * @var array
     */
    protected $_tags = array();

    /**
     * Document styles
     * @var array
     */
    protected $_styles = array();

    /**
     * Document scripts
     * @var array
     */
    protected $_scripts = array();

    /**
     * Document content type
     * @var string
     */
    protected $_contentType = 'text/html';

    /**
     * Document type
     * @var array
     */
    protected $_type = self::TYPE_XHTML_10_STRICT;

    /**
     * Constructor
     *
     * You can set base title using constructor. The <var>$title</var> parameter
     * accepts either a single title part as string or an array of title parts.
     * Title parts are concatenated using the separator provided via the
     * {@link AeDocument::getTitle() getTitle()} or {@link AeDocument::getHead()
     * getHead()} methods.
     *
     * You can also set several most commonly used options using class
     * constructor. The available options are:
     * - type:         document type. Default: XHTML 1.0 Strict
     * - content-type: document content type. Default: text/html
     * - icon:         document favicon path. Default: browser default
     *
     * @uses AeDocument::setType()
     * @uses AeDocument::setContentType()
     * @uses AeDocument::setIcon()
     *
     * @param string|array $title
     * @param array        $options
     */
    public function __construct($title = array(), $options = array())
    {
        if ($title instanceof AeType) {
            $title = $title->getValue();
        }

        if ($options instanceof AeArray) {
            $options = $options->getValue();
        }

        if (!empty($title)) {
            $this->addTitle($title);
        }

        if (is_array($options))
        {
            // *** Add document type
            if (isset($options['type'])) {
                $this->setType($options['type']);
            } else if (!is_array($this->_type)) {
                $this->setType($this->_type);
            }

            // *** Add content type
            if (isset($options['content-type'])) {
                $this->setContentType($options['content-type']);
            }

            // *** Add favicon
            if (isset($options['icon'])) {
                $this->setIcon($options['icon']);
            }
        }
    }

    /**
     * Add title
     *
     * Adds a part of the title to current title array. This array gets
     * concatenated when either {@link AeDocument::getHead()} or {@link
     * AeDocument::getTitle()} gets called, using the separator provided as
     * method parameter. See respective method documentation for more details.
     *
     * This method accepts an unlimited number of string arguments and treats
     * each string as a separate title part:
     * <code> $document->addTitle('My site');
     * $document->addTitle('Articles', 'My article');
     *
     * echo $document->getTitle(' - ', AeDocument::TITLE_REVERSE, false);
     * // prints "My article - Articles - My site"</code>
     *
     * It will also understand a single array of strings.
     *
     * @throws AeDocumentException #400 on invalid value
     *
     * @param string $title,...
     *
     * @return AeDocument self
     */
    public function addTitle($title)
    {
        $num = func_num_args();

        if ($num == 1)
        {
            $arg  = func_get_arg(0);
            $type = AeType::of($arg);

            if ($type == 'array') {
                $title = $arg;
            } else if ($type == 'string') {
                $title = array((string) $arg);
            } else {
                throw new AeDocumentException('Invalid value passed: expecting string or array, ' . $type . ' given', 400);
            }
        } else {
            $title = func_get_args();
        }

        if ($title instanceof AeType) {
            $title = $title->getValue();
        }

        foreach ($title as $bit)
        {
            if (AeType::of($bit) != 'string') {
                throw new AeDocumentException('Invalid value passed: expecting string, ' . AeType::of($bit) . ' given', 400);
            }

            $this->_title[] = (string) $bit;
        }

        return $this;
    }

    /**
     * Add head tag
     *
     * Adds a tag to the document head element, using arguments as tag options.
     * If an optional <var>$ie</var> parameter is passed and is an integer, the
     * tag is added with conditional comments for that version of ie. The
     * following example only adds the tag for ie6:
     * <code> $document->addTag('style', 'b{color:#00f;}', array(), 6);</code>
     *
     * Note, in the example above we add a style tag explicitly, but you should
     * use {@link AeDocument::addStyle() addStyle()} method instead.
     *
     * You can also use the <var>$ie</var> argument to specify more complex
     * conditions. The following code only adds the tag for ie 7 or below:
     * <code> $document->addTag('style', 'b{color:#00f;}', array(), 'lt 7');</code>
     *
     * @todo prevent adding tags with exactly the same attributes
     *
     * @param string     $name  tag name
     * @param string     $value tag value
     * @param array      $attrs an associative array of tag attributes
     * @param int|string $ie    IE version or condition
     *
     * @return AeDocument self
     */
    public function addTag($name, $value = null, $attrs = array(), $ie = null)
    {
        if ($value instanceof AeType) {
            $value = $value->getValue();
        }

        if ($ie instanceof AeScalar) {
            $ie = $ie->toInteger()->getValue();
        }

        $tag = array(
            'name'  => (string) $name,
            'value' => (string) $value,
            'attrs' => $attrs
        );

        if ($ie !== null) {
            $tag['ie'] = $ie;
        }

        $this->_tags[] = $tag;

        return $this;
    }

    /**
     * Add meta tag
     *
     * Adds a meta tag to the document head element, using arguments as tag
     * options:
     * <code> $document->addMeta('revisit-after', array(
     *     'content' => '14 days'
     * ));</code>
     *
     * @uses AeDocument::addTag()
     *
     * @todo overwrite existing tags with the same name, if present
     *
     * @param string $name  meta tag name attribute
     * @param array  $attrs additional meta tag attributes
     *
     * @return AeDocument self
     */
    public function addMeta($name, $attrs = array())
    {
        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        if ($name !== null) {
            $attrs['name'] = (string) $name;
        }

        return $this->addTag('meta', null, $attrs);
    }

    /**
     * Add link tag
     *
     * Adds a link tag to the document head element, using arguments as tag
     * options:
     * <code> $document->addLink('alternate', array(
     *     'href' => 'http://example.com/rss.xml',
     *     'title' => 'RSS news',
     *     'type' => 'application/rss+xml'
     * ));</code>
     *
     * @uses AeDocument::addTag()
     *
     * @param string $rel   link tag rel attribute
     * @param array  $attrs additional link tag attributes
     *
     * @return AeDocument self
     */
    public function addLink($rel, $attrs = array())
    {
        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        if ($rel !== null) {
            $attrs['rel'] = (string) $rel;
        }

        return $this->addTag('link', null, $attrs);
    }

    /**
     * Add style tag
     *
     * Adds a style tag to the document head element, using <var>$content</var>
     * as stylesheet location or, if <var>$internal</var> is set to true, as
     * internal stylesheet content. You can use <var>$attrs</var> parameter to
     * pass an associative array of additional tag attributes, however, several
     * basic attributes are set automatically for you. These attributes are
     * type for both internal and external stylesheets, rel and href for
     * external stylesheets only.
     *
     * You can also use the optional <var>$ie</var> attribute to make the
     * stylesheet exclusive for certain version(s) of IE, using IE's conditional
     * comments. See {@link AeDocument::addTag() addTag()} for more information.
     *
     * <b>NOTE:</b> This method checks if an external stylesheet has already
     * been added to the document and only adds it if it was not. This may lead
     * to improper stylesheet inclusion order. You can use {@link
     * AeDocument::addTag() addTag()} method to override this check, but any
     * stylesheet added this way will not be checked against when adding other
     * stylesheets.
     *
     * @uses AeDocument::addTag()
     *
     * @param string     $content  stylesheet href or internal stylesheet contents
     * @param bool       $internal internal stylesheet flag. Default: false
     * @param array      $attrs    additional tag attributes
     * @param int|string $ie       IE version or condition
     *
     * @return AeDocument self
     */
    public function addStyle($content, $internal = false, $attrs = array(), $ie = null)
    {
        if ($content instanceof AeScalar) {
            $content = (string) $content;
        }

        if ($internal instanceof AeScalar) {
            $internal = $internal->toBoolean()->getValue();
        }

        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        $attrs['type'] = isset($attrs['type']) ? $attrs['type'] : 'text/css';

        if ($internal) {
            return $this->addTag('style', $content, $attrs, $ie);
        }

        if (in_array($content, $this->_styles)) {
            return $this;
        }

        $this->_styles[] = $content;
        $attrs['rel']  = isset($attrs['rel']) ? $attrs['rel'] : 'stylesheet';
        $attrs['href'] = $content;

        return $this->addTag('link', null, $attrs, $ie);
    }

    /**
     * Add script tag
     *
     * Adds a script tag to the document head element, using <var>$content</var>
     * as script location or, if <var>$inline</var> is set to true, as inline
     * script content. You can use <var>$attrs</var> parameter to pass an
     * associative array of additional tag attributes, however, several basic
     * attributes are set automatically for you. These attributes are type
     * (text/javascript) for both inline and external scripts, src for external
     * scripts only.
     *
     * You can also use the optional <var>$ie</var> attribute to make the script
     * exclusive for certain version(s) of IE, using IE's conditional comments.
     * See {@link AeDocument::addTag() addTag()} for more information.
     *
     * <b>NOTE:</b> This method checks if an external script has already been
     * added to the document and only adds it if it was not. This may lead to
     * improper script inclusion order. You can use {@link AeDocument::addTag()
     * addTag()} method to override this check, but any script added this way
     * will not be checked against when adding other scripts.
     *
     * @uses AeDocument::addTag()
     *
     * @param string     $content script src or inline script contents
     * @param bool       $inline  inline script flag. Default: false
     * @param array      $attrs   additional tag attributes
     * @param int|string $ie      IE version or condition
     *
     * @return AeDocument self
     */
    public function addScript($content, $inline = false, $attrs = array(), $ie = null)
    {
        if ($inline instanceof AeScalar) {
            $inline = $inline->toBoolean()->getValue();
        }

        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        $attrs['type'] = isset($attrs['type']) ? $attrs['type'] : 'text/javascript';

        if (!$inline)
        {
            if (in_array((string) $content, $this->_scripts)) {
                return $this;
            }

            $this->_scripts[] = (string) $content;
            $attrs['src']     = (string) $content;
            $content          = '';
        }

        return $this->addTag('script', $content, $attrs, $ie);
    }

    /**
     * Set document type
     *
     * Sets the document type to the one specified. You can use one of the
     * preset document types via class constants (see below), or provide your
     * own doctype definition parameters:
     * <code> $document->setType(
     *     'PUBLIC "-//W3C//DTD XHTML 1.1//EN"',
     *     'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'
     * , true); </code>
     *
     * The effect of the example above is similar to that of:
     * <code> $document->setType(AeDocument::TYPE_XHTML_11);</code>
     *
     * @see AeDocument::TYPE_HTML_401_STRICT, AeDocument::TYPE_HTML_401_FRAMESET,
     *      AeDocument::TYPE_HTML_401_TRANSITIONAL
     * @see AeDocument::TYPE_XHTML_10_STRICT, AeDocument::TYPE_XHTML_10_FRAMESET,
     *      AeDocument::TYPE_XHTML_10_TRANSITIONAL
     * @see AeDocument::TYPE_XHTML_11
     * @see AeDocument::TYPE_HTML_5
     *
     * @param int|string $type    doctype constant or an FPI string
     * @param string     $link    doctype DTD link
     * @param bool       $xmlHead if this is true, an xml header will be added
     *                            above the doctype
     *
     * @return AeDocument self
     */
    public function setType($type = null, $link = null, $xmlHead = null)
    {
        if ($type === null) {
            $type = self::TYPE_XHTML_10_STRICT;
        }

        if (is_int($type))
        {
            // *** Pre-defined constants
            switch ($type)
            {
                case self::TYPE_XHTML_11: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD XHTML 1.1//EN"',
                        'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd',
                        true
                    );
                } break;

                case self::TYPE_XHTML_10_TRANSITIONAL: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"',
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd',
                        true
                    );
                } break;

                case self::TYPE_XHTML_10_FRAMESET: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"',
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd',
                        true
                    );
                } break;

                case self::TYPE_HTML_401_STRICT: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD HTML 4.01//EN"',
                        'http://www.w3.org/TR/html4/strict.dtd'
                    );
                } break;

                case self::TYPE_HTML_401_TRANSITIONAL: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"',
                        'http://www.w3.org/TR/html4/loose.dtd'
                    );
                } break;

                case self::TYPE_HTML_401_FRAMESET: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"',
                        'http://www.w3.org/TR/html4/frameset.dtd'
                    );
                } break;

                case self::TYPE_HTML_5: {
                    $dt = '';
                } break;

                case self::TYPE_XHTML_10_STRICT:
                default: {
                    $dt = array(
                        'PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"',
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd',
                        true
                    );
                } break;
            }
        } else {
            $dt = array();
        }

        if ($type instanceof AeScalar) {
            $type = (string) $type;
        }

        if ($type instanceof AeArray) {
            $type = $type->getValue();
        }

        if (empty($dt))
        {
            if (!is_string($type) && !is_array($type)) {
                throw new AeDocumentException('Invalid type passed: expecting constant, string or array, ' . AeType::of($type) . ' given', 400);
            } else if (is_string($type)) {
                $dt = array($type);
            } else {
                $dt = $type;
            }
        }

        if ($link !== null)
        {
            if ($link instanceof AeScalar) {
                $link = (string) $link;
            }

            $dt[1] = $link;
        }

        if ($xmlHead !== null)
        {
            if ($xmlHead instanceof AeScalar) {
                $xmlHead = $xmlHead->toBoolean()->getValue();
            }

            $dt[2] = $xmlHead;
        }

        $this->_type = $dt;

        return $this;
    }

    /**
     * Set content type
     *
     * Sets the document content type using a meta tag. Encoding is set to UTF-8
     * and cannot be changed.
     *
     * @param string $type content type. Default: text/html
     *
     * @return AeDocument self
     */
    public function setContentType($type = 'text/html')
    {
        $this->_contentType = (string) $type;

        return $this;
    }

    /**
     * Set favicon
     *
     * Sets the document favicon (shortcut icon) using a link tag. All the
     * attributes, except the icon location, are set, but you can override
     * the type attribute.
     *
     * @todo overwrite icon if present
     *
     * @uses AeDocument::addTag()
     *
     * @param string $location icon location
     * @param string $type     icon type. Default: image/x-icon
     *
     * @return AeDocument self
     */
    public function setIcon($location, $type = 'image/x-icon')
    {
        $attrs = array(
            'rel'  => 'shortcut icon',
            'href' => (string) $location,
            'type' => (string) $type
        );

        return $this->addTag('link', null, $attrs);
    }

    /**
     * Get tags
     *
     * Returns all the head tags as a valid html ready to be inserted inside the
     * head tag. If an optional <var>$indent</var> parameter is passed, it will
     * be used as a padding count for each tag. The first tag will not be
     * padded. The padding character is space.
     *
     * @param int $indent
     *
     * @return string
     */
    public function getTags($indent = 4)
    {
        $pre    = str_repeat(' ', $indent);
        $return = '';

        foreach ($this->_tags as $tag)
        {
            if (isset($tag['ie'])) {
                $return .= $pre . '<!--[if IE ' . $tag['ie']  . ']>' . "\n";
            }

            $return .= $pre . '<' . $tag['name'];

            foreach ($tag['attrs'] as $key => $val) {
                $return .= ' ' . $key . '="' . $val . '"';
            }

            if ($tag['value'] !== null) {
                $return .= '>' . $tag['value'] . '</' . $tag['name'] . '>';
            } else {
                $return .= ' />';
            }

            $return .= "\n";

            if (isset($tag['ie'])) {
                $return .= $pre . '<![endif]-->' . "\n";
            }
        }

        return ltrim($return);
    }

    /**
     * Get title
     *
     * Returns a formed title ready to be inserted into a head tag. If an
     * optional <var>$wrap</var> parameter is false, the title will not be
     * wrapped by the title tag. This is useful to include the title in the
     * document body.
     *
     * @todo add title escape
     *
     * @see AeDocument::TITLE_REGULAR, AeDocument::TITLE_REVERSE
     *
     * @param string $separator title bits separator. Default: &ndash;
     * @param int    $type      title type constant. Default: TITLE_REGULAR
     * @param bool   $wrap      title wrap flag. Default: true
     *
     * @return string
     */
    public function getTitle($separator = null, $type = null, $wrap = true)
    {
        if ($separator === null) {
            $separator = ' &ndash; ';
        }

        if ($type === null) {
            $type = self::TITLE_REGULAR;
        }

        $title = $this->_title;

        if (!empty($title))
        {
            if ($type instanceof AeScalar) {
                $type = $type->toInteger()->getValue();
            }

            if ($type == self::TITLE_REVERSE) {
                $title = array_reverse($title);
            }

            $title = implode((string) $separator, $title);
        }

        if ($wrap instanceof AeScalar) {
            $wrap = $wrap->toBoolean()->getValue();
        }

        if ($wrap) {
            $title = '<title>' . $title . '</title>' . "\n";
        }

        return $title;
    }

    /**
     * Get content type
     *
     * Returns a content type meta tag ready to be inserted into a head tag
     *
     * @return string
     */
    public function getContentType()
    {
        $ct = $this->_contentType;

        return '<meta http-equiv="Content-Type" content="' . $ct . '; charset=utf-8" />' . "\n";
    }

    /**
     * Get type
     *
     * Returns a document type declaration ready to be inserted into document.
     *
     * @return string
     */
    public function getType()
    {
        if (!is_array($this->_type)) {
            return '';
        }

        $dt     = $this->_type;
        $return = '';

        if (is_array($dt) && (bool) $dt[2] === true) {
            $return = '<' . '?xml version="1.0" encoding="utf-8"?' . '>' . "\n";
        }

        $return .= '<!DOCTYPE html';

        if (!empty($dt[0]))
        {
            $return .= ' ' . $dt[0];

            if (is_string($dt[1])) {
                // *** Spacing to indent double quotes
                $return .= "\n" . '                      "' . $dt[1] . '"';
            }
        }

        $return .= '>' . "\n";

        return $return;
    }

    /**
     * Get head
     *
     * Returns a document head section ready to be inserted into document. If an
     * optional <var>$wrap</var> parameter is set to false, the output will not
     * be wrapped by the head tag. This is useful if you want to provide
     * additional head tags inside the template.
     *
     * <var>$indent</var> parameter specifies a number of tabs to put before
     * each tag inside the head. If the head tag itself is present, it will be
     * indented by 1 tab less than this value (or 0, if this value is less
     * than 1).
     *
     * <var>$title</var> parameter is an array of arguments to pass to the
     * {@link AeDocument::getTitle() getTitle()} method. Note, that you can only
     * specify two first arguments, the third one is always set to true.
     *
     * @uses AeDocument::getContentType()
     * @uses AeDocument::getTitle()
     * @uses AeDocument::getTags()
     *
     * @param int   $indent section indent number. Default: 1
     * @param array $title  title method parameters. Default: empty
     * @param bool  $wrap   head wrap flag. Default: true
     *
     * @return string
     */
    public function getHead($indent = 1, $title = null, $wrap = true)
    {
        if ($indent instanceof AeScalar) {
            $indent = $indent->toInteger()->getValue();
        }

        if ($title === null) {
            $title = array();
        }

        if ($title instanceof AeArray) {
            $title = $title->getValue();
        }

        $title  = array_slice($title, 0, 2);
        $pre    = $indent > 0 ? str_repeat(' ', $indent * 4) : '';
        $return = '';

        $return .= $this->getContentType();
        $return .= $pre . $this->call('getTitle', $title);
        $return .= $pre . $this->getTags($indent);

        if ($wrap instanceof AeScalar) {
            $wrap = $wrap->toBoolean()->getValue();
        }

        $return = rtrim($return) . "\n";

        if ($wrap) {
            $hpre   = $indent - 1 > 0 ? str_repeat(' ', ($indent - 1) * 4) : '';
            $return = '<head>' . "\n" . $pre . $return . $hpre . '</head>' . "\n";
        }

        return $return;
    }

    /**
     * Get document
     *
     * Returns an instance of the document. This method can be used to ensure
     * AeDocument remains a singleton across the application. You can still
     * instantiate more than one instance of the class directly, however, if the
     * need arises. Just use the class constructor.
     *
     * @return AeDocument
     */
    public static function getInstance()
    {
        $args = func_get_args();
        return AeInstance::get('AeDocument', $args, true, false);
    }
}

/**
 * Document exception class
 *
 * Document-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeDocumentException extends AeException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Document');
        parent::__construct($message, $code);
    }
}

?>