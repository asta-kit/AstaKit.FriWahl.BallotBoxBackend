<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Domain\Model;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;


/**
 * A ballot box session.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 *
 * @Flow\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 */
abstract class Session {

	/**
	 * @var BallotBox
	 * @ORM\ManyToOne
	 */
	protected $ballotBox;

	/**
	 * @var int
	 */
	protected $status = self::STATUS_RUNNING;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $log;

	/**
	 * @var \DateTime
	 */
	protected $dateStarted;

	const STATUS_RUNNING = 1;
	const STATUS_ENDED = 2;

	public function __construct(BallotBox $ballotBox) {
		$this->ballotBox = $ballotBox;
		$this->dateStarted = new \DateTime();
	}

	/**
	 * @return \AstaKit\FriWahl\Core\Domain\Model\BallotBox
	 */
	public function getBallotBox() {
		return $this->ballotBox;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateStarted() {
		return $this->dateStarted;
	}

	public function isRunning() {
		$this->checkStatus();
		if (!$this->isAlive()) {
			$this->status = self::STATUS_ENDED;
		}
		return $this->status === self::STATUS_RUNNING;
	}

	public function isEnded() {
		return $this->status === self::STATUS_ENDED;
	}

	public function destroy() {
		$this->status = self::STATUS_ENDED;
	}

	abstract public function checkStatus();

	abstract public function isAlive();

	abstract public function terminate();

}
