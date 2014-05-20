<?php
//  second part of install.
//  first part installs mailserver, then, install2 begins,
//  i separated these installs because php email function does not work if i re-start php after email install...
// install functions in install_lib.php

include_once('install_lib.php');
include_once('install2.1.php');

if($argc>1) {
	print "argc:$argc\n\n";
	if($argv[1]=='noapt') {
		$noapt="noapt";
		echo "apt-get install disabled due to parameter:noapt \n";
	}

}


echo "\nincluded install2.1.php\nhere are variables transfered:\n";
echo
"
webdizin:$webdizin
ip:$ip
user_name:$user_name
user_email:$user_email
hostname:$hostname
installextrasoftware: $installextrasoftware
";

/*
ehcpmysqlpass:$ehcpmysqlpass
rootpass:$rootpass
newrootpass:$newrootpass
ehcpadminpass:$ehcpadminpass
*/

installsql();
install_vsftpd_server();
#infomail('_5_vsftpd install finished');

#install_nginx_webserver();
installapacheserver();

# scandb();  no more need to scan db since ver. 0.29.15
installfinish();



$message='';
exec('ifconfig',$msg);
exec('ps aux ',$msg);
foreach($msg as $m) $message.=$m."\n";
#infomail("_6_install finished.mail from inside php.user:$user_name,$user_email,$yesno",$message);
$msg="
your ehcp install finished. if you have questions or need help, please visit www.ehcp.net, ehcp forums or email me to info@ehcp.net

ehcp kurulumunuz tamamlandı. tebrikler. eğer sorularınız varsa ya da yardıma ihtiyacınız varsa, ehcp.net deki forum kısmına soru yazın veya info@ehcp.net adresine eposta gönderin.
https://launchpad.net/ehcp  bu adresi de kullanabilirsiniz.

ehcp developer..
";

if($user_email<>'') mail($user_email,'your ehcp install finished..have fun',$msg,'From: info@ehcp.net');

$realip=getlocalip2();
if(!$app->isPrivateIp($ip)) $realip.="-realip"; # change subject if this is a server with real ip...
$ip2=trim(file_get_contents("http://ehcp.net/diger/myip.php"));
$message.="\noutside Ip detected:$ip2";

infomail("_7_install finished.$realip.$ip2.mail from inside php.user:$user_name,$user_email,$yesno",$message);
#infomailusingwget("7_ehcp_install2.php.finished");

?>
