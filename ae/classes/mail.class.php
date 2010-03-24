<?php
/**
 * @todo write documentation
 */
class AeMail extends AeObject
{
    const PLAIN = 'text/plain';
    const HTML = 'text/html';

    protected $_to = array();
    protected $_cc = array();
    protected $_bcc = array();
    protected $_from;
    protected $_replyTo;
    protected $_subject;
    protected $_body;
    protected $_type = self::PLAIN;
    protected $_headers = array();

    public function __construct($to = null, $from = null, $subject = null, $body = null)
    {
        if ($to !== null) {
            $this->addTo($to);
        }

        if ($from !== null) {
            $this->setFrom($from);
        }

        if ($subject !== null) {
            $this->setSubject($subject);
        }

        if ($body !== null) {
            $this->setBody($body);
        }
    }

    public function send()
    {
        $headers = array();

        // *** Standard stuff
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: ' . $this->_type . '; charset=utf-8';
        $headers[] = 'X-Mailer: AeMail';

        // *** From
        if (isset($this->_from))
        {
            if (isset($this->_from['name'])) {
                $headers[] = 'From: ' . $this->_encodeHeader($this->_from['name']) . ' <' . $this->_from['mail'] . '>';
            } else {
                $headers[] = 'From: ' . $this->_from['mail'];
            }
        }

        // *** Reply-To
        if (isset($this->_replyTo))
        {
            if (isset($this->_replyTo['name'])) {
                $headers[] = 'Reply-To: ' . $this->_encodeHeader($this->_replyTo['name']) . ' <' . $this->_replyTo['mail'] . '>';
            } else {
                $headers[] = 'Reply-To: ' . $this->_replyTo['mail'];
            }
        }

        // *** To, Cc, Bcc
        foreach (array('to', 'cc', 'bcc') as $field)
        {
            if (count($this->get($field)) == 0) {
                continue;
            }

            $vals = array();

            foreach ($this->get($field, array()) as $row)
            {
                if (isset($row['name'])) {
                    $vals[] = $this->_encodeHeader($row['name']) . ' <' . $row['mail'] . '>';
                } else {
                    $vals[] = $row['mail'];
                }
            }

            $content = implode(', ', $vals);

            if ($field == 'to') {
                $to = $content;
            } else {
                $this->addHeader(ucfirst($field), $content);
            }
        }

        // *** Custom headers
        foreach ($this->headers as $header => $content) {
            $headers[] = ucfirst($header) . ': ' . $content;
        }

        $subject = (string) $this->subject;
        $body    = (string) $this->body;
        $headers = implode("\r\n", $headers) . "\r\n";

        $return = @mail($to, $this->_encodeHeader($subject), $body, $headers);

        $this->fireEvent('send', array($to, $subject, $body, $headers, $return));

        return $return;
    }

    protected function _encodeHeader($content)
    {
        return '=?utf-8?B?' . base64_encode($content) . '?=';
    }

    public function addTo($email)
    {
        return $this->_addEmail($email, $this->_to);
    }

    public function addCc($email)
    {
        return $this->_addEmail($email, $this->_cc);
    }

    public function addBcc($email)
    {
        return $this->_addEmail($email, $this->_bcc);
    }

    protected function _addEmail($email, &$source)
    {
        if ($email instanceof AeScalar) {
            $email = (string) $email;
        }

        if ($email instanceof AeArray) {
            $email = $email->getValue();
        }

        if (is_array($email))
        {
            foreach ($email as $_email) {
                $this->_addEmail($_email, $source);
            }

            return true;
        }

        $name = $this->_parseEmail($email);

        if (!isset($source[$email])) {
            $source[$email] = array('mail' => $email);
        }

        if ($name !== null && !isset($source[$email]['name'])) {
            $source[$email]['name'] = $name;
        }

        return true;
    }

    protected function _parseEmail(&$email)
    {
        if (strpos($email, '<'))
        {
            list ($name, $email) = explode('<', $email);

            $name  = trim($name);
            $email = substr($email, 0, -1);

            return $name;
        }

        return null;
    }

    public function setFrom($email)
    {
        if ($email instanceof AeScalar) {
            $email = (string) $email;
        }

        $name = $this->_parseEmail($email);

        $this->_from = array('mail' => $email);

        if ($name !== null) {
            $this->_from['name'] = $name;
        }

        return true;
    }

    public function setReplyTo($email)
    {
        if ($email instanceof AeScalar) {
            $email = (string) $email;
        }

        $name = $this->_parseEmail($email);

        $this->_replyTo = array('mail' => $email);

        if ($name !== null) {
            $this->_replyTo['name'] = $name;
        }

        return true;
    }

    public function setType($type)
    {
        if (!in_array($type, array(self::PLAIN, self::HTML))) {
            return false;
        }

        $this->_type = $type;
    }

    public function addHeader($name, $content)
    {
        $name    = (string) $name;
        $content = (string) $content;

        $this->_headers[$name] = $content;

        return true;
    }
}
?>