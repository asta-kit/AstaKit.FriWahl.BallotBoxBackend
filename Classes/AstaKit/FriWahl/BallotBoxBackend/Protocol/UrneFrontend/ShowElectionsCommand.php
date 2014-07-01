<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Model\Voting;
use TYPO3\Flow\Annotations as Flow;


/**
 *
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class ShowElectionsCommand extends AbstractCommand implements ListingCommand {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	public function process(array $parameters = NULL) {
		$election = $this->ballotBox->getElection();
		$votings = $election->getVotings();

		$i = 0;
		foreach ($votings as $voting) {
			++$i;
			/** @var $voting Voting */
			//$this->addResultLine($this->persistenceManager->getIdentifierByObject($voting) . ' ' . $voting->getName());
			$this->addResultLine($i . ' ' . $voting->getName());
		}
	}

}
