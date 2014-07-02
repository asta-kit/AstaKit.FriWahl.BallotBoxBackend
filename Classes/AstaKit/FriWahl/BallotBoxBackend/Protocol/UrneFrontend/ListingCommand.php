<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */


/**
 * This is a marker interface for the commands that output a list. Output of these commands has to be ended
 * with an empty line, otherwise the FriWahl client is confused.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
interface ListingCommand {
}
