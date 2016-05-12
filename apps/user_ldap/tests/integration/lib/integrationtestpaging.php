<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\user_ldap\tests\integration\lib;

use OCA\User_LDAP\User\Manager as LDAPUserManager;
use OCA\user_ldap\tests\integration\AbstractIntegrationTest;
use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User_LDAP;

require_once __DIR__  . '/../../../../../lib/base.php';

class IntegrationTestPaging extends AbstractIntegrationTest {
	/** @var  UserMapping */
	protected $mapping;

	/** @var User_LDAP */
	protected $backend;

	/**
	 * prepares the LDAP environment and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require(__DIR__ . '/../setup-scripts/createExplicitUsers.php');
		parent::init();

		$this->backend = new \OCA\User_LDAP\User_LDAP($this->access, \OC::$server->getConfig());
	}

	/**
	 * tests that paging works properly against a simple example (reading all
	 * of few users in smallest steps)
	 *
	 * @return bool
	 */
	protected function case1() {
		$limit = 1;
		$offset = 0;

		$filter = 'objectclass=inetorgperson';
		$attributes = ['cn', 'dn'];
		$users = [];
		do {
			$result = $this->access->searchUsers($filter, $attributes, $limit, $offset);
			foreach($result as $user) {
				$users[] = $user['cn'];
			}
			$offset += $limit;
		} while ($this->access->hasMoreResults());

		if(count($users) === 2) {
			return true;
		}

		return false;
	}
}

require_once(__DIR__ . '/../setup-scripts/config.php');
$test = new IntegrationTestPaging($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
