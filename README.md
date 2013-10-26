yii-xero
========

Extension for the Yii Framework to link apps up to the Xero accounting system.  

There is a model class (extending XeModel) for each entity in the Xero System, e.g. Contact, Invoice etc.

Currently only Invoices & Contacts are fully coded, and the extension only supports 'private' app types.


To use, add the component to your config file as follows:


    'xero' => array(
      'class' => 'ext.yii-xero.components.Xero',
      'appType' => 'private',
       'oAuthCallback' => 'http://localhost/xero/xero/auth', //oauth callback field - not needed for private apps

        'signatures' =>  array(
        // local
        'consumer_key' => (consumer key),
        'shared_secret' => (shared secret),
        //certificates
        'rsa_private_key' => (private key),
        'rsa_public_key'	=> (public key),
    )),

Here is an example of how to create a contact using the component:

    
    $contact = new XeContact();

    //add org details first
    $contact->contactNumber = $this->id;
    $contact->name = $this->org_name;
    $contact->taxNumber = $this->vat_number;

    //add address
    $address = new XeAddress();
    $address->addressType = XeAddress::AT_POBOX; //billing address
    $address->addressLine1 = $user->address1;
    $address->addressLine1 = $user->address1;
    $address->addressLine2 = $user->address2;
    $address->city = $user->town;
    $address->region = $user->county;
    $address->postalCode = $user->postcode;

    $contact->addresses->add($address);

    //add phone if present, splitting into area code and number
    $phone = new XePhone();

    $phoneList = explode(' ', $user->phone_number, 2);
    
    if(isset($phoneList[1]))
    {
      $phone->phoneCountryCode = '+44';
      $phone->phoneAreaCode = $phoneList[0];
      $phone->phoneNumber = $phoneList[1];
      $contact->phones->add($phone);
    }
    elseif(isset($phoneList[0]))
    {
        $phone->phoneCountryCode = '+43';
        $phone->phoneNumber = $phoneList[0];
        $contact->phones->add($phone);
    }

    //add user details
    $contact->firstName = $user->first_name;
    $contact->lastName = $user->last_name;
    $contact->emailAddress = $user->email;
    
    $contact->save()
    
    
You can retrieve an existing contact using:

    $c = XeContact::model()->retrieve($xeroid);
    
    
## License ##

Copyright (c) 2013, Iain Gray (with relevant code copyright to JR conlin, Chris Santala & Ronan Quirke)
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
  notice, this list of conditions and the following disclaimer in the
  documentation and/or other materials provided with the distribution.
* Neither the name of ITG Associates Ltd. nor the names of its
  contributors may be used to endorse or promote products derived from this
  software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
