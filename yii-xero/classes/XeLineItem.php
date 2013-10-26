<?php
/*
 * Model for LineItems
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 */ 

class XeLineItem extends XeModel{



     /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @var float
     */
    public $unitAmount;

    /**
     * @var int
     */
    public $accountCode;

    /*
     * string
     */
    public $itemCode;

    /**
     * @var string
     */
    public $taxType;

    /**
     * @var float
     */
    public $taxAmount;

    /**
     * @var float
     */
    public $lineAmount;

    /**
     * @var array
     */
    public $tracking;


    public function rules()
    {
        return array(
          array('description, quantity, unitAmount, accountCode', 'required'),
          array('quantity', 'numerical', 'min'=>1),

        );

    }



    public function collections()
    {
        return array(
            'tracking' => 'XeTrackingCategory',
        );
    }


}