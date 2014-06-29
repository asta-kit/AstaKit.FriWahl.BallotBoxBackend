<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol\UrneFrontend;


interface Command {

	public function process(array $parameters = NULL);

	public function printResult();

} 