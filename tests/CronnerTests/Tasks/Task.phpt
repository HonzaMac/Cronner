<?php

/**
 * @testCase
 */

namespace stekycz\Cronner\tests\Tasks;

use Mockery;
use Nette\Reflection\Method;
use Nette;
use stekycz\Cronner\Tasks\Task;
use stekycz\Cronner\tests\objects\TestObject;
use Tester\Assert;



require_once(__DIR__ . "/../bootstrap.php");

/**
 * @author Martin Štekl <martin.stekl@gmail.com>
 */
class TaskTest extends \TestCase
{

	/**
	 * @var object
	 */
	private $object;



	protected function setUp()
	{
		parent::setUp();
		$this->object = new TestObject();
	}



	public function testInvokesTaskWithSavingLastRunTime()
	{
		$now = new Nette\Utils\DateTime();
		$timestampStorage = Mockery::mock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->shouldReceive("saveRunTime")->with($now)->once();
		$timestampStorage->shouldReceive("setTaskName")->times(2);

		$method = new Method($this->object, 'test01');
		$task = new Task($this->object, $method, $timestampStorage);
		$task($now);
		Assert::$counter++; // Hack for nette tester
	}



	/**
	 * @dataProvider dataProviderShouldBeRun
	 * @param bool $expected
	 * @param int $loads
	 * @param string $methodName
	 * @param string $now
	 * @param string $lastRunTime
	 */
	public function testChecksIfCanBeRun($expected, $loads, $methodName, $now, $lastRunTime)
	{
		$now = new Nette\Utils\DateTime($now);
		$lastRunTime = $lastRunTime ? new Nette\Utils\DateTime($lastRunTime) : NULL;

		$method = $this->object->getReflection()->getMethod($methodName);

		$timestampStorage = Mockery::mock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->shouldReceive("loadLastRunTime")->times($loads)->andReturn($lastRunTime);
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$task = new Task($this->object, $method, $timestampStorage);
		Assert::same($expected, $task->shouldBeRun($now));
	}



	public function dataProviderShouldBeRun()
	{
		return array(
			// Test 01
			array(TRUE, 1, 'test01', '2013-02-01 12:00:00', NULL),
			array(TRUE, 1, 'test01', '2013-02-01 12:10:00', '2013-02-01 12:00:00'),
			array(FALSE, 1, 'test01', '2013-02-01 12:04:00', '2013-02-01 12:00:00'),
			// Test 02
			array(FALSE, 0, 'test02', '2013-02-05 12:00:00', NULL),
			array(FALSE, 0, 'test02', '2013-02-04 12:00:00', NULL),
			array(FALSE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-04 09:00:00'),
			array(TRUE, 1, 'test02', '2013-02-04 09:30:00', NULL),
			array(TRUE, 1, 'test02', '2013-02-04 09:30:00', '2013-02-03 15:30:00'),
		);
	}



	public function testShouldBeRunOnShortLaterRun()
	{
		$timestampStorage = Mockery::mock('\stekycz\Cronner\ITimestampStorage');
		$timestampStorage->shouldReceive("loadLastRunTime")->once()->andReturn(new Nette\Utils\DateTime('2014-08-15 09:00:01'));
		$timestampStorage->shouldReceive("setTaskName")->atLeast(1);

		$method = new Method($this->object, 'test03');
		$task = new Task($this->object, $method, $timestampStorage);
		Assert::true($task->shouldBeRun(new Nette\Utils\DateTime('2014-08-15 09:17:00')));
	}

}

run(new TaskTest());
