<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Protocol;
use AstaKit\FriWahl\BallotBoxBackend\Protocol\Exception\EndOfFileException;


/**
 * Handler for the standard input and output
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class StandardInOutStreamHandler implements StreamHandler {

	/**
	 * @var string
	 */
	protected $lineEnding = "\n";

	/**
	 * @var resource
	 */
	protected $inputStream;

	/**
	 * @var resource
	 */
	protected $outputStream;

	public function __construct() {
		$this->inputStream = STDIN;
		$this->outputStream = STDOUT;
	}

	/**
	 * @param resource $inputStream
	 */
	public function setInputStream($inputStream) {
		$this->inputStream = $inputStream;
	}

	/**
	 * @param resource $outputStream
	 */
	public function setOutputStream($outputStream) {
		$this->outputStream = $outputStream;
	}

	public function setLineEnding($ending) {
		$this->lineEnding = $ending;
	}

	/**
	 * @return string
	 * @throws \Exception When the end of the input stream has been reached
	 */
	public function readLine() {
		if (feof($this->inputStream)) {
			throw new EndOfFileException('End of input stream reached.', 1403519834);
		}
		return fgets($this->inputStream, 1024);
	}

	public function writeLine($contents) {
		fwrite($this->outputStream, $contents . $this->lineEnding);
	}

	public function close() {
		fclose($this->inputStream);
		fclose($this->outputStream);
	}
}
 