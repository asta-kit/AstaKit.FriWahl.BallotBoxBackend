<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Tests\Functional\Protocol;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\ProtocolError;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontendProtocol;
use AstaKit\FriWahl\BallotBoxBackend\Tests\Functional\ElectionBuilder;
use AstaKit\FriWahl\BallotBoxBackend\Tests\Functional\Protocol\Fixtures\RecordingStreamHandler;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Model\Election;
use AstaKit\FriWahl\Core\Domain\Model\EligibleVoter;
use AstaKit\FriWahl\Core\Domain\Model\SingleListVoting;
use AstaKit\FriWahl\Core\Domain\Model\Voting;
use TYPO3\Flow\Tests\FunctionalTestCase;


/**
 * Test case for the UrneFrontend protocol handler.
 *
 * This creates streams for the input and output and maps them to the protocol handler
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class UrneFrontendProtocolTest extends FunctionalTestCase {

	/**
	 * @var RecordingStreamHandler
	 */
	protected $ioHandler;

	/**
	 * {@inheritDoc}
	 */
	static protected $testablePersistenceEnabled = TRUE;

	/**
	 * @var UrneFrontendProtocol
	 */
	protected $protocolHandler;

	/**
	 * @var Election
	 */
	protected $election;

	/**
	 * @var ElectionBuilder
	 */
	protected $electionBuilder;

	public function setUp() {
		parent::setUp();

		$this->ioHandler = new RecordingStreamHandler();

		$this->electionBuilder = new ElectionBuilder($this->persistenceManager);
		$this->electionBuilder
			->withNumberOfVotings(3)
			->withAnonymousBallotBox();
	}

	protected function sendServerCommand($command, array $parameters = array()) {
		if (count($parameters) > 0) {
			$command .= ' ' . implode(' ', $parameters);
		}

		$this->ioHandler->addCommand($command);
	}

	protected function runServerSession() {
		$this->electionBuilder->finish();

		$election = $this->electionBuilder->getElection();

		$this->protocolHandler = new UrneFrontendProtocol($election->getBallotBoxes()->get(0), $this->ioHandler);
		$this->protocolHandler->run();
	}

	/**
	 * @param integer $commandNumber
	 * @return array
	 */
	protected function getResultsForCommandNumber($commandNumber) {
		--$commandNumber;
		$results = $this->ioHandler->getCommandResults();
		$commandResults = $results[$commandNumber];

		return $commandResults;
	}

	/**
	 * @param integer $commandNumber The number of the command, one-based
	 */
	protected function assertCommandSuccessful($commandNumber) {
		$commandResult = $this->getResultsForCommandNumber($commandNumber);

		$this->assertStringStartsWith('+OK', $commandResult[0], 'First line of command result does not indicate success.');
		if (count($commandResult) > 1) {
			$this->assertEquals('', $commandResult[count($commandResult) - 1], 'Last line of command result is not empty.');
		}
	}

	/**
	 * @param integer $commandNumber The number of the command, one-based
	 * @param integer $errorCode The error code to test for
	 */
	protected function assertCommandHasReturnedErrorCode($commandNumber, $errorCode) {
		$commandResult = $this->getResultsForCommandNumber($commandNumber);

		$this->assertStringStartsWith('-' . $errorCode, $commandResult[0], 'First line of command result does not contain expected error code.');
		if (count($commandResult) > 1) {
			$this->assertEquals('', $commandResult[count($commandResult) - 1], 'Last line of command result is not empty.');
		}
	}

	/**
	 * @param integer $commandNumber The number of the command, one-based
	 */
	protected function assertCommandResultIsEmptyList($commandNumber) {
		$commandResult = $this->getResultsForCommandNumber($commandNumber);

		$this->assertCommandResultIsListWithContents($commandNumber, array());
	}

	protected function assertCommandResultIsListWithContents($commandNumber, $listContents) {
		$commandResults = $this->getResultsForCommandNumber($commandNumber);

		$this->assertCommandSuccessful($commandNumber);
		$this->assertCount(count($listContents) + 2, $commandResults, 'Command result does not have right number of lines');
		$this->assertEquals($listContents, array_slice($commandResults, 1, count($listContents)));
	}

	/**
	 * @test
	 */
	public function showElectionsCommandReturnsListOfVotings() {
		$this->sendServerCommand('show-elections');
		$this->runServerSession();

		$results = $this->ioHandler->getCommandResults();

		$this->assertCommandResultIsListWithContents(1,
			array(
				'1 voting-0',
				'2 voting-1',
				'3 voting-2',
			)
		);
	}

	/**
	 * @test
	 */
	public function sessionCanBeEndedWithCommand() {
		$this->sendServerCommand('quit');
		$this->runServerSession();

		$this->assertCommandSuccessful(1);
	}

	/**
	 * @test
	 */
	public function unknownCommandLeadsToError() {
		$this->sendServerCommand(uniqid('command-'));
		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(1, 65533);
	}

	/**
	 * @test
	 */
	public function errorIsReturnedIfElectionIsNotActive() {
		$this->electionBuilder->withoutElectionPeriods();

		$this->sendServerCommand('show-elections');
		$this->runServerSession();

		$this->assertCount(0, $this->electionBuilder->getElection()->getPeriods());

		$this->assertCommandHasReturnedErrorCode(1, 11);
	}

	/**
	 * @test
	 */
	public function voterCheckFailsIfVoterDoesNotExist() {
		$this->sendServerCommand('check-voter', array('100AB'));

		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(1, ProtocolError::ERROR_VOTER_NOT_FOUND);
	}

	/**
	 * @test
	 */
	public function voterCheckFailsIfLettersDoNotMatch() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100, 'stuffandthings');
		$this->sendServerCommand('check-voter', array('100YZ'));

		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(1, ProtocolError::ERROR_LETTERS_DONT_MATCH);
	}

	/**
	 * @test
	 */
	public function informationOnVoterCanBeFetched() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100, 'stuffandthings');
		$this->sendServerCommand('check-voter', array('100FR'));

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(1, count($results));
		$this->assertCommandResultIsListWithContents(1,
			array(
				'Foo,Bar',
				'stuffandthings',
				'1 voting-0',
				'2 voting-1',
				'3 voting-2',
			)
		);
	}

	/**
	 * @test
	 */
	public function voterCanBeQueuedAndIsReturnedInQueue() {
		$voter = $this->electionBuilder->withVoter('Foo', 'Bar', 100);

		// enqueueing a voter for elections 1 and 2
		$this->sendServerCommand('insert-queue-element', array('100FR', '1', '2'));
		$this->sendServerCommand('show-queue');

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(2, count($results));
		$this->assertCommandSuccessful(1);
		$this->assertCommandResultIsListWithContents(2,
			array(
				'100FR 1 2',
			)
		);
	}

	/**
	 * @test
	 */
	public function queuedVoterCanBeCommittedAndIsRemovedFromQueueAfterwards() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100);

		// enqueueing a voter for elections 1 and 2
		$this->sendServerCommand('insert-queue-element', array('100FR', '1', '2'));
		$this->sendServerCommand('commit-queue-element', array('100FR'));
		$this->sendServerCommand('show-queue');

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(3, count($results));
		$this->assertCommandSuccessful(1);
		$this->assertCommandSuccessful(2);
		$this->assertCommandResultIsEmptyList(3);
	}

	/**
	 * @test
	 */
	public function committedVoterCanBeQueuedAndCommittedAgainForDifferentVoting() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100);

		// enqueueing a voter for elections 1 and 2
		$this->sendServerCommand('insert-queue-element', array('100FR', '1', '2'));
		$this->sendServerCommand('commit-queue-element', array('100FR'));
		$this->sendServerCommand('show-queue');
		$this->sendServerCommand('insert-queue-element', array('100FR', '3'));
		$this->sendServerCommand('commit-queue-element', array('100FR'));
		$this->sendServerCommand('show-queue');

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(6, count($results));
		$this->assertCommandSuccessful(1);
		$this->assertCommandSuccessful(2);
		$this->assertCommandResultIsEmptyList(3);
		$this->assertCommandSuccessful(4);
		$this->assertCommandSuccessful(5);
		$this->assertCommandResultIsEmptyList(6);
	}

	/**
	 * @test
	 */
	public function queuedVoterCanBeRemovedFromQueueAgain() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100);

		// enqueueing a voter for elections 1 and 2
		$this->sendServerCommand('insert-queue-element', array('100FR', '1', '2'));
		$this->sendServerCommand('delete-queue-element', array('100FR'));
		$this->sendServerCommand('show-queue');

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(3, count($results));
		$this->assertCommandSuccessful(1);
		$this->assertCommandSuccessful(2);
		$this->assertCommandResultIsEmptyList(3);
	}

	/**
	 * @test
	 */
	public function removedVoterCanBeQueuedAndCommittedAgain() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100);

		// enqueueing a voter for elections 1 and 2
		$this->sendServerCommand('insert-queue-element', array('100FR', '1', '2')); // 1
		$this->sendServerCommand('delete-queue-element', array('100FR'));           // 2
		// use a different number of votings for second try
		$this->sendServerCommand('insert-queue-element', array('100FR', '1'));      // 3
		$this->sendServerCommand('show-queue');                                     // 4
		$this->sendServerCommand('commit-queue-element', array('100FR'));           // 5
		$this->sendServerCommand('show-queue');                                     // 6

		$this->runServerSession();
		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(6, count($results));
		$this->assertCommandSuccessful(1);
		$this->assertCommandSuccessful(2);
		$this->assertCommandSuccessful(3);
		$this->assertCommandResultIsListWithContents(4,
			array(
				'100FR 1',
			)
		);
		$this->assertCommandSuccessful(5);
		$this->assertCommandResultIsEmptyList(6);
	}

}
