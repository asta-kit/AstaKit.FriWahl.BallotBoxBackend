<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Command;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Domain\Model\Session;
use AstaKit\FriWahl\BallotBoxBackend\Domain\Model\UrneFrontendSession;
use AstaKit\FriWahl\BallotBoxBackend\Domain\Repository\SessionRepository;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\StandardInOutStreamHandler;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\StreamHandler;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontendProtocol;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Model\Election;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;


/**
 * Command line controller for the ballot box backend.
 *
 * This is the central connecting part of the client-server system. Invoked by the SSH daemon, it hands the voting
 * session over to a protocol handler.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class BallotBoxSessionCommandController extends CommandController {

	/**
	 * @var StreamHandler
	 * @Flow\Inject
	 */
	protected $streamHandler;

	/**
	 * @var SessionRepository
	 * @Flow\Inject
	 */
	protected $sessionRepository;

	/**
	 * Runs a voting session for a ballot box.
	 *
	 * @param BallotBox $ballotBox
	 * @return void
	 */
	public function sessionCommand(BallotBox $ballotBox) {
		$protocolHandler = new UrneFrontendProtocol($ballotBox, $this->streamHandler);
		$protocolHandler->run();
	}

	/**
	 * Prints the status of all ballot boxes.
	 *
	 * @param Election $election
	 * @return void
	 */
	public function statusCommand(Election $election) {
		$statuses = array();
		/** @var $ballotBox BallotBox */
		foreach ($election->getBallotBoxes() as $ballotBox) {
			if (!$ballotBox->isAvailableForVotingSession()) {
				continue;
			}

			/** @var Session $session */
			$session = $this->getActiveSessionForBallotBox($ballotBox);

			$boxInfo = array();
			if ($session) {
				$boxInfo['status'] = 'online';
				$boxInfo['started'] = $session->getDateStarted()->format('Y-m-d H:i');
				if ($session instanceof UrneFrontendSession) {
					$boxInfo['pid'] = $session->getPid();
				}
				$pendingVotes = $ballotBox->getQueuedVotesCount();
				$boxInfo['votesPending'] = $pendingVotes;
			} else {
				$boxInfo['status'] = 'offline';
				$boxInfo['started'] = '-';
				$boxInfo['pid'] = '-';
				$pendingVotes = 0;
				$boxInfo['votesPending'] = '-';
			}
			$committedVotes = $ballotBox->getCommittedVotesCount();
			$totalVotes = $pendingVotes + $committedVotes;
			$boxInfo['votesCommitted'] = $committedVotes;
			$boxInfo['votesTotal'] = $totalVotes;

			$statuses[$ballotBox->getIdentifier()] = $boxInfo;
		}

		ksort($statuses);

		$this->outputLine(str_pad('', 40, ' ', STR_PAD_BOTH)           . ' |  Session |                  |       |           Votes               |');
		$this->outputLine(str_pad('Ballot box', 40, ' ', STR_PAD_BOTH) . ' |  Status  |   Date started   |  PID  | Pending | Committed |  Total  |');
		$this->outputLine(str_pad('', 40, '-')                         . '-+----------+------------------+-------+---------+-----------+---------+');
		if (count($statuses) == 0) {
			$this->outputLine(' No ballot box available for voting.');
		}
		foreach ($statuses as $ballotBoxIdentifier => $statusInfo) {
			$dateStarted = $statusInfo['started'];
			$pid = $statusInfo['pid'];
			$this->outputLine(
				str_pad($ballotBoxIdentifier, 40, ' ', STR_PAD_RIGHT) . ' | '
				. str_pad($statusInfo['status'], 8, ' ', STR_PAD_LEFT) . ' | '
				. str_pad($dateStarted, 16, ' ', STR_PAD_LEFT) . ' | '
				. str_pad($pid, 5, ' ', STR_PAD_LEFT) . ' | '
				. str_pad($statusInfo['votesPending'], 7, ' ', STR_PAD_LEFT) . ' | '
				. str_pad($statusInfo['votesCommitted'], 9, ' ', STR_PAD_LEFT) . ' | '
				. str_pad($statusInfo['votesTotal'], 7, ' ', STR_PAD_LEFT) . ' | '
			);
		}
	}

	/**
	 * Returns the active session for the current ballot box.
	 *
	 * @param BallotBox $ballotBox
	 * @return Session|null
	 */
	protected function getActiveSessionForBallotBox(BallotBox $ballotBox) {
		$sessions = $this->sessionRepository->findByBallotBox($ballotBox);

		/** @var $session Session */
		foreach ($sessions as $session) {
			if ($session->isRunning()) {
				return $session;
			}
		}
		return NULL;
	}

}
