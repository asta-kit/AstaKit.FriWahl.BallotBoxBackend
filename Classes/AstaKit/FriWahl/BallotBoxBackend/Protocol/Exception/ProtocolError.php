<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */


/**
 * Generic error class for errors that occur in a voting session.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class ProtocolError extends \RuntimeException {

	const ERROR_BALLOTBOX_NOT_LOGGED_IN = 1;
	const ERROR_LETTERS_DONT_MATCH = 4;
	const ERROR_VOTER_NOT_FOUND = 6;
	const ERROR_VOTE_ALREADY_CASTED = 8;
	const ERROR_BALLOTBOX_NOT_PERMITTED = 11;

	protected $errorMessages = array(
		1 => 'Urne nicht angemeldet',
		// only used if the election has not been initialized completely
		2 => 'Jetzt nicht',
		3 => 'Wird nicht gewaehlt',
		4 => 'Buchstaben passen nicht zu Matrikel-Nr.',
		5 => 'Stimme schon abgegeben',
		6 => 'Wähler nicht gefunden',
		7 => 'keine Matrikelnummer',
		8 => 'Stimme bereits abgegeben',
		9 => 'Interner Fehler',
		10 => 'Urne gesperrt',
		// this is a generic error also thrown if the current time is not within the election periods
		11 => 'Urne darf nicht waehlen',
		12 => 'Waehler schon in der Schlange',
		13 => 'Waehler nicht in der Schlange'
	);

	public function __construct($message, $code, \Exception $previous = NULL) {
		if ($this->errorMessages[$code] != '') {
			$newMessage = $this->errorMessages[$code];
			if ($message != '') {
				// allow to pass an additional message from where the exception is thrown
				$newMessage .= ' (' . $message . ')';
			}
			$message = $newMessage;
		}
		parent::__construct($message, $code, $previous);
	}
}
