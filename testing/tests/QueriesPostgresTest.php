<?php

class QueriesPostgresTest extends QueriesTest {
	public function setUp() {
		include __DIR__.'/../bootstrapPostgreSQL.php';
		\SciActive\R::_('Nymph', ['NymphConfig'], function($NymphConfig){
			$class = '\\Nymph\\Drivers\\'.$NymphConfig->driver['value'].'Driver';

			$Nymph = new $class($NymphConfig);
			return $Nymph;
		});
	}
}