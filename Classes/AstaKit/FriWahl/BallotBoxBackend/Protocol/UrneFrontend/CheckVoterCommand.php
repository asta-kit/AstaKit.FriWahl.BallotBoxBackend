<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError;


/**
 * Command to check the voting eligibility of a given voter and return information on them and
 * the votings they may participate in.
 *
 * This command is an extension of the protocol used by FriWahl 1 used up to 2013. It was introduced for
 * the second elections of the official student's representation at the KIT in summer 2014.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class CheckVoterCommand extends VoterRelatedCommand implements ListingCommand {

	public function process(array $parameters = NULL) {
		$voterId = $parameters[0];
		$voter = $this->findVoter($voterId);

		$this->addResultLine($voter->getGivenName() . ',' . $voter->getFamilyName());
		$this->addResultLine($voter->getDiscriminator('department')->getValue());

		$electionVotings = $this->ballotBox->getElection()->getVotings();
		// TODO use a consistent voting identifier mechanism
		foreach ($voter->getVotings() as $voting) {
			$index = $electionVotings->indexOf($voting) + 1;

			$this->addResultLine($index . ' ' . $voting->getName());
		}
	}

}
