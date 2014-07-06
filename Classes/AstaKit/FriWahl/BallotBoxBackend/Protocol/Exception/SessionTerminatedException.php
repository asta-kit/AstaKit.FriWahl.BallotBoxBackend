<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception;
/**
 * Exception thrown when a session has been ended by e.g. a second connection from a different host.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class SessionTerminatedException extends \RuntimeException {

}
