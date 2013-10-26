<?php
/*
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 */ 

class XeCreditNote extends XeRecord{

    /**
     * @var string
     */
    public $creditNoteID;


    /**
     * @return string
     */
    public function getId()
    {
        return $this->creditNoteID;
    }

}