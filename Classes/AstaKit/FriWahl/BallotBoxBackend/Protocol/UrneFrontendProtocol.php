<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\EndOfFileException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\QuitSessionException;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend\AbstractCommand;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend\ShowElectionsCommand;
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
	 * The stream handler to handle input and output
	 *
	 * @var StreamHandler
	 */
	protected $ioHandler;


	public function __construct(BallotBox $ballotBox, $streamHandler) {
		$this->ballotBox = $ballotBox;
		$this->ioHandler = $streamHandler;
	}

	/**
	 * Runs a ballot box session according to the protocol defined for the legacy "FriWahl" client/server software.
	 *
	 * @return void
	 */
	public function run() {
		$keepAlive = TRUE;
		while ($keepAlive) {
			try {
				$line = trim($this->ioHandler->readLine());
echo ".";
				$this->log->log("Received line: " . $line, LOG_DEBUG);

				if ($line == '') {
					continue;
				}

				$parameters = explode(' ', $line);
				$command = array_shift($parameters);

				/** @var AbstractCommand $commandHandler */
				$commandHandler = $this->getCommandObject($command);

				$commandHandler->process($parameters);

				$this->log->log('Command ' . $command . ' was processed', LOG_DEBUG);

				$this->ioHandler->writeLine("+OK");
				$commandHandler->printResult();

			} catch (ProtocolError $e) {
				// a generic error
				$this->ioHandler->writeLine(sprintf("-%d %s", $e->getCode(), $e->getMessage()));
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

	protected function getCommandObject($command) {
		$commandClassName = str_replace(' ', '', ucwords(str_replace('-', ' ', $command))) . 'Command';
		$commandClassName = __NAMESPACE__ . '\\UrneFrontend\\' . $commandClassName;

		return new $commandClassName($this->ballotBox, $this->ioHandler);
	}
}
