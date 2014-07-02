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
	 * Replacement for STDIN for the handler. The contents for this stream are written to testOutputStream
	 *
	 * @var resource
	 */
	protected $handlerInputStream;

	/**
	 * Replacement for STDOUT for the handler. The contents of this stream are read from testInputStream
	 *
	 * @var resource
	 */
	protected $handlerOutputStream;

	protected $testInputStream;

	protected $testOutputStream;

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

	protected function assertCommandSuccessful($commandNumber) {
		$results = $this->ioHandler->getCommandResults();
		$commandResult = $results[$commandNumber];

		$this->assertStringStartsWith('+OK', $commandResult[0], 'First line of command result does not indicate success.');
		if (count($commandResult) > 1) {
			$this->assertEquals('', $commandResult[count($commandResult) - 1], 'Last line of command result is not empty.');
		}
	}

	protected function assertCommandHasReturnedErrorCode($commandNumber, $errorCode) {
		$results = $this->ioHandler->getCommandResults();
		$commandResult = $results[$commandNumber];

		$this->assertStringStartsWith('-' . $errorCode, $commandResult[0], 'First line of command result does not contain expected error code.');
		if (count($commandResult) > 1) {
			$this->assertEquals('', $commandResult[count($commandResult) - 1], 'Last line of command result is not empty.');
		}
	}

	/**
	 * @test
	 */
	public function showElectionsCommandReturnsListOfVotings() {
		$this->sendServerCommand('show-elections');
		$this->runServerSession();

		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(
			array(
				'+OK',
				'1 voting-0',
				'2 voting-1',
				'3 voting-2',
				'',
			),
			$results[0]
		);
	}

	/**
	 * @test
	 */
	public function sessionCanBeEndedWithCommand() {
		$this->sendServerCommand('quit');
		$this->runServerSession();

		$this->assertCommandSuccessful(0);
	}

	/**
	 * @test
	 */
	public function unknownCommandLeadsToError() {
		$this->sendServerCommand(uniqid('command-'));
		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(0, 65533);
	}

	/**
	 * @test
	 */
	public function errorIsReturnedIfElectionIsNotActive() {
		$this->electionBuilder->withoutElectionPeriods();

		$this->sendServerCommand('show-elections');
		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(0, 11);
	}

	/**
	 * @test
	 */
	public function voterCheckFailsIfVoterDoesNotExist() {
		$this->sendServerCommand('check-voter', array('100AB'));

		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(0, ProtocolError::ERROR_VOTER_NOT_FOUND);
	}

	/**
	 * @test
	 */
	public function voterCheckFailsIfLettersDoNotMatch() {
		$this->electionBuilder->withVoter('Foo', 'Bar', 100, 'stuffandthings');
		$this->sendServerCommand('check-voter', array('100YZ'));

		$this->runServerSession();

		$this->assertCommandHasReturnedErrorCode(0, ProtocolError::ERROR_LETTERS_DONT_MATCH);
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
		$this->assertEquals(
			array(
				'+OK',
				'Foo,Bar',
				'stuffandthings',
				'1 voting-0',
				'2 voting-1',
				'3 voting-2',
				'',
			),
			$results[0]
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
		$this->assertEquals(
			array(
				'+OK',
				'100FR 1 2',
				'',
			),
			$results[1]
		);
	}

}
