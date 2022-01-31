<?php

/**
 * Restful Abstract
 *
 * PHP version 8
 *
 * @package Cathedral\Db
 * @author  Philip Michael Raab <philip@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Db\Controller;

use Cathedral\Db\Enum\Code;
use DBLayer\Entity\User;
use Inane\Util\ArrayUtil;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractRestfulController as LaminasAbstractRestfulController;
use Laminas\View\Model\JsonModel;
use Throwable;

use function array_key_exists;
use function array_merge;
use function array_pop;
use function array_shift;
use function array_walk;
use function ceil;
use function count;
use function explode;
use function func_get_args;
use function get_called_class;
use function get_class;
use function getenv;
use function in_array;
use function is_array;
use function is_null;
use function is_string;
use function json_encode;
use function method_exists;
use function property_exists;
use function str_replace;
use function strtolower;
use function ucfirst;

use Inane\Config\{
    ConfigAwareInterface,
    ConfigAwareTrait
};
use Inane\Option\{
    IpTrait,
    LogTrait
};
use Laminas\Db\TableGateway\{
    Feature\GlobalAdapterFeature,
    AbstractTableGateway
};
use Laminas\Log\{
    Writer\Db,
    Writer\Stream,
    Logger
};

/**
 * Restful Abstract
 *
 * @version 0.6.0
 *
 * @package Cathedral\Db
 *
 * - create → POST /collection
 * - read → GET /collection[/id]
 * - update → PUT /collection/id
 * - patch → PATCH /collection/id
 * - delete → DELETE /collection/id
 *
 * @method void customQueryOptions(&$options, $params)
 * @method void customResponseOptions(&$json)
 * @method void getListPost($data)
 * @method void createPre(&$data)
 */
abstract class AbstractRestfulController extends LaminasAbstractRestfulController implements
    ConfigAwareInterface,
    RestfulControllerInterface {

    use ConfigAwareTrait;
    use IpTrait;
    use LogTrait;

    /**
     * Things to overwritten in table class to customize behaviour.
     */

    /**
     * Special fields for processing
     *
     * Only JSON & NUMBER fields need be listing here
     *
     * 'id' => self::CONTENT_TYPE_NUMBER,
     * 'extra' => self::CONTENT_TYPE_JSON,
     *
     * @var array field => type
     */
    protected array $_processFields = [];

    /**
     * Auth Actions
     *
     * To change authentication requirements
     *  - true => auth required
     *  - false => guest access
     *
     * @var array action => auth
     */
    protected array $actionsAuth = [
        'get' => false,
        'getList' => false,
        'create' => true,
        'patch' => true,
        'update' => true,
        'replaceList' => true,
        'delete' => true,
    ];

    /**
     * Methods to adjust values
     * Add them to your tables rest controller to pre & post tweak data
     */

    // /**
    //  * Gets the query string parameters for pagination
    //  *
    //  * @param array $options
    //  * @param array $params
    //  *
    //  * @return void
    //  */
    // public function customQueryOptions(&$options, $params): void {
    //     if (isset($params['query_key'])) $options['where']['field'] = intval($params['query_key']);
    // }

    // /**
    //  * Gets the query string parameters for pagination
    //  *
    //  * @param mixed $json
    //  * @return void
    //  */
    // public function customResponseOptions(&$json): void {
    //     /** @var JsonModel $json */
    //     $payload = $json->getVariable('payload');

    //     for ($i = 0; $i < \count($payload); $i++) // loop over payload

    //     $json->setVariable('extra', ['eg' => 'this']);
    //     $json->setVariable('payload', $payload);
    // }

    // /**
    //  * Modify the resultset
    //  *
    //  * @param mixed $data
    //  * @return void
    //  */
    // public function getListPost($data): void {
    //     $data->buffer();
    //     foreach($data as $d) // loop over results
    // }

    // /**
    //  * Check data before creating record
    //  *
    //  * @param mixed $data
    //  * @return null|string error message
    //  */
    // public function createPre(&$data): ?string {
    //     // check data
    // return 'error message';
    // }

    /**
     * Things that should not need to be overwritten.
     */

    /**
     * @var string table name
     */
    protected string $_table;

    /**
     * @var string table class
     */
    protected string $_class;

    /**
     * @var \Laminas\Db\TableGateway\AbstractTableGateway dataTable
     */
    protected AbstractTableGateway $_dataTable;

    /**
     *
     * @var int log priority
     */
    protected int $_defaultLogPriority = Logger::DEBUG;
    // protected $_defaultLogPriority = \Laminas\Log\Logger::ERR;

    /**
     * @var \Laminas\Log\Logger the logger
     */
    protected Logger $_logger;

    /**
     * @var \Laminas\Log\Logger the database logger
     */
    protected Logger $_loggerDb;

    /**
     * True for collections
     *
     * @var bool isCollection
     */
    protected bool $_isCollection = false;

    /**
     * @var array
     */
    protected array $postGetList = [];

    /**
     * Returns the values passed to the function
     *
     * @param mixed $params
     *
     * @return array
     */
    protected function bundleArguments($params) {
        return count($params) == 1 ? array_combine([(is_array($params[0]) ? 'data' : 'id')], $params) : array_combine(['id', 'data'], $params);
    }

    /**
     * Checks and runs methods that modify the data
     *
     * Used in:
     * createResponse
     * getList
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return null|string
     * @since 0.5.0
     *
     */
    protected function callCustomProcess(string $method, array $arguments): ?string {
        if (method_exists($this, $method)) return $this->{$method}(...$arguments);
        return null;
    }

    /**
     * Create JSON response
     *
     * @param string|array $payload
     *          - data returned by query
     * @param Code         $code
     *          - response code
     * @param string       $extra_message
     *          - message to join to code->message
     * @param array        $extra
     *          - extra metaInfo to return to client
     *
     * @return JsonModel
     */
    protected function createResponse($payload = null, Code $code = Code::SUCCESS, string $extra_message = null, $extra = []): JsonModel {
        if (!is_array($payload)) $payload = [
            $payload,
        ];

        if ($extra_message != null && $extra_message != '') $extra_message = " - $extra_message";

        $defaults = [
            'extra_message' => false,
            'pagination' => false,
        ];

        $options = ArrayUtil::complete($extra, $defaults);

        $json = new JsonModel();
        $json->setVariable('payload', $payload);
        $json->setVariable('success', $code == Code::SUCCESS());

        $json->setVariable('info', [
            'service' => $this->getTable(),
            'result' => $this->_isCollection ? 'collection' : 'entity',
            'count' => $code == Code::SUCCESS() ? ($this->_isCollection ? count($payload) : 1) : 0,
        ]);
        $json->setVariable('time', time());

        foreach ($options as $name => $value) if ($value !== false) $json->setVariable($name, $value);

        if ($code != Code::SUCCESS()) $json->setVariable('error', [
            'code' => $code->getValue(),
            'message' => $code->getDescription() . $extra_message,
        ]);

        $this->callCustomProcess('customResponseOptions', ['json' => $json]);

        return $json;
    }

    /**
     * Check for fields in the element that need special precessing
     *
     * @param mixed $data
     *
     * @return array
     */
    public function processElement($result): array {
        $resultArray = $result->getArrayCopy();

        foreach ($this->_processFields as $field => $type) if (array_key_exists($field, $resultArray)) {
            if ($type == self::CONTENT_TYPE_JSON && $resultArray[$field] !== null && !is_array($resultArray[$field])) $resultArray[$field] = $this->jsonDecode($resultArray[$field]);
            elseif ($type == self::CONTENT_TYPE_NUMBER) $resultArray[$field] = (int)$resultArray[$field];
        }

        return $resultArray;
    }

    /**
     * Loops through resultset passing each result to get processed
     *
     * @param array $data array of elements
     *
     * @return array
     */
    public function processElementList($results): array {
        $resultsArray = [];
        if (count($this->_processFields) > 0) {
            foreach ($results as $result) $resultsArray[] = $this->processElement($result);
            return $resultsArray;
        }
        return $results->toArray();
    }

    /**
     * Convert json fields back to string before saving to db
     *
     * @since 0.6.0
     *
     * @param array $data
     *
     * @return array
     */
    protected function jsonEncodeColumns(array $data): array {
        foreach ($this->_processFields as $field => $type) if ($type == self::CONTENT_TYPE_JSON) {
            if (array_key_exists($field, $data) && !is_string($data[$field])) {
                $data[$field] = json_encode($data[$field], JSON_NUMERIC_CHECK);
            }
        }
        return $data;
    }

    /**
     * Checks if auth needed
     *
     * @param string $function
     *
     * @return bool
     */
    protected function validateAccess(string $function): bool {
        $defaults = [
            'get' => false,
            'getList' => false,
            'create' => true,
            'patch' => true,
            'update' => true,
            'replaceList' => true,
            'delete' => true,
        ];

        // ArrayUtil::merge($this->actionsAuth, $defaults);
        ArrayUtil::complete($this->actionsAuth, $defaults);

        if (array_key_exists($function, $this->actionsAuth)) return $this->actionsAuth[$function];

        return false;
    }

    /**
     * Set Value for property
     *
     * Checks that the field is valid for the object before setting the value
     *
     * @param array  $data  array with all valid fields
     * @param string $field field to update
     * @param mixed  $value new value
     *
     * @return bool true if field found and updated
     */
    protected function setFieldValue(array &$data, string $field, $value): bool {
        if (property_exists(get_class($this->getEntity()), $field)) if (!array_key_exists($field, $data)) {
            $data[$field] = $value;
            return true;
        }

        return false;
    }

    /**
     * Gets the query string parameters for pagination
     *
     * @return array
     */
    protected function parseQueryOptions(): array {
        $options = [
            'where' => [],
            'state' => [1, 2],
        ];

        $params = $this->params()->fromQuery();
        $columns = $this->getDataTable()->getColumns();

        foreach ($params as $key => $value) if (in_array($key, $columns)) $options['where'][$key] = $value;

        if (array_key_exists('page', $params)) {
            $options['page'] = (int)$params['page'];
            if ($options['page'] < 1) $options['page'] = 1;

            if ($params['pagesize']) $options['pagesize'] = (int)$params['pagesize'];
            else $options['pagesize'] = (int)getenv('REST_PAGESIZE') ?: $this::PAGINATION_PAGESIZE;
        }

        // option => ids: used to send a comma separated list of id values to retrieve
        if (array_key_exists('ids', $params)) $options['where']['id'] = explode(',', $params['ids']);

        if (array_key_exists('state', $params) && $params['state'] !== null) $options['state'][] = (int)$params['state'];

        $this->callCustomProcess('customQueryOptions', ['options' => $options, 'params' => $params]);
        return $options;
    }

    /**
     * Limit to user data
     *
     * @param       $entity
     * @param array $where
     *
     * @return array
     */
    protected function updateWhere($entity, array &$where = []): array {
        if (property_exists(get_class($entity), 'fk_users')) {
            /* @var $identity User */
            $identity = $this->identity();

            // $where = array_merge([
            //  'fk_users' => [$identity->getId()]
            // ], $where);
            $where['fk_users'] = $identity->getId();
        }
        return $where;
    }

    /**
     * Validate type of id field
     *
     * Check if id is numeric and converts it if necessary.
     *
     * @param $id
     *
     * @return array
     */
    protected function validateIdType(mixed $id): mixed {
        return $id = is_numeric($id) ? (int)$id : $id;
    }

    /**
     * Create a new resource
     *
     * POST /collection
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function create($data) {
        $action = 'create';
        $this->log()->debug($action, []);

        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_CREATE()->getDescription());

        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        /* @var $e User */
        $e = $this->getEntity();

        $this->setFieldValue($data, 'created', date('Y-m-d H:i:s'));
        $this->setFieldValue($data, 'fk_users', $userSession);

        $response = $this->callCustomProcess($action . 'Pre', ['data' => $data]);

        if (!is_null($response)) return $this->createResponse($data, Code::USER_TASK_ABORT(), Code::TASK_API_CREATE()->getDescription(), ['abort message' => $response]);

        $e->populate($data, false);
        $e->save();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Delete an existing resource
     *
     * DELETE /collection/id
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function delete($id) {
        $action = 'delete';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_DELETE()->getDescription());

        $id = $this->validateIdType($id);

        $e = $this->getEntity();
        if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_DELETE()->getDescription());

        $e->delete();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse([], Code::SUCCESS());
    }

    /**
     * Return single resource
     *
     * GET /collection/id
     *
     * @param mixed $id
     *
     * @return mixed
     */
    public function get($id) {
        $action = 'get';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_GET()->getDescription());

        $e = $this->getEntity();
        $id = $this->validateIdType($id);

        try {
            if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_GET()->getDescription());
        } catch (Throwable $th) {
            return $this->createResponse($id, Code::RECORD_INVALID(), array_shift(explode(',', array_pop(explode(':', $th->getMessage())))));
        }

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Return list of resources
     *
     * GET /collection
     *
     * @return mixed
     */
    public function getList() {
        $action = 'getList';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_GETLIST()->getDescription());

        $this->_isCollection = true;
        $dt = $this->getDataTable();
        $options = $this->parseQueryOptions();

        $where = [];
        if (array_key_exists('where', $options)) $where = array_merge($options['where'], $where);
        $this->updateWhere($dt->getEntity(), $where);

        if (property_exists(get_class($dt->getEntity()), 'state')) {
            $where = array_merge([
                'state' => $options['state'],
            ], $where);
        }
        unset($options['where']);

        if (!array_key_exists('page', $options)) {
            $resultList = $dt->select($where);
        } else {
            $resultList = $dt->selectPaginated($where);

            $resultList->setCurrentPageNumber($options['page']);
            $resultList->setItemCountPerPage($options['pagesize']);

            $options['items'] = $resultList->getTotalItemCount();
            $options['pages'] = (int)ceil($options['items'] / $options['pagesize']);
        }

        $this->callCustomProcess("{$action}Post", ['data' => $resultList]);

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($where)]));
        return $this->createResponse($this->processElementList($resultList), Code::SUCCESS(), null, ['options' => $where]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param mixed $data
     *          replacement data
     *
     * @see \Laminas\Mvc\Controller\AbstractRestfulController::replaceList()
     */
    public function replaceList($data) {
        $action = 'replaceList';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_REPLACELIST()->getDescription());

        $this->_isCollection = true;
        $e = $this->getEntity();

        if (!$e->get($data['id'])) return $this->createResponse($data['id'], Code::RECORD_INVALID(), Code::TASK_API_REPLACELIST()->getDescription());

        $e->exchangeArray($data);
        $e->save();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($data, Code::SUCCESS());
    }

    /**
     * Update an existing resource
     *
     * PUT /collection/id
     *
     * @param mixed $id
     * @param mixed $data
     *
     * @return mixed
     */
    public function update($id, $data) {
        $action = 'update';
        $this->log()->debug($action, []);
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_UPDATE()->getDescription());

        $id = $this->validateIdType($id);

        $e = $this->getEntity();
        // if (!$e->get($id)) {
        //     return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_UPDATE()->getDescription());
        // }

        $this->setFieldValue($data, 'modified', date('Y-m-d H:i:s'));
        $data = $this->jsonEncodeColumns($data);

        $e->exchangeArray($data);
        $this->log()->debug('update-exchange', $data);

        try {
            $e->save();
        } catch (Throwable $th) {
            return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_UPDATE()->getDescription() . "\n" . $th->getMessage());
        }

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Respond to the PATCH method
     *
     * PATCH /collection/id
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param
     *          $id
     * @param
     *          $data
     *
     * @return JsonModel|array
     */
    public function patch($id, $data): JsonModel|array {
        $action = 'patch';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_PATCH()->getDescription());

        $id = $this->validateIdType($id);

        $e = $this->getEntity();
        if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_PATCH()->getDescription());

        $this->setFieldValue($data, 'modified', date('Y-m-d H:i:s'));
        $data = $this->jsonEncodeColumns($data);

        $current = $e->getArrayCopy();
        foreach($data as $key => $value) if (array_key_exists($key, $current)) $e->{$key} = $value;

        $e->save();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Get this class
     *
     * @return string
     */
    public static function whoAmI(): string {
        return get_called_class();
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return mixed
     */
    protected function getClass() {
        if (!isset($this->_class)) {
            $moduleName = explode('\\', $this::class)[0];
            $table = ucfirst($this->getTable());
            $this->_class = '\\' . $moduleName . "\Model\\{$table}" . 'Table';
        }

        return $this->_class;
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return \Cathedral\Db\Model\AbstractModel
     */
    protected function getDataTable() {
        if (!isset($this->_dataTable)) {
            $class = $this->getClass();
            $this->_dataTable = new $class();
        }

        return $this->_dataTable;
    }

    /**
     * Creates and returns an Entity or null if invalid
     *
     * @return Cathedral\Db\Entity\AbstractEntity
     */
    protected function getEntity(): \Cathedral\Db\Entity\AbstractEntity {
        return $this->getDataTable()->getEntity();
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return string
     */
    protected function getTable(): string {
        if (!isset($this->_table)) {
            $table = explode('\\', $this::whoAmI());
            $table = array_pop($table);
            $table = str_replace('Controller', '', $table);
            $this->_table = strtolower($table);
        }

        return $this->_table;
    }

    /**
     * Creates an info array for logging
     *
     * @param string $action
     * @param array|null $data
     *
     * @return array
     */
    protected function getLogData(string $action, ?array $data = []): array {
        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $logData = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => $this->getTable() . ' => ' . $action,
            'function' => $this->getTable() . ' => ' . $action,
            // 'line' => '',
            'class' => $this->getClass(),
            'session' => $userSession,
        ];

        return array_merge($logData, $data);
    }

    /**
     * Return Logger
     *
     * @return Logger
     */
    protected function log(): Logger {
        if (!isset($this->_logger)) {
            // $priority = getenv('LOG_LEVEL') ?: $this->_defaultLogPriority;
            $priority = $this->_defaultLogPriority;
            $logger = new Logger();
            $writer = new Stream('log/data.' . $this->getTable() . '.log');
            // $writer->addFilter(new \Laminas\Log\Filter\Priority($this->_defaultLogPriority));
            // $writer->addFilter(new \Laminas\Log\Filter\Priority($priority));
            $logger->addWriter($writer);

            // $this->_logger = $this->config['log']['logger']($priority, 'dal');
            $this->_logger = $logger;
        }

        return $this->_logger;
    }

    /**
     * Return Logger
     *
     * @return Logger
     */
    protected function logDb(): Logger {
        if (!isset($this->_loggerDb)) {
            $logger = new Logger();
            $dbWriter = new Db(GlobalAdapterFeature::getStaticAdapter(), 'logs');
            $dbWriter->setFormatter(new \Laminas\Log\Formatter\Db('Y-m-d H:i:s'));
            $logger->addWriter($dbWriter);

            $this->_loggerDb = $logger;
        }

        return $this->_loggerDb;
    }
}
