<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Vincent Lascaux <vincent.lascaux at centraliens.net>        |
// +----------------------------------------------------------------------+
//
// $Id$

require_once "File/Archive/Writer.php";
require_once "Mail.php";
require_once "Mail/mime.php";

/**
  * Send the files attached to a mail.
  */
class File_Archive_Writer_Mail extends File_Archive_Writer
{
    /**
      * @var Mail_mime object
      */
    var $mime;

    /**
      * @var Mail object used to send email (built thanks to the factory)
      */
    var $mail;

    /**
      * @var Array or String An array or a string with comma separated recipients
      */
    var $to;

    /**
      * @var Array The headers that will be passed to the Mail_mime object
      */
    var $headers;

    /**
      * @var String Data read from the current file so far
      */
    var $currentData = null;

    /**
      * @var String Name of the file being attached
      */
    var $currentFilename = null;

    /**
      * @var String MIME of the file being attached
      */
    var $currentMime = null;

    /**
      * @param Mail $mail Object used to send mail (see Mail::factory)
      * @param Array or String $to An array or a string with comma separated recipients
      * @param Array $headers The headers that will be passed to the Mail_mime object
      * @param String $message Text body of the mail
      * @param String $htmlMessage HTML body of the mail
      */
    function File_Archive_Writer_Mail($to, $headers, $message, &$mail = null)
    {
        $this->mime = new Mail_mime();
        $this->mime->setTXTBody($message);
        if(!empty($htmlMessage)) {
            $this->mime->setHTMLBody($htmlMessage);
        }

        if($mail == null)
            $this->mail = Mail::factory("mail");
        else
            $this->mail =& $mail;

        $this->to = $to;
        $this->headers = $headers;
    }

    function setHTMLBody($data, $isfile = false)
    {
        return $this->mime->setHTMLBody($data, $isfile);
    }
    function addHTMLImage($file, $c_type='application/octet-stream', $name='', $isfile=true)
    {
        return $this->mime->addHTMLImage($file, $c_type, $name, $isfile);
    }

    function writeData($data)
    {
        $this->currentData .= $data;
    }
    function addCurrentData()
    {
        if($this->currentFilename == null) {
            return;
        }

        $this->mime->addAttachment($this->currentData, $this->currentMime, $this->currentFilename, false);
        $this->currentData = "";
    }
    function newFile($filename, $stat, $mime="application/octet-stream")
    {
        $this->addCurrentData();

        $this->currentFilename = $filename;
        $this->currentMime = $mime;
    }

    function close()
    {
        parent::close();
        $this->addCurrentData();

        $body = $this->mime->get();
        $headers = $this->mime->headers($this->headers);

        if(!$this->mail->send(
                $this->to,
                $headers,
                $body)
          ) {
            die("Error sending mail");
        }
    }
}

?>