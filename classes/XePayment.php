<?php
/**
 * Created by JetBrains PhpStorm.
 * User: iain
 * Date: 29/05/13
 * Time: 00:17
 * To change this template use File | Settings | File Templates.
 */

/*
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 */ 

class XePayment extends XeRecord {

    /**
     * @var string
     */
    public $paymentId;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->paymentId;
    }
}