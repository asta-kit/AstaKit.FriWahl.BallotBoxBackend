<?php
namespace AstaKit\FriWahl\BallotBoxBackend\Command;

/*                                                                                    *
 * This script belongs to the TYPO3 Flow package "AstaKit.FriWahl.BallotBoxBackend".  *
 *                                                                                    *
 *                                                                                    */

use AstaKit\FriWahl\Core\Domain\Model\BallotBox;
use AstaKit\FriWahl\Core\Domain\Model\Election;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Package\PackageManagerInterface;


/**
 * Commands for managing ballot boxes.
 *
 * @author Andreas Wolf <andreas.wolf@usta.de>
 */
class BallotBoxesCommandController extends CommandController {

	/**
	 * @var PackageManagerInterface
	 * @Flow\Inject
	 */
	protected $packageManager;

	/**
	 * Outputs a list of the public SSH keys and the commands to run a voting session of all ballot boxes.
	 *
	 * @param Election $election The election to list the ballot boxes for.
	 */
	public function authorizedKeysCommand(Election $election) {
		$ballotBoxes = $election->getBallotBoxes();

		$outputLines = array();
		$packagePath = $this->packageManager->getPackageOfObject($this)->getPackagePath();
		$sessionScriptPath = $packagePath . 'Scripts/BallotBoxSession.sh';

		/** @var BallotBox $ballotBox */
		foreach ($ballotBoxes as $ballotBox) {
			$publicKey = trim($ballotBox->getSshPublicKey());

			if ($publicKey == '') {
				$outputLines[] = '# No key available for ballot box ' . $ballotBox->getIdentifier();
				continue;
			}

			$scriptPath = $sessionScriptPath . ' ' . $ballotBox->getIdentifier();
			$outputLines[] = 'command="' . $scriptPath . '" '
				. 'no-port-forwarding no-X11-forwarding '
				. $ballotBox->getSshPublicKey();
		}

		$this->response->appendContent(implode(PHP_EOL, $outputLines) . PHP_EOL);
	}

}
