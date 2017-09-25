# Recombee API Client

A PHP client for easy use of the [Recombee](https://www.recombee.com/) recommendation API.

If you don't have an account at Recombee yet, you can create a free account [here](https://www.recombee.com/).

Documentation of the API can be found at [docs.recombee.com](https://docs.recombee.com/).

## Installation

The best way to install the client is through dependency manager [Composer](https://getcomposer.org/):

```
composer require recombee/php-api-client
```
or
```
{
    "require": {
        "recombee/php-api-client": "^1.5.0"
    }
}
```

## Examples

### Basic example
```php
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Ex;

$client = new Client('client-test', 'jGGQ6ZKa8rQ1zTAyxTc0EMn55YPF7FJLUtaMLhbsGxmvwxgTwXYqmUk5xVZFw98L');

const NUM = 100;
const PROBABILITY_PURCHASED = 0.1;

try
{
    // Generate some random purchases of items by users
    $purchase_requests = array();
    for($i=0; $i < NUM; $i++) {
        for($j=0; $j < NUM; $j++) {
            if(mt_rand() / mt_getrandmax() < PROBABILITY_PURCHASED) {

                $request = new Reqs\AddPurchase("user-{$i}", "item-{$j}",
                    ['cascadeCreate' => true] // Use cascadeCreate to create the
                                              // yet non-existing users and items
                );
                array_push($purchase_requests, $request);
            }
        }
    }
    echo "Send purchases\n";
    $res = $client->send(new Reqs\Batch($purchase_requests)); //Use Batch for faster processing of larger data

    // Get 5 recommendations for user 'user-25'
    $recommended = $client->send(new Reqs\UserBasedRecommendation('user-25', 5));

    echo 'Recommended items: ' . implode(',',$recommended) . "\n";
}
catch(Ex\ApiException $e)
{
    //use fallback
}
```

### Using property values
```php
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Ex;

const NUM = 100;
const PROBABILITY_PURCHASED = 0.1;

$client = new Client('client-test', 'jGGQ6ZKa8rQ1zTAyxTc0EMn55YPF7FJLUtaMLhbsGxmvwxgTwXYqmUk5xVZFw98L');
$client->send(new Reqs\ResetDatabase()); //Clear everything from the database

/*
We will use computers as items in this example
Computers have three properties 
  - price (floating point number)
  - number of processor cores (integer number)
  - description (string)
*/

// Add properties of items
$client->send(new Reqs\AddItemProperty('price', 'double'));
$client->send(new Reqs\AddItemProperty('num-cores', 'int'));
$client->send(new Reqs\AddItemProperty('description', 'string'));
$client->send(new Reqs\AddItemProperty('in_stock_from', 'timestamp'));

# Prepare requests for setting a catalog of computers
$requests = array();
for($i=0; $i<NUM; $i++)
{
    $r = new Reqs\SetItemValues(
      "computer-{$i}", //itemId
      //values:
      [ 
        'price' => rand(15000, 25000),
        'num-cores' => rand(1, 8),
        'description' => 'Great computer',
        'in_stock_from' => new DateTime('NOW')
      ],
      //optional parameters:
      ['cascadeCreate' => true] // Use cascadeCreate for creating item
                                 // with given itemId, if it doesn't exist]
    );
    array_push($requests, $r);
}

// Send catalog to the recommender system
$result =  $client->send(new Reqs\Batch($requests));
var_dump($result);

// Generate some random purchases of items by users
$requests = array();

for($i=0; $i<NUM; $i++)
    for($j=0; $j<NUM; $j++)
        if(mt_rand() / mt_getrandmax() < PROBABILITY_PURCHASED)
        {
           $r = new Reqs\AddPurchase("user-{$i}", "computer-{$j}", ['cascadeCreate' => true]);
           array_push($requests, $r);
        }

// Send purchases to the recommender system
$client->send(new Reqs\Batch($requests));

// Get 5 items related to item computer-6. Personalize them for user-42, who is currently viewing that item.
$recommended = $client->send(new Reqs\ItemBasedRecommendation('computer-6', 5, ['targetUserId' => 'user-42']));
echo 'Recommended items: ' . implode(',',$recommended) . "\n";

// Recommend only computers that have at least 3 cores
$recommended = $client->send(
  new Reqs\ItemBasedRecommendation('computer-6', 5, ['targetUserId' => 'user-42', 'filter' => "'num-cores'>=3"])
  );
echo 'Recommended items with at least 3 processor cores: ' . implode(',',$recommended) . "\n";

// Recommend only items that are more expensive then currently viewed item computer-6 (up-sell)
$recommended = $client->send(
  new Reqs\ItemBasedRecommendation('computer-6', 5,
    ['targetUserId' => 'user-42', 'filter' => "'price' > context_item[\"price\"]"])
  );
echo 'Recommended up-sell items: ' . implode(',',$recommended) . "\n"
```

### Exception handling

For the sake of brevity, the above examples omit exception handling. However, various exceptions can occur while processing request, for example because of adding an already existing item, submitting interaction of nonexistent user or because of timeout.

We are doing our best to provide the fastest and most reliable service, but production-level applications must implement a fallback solution since errors can always happen. The fallback might be, for example, showing the most popular items from the current category, or not displaying recommendations at all.

Example:
```php
use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Ex;

try
{
    $recommended = $client->send(
      new Reqs\ItemBasedRecommendation('computer-6', 5,
        ['targetUserId' => 'user-42', 'filter' => "'price' > context_item[\"price\"]"])
    );
}
catch(Ex\ApiTimeoutException $e)
{
    //Handle timeout => use fallback
}
catch(Ex\ResponseException $e)
{
    //Handle errorneous request => use fallback
}
catch(Ex\ApiException $e)
{
    //ApiException is parent of both ResponseException and ApiTimeoutException
}
```
