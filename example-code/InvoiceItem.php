<?php

/**
 * Parent class for Sales + Purchase Invoice Items
 * This is the model class for table "tbl_invoice_item".
 *
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *
 *
 * The followings are the available columns in table 'tbl_invoice_item':
 * @property integer $fk_job_id  composite primary key
 * @property integer $fk_invoice_id composite primary key
 * @property double $commission
 * @property double $amount
 * @property float $vat_rate vat rate at the time the invoice was raised
 * @property integer $status
 * @property string $description
 * @property integer $created_ts
 * @property integer $modified_ts
 * @property string $type
 * 
 * Virtual properties
 * @property float vatRate
 *
 * The followings are the available model relations:
 * @property Job $job
 */
class InvoiceItem extends ActiveRecord
{ 
          
        
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return BillingItem the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'tbl_invoice_item';
	}

	/**
	 * None atm - should be no mass update
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array();
	}
        
        
     
	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'job' => array(self::BELONGS_TO, 'Job', 'fk_job_id'),                      
			'invoice' => array(self::BELONGS_TO, 'Invoice', 'fk_invoice_id'),		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'fk_job_id' => 'Job',
			'amount' => 'Amount',
			'commission' => 'Commission',
			'status' => 'Status',
			'description' => 'Description',
			'netTotal'=> 'Net Amount',
			'vatTotal'=> 'VAT',
			'grossTotal'=> 'SubTotal',
			
		);
	}
	
	/**
	 * Set amounts to money format
	 */
	public function afterFind()
	{
		$this->amount = Util::formatMoney($this->amount);
		$this->commission = Util::formatMoney($this->commission);
		
		return parent::afterFind();
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;
		
		$criteria->compare('fk_invoice_id',$this->fk_invoice_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	
	/**
	 * Fixes vat rate and vat amount
	 */
	public function fixVat()
	{
		if (!isset($this->vat_rate))
		    {
			    $this->vat_rate=Yii::app()->params['standardVatRate'];
			    $this->vat_amount=$this->vatAmount;			    
		    }	
	}
        
	/**
	 * Gets the net total for the line
	 * @return float
	 */
	public function getNetTotal()
	{
		return $this->amount + $this->commission;
	}
	
	
	/**
	 * Gets the gross (vat inclusive) total for the line
	 * @return float
	 */
	public function getGrossTotal()
	{
		return Util::formatMoney($this->netTotal + $this->vatAmount);
	}
	
	
	public function getVatAmount()
	{
		if (isset($this->vat_amount))  // if there is a vat amount set, use it
			$vat =  $this->vat_amount;
		elseif($this->job->lsp->organisation->vat_registered) //same for both sales & purchase - vat on everything
			$vat = $this->netTotal * $this->vatRate;
		elseif('SalesInvoice'==get_class($this->invoice))  //sales invoices - vat only on commission if supplier not vat reg
			$vat = $this->commission * $this->vatRate;
		elseif('PurchaseInvoice'==get_class($this->invoice)) //purchase invoices, no vat
			$vat = 0;
		
		return Util::formatMoney($vat);
	}
	
	/**
	 * Gets the vat rate - either from config file or database if set
	 * @return float
	 */
	public function getVatRate()
	{
		if (!is_null($this->vat_rate))
			$vat = $this->vat_rate;
		else 
			$vat = Yii::app()->params['standardVatRate'];
		
		return $vat;
	}
	
	/**
	 * Fixes the vat rate to the current vat rate
	 * used for saving the rate when invoice is raised
	 * @param float rate
	 */
	public function setVatRate($rate=null)
	{
		if (!is_null($rate))
			$this->vat_rate = $rate;
		else 
			$this->vat_rate = Yii::app()->params['standardVatRate'];
	}


    /**
     * Gets a Xero LineItem object for this InvoiceItem
     * @param $xeroAccountCode int The Xero Account Code for the Item
     * @return XeLineItem
     */
    public function generateXeroLineItem($xeroAccountCode)
    {
        $xItem = new XeLineItem();
        $xItem->description = $this->description;
        $xItem->quantity = 1;
        $xItem->unitAmount = $this->amount;
        $xItem->accountCode = $xeroAccountCode;

        return $xItem;
    }
	
	
	
        
   
}