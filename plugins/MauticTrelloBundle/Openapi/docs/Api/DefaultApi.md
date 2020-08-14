# MauticPlugin\MauticTrelloBundle\Openapi\lib\DefaultApi

All URIs are relative to *https://api.trello.com/1*

Method | HTTP request | Description
------------- | ------------- | -------------
[**addCard**](DefaultApi.md#addCard) | **POST** /card | 
[**getBoards**](DefaultApi.md#getBoards) | **GET** /members/me/boards | 
[**getLists**](DefaultApi.md#getLists) | **GET** /boards/{boardId}/lists | 



## addCard

> \MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\Card addCard($newCard)



Creates a new Trello card

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: apiToken
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('token', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('token', 'Bearer');

// Configure API key authorization: appKey
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('key', 'Bearer');


$apiInstance = new MauticPlugin\MauticTrelloBundle\Openapi\lib\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$newCard = new \MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\NewCard(); // \MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\NewCard | Card to be added

try {
    $result = $apiInstance->addCard($newCard);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->addCard: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters


Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **newCard** | [**\MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\NewCard**](../Model/NewCard.md)| Card to be added |

### Return type

[**\MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\Card**](../Model/Card.md)

### Authorization

[apiToken](../../README.md#apiToken), [appKey](../../README.md#appKey)

### HTTP request headers

- **Content-Type**: application/json
- **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints)
[[Back to Model list]](../../README.md#documentation-for-models)
[[Back to README]](../../README.md)


## getBoards

> \MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\TrelloBoard[] getBoards($fields, $filter)



Get all boards the user has access to

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: apiToken
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('token', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('token', 'Bearer');

// Configure API key authorization: appKey
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('key', 'Bearer');


$apiInstance = new MauticPlugin\MauticTrelloBundle\Openapi\lib\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$fields = id,name; // string | 
$filter = open; // string | 

try {
    $result = $apiInstance->getBoards($fields, $filter);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->getBoards: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters


Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **fields** | **string**|  | [optional]
 **filter** | **string**|  | [optional]

### Return type

[**\MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\TrelloBoard[]**](../Model/TrelloBoard.md)

### Authorization

[apiToken](../../README.md#apiToken), [appKey](../../README.md#appKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints)
[[Back to Model list]](../../README.md#documentation-for-models)
[[Back to README]](../../README.md)


## getLists

> \MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\TrelloList[] getLists($boardId, $cards, $filter, $fields)



Get all lists on a board

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure API key authorization: apiToken
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('token', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('token', 'Bearer');

// Configure API key authorization: appKey
$config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKey('key', 'YOUR_API_KEY');
// Uncomment below to setup prefix (e.g. Bearer) for API key, if needed
// $config = MauticPlugin\MauticTrelloBundle\Openapi\lib\Configuration::getDefaultConfiguration()->setApiKeyPrefix('key', 'Bearer');


$apiInstance = new MauticPlugin\MauticTrelloBundle\Openapi\lib\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$boardId = 5e5c1f7d35b240381adccdcb; // string | 
$cards = none; // string | 
$filter = open; // string | 
$fields = id,name,pos; // string | 

try {
    $result = $apiInstance->getLists($boardId, $cards, $filter, $fields);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->getLists: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters


Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **boardId** | **string**|  |
 **cards** | **string**|  | [optional]
 **filter** | **string**|  | [optional]
 **fields** | **string**|  | [optional]

### Return type

[**\MauticPlugin\MauticTrelloBundle\Openapi\lib\Model\TrelloList[]**](../Model/TrelloList.md)

### Authorization

[apiToken](../../README.md#apiToken), [appKey](../../README.md#appKey)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints)
[[Back to Model list]](../../README.md#documentation-for-models)
[[Back to README]](../../README.md)

