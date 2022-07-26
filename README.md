# Cathedral\Db

> $Id$ ($Date$)

Database class for use with builder and its generated code.

## Custom methods

There are a few custom methods you can create to easily modify your data before sending it to client.

- customQueryOptions
  - runs prior to data access
  - lets you modify where clauses
  - check query string values
- customResponseOptions
  - runs last, just prior to sending data
  - has full access to the json response object
- getListPost
  - runs after data fetched from db
- createPre
  - check data before db record gets created


```php
/**
 * Gets the query string parameters for pagination
 *
 * @param array $options
 * @param array $params
 *
 * @return void
 */
public function customQueryOptions(&$options, $params): void {
    if (isset($params['fk_champions'])) $options['where']['fk_champions'] = intval($params['fk_champions']);
}

/**
 * Gets the query string parameters for pagination
 *
 * @param mixed $json
 * @return void
 */
public function customResponseOptions(&$json): void {
    /** @var JsonModel $json */
    $payload = $json->getVariable('payload');

    for ($i = 0; $i < \count($payload); $i++) $payload[$i]['champion'] = $this->_champions[$payload[$i]['fk_champions']];

    $json->setVariable('extra', ['champions' => $this->_champions]);
    $json->setVariable('payload', $payload);
}

/**
 * Modify the resultset
 *
 * @param mixed $data
 * @return void
 */
public function getListPost($data): void {
    $data->buffer();
    foreach($data as $d) $this->_champions[$d->Champion()->id] = $d->Champion()->name;
}

/**
 * Check data before creating record
 *
 * @param mixed $data
 * @return null|string error
 */
public function createPre(&$data): ?string {
    // check data
    return null;
}
    ```

## Auth configurations

...

