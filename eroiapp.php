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

	function build_logrotate_conf($arr2,$host){
		if($this->debuglevel>0) print_r($arr2);
		$logrotate="# jason was here\n";
		foreach($arr2 as $dom) {
			$logrotate.=$dom['homedir']."/logs/access_log ".$dom['homedir']."/logs/error_log ";
		}
		#	$logrotate.=" /var/log/ehcp.log /var/log/apache_common_access_log {
		#}";
		$logrotate.=" /var/log/ehcp.log /var/log/apache_common_access_log {\n\tweekly\n\tmissingok\n\trotate 52\n\tcompress\n\tdelaycompress\n\tnotifempty\n\tcreate 644 root root\n\tsharedscripts\n\tpostrotate\n\t\t/etc/init.d/apache2 reload\n\tendscript\n
}";
		passthru2('mkdir -p '.$this->ehcpdir.'/etc/logrotate.d/');
		writeoutput($this->ehcpdir.'/etc/logrotate.d/ehcp',$logrotate,'w',True);

		$cmd="cp -vf ".$this->ehcpdir.'/etc/logrotate.d/ehcp /etc/logrotate.d/';
		if((!$host) or ($host=='localhost')) passthru2($cmd); # bu kısım bir fonksiyon yapılabilir.
		else $this->cmds[]=$cmd;	# multi server da kullanmak uzere
	}

	function showSimilarFunctions($func){
		# the text here may be read from a template
		$out1="Similar/Related $func Functions:";

		switch($func){
			case 'ftp'   : $out="<a href='?op=addftpuser'>Add New ftp</a>, <a href='?op=addftptothispaneluser'>Add ftp Under My ftp</a>, <a href='?op=addsubdirectorywithftp'>Add ftp in a subDirectory Under Domainname</a>, <a href='?op=addsubdomainwithftp'>Add subdomain with ftp</a>, <a href='?op=add_ftp_special'>Add ftp under /home/xxx (admin)</a>, <a href='net2ftp' target=_blank>WebFtp (Net2Ftp)</a>, <a href='?op=listallftpusers'>List All Ftp Users</a> ";break;
			case 'mysql' : $out="<a href='?op=domainop&amp;action=listdb'>List / Delete Mysql Db's</a>, <a href='?op=addmysqldb'>Add Mysql Db&amp;dbuser</a>, <a href='?op=addmysqldbtouser'>Add Mysql db to existing dbuser</a>, <a href='?op=dbadduser'>Add Mysql user to existing db</a>, <a href='/phpmyadmin' target=_blank>phpMyadmin</a>";break;
			case 'email' : $out="<a href='?op=listemailusers'>List Email Users / Change Passwords</a>, <a href='?op=addemailuser'>Add Email User</a>, Email forwardings: <a href='?op=emailforwardings'>List</a> - <a href='?op=addemailforwarding'>Add</a>, <a href='?op=bulkaddemail'>Bulk Add Email</a>, <a href='?op=editEmailUserAutoreply'>edit Email Autoreply</a> ,<a href='webmail' target=_blank>Webmail (Squirrelmail)</a>";break;
			case 'domain': $out="<a href='?op=addDomainToThisPaneluser'>Add Domain To my ftp user (Most Easy)</a> - <a href='?op=adddomaineasy'>Easy Add Domain (with separate ftpuser)</a> - <a href='?op=adddomain'>Normal Add Domain (Separate ftp&panel user)</a> - <a href='?op=bulkadddomain'>Bulk Add Domain</a> - <a href='?op=adddnsonlydomain'>Add dns-only hosting</a>- <a href='?op=adddnsonlydomainwithpaneluser'>Add dns-only hosting with separate paneluser</a><br>Different IP(in this server, not multiserver): <a href='?op=adddomaineasyip'>Easy Add Domain to different IP</a> - <a href='?op=setactiveserverip'>set Active webserver IP</a><br>List Domains: <a href='?op=listselectdomain'>short listing</a> - <a href='?op=listdomains'>long listing</a>";break;
			case 'redirect': $out="<a href='?op=editdomainaliases'>Edit Domain Aliases</a>";break;
			case 'options' : $out=	"
	<br><a href='?op=options&edit=1'>Edit/Change Options</a><br>
	<br><a href='?op=changemypass'>Change My Password</a>
	<br><a href='?op=listpanelusers'>List/Add Panelusers/Resellers</a>
	<br><a href='?op=dosyncdns'>Sync Dns</a>
	<br><a href='?op=dosyncdomains'>Sync Domains</a><br>
	<br><a href='?op=dosyncftp'>Sync Ftp (for non standard home dirs)</a><br>
	<hr><a href='?op=advancedsettings'>Advanced Settings</a><br><br>
	<br><a href='?op=dofixmailconfiguration'>Fix Mail Configuration<br>Fix ehcp Configuration</a> (This is used after changing ehcp mysql user pass, or if you upgraded from a previous version, in some cases)<br>
	<!-- <br><a href='?op=dofixapacheconfigssl'>Fix apache Configuration with ssl</a> -->
	<br><a href='?op=dofixapacheconfignonssl'>Fix apache Configuration without ssl</a>
	<br>
	<hr>
	<a href='?op=listservers'>List/Add Servers/ IP's</a><br>
	<hr>

	Experimental:
	<br><a href='?op=donewsyncdns'>New Sync Dns - Multiserver</a>
	<br><a href='?op=donewsyncdomains'>New Sync Domains - Multiserver</a><br>
	<br><a href='?op=multiserver_add_domain'>Multiserver Add Domain</a>
	<hr>

	";break;
			case 'customhttpdns': $out="Custom Http: <a href='?op=customhttp'>List</a> - <a href='?op=addcustomhttp'>Add</a>, Custom dns: <a href='?op=customdns'>List</a> - <a href='?op=addcustomdns'>Add</a>";break;
			case 'subdomainsDirs': $out="SubDomains: <a href='?op=subdomains'>List</a> - <a href='?op=addsubdomain'>Add</a> - <a href='?op=addsubdomainwithftp'>Add subdomain with ftp</a> - <a href='?op=addsubdirectorywithftp'>Add subdirectory with ftp (Under domainname)</a>";break;
			case 'HttpDnsTemplatesAliases': $out="<a href='?op=editdnstemplate'>Edit dns template for this domain </a> - <a href='?op=editapachetemplate'>Edit apache template for this domain </a> - <a href='?op=editdomainaliases'>Edit Aliases for this domain </a>";break;
			case 'panelusers': $out="<a href='?op=listpanelusers'>List All Panelusers/Clients</a>, <a href='?op=resellers'>List Resellers</a>, <a href='?op=addpaneluser'>Add Paneluser/Client/Reseller</a>";break;
			case 'server':$out="<a href='?op=listservers'>List Servers/IP's</a> - <a href='?op=addserver'>Add Server</a> - <a href='?op=addiptothisserver'>Add ip to this server</a> - <a href='?op=setactiveserverip'>set Active webserver IP</a>";break;
			case 'backup':$out="<a href='?op=dobackup'>Backup</a> - <a href='?op=dorestore'>Restore</a> - <a href='?op=listbackups'>List Backups</a>";break;
			case 'vps': $out="<a href='?op=vps'>VPS Home</a> - <a href='?op=add_vps'>Add new VPS</a> - <a href='?op=settings&group=vps'>VPS Settings</a> - <a href='?op=vps&op2=other'>Other Vps Ops</a>";break;

			default	 : $out="(internal ehcp error) This similar function is not defined in ".__FUNCTION__." : ($func)"; $out1='';break;
		}

		$this->output.="<br><br>$out1".$out."<br>";
	}

}
