<?php
// necessary for the custom signal handler used here
declare(ticks = 10);

namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Domain\Model\Session;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\EndOfFileException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\QuitSessionException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\SessionTerminatedException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend\AbstractCommand;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend\Command;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend\ShowElectionsCommand;
use AstaKit\FriWahl\BallotBoxBackend\Session\SessionHandler;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Repository\ElectionRepository;
use AstaKit\FriWahl\Core\Domain\Service\VotingService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\Logger;


/**
 * Protocol handler for the so-called "Urne Frontend" implementing the client-side functionality of FriWahl 1.
 *
 * This is a handler used to provide backwards compatibility to the old frontend implementation. By default it reads
 * the standard input and outputs the results to standard output, but this may be changed to
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class UrneFrontendProtocol implements ProtocolHandler {

	/**
	 * @var BallotBox
	 */
	protected $ballotBox;

	/**
	 * @var VotingService
	 * @Flow\Inject
	 */
	protected $votingService;

	/**
	 * @var resource
	 */
	protected $inputStream;

	/**
	 * @var resource
	 */
	protected $outputStream;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $log;

	/**
	 * @var SessionHandler
	 * @Flow\Inject
	 */
	protected $sessionHandler;

	/**
	 * The current ballot box session. Is regularly checked to see if it is still active.
	 *
	 * @var Session
	 */
	protected $session;

	/**
	 * The stream handler to handle input and output
	 *
	 * @var StreamHandler
	 */
	protected $ioHandler;


	/**
	 * @param BallotBox $ballotBox
	 * @param StreamHandler $streamHandler
	 */
	public function __construct(BallotBox $ballotBox, StreamHandler $streamHandler) {
		$this->ballotBox = $ballotBox;
		$this->ioHandler = $streamHandler;

		pcntl_signal(SIGUSR1, function($signal) {
			$this->handleSignal($signal);
		});
	}

	/**
	 * Runs a ballot box session according to the protocol defined for the legacy "FriWahl" client/server software.
	 *
	 * @return void
	 */
	public function run() {
		$this->session = $this->sessionHandler->startSessionForBallotBox($this->ballotBox);

		$keepAlive = TRUE;
		while ($keepAlive) {
			try {
				$line = trim($this->ioHandler->readLine());

				$this->checkBallotBoxAvailable();

				$this->log->log("Received line: " . $line, LOG_DEBUG);

				if ($line == '') {
					continue;
				}

				$this->handleCommand($line);

			} catch (ProtocolError $e) {
				// a generic error
				$this->ioHandler->writeLine(sprintf("-%d %s", $e->getCode(), $e->getMessage()));
			} catch (SessionTerminatedException $e) {
				$this->ioHandler->writeLine("-1023 " . $e->getMessage());
				$keepAlive = FALSE;
			} catch (QuitSessionException $e) {
				$keepAlive = FALSE;
			} catch (EndOfFileException $e) {
				$keepAlive = FALSE;
			} catch (\Exception $e) {
				$this->ioHandler->writeLine("-65533 " . $e->getMessage());
			}
		}

		$this->ioHandler->close();
	}

	/**
	 * Returns a command object
	 *
	 * @param string $commandName
	 * @return Command
	 * @throws \InvalidArgumentException
	 */
	protected function getCommandObject($commandName) {
		$commandClassName = str_replace(' ', '', ucwords(str_replace('-', ' ', $commandName))) . 'Command';
		$commandClassName = __NAMESPACE__ . '\\UrneFrontend\\' . $commandClassName;

		if (!class_exists($commandClassName)) {
			throw new \InvalidArgumentException('Unknown command ' . $commandName);
		}

		return new $commandClassName($this->ballotBox, $this->ioHandler);
	}

	/**
	 * @return void
	 * @throws ProtocolError
	 * @throws SessionTerminatedException
	 */
	protected function checkBallotBoxAvailable() {
		if (!$this->ballotBox->getElection()->isActive()) {
			throw new ProtocolError('Election inactive', ProtocolError::ERROR_BALLOTBOX_NOT_PERMITTED);
		}
		if (!$this->session->isRunning()) {
			throw new SessionTerminatedException();
		}
	}

	/**
	 * @param $command
	 */
	protected function handleCommand($command) {
		$parameters = explode(' ', $command);
		$command = array_shift($parameters);

		/** @var AbstractCommand $commandHandler */
		$commandHandler = $this->getCommandObject($command);

		$commandHandler->process($parameters);

		$this->log->log('Command ' . $command . ' was processed', LOG_DEBUG);

		$this->ioHandler->writeLine("+OK");
		$commandHandler->printResult();
	}

	/**
	 * @param $signal
	 */
	public function handleSignal($signal) {
		if ($signal === SIGUSR1) {
			$this->log->log('Received SIGUSR1', LOG_DEBUG);

			$this->session->checkStatus();
		}
	}

}
