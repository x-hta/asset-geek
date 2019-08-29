<?php

class crossdomainxml {

	private $prod_hosts = array(
		'assetgeek.coom',
	);

	function show() {
		header('Content-Type: text/xml', $replace = true);
		$host = (DEBUG_MODE ? $_GET['host'] : '') ?: $_SERVER['HTTP_HOST']; // $_GET['host'] just for debug purposes
		// Based on example from twitter https://twitter.com/crossdomain.xml
		// http://stackoverflow.com/questions/213251/can-someone-post-a-well-formed-crossdomain-xml-sample
		// http://www.hardened-php.net/library/poking_new_holes_with_flash_crossdomain_policy_files.html#badly_configured_crossdomain.xml
		// https://www.adobe.com/devnet/articles/crossdomain_policy_file_spec.html
		// https://github.com/h5bp/html5-boilerplate/blob/master/src/crossdomain.xml
		if (in_array($host, $this->prod_hosts)) {
			$out = '<?xml version="1.0" ?>
				<!DOCTYPE cross-domain-policy SYSTEM "http://www.adobe.com/xml/dtds/cross-domain-policy.dtd">
				<cross-domain-policy xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.adobe.com/xml/schemas/PolicyFile.xsd">
				<allow-access-from domain="'.$host.'"/>
				<allow-access-from domain="i.'.$host.'"/>
				<allow-access-from domain="api.'.$host.'"/>
				<allow-access-from domain="search.'.$host.'"/>
				<allow-access-from domain="static.'.$host.'"/>
				<site-control permitted-cross-domain-policies="master-only"/>
				<allow-http-request-headers-from domain="*.'.$host.'" headers="*" secure="false"/>
				</cross-domain-policy>
			';
		} elseif (is_dev()) {
			$out = '<?xml version="1.0" ?>
				<!DOCTYPE cross-domain-policy SYSTEM "http://www.adobe.com/xml/dtds/cross-domain-policy.dtd">
				<cross-domain-policy>
					<site-control permitted-cross-domain-policies="all"/>
					<allow-access-from domain="*" to-ports="*" secure="false"/>
					<allow-http-request-headers-from domain="*" headers="*" secure="false"/>
				</cross-domain-policy>
			';
		} else {
			$out = '<?xml version="1.0" ?>
				<!DOCTYPE cross-domain-policy SYSTEM "http://www.adobe.com/xml/dtds/cross-domain-policy.dtd">
				<cross-domain-policy>
					<site-control permitted-cross-domain-policies="none"/>
				</cross-domain-policy>
			';
		}
		header('Content-Type: text/xml', $replace = true);
		print $out;
		exit;
	}
}
