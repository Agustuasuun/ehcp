<?php
//  first part of install.
//  this installs mailserver, then, install2 begins, 
//  i separated these installs because php email function does not work if i re-start php after email install... 

if($argc>1) {
	print "argc:$argc\n\n";
	if($argv[1]=='noapt') {
		$noapt="noapt";
		echo "apt-get install disabled due to parameter:noapt \n";
	}
	
}

include_once('install_lib.php');
initialize();
echo "\n------\ninstallpath set as: $ehcpinstalldir \n";
installfiles();
installmailserver(); 
#infomail('_4_ehcp_mailserver,installfiles complete');
passvariablestoinstall2();

?>
