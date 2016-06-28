<?php
/**
 * @author Thomas Citharel <tcit@tcit.fr>
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

// load needed apps
$RUNTIME_APPTYPES = ['authentication', 'logging'];

OC_App::loadApps($RUNTIME_APPTYPES);

OC_Util::obEnd();
\OC::$server->getSession()->close();

// Backends
$authBackend = new OCA\DAV\Connector\PublicAuth(
	\OC::$server->getRequest(),
	\OC::$server->getShareManager(),
	\OC::$server->getSession()
);

$serverFactory = new OCA\DAV\Connector\Sabre\ServerFactory(
	\OC::$server->getConfig(),
	\OC::$server->getLogger(),
	\OC::$server->getDatabaseConnection(),
	\OC::$server->getUserSession(),
	\OC::$server->getRequest()
);

$requestUri = \OC::$server->getRequest()->getRequestUri();

$linkCheckPlugin = new \OCA\DAV\CalDAV\Sharing\PublicLinkCheckPlugin();

$db = \OC::$server->getDatabaseConnection();
$cardDavBackend = new CardDavBackend($db, $principalBackend);

$debugging = \OC::$server->getConfig()->getSystemValue('debug', false);

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend);
$addressBookRoot->disableListing = !$debugging; // Disable listing

$nodes = array(
	$principalCollection,
	$addressBookRoot,
);

// Fire up server
$server = new \Sabre\DAV\Server($nodes);
$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new MaintenancePlugin());
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'ownCloud'));
$server->addPlugin(new Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}

$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());
$server->addPlugin(new \OCA\DAV\CardDAV\ImageExportPlugin(\OC::$server->getLogger()));
$server->addPlugin(new ExceptionLoggerPlugin('carddav', \OC::$server->getLogger()))

$server->addPlugin($linkCheckPlugin);

// And off we go!
$server->exec();
