# steedy-client-api-php
Steedy PHP Client API

You can view the complete API documentation [here](https://apidocs.1steedy.fr/). (only in french atm)
Feel free to get in touch for anything you'd like to know.


## Install
Via Composer:

``` bash
$ composer require steedy/steedy-client-api-php
```

## Usage

1. [Initialize](#initialize)
2. [Create a delivery quote](#create-a-delivery-quote)
3. [Validate a delivery quote](#validate-a-delivery-quote)
4. [Get a delivery status](#get-a-delivery-status)
5. [Cancel a delivery](#cancel-a-delivery)

### Initialize

```php
$client_id = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'; // contact us at api@1steedy.fr to initiate your API access
$client_secret = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$api = new \Steedy\API($client_id, $client_secret);
$api->auth();
```

### Create a Delivery Quote
Before you initiate your delivery, you must create a quote for it. 

**Notice**: You can create a quote with a minimal set of parameters, but we recommend you to set as many information 
as you can in order to ensure the delivery goes well.

```php
$delivery_quote = $api->post('delivery/create', array(
    'origin_name' => 'Thomas Rambaud',
    'origin_tel' => '0600000000',
    'origin_email' => 'thomas@1steedy.fr',
    'origin_address' => 'Rue de Rivoli, 75001 Paris',
    'origin_commentary' => 'iMac 27 to deliver',
    'destinations' => array(
        array(
            'name' => 'Pierre Guerin',
            'address' => '16 avenue Reille, 75014 Paris',
            'tel' => '0600000000',
            'email' => 'pierre@1steedy.fr',
        )
    )
    'delivery_size' => 3
));
```

##### With scheduled pickup

More information about delivery scheduling [on our complete documentation](https://apidocs.1steedy.fr/#tag/Planification).

```php
$delivery_quote = $api->post('delivery/create', array(
    'origin_name' => 'Thomas Rambaud',
    'origin_tel' => '0600000000',
    'origin_email' => 'thomas@1steedy.fr',
    'origin_address' => 'Rue de Rivoli, 75001 Paris',
    'origin_commentary' => 'iMac 27 to deliver',
    'destinations' => array(
        array(
            'name' => 'Pierre Guerin',
            'address' => '16 avenue Reille, 75014 Paris',
            'tel' => '0600000000',
            'email' => 'pierre@1steedy.fr',
        )
    )
    'delivery_size' => 3,
    'schedule_at' => 1511710610
));
```

##### Multiple dropoffs

```php
$delivery_quote = $api->post('delivery/create', array(
    'origin_name' => 'Thomas Rambaud',
    'origin_tel' => '0600000000',
    'origin_email' => 'thomas@1steedy.fr',
    'origin_address' => 'Rue de Rivoli, 75001 Paris',
    'origin_commentary' => 'iMac 27 to deliver',
    'destinations' => array(
        array(
            'name' => 'Pierre Guerin',
            'address' => '16 avenue Reille, 75014 Paris',
            'tel' => '0600000000',
            'email' => 'pierre@1steedy.fr',
        ),
        array(
            'name' => 'Réginald Cassius',
            'address' => '14, villa des Coteaux, 93340 Le Raincy',
            'tel' => '0600000000',
            'email' => 'reginald@1steedy.fr',
            'commentary' => 'Door code: XXXX'
        )
    )
    'delivery_size' => 3
));
// print $delivery_quote['quote_id'] ==> 123
```

### Validate a delivery quote

Once you successfully created a Delivery Quote, you can validate it. Validating a Quote will trigger its charge process.

```php
$validate_result = $api->post('delivery/validate', array(
    'quote_id' => $quote_id
));
// print $validate_result['order_id'] ==> 123
```

### Get a delivery status

You can check your delivery status by querying the `/delivery/follow` endpoint. 
Once the delivery is accepted by a Steedy, you will be able to get geocoding informations about the Steedy doing the delivery.

```php
$follow_result = $api->get('/delivery/follow', array(
    'order_id' => $order_id
));
```
### Cancel a delivery

If you delivery has not been accepted by a steedy already, you can still cancel it and get refund.
Post to `/delivery/cancel` to cancel your delivery order.

```php
$cancel_result = $api->post('delivery/cancel', array(
    'order_id' => $order_id
));
```