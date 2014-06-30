<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Tests\Functional;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Model\Election;
use AstaKit\FriWahl\Core\Domain\Model\ElectionPeriod;
use AstaKit\FriWahl\Core\Domain\Model\EligibleVoter;
use AstaKit\FriWahl\Core\Domain\Model\SingleListVoting;
use AstaKit\FriWahl\Core\Domain\Model\Voting;
use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;


/**
 * Builder for elections for functional tests.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class ElectionBuilder {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Election
	 */
	protected $election;

	/**
	 * @var Voting[]
	 */
	protected $votings = array();


	public function __construct(PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;

		$this->election = new Election('test-' . uniqid(), uniqid());
		new ElectionPeriod(new \DateTime('-1 hour'), new \DateTime('+1 hour'), $this->election);
		$this->persistenceManager->add($this->election);
	}

	/**
	 * @return \AstaKit\FriWahl\Core\Domain\Model\Election
	 */
	public function getElection() {
		return $this->election;
	}

	public function withoutElectionPeriods() {
		$this->election->setPeriods(new ArrayCollection());

		return $this;
	}

	public function withAnonymousBallotBox() {
		$this->createBallotBox(uniqid());

		return $this;
	}

	public function withNamedBallotBox($name) {
		$this->createBallotBox($name);

		return $this;
	}

	public function withNumberOfVotings($votingsCount) {
		$this->createVotings($votingsCount);

		return $this;
	}

	public function withVoter($firstName, $lastName, $matriculationNumber, $department = NULL) {
		$this->createVoter($firstName, $lastName, $matriculationNumber, $department);

		return $this;
	}

	public function withAnonymousVoter($matriculationNumber, $department) {
		static $currentMatriculationNumber = PHP_INT_MAX;
		if ($matriculationNumber === NULL) {
			--$currentMatriculationNumber;
			$matriculationNumber = $currentMatriculationNumber;
		}
		$this->createVoter(uniqid(), uniqid(), $matriculationNumber, $department);

		return $this;
	}

	/**
	 * Creates a ballot box
	 *
	 * @param string $name
	 */
	protected function createBallotBox($name) {
		$ballotBox = new BallotBox($name, $this->election);
		$this->persistenceManager->add($ballotBox);
	}

	/**
	 * @param int $votingsCount
	 * @return Voting[]
	 */
	protected function createVotings($votingsCount = 1) {
		$votings = array();
		for ($i = 0; $i < $votingsCount; ++$i) {
			$voting = new SingleListVoting('voting-' . $i, $this->election);
			$this->persistenceManager->add($voting);
			$votings[] = $voting;
		}

		$this->persistenceManager->persistAll();

		return $this;
	}

	protected function createVoter($firstName, $lastName, $matriculationNumber, $department) {
		$voter = new EligibleVoter($this->election, $firstName, $lastName, uniqid());
		$voter->addDiscriminator('matriculationNumber', $matriculationNumber);
		$voter->addDiscriminator('department', $department);
		$this->persistenceManager->add($voter);
		$this->persistenceManager->persistAll();

		return $voter;
	}
}
 