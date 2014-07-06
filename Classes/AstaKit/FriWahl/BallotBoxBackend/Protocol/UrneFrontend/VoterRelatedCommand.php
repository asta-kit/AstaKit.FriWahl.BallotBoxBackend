<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError;
use AstaKit\FriWahl\Core\Domain\Model\EligibleVoter;
use AstaKit\FriWahl\Core\Domain\Repository\ElectionRepository;
use TYPO3\Flow\Annotations as Flow;


/**
 * Abstract base class for commands that deal with voters.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
abstract class VoterRelatedCommand extends AbstractCommand {

	/**
	 * @var ElectionRepository
	 * @Flow\Inject
	 */
	protected $electionRepository;

	/**
	 * Finds a voter with the given voter ID or fails if no voter can be found.
	 *
	 * @param string $voterId
	 * @return EligibleVoter
	 * @throws \AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError
	 */
	protected function findVoter($voterId) {
		$matriculationNumber = substr($voterId, 0, -2);
		$election = $this->ballotBox->getElection();

		/** @var EligibleVoter $voter */
		$voter = $this->electionRepository->findOneVoterByDiscriminator($election, 'matriculationNumber', $matriculationNumber);

		if (!$voter) {
			throw new ProtocolError('', ProtocolError::ERROR_VOTER_NOT_FOUND);
		}

		$this->verifyVoterId($voter, $voterId);

		return $voter;
	}

	/**
	 * Verifies the given voter id, i.e. checks if the letters at the end are correct.
	 *
	 * @param EligibleVoter $voter
	 * @param string $voterId
	 * @throws \AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError
	 */
	protected function verifyVoterId(EligibleVoter $voter, $voterId) {
		$passedLetters = substr($voterId, -2);
		$voterLetters = substr($voter->getIdentifier(), -1);

		if (strtolower($passedLetters) != strtolower($voterLetters)) {
			throw new ProtocolError('', ProtocolError::ERROR_LETTERS_DONT_MATCH);
		}
	}

}
