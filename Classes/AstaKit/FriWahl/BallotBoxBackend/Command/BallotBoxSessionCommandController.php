<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Command;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\StandardInOutStreamHandler;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontendProtocol;
use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use TYPO3\Flow\Cli\CommandController;


/**
 * Command line controller for the ballot box backend.
 *
 * This is the central connecting part of the client-server system. Invoked by the SSH daemon, it hands the voting
 * session over to a protocol handler.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class BallotBoxSessionCommandController extends CommandController {

	/**
	 * Runs a voting session for a ballot box.
	 *
	 * @param BallotBox $ballotBox
	 * @return void
	 */
	public function sessionCommand(BallotBox $ballotBox) {
		$protocolHandler = new UrneFrontendProtocol($ballotBox, new StandardInOutStreamHandler());
		$protocolHandler->run();
	}

	/**
	 * Prints the status of all ballot boxes.
	 *
	 * @return void
	 */
	public function statusCommand() {
		// TODO implement
	}

}
