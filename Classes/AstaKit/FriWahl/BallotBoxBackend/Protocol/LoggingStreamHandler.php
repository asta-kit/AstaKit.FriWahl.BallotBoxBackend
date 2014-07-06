<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\LoggerInterface;


/**
 * A decorator around an ordinary stream handler, which logs all
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class LoggingStreamHandler implements StreamHandler {

	/**
	 * @var StreamHandler
	 */
	protected $originalHandler;

	/**
	 * @var \AstaKit\FriWahl\BallotBoxBackend\Protocol\VotingLoggerInterface
	 * @Flow\Inject
	 */
	protected $logger;

	/**
	 * If set, the memory consumption of the current process is logged after each command.
	 *
	 * @var bool
	 */
	protected $logMemoryConsumption = FALSE;


	/**
	 * @param StreamHandler $originalHandler
	 */
	public function setOriginalHandler(StreamHandler $originalHandler) {
		$this->originalHandler = $originalHandler;
	}

	public function setLineEnding($lineEnding) {
		$this->originalHandler->setLineEnding($lineEnding);
	}

	protected function convertSize($size) {
		// see http://de3.php.net/manual/en/function.memory-get-usage.php#96280
		$units = array('B','KiB','MiB', 'GiB');
		$power = (int)floor(log($size, 1024));
		return @round($size / pow(1024, $power), 2) . ' ' . $units[$power];
	}

	public function readLine() {
		if ($this->logMemoryConsumption) {
			$this->logger->log('Mem usage (current/peak): '
				. $this->convertSize(memory_get_usage()) . '/' . $this->convertSize(memory_get_peak_usage()));
		}
		$line = $this->originalHandler->readLine();
		$this->logger->log('< ' . trim($line));

		return $line;
	}

	public function writeLine($contents) {
		$this->originalHandler->writeLine($contents);
		$this->logger->log('> ' . $contents);
	}

	public function close() {
		return $this->originalHandler->close();
	}

}
