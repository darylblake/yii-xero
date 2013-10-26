<?php
/*
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *
 */
class XeAddress extends XeModel{

    const AT_POBOX = "POBOX";

    const AT_STREET = "STREET";

    /**
     * @var string
     */
    public $addressType;

    /**
     * @var string
     */
    public $addressLine1;

    /**
     * @var string
     */
    public $addressLine2;

    /**
     * @var string
     */
    public $addressLine3;

    /**
     * @var string
     */
    public $addressLine4;

    /**
     * @var string
     */
    public $city;

    /**
     * @var string
     */
    public $region;

    /**
     * @var string
     */
    public $postalCode;

    /**
     * @var string
     */
    public $attentionTo;


    /**
     * @return array Validation Rules
     */
    public function rules()
    {
        return array(
          array('addressType', 'in', 'range'=>array(self::AT_POBOX, self::AT_STREET)),
        );
    }

}