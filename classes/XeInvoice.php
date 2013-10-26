<?php
/**
 * Class XeInvoice
 *
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 * @link http://developer.xero.com/api/invoices/
 *
 * TODO - add save lineitems
 *
 *
 */

class XeInvoice extends XeRecord {

    /**
     * Payable Invoice
     */
    const TYPE_PAYABLE = "ACCPAY";

    /**
     * Receivable Invoice
     */
    const TYPE_RECEIVABLE = "ACCREC";

    /**
     * Status Draft
     */
    const ST_DRAFT="DRAFT";

    /**
     * Status Submitted
     */
    const ST_SUBMITTED="SUBMITTED";

    /**
     * Status Authorised
     */
    const ST_AUTHORISED="AUTHORISED";

    /**
     * Status Deleted
     */
    const ST_DELETED = "DELETED";


    /**
     * @var string
     */
    public $invoiceID;



    /**
     *
     * @var string
     */
    public $type;

    /**
     * @var XeContact
     */
    public $contact;

    /**
     * Array of Lineitems
     * @var array
     */
    public $lineItems;

    /**
     * @var string
     */
    public $date;

    /**
     * @var string
     */
    public $dueDate;

    /**
     * @var string
     */
    public $lineAmountTypes;

    /**
     * @var string
     */
    public $invoiceNumber;

    /**
     * @var string
     */
    public $reference;

    /**
     * @var string
     */
    public $brandingThemeId;

    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $currencyCode;


    /**
     * @var float
     */
    public $currencyRate;


    /**
     * @var string
     */
    public $status=self::ST_DRAFT;

    /**
     * @var boolean
     */
    public $sentToContact;

    /**
     * @var float
     */
    public $subTotal;

    /**
     * @var float
     */
    public $total;

    /**
     * @var float
     */
    public $totalTax;

    /**
     * @var boolean
     */
    public $hasAttachments;

    /**
     * @var array
     */
    public $payments;

    /**
     * @var float
     */
    public $amountDue;

    /**
     * @var float
     */
    public $amountPaid;

    /**
     * @var float
     */
    public $amountCredited;

    /**
     * @var string
     */
    public $updatedDateUTC;

    /**
     * @var array
     */
    public $creditNotes;

    /**
     * @var float
     */
    public $totalDiscount;

    /**
     * @param string $type Invoice Type
     * @param string $scenario
     * @throws CException
     */
    public function __construct($type=null, $scenario = '')
    {
        if($type)
        {
            if(!in_array($type, array(self::TYPE_PAYABLE, self::TYPE_RECEIVABLE)))
                throw new CException(Yii::t('yii-xero', 'Invalid Type {type}', array('{type}'=> $type)));

            $this->type = $type;
        }

        parent::__construct($scenario);
    }

    /**
     * @return array
     */
    public function collections()
    {
        return array(
            'lineItems' => 'XeLineItem',
            'payments'=>'XePayment',
            'creditNotes' => 'XeCreditNote',
        );
    }

    /**
     * Validation rules
     * @return array
     * TODO add for certain scenarios
     */
    public function rules()
    {
        return array(
            array('type', 'required'),
            array('type', 'in', 'range'=>array(self::TYPE_PAYABLE, self::TYPE_RECEIVABLE)), //invoice types
            array('lineAmountTypes', 'in', 'range'=>array('Exclusive', 'Inclusive', 'NoTax')),
            array('status', 'in', 'range'=> array(self::ST_AUTHORISED, self::ST_DRAFT, self::ST_SUBMITTED)), //invoice statuses



        );
    }



    /**
     * Gets the current invoice ID
     * @return string
     */
    public function getId(){
        return $this->invoiceID;
    }


    /**
     * Adds a lineitem with the min. required info.
     * @param $description String
     * @param $quantity Float
     * @param $unitAmount Float
     * @param $accountCode Int
     */
    public function addLineItem($description, $quantity, $unitAmount, $accountCode)
    {
        $item = new XeLineItem();
        $item->description = $description;
        $item->quantity = $quantity;
        $item->unitAmount = $unitAmount;
        $item->accountCode = $accountCode;

        $this->lineItems->add($item);
    }







}