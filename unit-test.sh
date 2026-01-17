php8.4 -d zend.assertions=1 bin/phpunit -d \
  memory_limit=3G \
  --bootstrap vendor/autoload.php \
  --configuration app/phpunit.xml.dist \
  --cache-result \
  --order-by=defects \
  --stop-on-failure
