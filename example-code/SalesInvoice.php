<?php
/**
 * Class SalesInvoice
 *
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *
 */

class SalesInvoice extends Invoice{
    
    static function model($className=__CLASS__) 
	{
		return parent::model($className);
	}
    
    public function defaultScope() {
        
        return array(
				'condition'=>"t.type='salesinvoice'",
		);
        
    }


    /**
     * Gets a Xe Invoice Object
     * @param string $linkUrl - the base Url for the Xero "URL" field
     * @return XeInvoice
     */
    public function getXeroInvoice($linkUrl=null)
    {
        $xi = $this->generateXeroInvoice('sales');

        if ($linkUrl)
            $xi->url = $linkUrl;

        return $xi;
    }

    /**
     * Saves the xero invoice and updates this record with the id
     * @param string $linkUrl - the base Url for the Xero "URL" field
     * @return bool
     */
    public function saveXeroInvoice($linkUrl=null)
    {
        return $this->saveXeInvoice($this->getXeroInvoice($linkUrl));
    }


    /**
	 * Overridden function to set database field for class
	 */
	protected function beforeSave() {
		if(empty($this->type))
			$this->type = 'salesinvoice';
		return parent::beforeSave();
	}
    
    
}

?>
