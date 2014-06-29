<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\Core\Domain\Model\BallotBox;


interface ProtocolHandler {

	// this is commented out because Flow currently does not include constructor arguments from interfaces
	// in generated proxy classes, leading to a fatal error because the constructor declarations do not match anymore
	//public function __construct(BallotBox $ballotBox);

}
