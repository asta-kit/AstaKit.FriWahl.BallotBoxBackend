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
	 * @param StreamHandler $originalHandler
	 */
	public function setOriginalHandler(StreamHandler $originalHandler) {
		$this->originalHandler = $originalHandler;
	}

	public function setLineEnding($lineEnding) {
		$this->originalHandler->setLineEnding($lineEnding);
	}

	public function readLine() {
		$line = $this->originalHandler->readLine();
		$this->logger->log('<' . $line);

		return $line;
	}

	public function writeLine($contents) {
		$this->originalHandler->writeLine($contents);
		$this->logger->log('>' . $contents);
	}

	public function close() {
		return $this->originalHandler->close();
	}

}
