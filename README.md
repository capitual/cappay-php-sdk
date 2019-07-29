# CapPay SDK for PHP

This is CapPay's PHP SDK.

With this SDK you are able to start receiving payments in crypto-currencies in a few minutes!

## Logging In

Note that you have to login to the button generator on [CapPay Button Generator](https://cappay.capitual.com/my/button).

You will also need your merchant ID, that can be found [on your Capitual profile](https://my.capitual.com/account).

## Installing

You can either [download the library](http://capgo.gq/phpsdk-dl) or use [Composer](https://getcomposer.org) to install it directly:

```bash
composer require capitual/cappay-php-sdk
```

After it, include the `CapPay.php` file on your project (or Composer's autoload file) and you are ready to start using CapPay.

## Creating a new invoice

In order to send a new invoice to receive a new payment, create a new instance of the Capitual\CapPay class.

```php
$invoice = new \Capitual\CapPay;
```

Then set your merchant ID.

```php
$invoice->merchant = 1234;
```

Set the address of your Capitual wallet that will receive the funds for this payment. **This is not a crypto-wallet address.** This is the address of one of your Capitual wallets. Note that you may choose a wallet of any currency, but if the currency is different from the invoice currency (that we'll set below), an exchange rate will apply. Otherwise, no fees are involved.

```php
$invoice->wallet = 'CAP-XXXXXXXX-XXXXXX-XX';
```

Now it's time to set the payment currency and value. The value is a string. Always use dots (.) for decimals (do not worry about using the correct amount of decimals if this is not relevant for your use case), and not commas.

The currency may be one of the following:

| Code   | Currency  |
|---|---|
| BTC  | Bitcoin  |
| LTC  | Litecoin  |
| DSH | Dash |
| USD | US Dollar |
| EUR | Euro |
| BRL | Brazilian Real |

```php
$invoice->currency = 'USD';
$invoice->value = '100.00';
```

Now, set the payee's email address. Note that the invoice will also be sent to his email.

```php
$invoice->payee = 'johndoe@mailinator.com';
```

If you want, you can also set a human-readable payment description for your customer:

```php
$invoice->description = 'Payment for order 123'; // optional
```

Optionally set expiration date, as a Unix timestamp (UTC timezone).

```php
$invoice->expires = strtotime("+48 hours"); // optional
```

**IPN**: If you want, you can set a public accessible URL that Capitual servers should call once the payment is done. You may include query string variables.

```php
$invoice->ipn = 'https://mysite.com/ipn.php'; // optional
``` 

**That's all!** To create the invoice, just do:

```php
$invoice->create();
```

After the invoice was created, you may retrieve its ID using:

```php
$invoice_id = $invoice->id;
// proceed to save $invoice_id to the database...
```

You may also get its URL using:

```php
$url = $invoice->url;

// or a short link
$url = $invoice->getShortLink();
```

You may redirect or create a link to this URL for your user to be able to pay.

It's also advisable to set a return URL, which your user will be redirected to after the payment (it does not work on the shortlink). To do so, just append a "return_url" query param.

```php
$url = $invoice->url.'?return_url=http://yoursite.com/thanks.php';
```

Note that the user requesting your `return_url` **does not mean the payment is complete**, as crypto transactions require waiting for confirmation time. Delivering the product/service before the confirmation is dangerous and may lead to its loss, as transactions with low fees may never be confirmed. Use IPN (see next article) for checking when the transaction is confirmed.

## IPN - Instant Payment Notification

The URL you set as `ipn` will receive HTTP POST requests (`x-www-form-urlencoded`) with two variables:

| Variable | Description |
|---|---|
| InvoiceID | The invoice ID (as in `$invoice->id`) | 
| Type | Message type |

The message type may be (notice the case):

| Type | Description |
|---|---|
| `Received` | The invoice was paid, but not yet confirmed. <span style="color: orange">It's <b>not</b> yet safe to deliver the product/service. |
| `Paid` | The invoice was paid and confirmed. <span style="color: green">It's now safe to deliver the product/service.</span> |
| `Cancelled` | The invoice was cancelled (this can only be done by the invoice sender) |

In a perfect situation, IPN by itself could be enough, but unfortunately we know that the bad guys are trying to break things all the time, and may discover your IPN URL and spoof the request, making your system to think a payment has been done while it hasn't. Therefore, **you shall not take IPN requests as a reliable source of truth**. Rather, treat it simply as a notification. You should retrieve information about the invoice when you receive a IPN request.

## Retrieving information about an invoice

In order to retrieve information about an invoice, simply create a new instance of the class, passing the invoice ID as argument to the constructor:

```php
$invoice = new \Capitual\CapPay('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX');
```

Now, you may get every property of the class, just like you've just set it.

```php
echo $invoice->currency; // "USD"
echo $invoice->amount; // "100.00"
```

Note that for security reasons it's not possible to retrieve `$invoice->payee` using this method. You should store it on your database after creating the invoice. You may retrieve the invoice payee by logging in to your [Capitual account](https://my.capitual.com), or by implementing the complete [Capitual API](https://my.capitual.com/apps/mine).

You may get the invoice status using:

```php
echo $invoice->status; // "pending", "paid", "canceled" or "expired"
```

For the sake of code readability, the invoice status strings are also available as static values:

```php
if ($invoice->status === \Capitual\CapPay::STATUS_PENDING) {
	// no payment has been received yet
}
elseif ($invoice->status === \Capitual\CapPay::STATUS_PAID) {
	// paid and confirmed
}
elseif ($invoice->status === \Capitual\CapPay::STATUS_CANCELED) {
	// invoice cancelled by its sender
}
elseif ($invoice->status === \Capitual\CapPay::STATUS_EXPIRED) {
	// due date has passed without payment
}
```

