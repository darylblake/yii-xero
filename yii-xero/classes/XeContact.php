<?php
/**
 * Class XeCustomer
 *
 * @author Iain Gray <igray@itgassociates.com>
 * @copyright Copyright &copy; Iain Gray 2013-
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 * @package yii-xero
 *
 */

class XeContact extends XeRecord {


    /**
     * Active
     */
    const STATUS_ACTIVE = 'ACTIVE';

    /**
     * Deleted
     */
    const STATUS_DELETED = 'DELETED';

    /**
     * @var string Xero identifier
     */
    public $contactID;


    /**
     * @var string external identifier
     */
    public $contactNumber;


    /**
     * @var string Current status of a contact
     */
    public $contactStatus;

    /**
     * @var string  Name of contact organisation (max length = 500)
     */
    public $name;

    /**
     * @var string First name of contact person (max length = 255)
     */
    public $firstName;

    /**
     * @var string Last name of contact person (max length = 255)
     */
    public $lastName;

    /**
     * @var string Email address of contact person (umlauts not supported) (max length = 500)
     */
    public $emailAddress;

    /**
     * @var string Skype user name of contact
     */
    public $skypeUserName;

    /**
     * @var string Bank account number of contact
     */
    public $bankAccountDetails;

    /**
     * @var string  Tax number of contact – this is also known as the ABN (Australia), GST Number (New Zealand), VAT Number (UK) or Tax ID Number (US and global) in the Xero UI depending on which regionalized version of Xero you are using (max length = 50)
     */
    public $taxNumber;

    /**
     * @var string Default tax type used for contact on AR invoices
     */
    public $accountsReceivableTaxType;


    /**
     * @var string Default tax type used for contact on AP invoices
     */
    public $accountsPayableTaxType;

    /**
     * @var CTypedList Store certain address types for a contact
     * TODO add address detail as in http://developer.xero.com/api/types/#Addresses
     */
    public $addresses;


    /**
     * @var CTypedList string Store certain phone types for a contact
     * TODO add phone detail as in http://developer.xero.com/api/types/#Phones
     */
    public $phones;


     /**
     * @var string Displays which contact groups a contact is included in
     */
    public $contactGroups;

    /**
     * @var boolean  true or false – Boolean that describes if a contact that has any AP invoices entered against them. Cannot be set via PUT or POST – it is automatically set when an accounts payable invoice is generated against this contact.
     */
    public $isSupplier;

    /**
     * @var boolean  true or false – Boolean that describes if a contact that has any AP invoices entered against them. Cannot be set via PUT or POST – it is automatically set when an accounts payable invoice is generated against this contact.
     */
    public $isCustomer;


    /**
     * @var string true or false – Boolean that describes if a contact that has any AP invoices entered against them. Cannot be set via PUT or POST – it is automatically set when an accounts payable invoice is generated against this contact.
     */
    public $defaultCurrency;

    public function collections()
    {
        return array(
          'addresses' => 'XeAddress',
           'phones'=>'XePhone',
        );
    }


    /**
     * @return string
     */
    public function getId()
    {
        return $this->contactID;
    }


    /**
     * Validation rules for Contact
     * @return array
     */
    public function rules()
    {
        return array(
            array('name, emailAddress', 'length', 'max'=>500),
            array('firstName, lastName', 'length', 'max'=>255),
            array('taxNumber', 'length', 'max'=>50),
            array('emailAddress', 'email'),
            array('name, firstName, lastName, emailAddress, skypeUserName, bankAccountDetails, taxNumber, accountsReceivableTaxType, accountsPayableTaxType, defaultCurrency', 'safe' )
        );

    }

    /**
     * Returns a list of Contacts
     */
    public function listContacts()
    {

        $returnArray = array();

        $xmlObject = simplexml_load_string(Yii::app()->xero->apiGet('contacts'));
        $contacts =  parent::simpleXmltoArray($xmlObject->Contacts)['Contact'];



        foreach ($contacts as $c){
            $contact = new XeContact();
            $contact->loadPropertiesFromArray($c);
            $returnArray[] = $contact;
        }

        return $returnArray;
    }









}