<?php

#cya include once...
include_once("classapp.php");

class EroiApplication extends Application{
# so the only thing I need do here is re-define functions where necessary

	function updateWebstats(){
		global $skipUpdateWebstats;
		if($skipUpdateWebstats or $this->miscconfig['enablewebstats']=='') {
			# if you put webstats.sh in crontab
			echo "\nSkipping ".__FUNCTION__." because of config directive (\$skipUpdateWebstats) or enablewebstats is not checked in options.\n";
			return false;
		}

		$this->requireCommandLine(__FUNCTION__);
		$res=$this->query("select domainname,homedir from domains where status='$this->status_active' and homedir<>''");
		$str='';
		foreach($res as $dom){
			passthru2("mkdir -p ".$dom['homedir']."/httpdocs/webstats/");
			$str.="webalizer -q -p -n www.".$dom['domainname']." -o ".$dom['homedir']."/httpdocs/webstats ".$dom['homedir']."/logs/access_log -R 100 TopReferrers -r ".$dom['domainname']." HideReferrer \n";
		}
		echo $str;

		writeoutput2("/etc/ehcp/webstats.sh",$str,"w");
		passthru2("chmod a+x /etc/ehcp/webstats.sh");
	#	passthru2("/etc/ehcp/webstats.sh");
		echo "\nwrite webstats file to /etc/ehcp/webstats.sh complete... need to put this in crontab or run automatically.. \n";

	}

}
