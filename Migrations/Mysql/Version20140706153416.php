<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Migration to add the ballot box session table
 */
class Version20140706153416 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("CREATE TABLE astakit_friwahl_ballotboxbackend_domain_model_session (
			persistence_object_identifier VARCHAR(40) NOT NULL,
			ballotbox VARCHAR(40) DEFAULT NULL,
			status INT NOT NULL,
			datestarted DATETIME NOT NULL,
			dtype VARCHAR(255) NOT NULL,
			pid INT DEFAULT NULL,

			INDEX IDX_59D4EF4FB32AB266 (ballotbox),
			PRIMARY KEY (persistence_object_identifier)
		) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
		$this->addSql("ALTER TABLE astakit_friwahl_ballotboxbackend_domain_model_session
			ADD CONSTRAINT FK_59D4EF4FB32AB266
				FOREIGN KEY (ballotbox)
				REFERENCES astakit_friwahl_core_domain_model_ballotbox (identifier)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP TABLE astakit_friwahl_ballotboxbackend_domain_model_session");
	}
}
