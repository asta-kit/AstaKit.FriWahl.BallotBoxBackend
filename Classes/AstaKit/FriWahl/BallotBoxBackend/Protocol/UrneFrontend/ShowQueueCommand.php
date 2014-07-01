<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;
use AstaKit\FriWahl\Core\Domain\Model\EligibleVoter;
use TYPO3\Flow\Annotations as Flow;


/**
 * Command to show all queued voters with their queued votes.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class ShowQueueCommand extends AbstractCommand implements ListingCommand {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @param array $parameters
	 */
	public function process(array $parameters = NULL) {
		$pendingVotes = $this->ballotBox->getQueuedVotes();

		$electionVotings = $this->ballotBox->getElection()->getVotings();

		$queue = array();
		foreach ($pendingVotes as $vote) {
			$voterId = $this->getVoterIdForVoter($vote->getVoter());

			if (!isset($queue[$voterId])) {
				$queue[$voterId] = array();
			}

			$votingId = $electionVotings->indexOf($vote->getVoting());
			if ($votingId === FALSE) {
				throw new \RuntimeException('Voting not found!');
			}
			// voting IDs are one- and not zero-based
			++$votingId;
			$queue[$voterId][] = $votingId;
		}

		ksort($queue);

		foreach ($queue as $voterId => $elements) {
			$this->addResultLine($voterId . ' ' . implode(' ', $elements));
		}
	}

	/**
	 * Returns the ID for the voter as displayed in the queue (and entered by the
	 *
	 * @param EligibleVoter $voter
	 * @return string
	 */
	protected function getVoterIdForVoter(EligibleVoter $voter) {
		$identifier = $voter->getDiscriminator('matriculationNumber')->getValue();
		$identifier .= strtoupper(substr($voter->getGivenName(), 0, 1) . substr($voter->getFamilyName(), -1));

		return $identifier;
	}

}
