<?php

/**
 * Restful Abstract
 * 
 * PHP version 7
 *
 * @package Cathedral\Db
 * @author Philip Michael Raab <philip@inane.co.za>
 */

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
use function property_exists;
use function str_replace;
use function strtolower;
use function ucfirst;

/**
 * Restful Abstract
 *
 * @version 0.2.0
 */
abstract class AbstractRestfulController extends LaminasAbstractRestfulController implements
    ConfigAwareInterface,
    RestfulControllerInterface {

    use ConfigAwareTrait;
    use \Inane\Option\IpTrait;
    use \Inane\Option\LogTrait;

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
     *
     * @var string table name
     */
    protected $_table = '';

    /**
     * Special fields for processing
     *
     * @var array field => type
     */
    protected $_processFields = [];

    /**
     * Auth Actions
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
     * Loops through resultset passing each result to get proccessed
     *
     * @param mixed $data
     * @return array
     */
    public function processElements($results): array {
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
     * Checks that the field is valid for the object before setting the value
     *
     * @param array $data
     * @param string $field
     * @param [type] $value
     * 
     * @return bool
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
     * @param array $options
     * @return void
     */
    public function customQueryOptions(&$options, $params): void {
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

        if ($params['page']) {
            $options['page'] = (int) $params['page'];
            if ($options['page'] < 1) $options['page'] = 1;

            if ($params['pagesize']) $options['pagesize'] = (int) $params['pagesize'];
            else $options['pagesize'] = (int) getenv('REST_PAGESIZE') ?: $this::PAGINATION_PAGESIZE;
        }

        if ($params['ids']) $options['where']['id'] = explode(',', $params['ids']);

        if ($params['state'] !== null) $options['state'][] = (int) $params['state'];

        $this->customQueryOptions($options, $params);
        return $options;
    }

    protected function preCreate(array &$data): array {
        return $data;
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
     * Create a new resource
     *
     * @param mixed $data
     * @return mixed
     */
    public function create($data) {
        $action = 'create';
        $this->log()->debug($action, []);

        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_CREATE()->getDescription());

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        /* @var $e \DBLayer\Entity\User */
        $e = $this->getEntity();

        $this->setFieldValue($data, 'created', date('Y-m-d H:i:s'));
        $this->setFieldValue($data, 'fk_users', $userSession);
        // $this->setFieldValue($data, 'id', null);

        // $data['id'] = null;
        $this->preCreate($data);
        $e->populate($data, false);

        $e->save();

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
            // 'options' => json_encode(['data' => $data]),
        ];

        $this->logDb()->info('route:', $extra);
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

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $e = $this->getEntity();
        if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_DELETE()->getDescription());

        $e->delete();

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
            // 'options' => json_encode(['id' => $id]),
        ];

        $this->logDb()->info('route:', $extra);
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

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $e = $this->getEntity();
        try {
            if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_GET()->getDescription());
        } catch (\Throwable $th) {
            return $this->createResponse($id, Code::RECORD_INVALID(), array_shift(explode(',', array_pop(explode(':', $th->getMessage())))));
        }

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
            // 'options' => json_encode(['id' => $id]),
        ];

        $this->logDb()->info('route:', $extra);
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

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

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

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($where),
        ];

        $this->logDb()->info('route:', $extra);
        return $this->createResponse($this->processElements($resultList), Code::SUCCESS(), null, ['options' => $where]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @param mixed $data
     *          replacment data
     * @see \Laminas\Mvc\Controller\AbstractRestfulController::replaceList()
     */
    public function replaceList($data) {
        $action = 'replaceList';
        if ($this->validateAccess($action) && $this->identity() === null) return $this->createResponse([], Code::ERROR_IDENTITY(), Code::TASK_API_REPLACELIST()->getDescription());

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $this->_isCollection = true;
        $e = $this->getEntity();

        if (!$e->get($data['id'])) return $this->createResponse($data['id'], Code::RECORD_INVALID(), Code::TASK_API_REPLACELIST()->getDescription());

        $e->exchangeArray($data);
        $e->save();
        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
            // 'options' => json_encode(['data' => $data]),
        ];
        $this->logDb()->info('route:', $extra);
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

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

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

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            // 'options' => json_encode(['id' => $id, 'data' => $data]),
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
        ];
        $this->logDb()->info('route:', $extra);
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

        $userId = $this->identity() ? $this->identity()->getId() : 0;
        $userSession = $this->identity() ? $this->identity()->getSession() : '';

        $e = $this->getEntity();
        if (!$e->get($id)) return $this->createResponse($id, Code::RECORD_INVALID(), Code::TASK_API_PATCH()->getDescription());

        $this->setFieldValue($data, 'modified', date('Y-m-d H:i:s'));
        array_walk($data, function ($item, $key, &$entity) {
            $entity->$key = $item;
        }, $e);

        $e->save();

        $extra = [
            'ip_address' => $this->getIp(),
            'user_id' => $userId,
            'route' => 'api/' . $this->_table . '/' . $action,
            'file' => 'dblayer/' . $this->_table . '/' . $action,
            'class' => $this->getClass(),
            'function' => $action,
            'session' => $userSession,
            'options' => JSON::encode($this->bundleArguments(func_get_args())),
            // 'options' => json_encode(['id' => $id, 'data' => $data]),
        ];

        $this->logDb()->info('route:', $extra);
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
