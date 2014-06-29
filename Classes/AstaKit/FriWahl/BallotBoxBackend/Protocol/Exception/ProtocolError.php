<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception;
/**
 *
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class ProtocolError extends \RuntimeException {

	const ERROR_BALLOTBOX_NOT_LOGGED_IN = 1;
	const ERROR_VOTER_NOT_FOUND = 6;
	const ERROR_VOTE_ALREADY_CASTED = 8;

	protected $errorMessages = array(
		1 => 'Urne nicht angemeldet',
		6 => 'Wähler nicht gefunden',
		8 => 'Stimme bereits abgegeben',
	);

	/*	values (2, 'Jetzt nicht');
		values (3, 'Wird nicht gewaehlt');
		values (4, 'Buchstaben passen nicht zu Matrikel-Nr.');
		values (5, 'Stimme schon abgegeben');
		values (6, 'Waehler-ID unbekannt');
		values (7, 'keine Matrikelnummer');
		values (8, 'Stimme bereits abgegeben');
		values (9, 'Interner Fehler');
		values (10, 'Urne gesperrt');
		values (11, 'Urne darf nicht waehlen');
		values (12, 'Waehler schon in der Schlange');
		values (13, 'Waehler nicht in der Schlange');*/
}
