<?php
/**
 * DBLayer Response Codes Enum
 *
 * @package DBLayer
 * @author philip<philipr@digitalcabinet.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Db\Enum;

use Inane\Type\Enum;

/**
 * DBLayer Response Codes Enum
 *
 * @method static Code SUCCESS()
 * @method static Code ERROR()
 * @method static Code ERROR_IDENTITY()
 * @method static Code TABLE_ERROR()
 * @method static Code TABLE_INVALID()
 * @method static Code RECORD_ERROR()
 * @method static Code RECORD_INVALID()
 * @method static Code TASK_API_CREATE()
 * @method static Code TASK_API_DELETE()
 * @method static Code TASK_API_GET()
 * @method static Code TASK_API_GETLIST()
 * @method static Code TASK_API_REPLACELIST()
 * @method static Code TASK_API_UPDATE()
 * @method static Code TASK_API_PATCH()
 *
 * @package Cathedral\Db
 */
class Code extends Enum {
	const SUCCESS = '100';

	const ERROR = '200';
	const ERROR_IDENTITY = '210';

	const TABLE_ERROR = '300';
	const TABLE_INVALID = '301';

	const RECORD_ERROR = '320';
	const RECORD_INVALID = '321';

	const TASK_API_CREATE = '601';
	const TASK_API_DELETE = '602';
	const TASK_API_GET = '603';
	const TASK_API_GETLIST = '604';
	const TASK_API_REPLACELIST = '605';
	const TASK_API_UPDATE = '606';
	const TASK_API_PATCH = '607';

    const USER_TASK_ABORT = '701';

	/**
	 * @var string[] the descriptions
	 */
	protected static $descriptions = [
		'100' => 'Success',
		'200' => 'Unknown error',
		'210' => 'Identity required to access data',
		'300' => 'Unknown table error',
		'301' => 'Table invalid',
		'320' => 'Unknown record error',
		'321' => 'ID invalid: no such record',
		'601' => 'Task API Create',
		'602' => 'Task API Delete',
		'603' => 'Task API Get',
		'604' => 'Task API GetList',
		'605' => 'Task API ReplaceList',
		'606' => 'Task API Update',
		'607' => 'Task API Patch',
		'701' => 'User Task Abort',
	];
}
