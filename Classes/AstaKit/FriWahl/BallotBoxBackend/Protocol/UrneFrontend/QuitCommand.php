<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\QuitSessionException;


/**
 * Command sent by the client when it wants to close the running session.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class QuitCommand extends AbstractCommand {

	public function process(array $parameters = NULL) {
		// the exception is only thrown when print the result
	}

	public function printResult() {
		parent::printResult();
		throw new QuitSessionException();
	}

}
