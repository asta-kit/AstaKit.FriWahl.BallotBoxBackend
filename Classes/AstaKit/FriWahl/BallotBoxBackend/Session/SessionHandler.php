<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Session;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Domain\Model\Session;
use AstaKit\FriWahl\BallotBoxBackend\Domain\Model\UrneFrontendSession;
use AstaKit\FriWahl\BallotBoxBackend\Domain\Repository\SessionRepository;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use TYPO3\Flow\Annotations as Flow;


/**
 * Session handler
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 *
 * @Flow\Scope("singleton")
 */
class SessionHandler {

	/**
	 * @var SessionRepository
	 * @Flow\Inject
	 */
	protected $sessionRepository;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $log;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;


	/**
	 * Starts a new session for the given ballot box. Already existing sessions are terminated.
	 *
	 * @param BallotBox $ballotBox
	 * @return UrneFrontendSession
	 */
	public function startSessionForBallotBox(BallotBox $ballotBox) {
		do {
			$result = $this->findAndTerminateExistingSessions($ballotBox);

			if ($result) {
				sleep(5);
			}
		} while ($result === TRUE);

		$session = new UrneFrontendSession($ballotBox);
		$this->sessionRepository->add($session);
		$this->persistenceManager->persistAll();

		$this->log->log('Started new session for ballot box ' . $ballotBox->getIdentifier(), LOG_DEBUG);

		return $session;
	}

	/**
	 * @param BallotBox $ballotBox
	 * @return bool TRUE if any session was found.
	 */
	protected function findAndTerminateExistingSessions(BallotBox $ballotBox) {
		$existingSessions = $this->sessionRepository->findByBallotBox($ballotBox);

		if (count($existingSessions) === 0) {
			$this->log->log('No existing sessions found for ballot box ' . $ballotBox->getIdentifier(), LOG_DEBUG);
			return FALSE;
		}
		$this->log->log('Terminating existing sessions for ballot box ' . $ballotBox->getIdentifier(), LOG_DEBUG);

		$activeSessionFound = FALSE;
		/** @var $session Session */
		foreach ($existingSessions as $session) {
			if ($session->isRunning()) {
				$activeSessionFound = TRUE;
				$session->terminate();
				$this->persistenceManager->update($session);
			}
		}

		return $activeSessionFound;
	}

}
