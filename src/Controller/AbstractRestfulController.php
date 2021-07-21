<?php
/**
 * Restful Abstract
 * 
 * PHP version 7
 *
 * @package Cathedral\Db
 * @author Philip Michael Raab <philip@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Db\Controller;

use Cathedral\Db\Enum\Code;
use Inane\Config\ConfigAwareInterface;
use Inane\Config\ConfigAwareTrait;
use Laminas\Db\TableGateway\Feature\GlobalAdapterFeature;
use Laminas\Json\Json;
use Laminas\Log\Writer\Db;
use Laminas\Mvc\Controller\AbstractRestfulController as LaminasAbstractRestfulController;
use Laminas\View\Model\JsonModel;

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
use function method_exists;
use function property_exists;
use function str_replace;
use function strtolower;
use function ucfirst;

/**
 * Restful Abstract
 *
 * @version 0.4.0
 */
abstract class AbstractRestfulController extends LaminasAbstractRestfulController implements
    ConfigAwareInterface,
    RestfulControllerInterface {

    use ConfigAwareTrait;
    use \Inane\Option\IpTrait;
    use \Inane\Option\LogTrait;

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
    protected $_processFields = [];

    /**
     * Auth Actions
     * 
     * To change authentication requirements
     *  - true => auth required
     *  - false => guest access
     *
     * @var array action => auth
     */
    protected $actionsAuth = [
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
     */

    /**
     * OVERRIDE: Update Options with custom values
     * 
     * $options['where'] = [] - each entry extends the where clause
     * 
     * @param array $options update with valid options
     * @param array $params query parameters
     * 
     * @return void
     */
    public function customQueryOptions(&$options, $params): void {
    }

    /**
     * OVERRIDE: Modify data before creating record
     * 
     * To halt creation return false
     *
     * @param array $data
     * 
     * @return null|string if string returned creation aborted and string used for error message
     */
    protected function preCreate(array &$data): ?string {
        return null;
    }

    /**
     * Things that should not need to be overwritten.
     */

    /**
     * @var string table name
     */
    protected $_table = '';

    /**
     *
     * @var mixed table class
     */
    protected $_class = null;

    /**
     *
     * @var mixed datatable
     */
    protected $_datatable = null;

    /**
     *
     * @var int log priority
     */
    // protected $_defaultLogPriority = \Laminas\Log\Logger::ERR;
    protected $_defaultLogPriority = \Laminas\Log\Logger::DEBUG;

    /**
     * @var mixed the logger
     */
    protected $_logger = null;

    /**
     * @var mixed the database logger
     */
    protected $_loggerDb = null;

    /**
     * True for collections
     *
     * @var bool isCollection
     */
    protected $_isCollection = false;

    protected $postGetList = [];

    /**
     * Returns the values passed to the function
     * 
     * @param mixed $params 
     * @return array 
     */
    protected function bundleArguments($params) {
        return count($params) == 1 ? array_combine([(is_array($params[0]) ? 'data' : 'id')], $params) : array_combine(['id', 'data'], $params);
    }

    /**
     * Create JSON response
     *
     * @param string|array $payload
     *          - data returned by query
     * @param Code $code
     *          - response code
     * @param string $extra_message
     *          - message to join to code->message
     * @param array $extra
     *          - extra metainfo to return to client
     *
     * @return \Laminas\View\Model\JsonModel
     */
    protected function createResponse($payload = null, Code $code = Code::SUCCESS, string $extra_message = null, $extra = []): JsonModel {
        if (!is_array($payload)) $payload = [
            $payload
        ];

        if ($extra_message != null && $extra_message != '') $extra_message = " - $extra_message";

        $defaults = [
            'extra_message' => false,
            'pagination' => false,
        ];

        $options = \Inane\Util\ArrayUtil::merge($extra, $defaults);

        $json = new JsonModel();
        $json->setVariable('payload', $payload);
        $json->setVariable('success', $code == Code::SUCCESS());

        $json->setVariable('info', [
            'service' => $this->_table,
            'result' => $this->_isCollection ? 'collection' : 'entity',
            'count' => $code == Code::SUCCESS() ? ($this->_isCollection ? count($payload) : 1) : 0,
        ]);
        $json->setVariable('time', time());

        foreach ($options as $name => $value) if ($value !== false) $json->setVariable($name, $value);

        if ($code != Code::SUCCESS()) $json->setVariable('error', [
            'code' => $code->getValue(),
            'message' => $code->getDescription() . $extra_message
        ]);

        return $json;
    }

    /**
     * Check for fields in the element that need special precessing
     *
     * @param mixed $data
     * @return array
     */
    public function processElement($result): array {
        $resultArray = $result->getArrayCopy();

        foreach ($this->_processFields as $field => $type) if (array_key_exists($field, $resultArray)) {
            if ($type == self::CONTENT_TYPE_JSON && $resultArray[$field] !== null && !is_array($resultArray[$field])) $resultArray[$field] = $this->jsonDecode($resultArray[$field]);
            elseif ($type == self::CONTENT_TYPE_NUMBER) $resultArray[$field] = (int) $resultArray[$field];
        }

        return $resultArray;
    }

    /**
     * Loops through resultset passing each result to get processed
     *
     * @param array $data array of elements
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
     * Checks if auth needed
     * 
     * @param string $function 
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

        \Inane\Util\ArrayUtil::merge($this->actionsAuth, $defaults);

        if (array_key_exists($function, $this->actionsAuth)) return $this->actionsAuth[$function];

        return false;
    }

    /**
     * Set Value for property
     * 
     * Checks that the field is valid for the object before setting the value
     *
     * @param array $data array with all valid fields
     * @param string $field field to update
     * @param mixed $value new value
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
            $options['page'] = (int) $params['page'];
            if ($options['page'] < 1) $options['page'] = 1;

            if ($params['pagesize']) $options['pagesize'] = (int) $params['pagesize'];
            else $options['pagesize'] = (int) getenv('REST_PAGESIZE') ?: $this::PAGINATION_PAGESIZE;
        }

        if (array_key_exists('page', $params)) $options['where']['id'] = explode(',', $params['ids']);

        if (array_key_exists('state', $params) && $params['state'] !== null) $options['state'][] = (int) $params['state'];

        if (method_exists($this, 'customQueryOptions'))
        $this->customQueryOptions($options, $params);
        return $options;
    }

    /**
     * Limit to user data
     *
     * @param $entity
     * @param array $where
     * @return array
     */
    protected function updateWhere($entity, array &$where = []): array {
        if (property_exists(get_class($entity), 'fk_users')) {
            /* @var $identity \DBLayer\Entity\User */
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
        return $id = is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Create a new resource
     *
     * @param mixed $data
     * @return mixed
     */
    public function create($data) {
        $action = 'create';
        $this->log()->debug($action, []);

        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_CREATE()->getDescription());

        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        /* @var $e \DBLayer\Entity\User */
        $e = $this->getEntity();

        $this->setFieldValue($data, 'created', date('Y-m-d H:i:s'));
        $this->setFieldValue($data, 'fk_users', $userSession);
        
        $response = $this->preCreate($data);
        if (!is_null($response)) return $this->createResponse($data, Code::USER_TASK_ABORT(), Code::TASK_API_CREATE()->getDescription(), ['abort message' => $response]);

        $e->populate($data, false);
        $e->save();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Delete an existing resource
     *
     * @param mixed $id
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
     * @param mixed $id
     * @return mixed
     */
    public function get($id) {
        $action = 'get';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_GET()->getDescription());

        $e = $this->getEntity();
        $id = $this->validateIdType($id);

        try {
            if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_GET()->getDescription());
        } catch (\Throwable $th) {
            return $this->createResponse($id, Code::RECORD_INVALID(), array_shift(explode(',', array_pop(explode(':', $th->getMessage())))));
        }

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Return list of resources
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
                'state' => $options['state']
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
            $options['pages'] = (int) ceil($options['items'] / $options['pagesize']);
        }

        // $this->postGetList();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($where)]));
        return $this->createResponse($this->processElementList($resultList), Code::SUCCESS(), null, ['options' => $where]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param mixed $data
     *          replacement data
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
     * @param mixed $id
     * @param mixed $data
     * @return mixed
     */
    public function update($id, $data) {
        // \Inane\Debug\Logger::dump($this->getDataTable()->isSequence, 'this->getDataTable()->isSequence', true);
        $action = 'update';
        $this->log()->debug($action, []);
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_UPDATE()->getDescription());

        $id = $this->validateIdType($id);

        $e = $this->getEntity();
        // if (!$e->get($id)) {
        //     return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_UPDATE()->getDescription());
        // }

        $this->setFieldValue($data, 'modified', date('Y-m-d H:i:s'));
        $e->exchangeArray($data);
        $this->log()->debug('update-exchange', $data);

        try {
            $e->save();
        } catch (\Throwable $th) {
            return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_UPDATE()->getDescription() . "\n" . $th->getMessage());
        }

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    /**
     * Respond to the PATCH method
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param
     *          $id
     * @param
     *          $data
     * @return array
     */
    public function patch($id, $data) {
        $action = 'patch';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_PATCH()->getDescription());

        $id = $this->validateIdType($id);

        $e = $this->getEntity();
        if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_PATCH()->getDescription());

        $this->setFieldValue($data, 'modified', date('Y-m-d H:i:s'));
        array_walk($data, function ($item, $key, &$entity) {
            if (array_key_exists($key, $this->_processFields) && $this->_processFields[$key] == self::CONTENT_TYPE_JSON && is_string($item)) {
                if ($item == '') $item = null;
                else $item = JSON::decode($item);
            }
            $entity->$key = $item;
        }, $e);

        $e->save();

        $this->logDb()->info('route:', $this->getLogData($action, ['options' => JSON::encode($this->bundleArguments(func_get_args()))]));
        return $this->createResponse($this->processElement($e), Code::SUCCESS());
    }

    public static function whoAmI() {
        return get_called_class();
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return mixed
     */
    protected function getClass() {
        if (!$this->_class) {
            $moduleName = explode('\\', $this::class)[0];
            $table = ucfirst($this->getTable());
            $this->_class = '\\' . $moduleName . "\Model\\{$table}" . 'Table';
        }

        return $this->_class;
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return mixed
     */
    protected function getDataTable() {
        if (!$this->_datatable) {
            $class = $this->getClass();
            $this->_datatable = new $class();
        }

        return $this->_datatable;
    }

    /**
     * Creates and returns an Entity or null if invalid
     *
     * @return mixed
     */
    protected function getEntity() {
        return $this->getDataTable()->getEntity();
    }

    /**
     * Creates and returns a DataTable or null if invalid
     *
     * @return mixed
     */
    protected function getTable() {
        if (!$this->_table) $this->_table = strtolower(str_replace('Controller', '', array_pop(explode('\\', $this::whoAmI()))));

        return $this->_table;
    }

    protected function getLogData(string $action, ?array $data = []): array {
        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $logData = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => $this->_table . ' => ' . $action,
            'function' => $this->_table . ' => ' . $action,
            // 'line' => '',
            'class' => $this->getClass(),
            'session' => $userSession,
        ];

        return array_merge($logData, $data);
    }

    /**
     * Return Logger
     *
     * @return \Laminas\Log\Logger
     */
    protected function log(): \Laminas\Log\Logger {
        if ($this->_logger == null) {
            // $priority = getenv('LOG_LEVEL') ?: $this->_defaultLogPriority;
            $priority = $this->_defaultLogPriority;
            $logger = new \Laminas\Log\Logger();
            $writer = new \Laminas\Log\Writer\Stream('log/data.' . $this->_table . '.log');
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
     * @return \Laminas\Log\Logger
     */
    protected function logDb(): \Laminas\Log\Logger {
        if ($this->_loggerDb == null) {
            $logger = new \Laminas\Log\Logger();
            $dbWriter = new Db(GlobalAdapterFeature::getStaticAdapter(), 'logs');
            $dbWriter->setFormatter(new \Laminas\Log\Formatter\Db('Y-m-d H:i:s'));
            $logger->addWriter($dbWriter);

            $this->_loggerDb = $logger;
        }

        return $this->_loggerDb;
    }
}
