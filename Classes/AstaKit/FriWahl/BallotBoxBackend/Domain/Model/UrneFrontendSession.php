<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Domain\Model;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\SessionTerminatedException;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;


/**
 *
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 *
 * @Flow\Entity
 */
class UrneFrontendSession extends Session {

	/**
	 * @var int
	 */
	protected $pid;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $log;

	/**
	 * The System V message queue
	 *
	 * @var object
	 * @Flow\Transient
	 */
	protected $queue;

	public function __construct(BallotBox $ballotBox) {
		parent::__construct($ballotBox);

		$this->pid = posix_getpid();
	}

	public function initializeObject() {
		$this->queue = msg_get_queue(1);
	}

	public function terminate() {
		$message = array(
			'type' => 'terminate',
			'ballotBox' => $this->ballotBox->getIdentifier(),
			'sourcePid' => posix_getpid(),
		);

		$this->log->log('Sending termination signal to session with PID ' . $this->pid, LOG_DEBUG);

		// The PID of the target process is used as the message type; this is allowed behaviour, see e.g.
		// <http://docs.oracle.com/cd/E19683-01/806-4125/svipc-23310/index.html>
		msg_send($this->queue, $this->pid, $message);

		$result = posix_kill($this->pid, SIGUSR1);
		if ($result === TRUE) {
			$this->log->log('Sent SIGUSR1 to process ' . $this->pid, LOG_DEBUG);
		} else {
			$this->log->log('Failed sending SIGUSR1 to process' . $this->pid, LOG_DEBUG);
		}

		$this->status = self::STATUS_ENDED;
	}

	/**
	 * Checks the status of this session.
	 *
	 * @throws \AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\SessionTerminatedException
	 */
	public function checkStatus() {
		if (posix_getpid() === $this->pid) {
			msg_receive($this->queue, $this->pid, $messageType, 1024, $message, TRUE, MSG_IPC_NOWAIT);

			$this->log->log('Checking status of ballot box session', LOG_DEBUG);
			if ($message && is_array($message)) {
				$this->log->log('Received message of type ' . $message['type'] . ' from PID ' . $message['sourcePid'], LOG_DEBUG);

				if ($message['type'] === 'terminate') {
					throw new SessionTerminatedException('Session terminated by request from PID ' . $message['sourcePid']);
				}
			}
		}
	}

	/**
	 * Returns TRUE if the client belonging to this session is still alive
	 */
	public function isAlive() {
		if ($this->pid == posix_getpid()) {
			// we are obviously still running
			return TRUE;
		}
		if ($this->isPidRunning($this->pid)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @param int $pid
	 * @return bool
	 */
	protected function isPidRunning($pid) {
		return posix_kill($pid, 0);
	}

}
