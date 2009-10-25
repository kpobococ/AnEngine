<?php
/**
 * @todo write documentation
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

    protected $_title;
    protected $_tags = array();
    protected $_styles = array();
    protected $_scripts = array();
    protected $_contentType = array(
        'type'    => 'text/html',
        'charset' => 'utf-8'
    );
    protected $_documentType = self::TYPE_XHTML_10_STRICT;

    public function __construct($title = array(), $options = array())
    {
        if ($title instanceof AeArray) {
            $title = $title->getValue();
        }

        if ($options instanceof AeArray) {
            $options = $options->getValue();
        }

        $this->_title = (array) $title;

        if (is_array($options))
        {
            // *** Add document type
            if (isset($options['doctype'])) {
                $this->setDocumentType($options['doctype']);
            } else if (!is_array($this->_documentType)) {
                $this->setDocumentType($this->_documentType);
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

    public function addTitle($title)
    {
        if ($title instanceof AeScalar) {
            $title = (string) $title;
        }

        if ($title instanceof AeArray) {
            $title = $title->getValue();
        }

        $title = (array) $title;

        foreach ($title as $bit) {
            $this->_title[] = $bit;
        }

        return true;
    }

    public function unshiftTitle($title)
    {
        if ($title instanceof AeScalar) {
            $title = (string) $title;
        }

        if ($title instanceof AeArray) {
            $title = $title->getValue();
        }

        $title = (array) $title;

        foreach ($title as $bit) {
            array_unshift($this->_title, $bit);
        }

        return true;
    }

    public function addTag($name, $value = null, $attrs = array(), $ie = null)
    {
        if ($value instanceof AeScalar) {
            $value = $value->getValue();
        }

        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        if ($ie instanceof AeScalar) {
            $ie = $ie->toInteger()->getValue();
        }

        $tag = array(
            'name'  => (string) $name,
            'value' => $value,
            'attrs' => $attrs
        );

        if ($ie !== null) {
            $tag['ie'] = $ie;
        }

        $this->_tags[] = $tag;

        return true;
    }

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

        $attrs['type'] = 'text/css';

        if ($internal) {
            return $this->addTag('style', $content, $attrs, $ie);
        }

        if (in_array($content, $this->_styles)) {
            return true;
        }

        $this->_styles[] = $content;
        $attrs['rel']  = 'stylesheet';
        $attrs['href'] = $content;

        return $this->addTag('link', null, $attrs, $ie);
    }

    public function addScript($content, $inline = false, $attrs = array(), $ie = null)
    {
        if ($inline instanceof AeScalar) {
            $inline = $inline->toBoolean()->getValue();
        }

        if ($attrs instanceof AeArray) {
            $attrs = $attrs->getValue();
        }

        $attrs['type'] = 'text/javascript';

        if (!$inline)
        {
            if (in_array((string) $content, $this->_scripts)) {
                return true;
            }

            $this->_scripts[] = (string) $content;
            $attrs['src']     = (string) $content;
            $content          = '';
        }

        return $this->addTag('script', $content, $attrs, $ie);
    }

    public function setDocumentType($type = self::TYPE_XHTML_10_STRICT, $link = null, $xmlHead = null)
    {
        if (is_int($type))
        {
            // *** Pre-defined constants
            switch ($type)
            {
                case self::TYPE_XHTML_11: {
                    $dt = array(
                        '-//W3C//DTD XHTML 1.1//EN',
                        'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd',
                        true
                    );
                } break;

                case self::TYPE_XHTML_10_TRANSITIONAL: {
                    $dt = array(
                        '-//W3C//DTD XHTML 1.0 Transitional//EN',
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd',
                        true
                    );
                } break;

                case self::TYPE_XHTML_10_FRAMESET: {
                    $dt = array(
                        '-//W3C//DTD XHTML 1.0 Frameset//EN',
                        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd',
                        true
                    );
                } break;

                case self::TYPE_HTML_401_STRICT: {
                    $dt = array(
                        '-//W3C//DTD HTML 4.01//EN',
                        'http://www.w3.org/TR/html4/strict.dtd'
                    );
                } break;

                case self::TYPE_HTML_401_TRANSITIONAL: {
                    $dt = array(
                        '-//W3C//DTD HTML 4.01 Transitional//EN',
                        'http://www.w3.org/TR/html4/loose.dtd'
                    );
                } break;

                case self::TYPE_HTML_401_FRAMESET: {
                    $dt = array(
                        '-//W3C//DTD HTML 4.01 Frameset//EN',
                        'http://www.w3.org/TR/html4/frameset.dtd'
                    );
                } break;

                case self::TYPE_XHTML_10_STRICT:
                default: {
                    $dt = array(
                        '-//W3C//DTD XHTML 1.0 Strict//EN',
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
                return false;
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

        $this->_documentType = $dt;

        return true;
    }

    public function setContentType($type = 'text/html', $charset = 'utf-8')
    {
        $this->_contentType = array(
            'type'    => (string) $type,
            'charset' => (string) $charset
        );

        return true;
    }

    public function setIcon($location, $type = 'image/x-icon')
    {
        $attrs = array(
            'rel'  => 'icon',
            'href' => (string) $location,
            'type' => (string) $type
        );

        $this->addTag('link', null, $attrs);

        $attrs['rel'] = 'shortcut icon';

        return $this->addTag('link', null, $attrs);
    }

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

    public function getTitle($separator = ' &ndash; ', $type = self::TITLE_REGULAR, $wrap = true)
    {
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

    public function getContentType()
    {
        $ct = $this->_contentType;

        return '<meta http-equiv="Content-Type" content="' . $ct['type'] . '; charset=' . $ct['charset'] . '" />' . "\n";
    }

    public function getDocumentType()
    {
        if (!is_array($this->_documentType)) {
            return '';
        }

        $dt     = $this->_documentType;
        $return = '';

        if ((bool) $dt[2] === true) {
            $return = '<' . '?xml version="1.0" encoding="utf-8"?' . '>' . "\n";
        }

        $return .= '<!DOCTYPE html PUBLIC "' . $dt[0] . '"';

        if (is_string($dt[1])) {
            // *** Spacing to indent double quotes
            $return .= "\n" . '                      "' . $dt[1] . '"';
        }

        $return .= '>' . "\n";

        return $return;
    }

    public function getHead($indent = 4, $titleSeparator = ' &ndash; ', $titleType = self::TITLE_REGULAR, $wrap = true)
    {
        if ($indent instanceof AeScalar) {
            $indent = $indent->toInteger()->getValue();
        }

        $pre    = str_repeat(' ', $indent);
        $return = '';

        $return .= $this->getContentType();
        $return .= $pre . $this->getTitle($titleSeparator, $titleType);
        $return .= $pre . $this->getTags($indent);

        if ($wrap instanceof AeScalar) {
            $wrap = $wrap->toBoolean()->getValue();
        }

        $return = rtrim($return) . "\n";

        if ($wrap) {
            $hpre   = str_repeat(' ', $indent - 4);
            $return = '<head>' . "\n" . $pre . $return . $hpre . '</head>' . "\n";
        }

        return $return;
    }

    /**
     *
     * @return AeDocument
     */
    public static function getInstance()
    {
        $args = func_get_args();
        return AeInstance::get('AeDocument', $args, true, false);
    }
}

?>