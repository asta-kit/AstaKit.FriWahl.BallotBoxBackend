<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Tests\Functional\Protocol;
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

		$election = $this->electionBuilder->getElection();

		$this->protocolHandler = new UrneFrontendProtocol($election->getBallotBoxes()->get(0), $this->ioHandler);
	}

	protected function sendServerCommand($command, array $parameters = array()) {
		if (count($parameters) > 0) {
			$command .= ' ' . implode(' ', $parameters);
		}

		$this->ioHandler->addCommand($command);
	}

	protected function runServerSession() {
		$this->persistenceManager->persistAll();
		$this->persistenceManager->clearState();

		$this->protocolHandler->run();
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

		$results = $this->ioHandler->getCommandResults();

		$this->assertEquals(
			array(
				'+OK',
				'',
			),
			$results[0]
		);
	}

}
