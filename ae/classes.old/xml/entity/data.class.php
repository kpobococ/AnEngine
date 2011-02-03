<?php

class AeXml_Entity_Data extends AeXml_Entity
{
    public function __construct($data = null)
    {
        $this->setData($data);
    }

    public function toString($level = 0)
    {
        if ($level instanceof AeScalar) {
            $level = $level->toInteger()->getValue();
        }

        if ($level == 0) {
            $pre = '';
        } else {
            $pre = $level > 0 ? str_repeat(' ', $level * 4) : '';
        }

        return $pre . $this->_getCleanData($this->_data);
    }
}

/**
 * XML data exception class
 *
 * XML data-specific exception class
 *
 * @author Anton Suprun <kpobococ@gmail.com>
 * @version 1.0
 * @package AnEngine
 * @todo add subpackage once custom documentor is done //Exception
 */
class AeXmlEntityDataException extends AeXmlEntityException
{
    /**
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 500)
    {
        $this->_appendPrefix('Data');
        parent::__construct($message, $code);
    }
}
?>