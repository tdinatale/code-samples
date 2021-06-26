<?php
require_once __DIR__ . '/APIs/vendor/autoload.php';
use Aws\Common\Aws;
use Aws\S3\Enum\CannedAcl;

/**
 * Use Amazon S3 to cache data as text file.
 */
class Cache {

	# S3 Client connection.
	private $s3Client;

	# S3 bucket to use.
	private $bucket = 'buckets-of-fun';

	# S3 file path and name inside of bucket, aka key.
	private $key;

	# Number of seconds until cache expires.
	private $time;

	public function __construct($time = 1800) {

		$this->time = $time;

		# Connect to AWS.
		$aws = Aws::factory(array(
			'key'    => AWS_PUBLIC_KEY,
			'secret' => AWS_SECRET_KEY,
			'region' => 'us-east-1',
		));
		$this->s3Client = $aws->get('s3');

		$dbt = debug_backtrace();

		/*
		Build the unique filename to be cached on S3, consisting of:
			- API file withoout extension.
			- API function name
			- MD5 string consisting of:
				- host name
				- serialized array of function arguments.
				- serialized array of get arguments.
				- serialized array of post arguments.
		*/
		$this->key = 'cache/' . str_replace('.php', '', basename($dbt[0]['file'])) . '/' . $dbt[1]['function'] . '/' . md5($_SERVER['HTTP_HOST'] . serialize($dbt[1]['args']) . serialize($_GET) . serialize($_POST));
	}

	public function read() {
		# Check to see if object exists in bucket.
		if ($this->s3Client->doesObjectExist($this->bucket, $this->key, array())) {

			# Get the object!
			$result = $this->s3Client->getObject(array(
				'Bucket' => $this->bucket,
				'Key'    => $this->key,
			));

			# Check cache time.
			if (strtotime($result['LastModified']) > (time() - $this->time)) {
				# Cache expiration time is still good, order up!
				return strval($result['Body']);
			}
		}
		return NULL;
	}

	public function write($data) {
		if (!empty($data)) {
			$this->s3Client->putObject(array(
				'Bucket' => $this->bucket,
				'Body' => $data,
				'ContentType' => 'text/plain',
				'Key'    => $this->key,
				'ACL'    => CannedAcl::BUCKET_OWNER_FULL_CONTROL,
			));
		}
	}

	public function __destruct() {
		# Clean up expired cache files.
		$iterator = $this->s3Client->getIterator('ListObjects', array(
			'Bucket' => $this->bucket,
			'Prefix' => dirname($this->key),
		));
		foreach ($iterator as $object) {
			if (strtotime($object['LastModified']) < (time() - $this->time)) {
				# Expired, delete it.
				$this->s3Client->deleteObject(array(
					'Bucket' => $this->bucket,
					'Key'    => $object['Key'],
				));
			}
		}
	}
}