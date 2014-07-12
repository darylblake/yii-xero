<?php

/**
 * Parent class for SalesInvoice and PurchaseInvoice
 * This is the model class for table "tbl_invoice".
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *
 * The followings are the available columns in table 'tbl_invoice':
 * @property integer $id
 * @property string $xero_id
 * @property string $number
 * @property integer $fk_organisation_id
 * @property integer $status
 * @property integer $created_ts
 * @property integer $modified_ts
 * @property string $type
 *
 * The followings are the available model relations:
 * @property Organisation $organisation
 * 
 * Raises the following events
 * onInvoiceRaised
 */
class Invoice extends ActiveRecord
{
    
        /**
         * Status fields
         */
       const IS_DRAFT = 1;
       const IS_RAISED = 10;
       const IS_PAID = 50;
       
       
       
       
       /**        
        * @var CTypedList of invoice line items
        */
       public $invoiceLines;


       /**
        * Sets the default status to draft
        * 
        * @param string $scenario 
        * @see CActiveRecord::__construct()
        */
       public function __construct($scenario = 'insert') {
      
       $this->invoiceLines = new CTypedList('InvoiceItem');
       
       
           
       parent::__construct($scenario);
       }
    
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Invoice the static model class
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
		return 'tbl_invoice';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('number', 'length', 'max'=>50),
			array('type', 'length', 'max'=>30),
            array('status', 'numerical'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, invoice_number, status, created_ts, modified_ts, type', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'organisation' => array(self::BELONGS_TO, 'Organisation', 'fk_organisation_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'invoice_number' => 'Invoice Number',
			'fk_organisation_id' => 'Fk Organisation',
			'status' => 'Status',
			'type' => 'Type',
		);
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

		$criteria->compare('id',$this->id);
		$criteria->compare('fk_organisation_id',$this->fk_organisation_id);
		$criteria->compare('status',$this->status);
//		$criteria->compare('created_ts',$this->created_ts);
//		$criteria->compare('modified_ts',$this->modified_ts);
		$criteria->compare('type',$this->type,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
     
     /**
      * Adds an item to the invoice
      * @param int $jobId
      */   
     public function addItem($jobId)
     {
         $item  = new InvoiceItem();
         $item->fk_invoice_id = $this->id;
         $item->fk_job_id = $jobId;
         
         $this->invoiceLines->add($item);
         
         return $item;
     }
     
     /**
      * Load items in the invoiceLines array
      */
     protected function afterFind()
     {
         
         if(!$this->isNewRecord)
         {
//             $savedItems = InvoiceItem::model()->findAllByAttributes("fk_invoice_id={$this->id}");
             $savedItems = InvoiceItem::model()->findAllByAttributes(array('fk_invoice_id'=>$this->id));

             foreach ($savedItems as $item)
             {
                 $this->invoiceLines->add($item);
             }
         }
	 
	 //convert date from SQL format
	if (isset($this->tax_date))
		$this->tax_date = date('d-m-Y', strtotime($this->tax_date));
	 
     }
     
    
     
        
     /**
      * Gets the latest draft invoice - if one doesn't exist, creates one.
      * @param int $organisationId 
      * @param string $type the invoice type
      * @return Invoice subclass
      * @throws CException if org Id is invalid
      */   
     public static function getDraftInvoice($organisationId)                
     {
         $class = get_called_class();	 
         
         if (is_null(Organisation::model()->findByPk($organisationId)))
                 throw new CException (Yii::t('Invoice', '{id} is not a valid Organisation Id', array('{id}'=>$organisationId)));
         
         $invoice = $class::model()->findByAttributes(array('fk_organisation_id'=>$organisationId, 'status'=>self::IS_DRAFT/*, 'type'=>  strtolower($class)*/));
         //new invoice required
         if (is_null($invoice))
         {
             
             $invoice = new $class;
             
             $invoice->fk_organisation_id = $organisationId;
             $invoice->invoiceLines = new CTypedList('InvoiceItem');           
             $invoice->status = self::IS_DRAFT;
             
             $invoice->save(false);
         }
         
         return $invoice;             
     }
     
     /**
      * Gets the total inclusive of VAT
      * @return float
      */
     public function getGrossTotal()
     {
	     $total = 0;
	     
	     foreach ($this->invoiceLines as $line)
	     {	     
		     $total = $total + $line->grossTotal;
	     }
	     
	     return Util::formatMoney($total);	
     }
     
     
     /**
      * Gets the last item on the invoice by insertion order
      * @return InvoiceItem, null if none found
      */
     public function getLastItem()
     {
	    $numLines = $this->invoiceLines->count();

	    if (0==$numLines)
		    $lastItem = null;
	    else 
		    $lastItem = $this->invoiceLines->itemAt($numLines-1);

	    return $lastItem;
     }
     
     /**
      * Gets the total for the current invoice net of vat
      * @return float 
      */
     public function getNetTotal()
     {
	     $total = 0;
	     
	     foreach ($this->invoiceLines as $line)
	     {	     
		     $total = $total + $line->netTotal;
	     }
	     
	     return Util::formatMoney($total);	     
     }
     
     /**
      * The status name
      * @return String
      */
     public function getStatusLabel()
     {
	     $statuses = array
	     (
		self::IS_DRAFT =>  'Draft',
		self::IS_RAISED => 'Raised',
		self::IS_PAID => 'Paid',
	     );
	     
	     return $statuses[$this->status];	     
     }
     /**
      * Gets the VAT
      * @return float
      */
     public function getVatTotal()
     {
	     $total = 0;
	     
	     foreach ($this->invoiceLines as $line)
	     {	     
		     $total = $total + $line->vatAmount;
	     }
	     
	     return Util::formatMoney($total);	  
     }
	
     
     /**
      * Raises on InvoiceRaised Event
      * @param CEvent $event 
      */
     public function onInvoiceRaised($event)
     {
	     $this->raiseEvent('onInvoiceRaised', $event);
     }
     
     /**
      * Function to raise all invoices
      * 1. Set all draft invoices to IS_INVOICED
      * 2. Fixes vat rate & amount for all lines
      * 3. Save themn
      * 4. Trigger invoice raised event
      */
     public static function raiseInvoices()
     {
	     $class = get_called_class();
	     
	     $invoiceList = $class::model()->findAllByAttributes(array('status' => self::IS_DRAFT));
	     
	     foreach ($invoiceList as $invoice)
	     {
		     if ($invoice->netTotal>0) //for any invoices with items
		     {
			$invoice->status = self::IS_RAISED;
			$invoice->tax_date = date('d-m-Y');
			
			foreach($invoice->invoiceLines as $line)
			{
				$line->fixVat();
			}
			
			$invoice->save(false);
			$invoice->onInvoiceRaised(new CEvent($invoice));
		     }
	     }
     }


    /**
     * Checks whether or not this invoice has a non-deleted counterpart in Xero.
     *
     *
     * @return bool
     */
    public function xeroInvoiceExists()
    {
        if (!isset($this->xero_id))
            return false;

        $xInvoice = XeInvoice::model()->retrieve($this->xero_id);

        if(is_null($xInvoice)||XeInvoice::ST_DELETED===$xInvoice->status)
            return false;

        else return true;
    }


        
     /********************************************************
      * 
      *         Protected functions
      * 
      ********************************************************/   
    /**
     * Save all line items
     */
    protected function afterSave()
    {
        foreach ($this->invoiceLines as $item)
        {
            try
	    {
		    $item->save(false);
	    }
	    catch (Exception $e)
	    {
		    if (defined(YII_DEBUG))
		    {
			echo ("Problem saving item: Job ID $item->fk_job_id Invoice ID $item->fk_invoice_id");
			echo $e->getMessage();
			return false;
		    }
		    else
		    {
			    throw $e;
		    }
			 
			
	    }
        }
    }
     
    protected function beforeSave() {
    	if(empty($this->type)) {
    		$childClass = strtolower(get_called_class());
    		$this->type = $childClass;
    	}
	
	//set  tax date to sql format
	if (isset($this->tax_date))
		$this->tax_date = date('Y-m-d', strtotime($this->tax_date));
	
    	return parent::beforeSave();
    }
        
        /**
     * We're overriding this method to fill findAll() and similar method result
     * with proper models.
     *
     * @param array $attributes
     * @return Invoice
     */
    protected function instantiate($attributes){
    	switch($attributes['type']){
    		case 'salesinvoice':
    			$class='SalesInvoice';
    			break;
    		case 'purchaseinvoice':
    			$class='PurchaseInvoice';
    			break;
    		default:
    			$class=get_class($this);
    	}
    	$model=new $class(null);
    	return $model;
    }

    /**
     * Generates a Xero Invoice object for the current invoice
     *
     * @param $type
     * @return XeInvoice
     * @throws CException
     */
    protected function generateXeroInvoice($type)
    {
        if($this->status === self::IS_DRAFT)
            throw new CException (Yii::t('Invoices', 'Cannot add this invoice to Xero until it has been raised here.'));

        switch($type)
        {
            case 'purchase':
                $xeInvoiceType = XeInvoice::TYPE_PAYABLE;
                $xeNominal = Yii::app()->params['defaultXeroPurchaseNominal'];
                $xeReferenceField = 'invoiceNumber';
                break;
            case 'sales':
                $xeInvoiceType = XeInvoice::TYPE_RECEIVABLE;
                $xeNominal = Yii::app()->params['defaultXeroSalesNominal'];
                $xeReferenceField = 'reference';
                break;
            default:
                throw new CException(Yii::t('Invoice', 'Invalid invoice type {type}', array('{type}', $type)));
        }


        $xInvoice = new XeInvoice($xeInvoiceType);

        //add contact
        $xInvoice->contact = $this->organisation->xeroInvoiceContact();



        //add line itmes
        foreach($this->invoiceLines as $line)
        {
            $xInvoice->lineItems->add($line->generateXeroLineItem($xeNominal));
        }

        //add other details like dates etc.
        /*$xInvoice->date = $this->tax_date;
        $xInvoice->dueDate = $this->tax_date;*/

        //set the appropriate reference field to match the lingoing field
        $xInvoice->$xeReferenceField = $this->id;


        return $xInvoice;
    }


    /**
     * Saves the Xero Invoice and updates the field
     * @param $xeInvoice XeInvoice
     * @return bool
     */
    protected function saveXeInvoice($xeInvoice)
    {
        $result = $xeInvoice->save();

        if($result)
        {
            $this->xero_id = $xeInvoice->id;
            return ($this->save(false, array('xero_id')));
        }
        else
        {
            return false;
        }
    }


}