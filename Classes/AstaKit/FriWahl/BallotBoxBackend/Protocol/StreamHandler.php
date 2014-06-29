<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;

/**
 *
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
interface StreamHandler {

	public function setLineEnding($lineEnding);

	public function readLine();

	public function writeLine($contents);

	public function close();

}
