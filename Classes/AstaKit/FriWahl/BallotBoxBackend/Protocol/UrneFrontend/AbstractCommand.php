<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;


use AstaKit\FriWahl\BallotBoxBackend\Protocol\StreamHandler;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\Logger;


/**
 * Base class for commands run by
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
abstract class AbstractCommand implements Command {

	/**
	 * @var BallotBox
	 */
	protected $ballotBox;

	/**
	 * @var StreamHandler
	 */
	protected $ioHandler;

	/**
	 * @var array
	 */
	protected $result = array();

	const RESULT_SUCCESS = 0;
	const RESULT_FAILURE = -1;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $log;


	/**
	 * @param BallotBox $ballotBox
	 * @param StreamHandler $ioHandler
	 */
	public function __construct(BallotBox $ballotBox, StreamHandler $ioHandler) {
		$this->ballotBox = $ballotBox;
		$this->ioHandler = $ioHandler;

		$this->log = new Logger();
	}

	/**
	 * Prints the result of this command after it has been processed
	 */
	public function printResult() {
		foreach ($this->result as $line) {
			$this->log->log('Printing output: ' . $line, LOG_DEBUG);
			$this->ioHandler->writeLine($line);
		}
		// This empty line is used by the client to detect the end of a server response
		$this->ioHandler->writeLine("");
		$this->result = array();
	}

	protected function addResultLine($line) {
		$this->result[] = $line;
	}

}
