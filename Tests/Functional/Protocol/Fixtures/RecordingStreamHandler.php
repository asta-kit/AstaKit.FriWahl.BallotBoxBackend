<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Tests\Functional\Protocol\Fixtures;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\EndOfFileException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\StreamHandler;


/**
 * TODO implement this class and use it to run a voting session via proc_open
 *
 * @author Andreas Wolf <FIXME>
 */
class RecordingStreamHandler implements StreamHandler, \Iterator {

	protected $lineEnding = "\n";

	protected $commands = array();

	/**
	 * The pointer to the current command. Is used to address entries in commandResults
	 * @var int
	 */
	protected $currentCommandPointer = -1;

	protected $commandResults = array();

	public function addCommand($command) {
		$this->commands[] = $command;
	}

	public function getCommandResults() {
		return $this->commandResults;
	}

	public function getResultsForCommand($commandPointer) {
		return implode($this->lineEnding, $this->commandResults[$commandPointer]);
	}

	public function setLineEnding($lineEnding) {
		$this->lineEnding = $lineEnding;
	}

	public function readLine() {
		++$this->currentCommandPointer;
		if ($this->currentCommandPointer >= count($this->commands)) {
			throw new EndOfFileException();
		}

		return $this->commands[$this->currentCommandPointer];
	}

	public function writeLine($contents) {
		$this->commandResults[$this->currentCommandPointer][] = $contents;
	}

	public function close() {
		// TODO: Implement close() method.
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the current element
	 *
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current() {
		return $this->commands[$this->currentCommandPointer];
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Move forward to next element
	 *
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next() {
		++$this->currentCommandPointer;
		$this->commandResults[] = array();
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Return the key of the current element
	 *
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key() {
		return '';
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Checks if current position is valid
	 *
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid() {
		return $this->currentCommandPointer < count($this->commands);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Rewind the Iterator to the first element
	 *
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind() {
		$this->currentCommandPointer = 0;
		$this->commandResults = array();
	}

}
