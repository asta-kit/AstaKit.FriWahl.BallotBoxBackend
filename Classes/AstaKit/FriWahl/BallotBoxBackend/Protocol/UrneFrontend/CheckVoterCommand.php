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
 * Command to check the voting eligibility of a given voter and return information on them and
 * the votings they may participate in.
 *
 * This command is an extension of the protocol used by FriWahl 1 used up to 2013. It was introduced for
 * the second elections of the official student's representation at the KIT in summer 2014.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class CheckVoterCommand extends AbstractCommand implements ListingCommand {

	/**
	 * @var ElectionRepository
	 * @Flow\Inject
	 */
	protected $electionRepository;


	public function process(array $parameters = NULL) {
		$voterId = $parameters[0];
		$matriculationNumber = substr($voterId, 0, -2);

		/** @var EligibleVoter $voter */
		$voter = $this->findVoterByMatriculationNumber($matriculationNumber);

		if (!$voter) {
			throw new ProtocolError('', ProtocolError::ERROR_VOTER_NOT_FOUND);
		}

		$this->verifyVoterId($voter, $voterId);

		$this->addResultLine($voter->getGivenName() . ',' . $voter->getFamilyName());
		$this->addResultLine($voter->getDiscriminator('department')->getValue());

		// TODO use a consistent voting identifier mechanism
		$i = 0;
		foreach ($voter->getVotings() as $voting) {
			++$i;
			$this->addResultLine($i . ' ' . $voting->getName());
		}
	}

	/**
	 * Verifies the given voter id, i.e. checks if the letters at the end are correct
	 *
	 * @param EligibleVoter $voter
	 * @param string $voterId
	 * @throws \AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError
	 */
	protected function verifyVoterId(EligibleVoter $voter, $voterId) {
		$passedLetters = substr($voterId, -2);
		$voterLetters = substr($voter->getGivenName(), 0, 1) . substr($voter->getFamilyName(), -1);
		if (strtolower($passedLetters) != strtolower($voterLetters)) {
			throw new ProtocolError('', ProtocolError::ERROR_LETTERS_DONT_MATCH);
		}
	}

	protected function findVoterByMatriculationNumber($matriculationNumber) {
		$election = $this->ballotBox->getElection();

		return $this->electionRepository->findOneVoterByDiscriminator($election, 'matriculationNumber', $matriculationNumber);
	}

}
