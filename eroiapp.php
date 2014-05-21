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
			if ($this->miscconfig['enablecommonwebstats']=='') {
				passthru2("mkdir -p ".$dom['homedir']."/httpdocs/webstats/");
				$str.="webalizer -q -p -n www.".$dom['domainname']." -o ".$dom['homedir']."/httpdocs/webstats ".$dom['homedir']."/logs/access_log -R 100 TopReferrers -r ".$dom['domainname']." HideReferrer \n";
			}else {
				passthru2("mkdir -p " .$this->miscconfig["commonwebstatsdir"] ."/".$dom['domainname']);
				$str.="webalizer -q -p -n www.".$dom['domainname']
					. " -o "
					. $this->miscconfig['commonwebstatsdir']
					. "/"
					. $dom['domainname']
					. " "
					. $dom['homedir']
					. "/logs/access_log -R 100 TopReferrers -r "
					. $dom['domainname']." HideReferrer \n";

			}


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

	function options(){
		$this->requireAdmin();

		global $edit,$_insert,$dnsip;
		$this->getVariable(array('edit','_insert','dnsip','localip'));
		#echo print_r2($this->miscconfig);

		# new style: options as an array, so, easy addition of new options..
		$optionlist=array(
		array('updatehostsfile','checkbox','lefttext'=>'This machine is used for Desktop access too (Update hosts file with domains)','default'=>'Yes','checked'=>$this->miscconfig['updatehostsfile']),
		array('localip','lefttext'=>'Local ip of server','default'=>$this->miscconfig['localip']),
		array('dnsip','lefttext'=>'dnsip (outside/real/static ip of server)','default'=>$this->miscconfig['dnsip']),
		array('dnsipv6','lefttext'=>'dnsip V6(outside/real/static V6 ip of server)','default'=>$this->miscconfig['dnsipv6'],'righttext'=>'Leave empty to disable (experimental even if enabled)'),
		array('updatednsipfromweb','checkbox','lefttext'=>'Do you use dynamic ip/dns?','righttext'=>'Check this if your server is behind a dynamic IP','default'=>'Yes','checked'=>$this->miscconfig['updatednsipfromweb']),
		array('banner','textarea','default'=>$this->miscconfig['banner']),
		array('adminemail','lefttext'=>'Admin Email','default'=>$this->miscconfig['adminemail']),
		array('defaulttemplate','default'=>$this->miscconfig['defaulttemplate']),
		array('defaultlanguage','default'=>$this->defaultlanguage),
		array('messagetonewuser','textarea','default'=>$this->miscconfig['messagetonewuser']),
		array('disableeditdnstemplate','checkbox','lefttext'=>'Disable Custom http for non-admins','default'=>'Yes','checked'=>$this->miscconfig['disableeditdnstemplate'],'righttext'=>'This is a security measure to disable non-experienced users to break configs'),
		array('disableeditapachetemplate','checkbox','lefttext'=>'Disable Custom dns for non-admins','default'=>'Yes','checked'=>$this->miscconfig['disableeditapachetemplate'],'righttext'=>'This is a security measure to disable non-experienced users to break configs'),
		array('turnoffoverquotadomains','checkbox','lefttext'=>'Turn off over quota domains','default'=>'Yes','checked'=>$this->miscconfig['turnoffoverquotadomains']),
		array('quotaupdateinterval','default'=>$this->miscconfig['quotaupdateinterval'],'righttext'=>'interval in hours'),
		array('userscansignup','checkbox','default'=>'Yes','checked'=>$this->miscconfig['userscansignup'],'righttext'=>'disabled by default, can users sign up for domains/ftp? (you should approve/reject them in short time)'),
		array('enablewebstats','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablewebstats'],'righttext'=>'enabled by default, this can use some of server resources, so, disabling it may help some slow servers to speed up'),
		array('enablecommonwebstats','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablecommonwebstats'],'righttext'=>'This will allow webstats to be located somewhere else accessible by the filesystem'),
		array('commonwebstatsdir','textarea','default'=>$this->miscconfig['commonwebstatsdir']),
		array('enablewildcarddomain','checkbox','default'=>'Yes','checked'=>$this->miscconfig['enablewildcarddomain'],'righttext'=>'do you want xxxx.yourdomain.com to show your domain homepage? disabled by default, and shows server home, which is default index, ehcp home.')

		#array('singleserverip','default'=>$this->miscconfig['singleserverip'])

		);



		if($_insert){
			$this->requireNoDemo();
			$old_webserver_type=$this->miscconfig['webservertype']."-".$this->miscconfig['webservermode'];
			if($old_webserver_type=='') $old_webserver_type='apache2-nonssl';

			$this->output.="Updating configuration...";
			$this->validate_ip_address($dnsip);

			foreach($optionlist as $option) {
				global $$option[0]; # make it global to be able to read in getVariable function..may be we need to fix the global thing..
				$this->getVariable($option[0]);
				$this->setConfigValue($option[0],${$option[0]});
			}

			# options that use longvalue:
			$this->setConfigValue("banner","",'value');# delete short value for banner, if there is any.. because longvalue is used for banner.
			$this->setConfigValue("banner",$banner,'longvalue');

			# operations that needs daemon or other settings.

			if($dnsip<>$this->miscconfig['dnsip']){ # fix all dnsip related config if dnsip is changed...
				$this->addDaemonOp("fixmailconfiguration",'','','','fix mail configuration'); # fixes postfix configuration, hope this works..yes, works...
			}

			if($defaultlanguage) { # update for current session too..
				$_SESSION['currentlanguage']='';
				$this->defaultlanguage=$this->currentlanguage=$defaultlanguage;
			}

			# load latest config again in this session.
			$this->loadConfigWithDaemon(); # loads config for this session, to show below..
			if($updatehostsfile<>'')  $this->addDaemonOp("syncdomains",'','','','sync domains-update hostsfile'); # updateHostsFile degistiginden dolayi, syncdomains gerekiyor..

			$this->output.="..update complete.";

		} elseif ($edit) {
			$optionlist[]=array('op','default'=>__FUNCTION__,'type'=>'hidden');
			$this->output.="<h2>Options:</h2><br>".inputform5($optionlist);

		} else {
			$this->output.="<h2>Options:</h2><br>".print_r3($this->miscconfig,"$this->th Option Name </th>$this->th Option Value </th>");
		}

		$this->showSimilarFunctions('options');
		$this->debugecho(print_r2($this->miscconfig),3,false);
	}

	function serverStatus(){
		$this->requireAdmin();
		#-------------- deconectat edit ---------------------------------------------------------
		#  ehcpdeveloper note: in fact, these html should be abstracted from source. left as of now.

		$this->output.="<div class='footer'>(It is normal that only one of apache2,nginx.. etc. webservers are running)<br><table> ";
		$this->check_program_service('apache2','dostartapache2','dostopapache2','dorestartapache2');
		#$this->check_program_service('nginx','dostartnginx','dostopnginx','dorestartnginx');
		$this->check_program_service('mysqld','dostartmysqld','dostopmysqld','dorestartmysqld');
		$this->check_program_service('vsftpd','dostartvsftpd','dostopvsftpd','dorestartvsftpd');
		$this->check_program_service('bind','dostartbind','dostopbind','dorestartbind');
		$this->check_program_service('postfix','dostartpostfix','dostoppostfix','dorestartpostfix');
		$this->output.="</table></div> ";

		$systemStatus=$this->executeProg3($this->ehcpdir."/misc/serverstatus.sh"); #moved the bash script in a separate file; this way it will be easyer to edit.

		$this->output.="<div class=\"footer\"><pre>".$systemStatus."</pre></div>";
		#-------------- end deconectat edit -----------------------------------------------------


		$topoutput=$this->executeProg3("top -b -n 1 | head -40");
		$this->output.="<hr><div align=left>Top output: <br> <pre>$topoutput</pre></div>";

		$topoutput=$this->executeProg3("tail -200 /var/log/syslog");
		$this->output.="<hr><div align=left>Syslog (to see this, you must chmod a+r /var/log/syslog on server console, <a target=_blank href='?op=adjust_system'>adjust system for this</a>): <br> <pre>$topoutput</pre></div>";

		return True;
	}

	function syncDomains($file='') {

		$this->requireCommandLine(__FUNCTION__);

		$success=parent::syncDomains($file='');

		# now we build the backups configuration, using hints from the actual ehcp backup functions

		/*
		 * populate $arr with an array of entries from table "domains"
		 */
		$filt=andle($this->activefilt,"(serverip is null or serverip='') and homedir<>''"); # exclude where serverip is set, that is, for remote dns hosted only domains..
		$arr=$this->getDomains($filt);
		if($this->debuglevel>0) print_r($arr);


#		$arr2=array();


		foreach($arr as $dom) {
			/*
			 * Ok, for each entry in $arr, I go ahead and call jasonTriggerBackupConfiguration
			 */
			$result=jasonTriggerBackupConfiguration($dom);



		}
		return $success;
	}

	function jasonTriggerBackupConfiguration($info){

		# probably should be a commandline function 'cuz I don't want apache writing these files!.
		$this->requireCommandLine(__FUNCTION__);
		$success=True;
		# get backupdirname

		$backupdirname=$this->miscconfig['commonbackupconfdir'];
		$systemhostname=$this->misconfig['systemhostname'];
		$customer_template_filename=$backupdirname . "/customertemplate";

		$customer_template_file=file_get_contents($customer_template_filename);
		#reset template

		$customer_template=$customer_template_file;

		$dom=array();
		if (is_array ($info)) {
			# this has been called from EroiApplication::syncDomains
			$dom=$info;
		} else {
			/*
			 *  we were only passed the domain name...
			 *  we must now populate $dom appropriately
			 */
			#stub:
			return true;
		}

		/*
		 * now that $dom has been defined, do the needful:
		 */
		$domainname=$dom['domainname'];
		$homedir=$dom['homedir'];
		$databases="";
		# get database info
		$dbs=$this->query("select * from mysqldb where domainname like '$domainname'");

		foreach($dbs as $line) {
			$databases .= $line['dbname'] . " "; #note space at the end!
		}

		#going to do something with this in order to get the grant lines to include...
		#$mysqlusers=$this->query("select * from mysqlusers where domainname like '$domainname' ");

		# get the ftpusername:
		$ftpaccounts=$this->query("select * from ftpaccounts where domainname like '$domainname' ");
		$temp=$ftpaccounts[0];
		$ftpuser=$temp['ftpusername'];
		$customer_configfile=$backupdirname . "/" . $systemhostname . "/customertest/" . $ftpuser . ".conf";

		$customer_template=str_replace(array('eroi_ftpuser','eroi_dblist','eroi_domain','eroi_homedir'),array($ftpuser,$databases,$domainname,$homedir ),$customer_template);

		#use writeoutput2 to write config file!
		$res=writeoutput2($customer_configfile, $customer_template, 'w', false );
		if ( ! $res ) {
				$success=False;
		}
		return $success;
	}

	function runOp($op){ # these are like url to function mappers...  maps op variable to some functions in ehcp
		global $id,$domainname;
		$this->getVariable(array('id','domainname'));
		$op=strtolower($op);
		$otheroperations=array('advancedsettings');


		switch ($op) {


			# other
			case 'settings'					: return $this->settings();break;
			case 'adjust_system'			: return $this->adjust_system();break;
			case 'redirect_domain'			: return $this->redirect_domain();break;
			case 'information'				: return $this->information($id);break;

			#multi-server operations:
			case 'multiserver_add_domain'	: return $this->multiserver_add_domain();break;

			case 'new_sync_all'				: return $this->new_sync_all();break;
			case 'new_sync_domains'			: return $this->new_sync_domains();break;
			case 'new_sync_dns'				: return $this->new_sync_dns();break;
			case 'multiserver_add_ftp_user_direct': return $this->gui_multiserver_add_ftp_user_direct();break;

			#single-server operations:
			case 'bulkaddemail'				: return $this->bulkAddEmail();break;
			case 'whitelist'				: return $this->whitelist();break;
			case 'fixmailconfiguration'		: return $this->fixMailConfiguration();break;
			case 'dofixmailconfiguration'	: return $this->addDaemonOp('fixmailconfiguration','','','','fix mail configuration');break;
			case 'dofixapacheconfigssl'		: return $this->addDaemonOp('fixApacheConfigSsl','','','','fixApacheConfigSsl');break;
			case 'dofixapacheconfignonssl'	: return $this->addDaemonOp('fixApacheConfigNonSsl','','','','fixApacheConfigNonSsl');break;
			case 'rebuild_webserver_configs': return $this->rebuild_webserver_configs();break;

			case 'updatediskquota'			: return $this->updateDiskQuota();break;
			case 'doupdatediskquota' 		: $this->addDaemonOp('updatediskquota','',$domainname,'','update disk quota');return $this->displayHome();break;

			#editing of dns/apache templates for domains, on ehcp db
			case 'editdnstemplate'			: return $this->editDnsTemplate();break;
			case 'editapachetemplate'		: return $this->editApacheTemplate();break;
			case 'editdomainaliases'		: return $this->editDomainAliases();break;

			case 'changedomainserverip'		: return $this->changedomainserverip();break;
			case 'warnings'					: break; # this will be written just before show..
			case 'bulkadddomain'			: return $this->bulkaddDomain();break ;
			case 'bulkdeletedomain' 		: return $this->bulkDeleteDomain();break ;
			case 'exportdomain'				: return $this->exportDomain();break;

			case 'adddnsonlydomain' 		: return $this->addDnsOnlyDomain();break;
			case 'adddnsonlydomainwithpaneluser': return $this->addDnsOnlyDomainWithPaneluser();break;

			case 'getselfftpaccount'		: return $this->getSelfFtpAccount();break;
			case 'adddomaintothispaneluser'	: return $this->addDomainToThisPaneluser();break;

			case 'dodownloadallscripts'		: return $this->doDownloadAllscripts();break;
			case 'choosedomaingonextop'		: return $this->chooseDomainGoNextOp();break;

			case 'getmysqlserver'			: return $this->getMysqlServer();break;

			case 'emailforwardingsself'		: return $this->emailForwardingsSelf();break;
			case 'addemailforwardingself'	: return $this->addEmailForwardingSelf();break;

			case 'cmseditpages'				: return $this->cmsEditPages();break;
			case 'listservers' 				: return $this->listServers();break;
			case 'addserver'				: return $this->addServer();break;
			case 'addiptothisserver'		: return $this->add_ip_to_this_server();break;
			case 'setactiveserverip'		: return $this->set_active_server_ip();break;


			case 'advancedsettings'			: return $this->advancedsettings();break;
			case 'delemailforwarding'		: return $this->delEmailForwarding();break;
			case 'addemailforwarding'		: return $this->addEmailForwarding();break;
			case 'emailforwardings' 		: return $this->emailForwardings();break;
			case 'addscript'				: return $this->addScript();break;
			case 'addnewscript'	 			: return $this->addNewScript();break;

			case 'suggestnewscript' 		: return $this->suggestnewscript();break;
			case 'listselectdomain' 		: return $this->listselectdomain();break;
			case 'selectdomain'	 			: return $this->selectdomain($id);break;
			case 'deselectdomain'   		: return $this->deselectdomain();break;
			case 'otheroperations'  		: return $this->otheroperations();break;


			case 'loadconfig'	   			: return $this->loadConfig();break;
			#case 'showconf'					: return $this->showConfig();break;
			case 'changemypass'				: return $this->changeMyPass();break;

			# for mysql, stop and start is meaningless, because if mysql cannot run, then, panel also cannot be accessible or this functions do not work.
			case 'dorestartmysql'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'mysql','info2'=>'restart')); break;

			case 'dostopapache2'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'apache2','info2'=>'stop')); break;
			case 'dostartapache2'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'apache2','info2'=>'start')); break;
			case 'dorestartapache2'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'apache2','info2'=>'restart')); break;

			case 'dostopvsftpd'				: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'vsftpd','info2'=>'stop')); break;
			case 'dostartvsftpd'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'vsftpd','info2'=>'start')); break;
			case 'dorestartvsftpd'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'vsftpd','info2'=>'restart')); break;

			case 'dostopbind'				: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'bind9','info2'=>'stop')); break;
			case 'dostartbind'				: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'bind9','info2'=>'start')); break;
			case 'dorestartbind'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'bind9','info2'=>'restart')); break;

			case 'dostoppostfix'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'postfix','info2'=>'stop')); break;
			case 'dostartpostfix'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'postfix','info2'=>'start')); break;
			case 'dorestartpostfix'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'service','info'=>'postfix','info2'=>'restart')); break;


			case 'donewsyncdomains'			: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'new_sync_domains')); break;
			case 'donewsyncdns'				: $this->requireAdmin(); return $this->add_daemon_op(array('op'=>'new_sync_dns')); break;

			case 'dosyncdomains'			: return $this->addDaemonOp('syncdomains','','','','sync domains');break;
			case 'dosyncdns'				: return $this->addDaemonOp('syncdns','','','','sync dns');break;
			case 'dosyncftp' 				: return $this->addDaemonOp('syncftp','','','','sync ftp for nonstandard homes');break;
			case 'dosyncapacheauth'			: return $this->addDaemonOp('syncapacheauth','','','','sync apache auth');break;
			case 'options'		  			: return $this->options();


			case 'backups'					: return $this->backups();break;
			case 'dobackup'					: return $this->doBackup();break;
			case 'dorestore'				: return $this->doRestore();break;
			case 'listbackups'				: return $this->listBackups();break;

			# these sync functions are executed in daemon mode.
			case 'syncdomains'				: return $this->syncDomains();break;
			case 'syncftp'					: return $this->syncFtp();break;
			case 'syncdns'					: return $this->syncDns();break;
			case 'syncall'					: return $this->syncAll();break;
			case 'syncapacheauth'			: return $this->syncApacheAuth();break;
			case 'fixapacheconfigssl'		: return $this->fixApacheConfigSsl();break;
			case 'fixapacheconfignonssl'	: return $this->fixApacheConfigNonSsl();break;


			#case 'syncallnew'	: return $this->syncallnew();break;
			case 'listdomains'				: return $this->listDomains();break;  # ayni zamanda domain email userlarini da listeler.
			case 'subdomains'	   			: return $this->subDomains();	break;
			case 'addsubdomain'	 			: return $this->addSubDomain();  break;
			case 'addsubdomainwithftp'		: return $this->addSubDomainWithFtp();  break;
			case 'addsubdirectorywithftp'	:return $this->addSubDirectoryWithFtp();  break;


			case 'delsubdomain'	 			: return $this->delSubDomain();  break;


			case 'editdomain'				: return $this->editdomain();
			case 'listpassivedomains'		: return $this->listDomains('',$this->passivefilt);break;
			case 'phpinfo'					: return $this->phpinfo();break;
			case 'help'						: return $this->help();break;
			case 'syncpostfix'				: return $this->syncpostfix();break;
			case 'listemailusers'			: return $this->listemailusers();break;
			case 'listallemailusers'		: return $this->listallemailusers();break;
			case 'listpanelusers'   		: return $this->listpanelusers();break;
			case 'resellers'				: return $this->resellers();break;

			case 'deletepaneluser'  		: return $this->deletepaneluser();break;

			case 'operations'	   			: $this->requireAdmin();$this->listTable('operations','operations_table','');break;

			case 'listallftpusers'  		: return $this->listAllFtpUsers();break;
			case 'listftpusersrelatedtodomains': return $this->listAllFtpUsers("domainname<>''");break;
			case 'listftpuserswithoutdomain': return $this->listAllFtpUsers("domainname='' or domainname is null");break;
			case 'listftpusers'	 			: return $this->listftpusers();break;
			case 'sifrehatirlat'			: return $this->sifreHatirlat();break;
			case 'todolist'					: return $this->todolist();break;
			case 'adddomain'				: return $this->addDomain();break;
			case 'adddomaineasy'			: return $this->addDomainEasy();break;
			case 'adddomaineasyip'			: return $this->addDomainEasyip();break;
			case 'transferdomain'	   		: return $this->transferDomain(); break;
			case 'deletedomain'				: return $this->deleteDomain();break;
			case 'addemailuser'				: return $this->addEmailUser();break;
			case 'addftpuser'				: return $this->addFtpUser();break;
			case 'addftptothispaneluser'	: return $this->addFtpToThisPaneluser();break;# added in 7.6.2009
			case 'add_ftp_special'			: return $this->add_ftp_special();break;

			case 'userop'		   			: return $this->userop();break;
			case 'domainop'		 			: return $this->domainop();break;
			case 'addmysqldb'	   			: return $this->addMysqlDb();   break;
			case 'addmysqldbtouser' 		: return $this->addMysqlDbtoUser();   break;
			case 'addpaneluser'				: return $this->addPanelUser();break;
			case 'editpaneluser'			: return $this->editPanelUser();break;
			case 'editftpuser'				: return $this->editFtpUser();break;
			case 'domainsettings'			: return $this->domainSettings();break;

			case 'logout'					: return $this->logout();break;
			case 'daemon'					: return $this->daemon();break;
			case 'test'						: return $this->test();	break;
			case 'aboutcontactus'   		: return $this->aboutcontactus();break;
			case 'applyforaccount'  		: return $this->applyforaccount();break;
			case 'applyfordomainaccount'	: return $this->applyfordomainaccount();break;
			case 'applyforftpaccount'		: return $this->applyforftpaccount();break;
			case 'setconfigvalue2'  		: return $this->setConfigValue2($id);break;
			case 'customhttp'				: return $this->customHttpSettings();break;
			case 'addcustomhttp'			: return $this->addCustomHttp();break;
			case 'deletecustom'				: return $this->deleteCustomSetting();break;
			case 'customdns'				: return $this->customDnsSettings();break;
			case 'addcustomdns'				: return $this->addCustomDns();break;
			case 'dbedituser'	   			: return $this->dbEditUser();break;
			case 'dbadduser'				: return $this->dbAddUser();break;

			case 'editemailuser'			: # same as below
			case 'editemailuserself'		: return $this->editEmailUser();break;

			case 'editemailuserautoreplyself':
			case 'editemailuserautoreply'	: return $this->editEmailUserAutoreply();break;

			case 'editemailuserpasswordself':
			case 'editemailuserpassword'	: return $this->editEmailUserPassword();break;

			case 'directories'	  			: return $this->directories();break;
			case 'listmyalldirectories'		: return $this->listMyAllDirectories();break;
			case 'adddirectory'	 			: return $this->addDirectory();break;
			case 'deletedirectory'  		: return $this->deleteDirectory();break;
			case 'changetemplate'   		: return $this->changetemplate();break;
			case 'addredirect'				: return $this->addRedirect();break;
			case 'serverstatus'				: return $this->serverStatus();break;
			case 'setlanguage'				: $this->setLanguage($id);$this->displayHome();break;
			case 'setdefaultdomain'			: $this->setDefaultDomain();$this->displayHome();break;

			case 'dologin'					: # default anasayfa, same as below:
			case ''							: $this->displayHome();break;

			# virtual machine (vps) opcodes:
			case 'vps_home'					: return $this->call_func_in_module('Vps_Module','vps_home'); break;
			case 'vps'						: return $this->call_func_in_module('Vps_Module','vps'); break;
			case 'vps_mountimage'			: return $this->call_func_in_module('Vps_Module','vps_mountimage'); break;
			case 'vps_dismountimage'		: return $this->call_func_in_module('Vps_Module','vps_dismountimage'); break;
			case 'add_vps'					: return $this->call_func_in_module('Vps_Module','add_vps'); break;


			default							: return $this->errorText("(runop) internal ehcp error: Undefined operation: $op <br> This feature may not be complete");break;

		}# switch
		return True;

	}# func runop

	function runop2($op,$action,$info,$info2='',$info3=''){
		// for operations that needs more than one argument. such as domain add/delete, especially for daemon mode.
		global $commandline;
		$this->requireCommandLine(__FUNCTION__);

		echo "(runop2) op:$op, action:$action, info:$info, info2:$info2 \n";

		switch ($op) { # info3 is usually server

			case 'daemon_backup_domain': return $this->daemon_backup_domain($info);break;
			case 'daemondomain'	: return $this->daemondomain($action,$info,$info2,$info3);	break;
			case 'daemonftp'	: return $this->daemonftp($action,$info,$info2,$info3);	break;
			case 'daemonbackup' : return $this->daemonBackup($action,$info,$info2); break;
			case 'daemonrestore': return $this->daemonRestore($action,$info,$info2); break;
			case 'installscript': return $this->installScript($action,$info,$info2); break;
			case 'downloadallscripts': return $this->downloadAllScripts(); break;
			case 'updatediskquota': return $this->updateDiskQuota($info); break;
			case 'service': 		return $this->service($info,$info2); break;
			case 'daemon_vps':		return $this->call_func_in_module('Vps_Module','daemon_vps',array('action'=>$action,'info'=>$info)); break; # array in this is params


			default: return $this->errorText("(runop2)internal ehcp error: runop2:Undefined Operation: ".$op." <br> This feature may not be complete-4");
		}// switch

	}
	function domainop(){
		global $domainname,$action,$dbusername,$dbuserpass,$dbname,$id,$confirm;
		$this->getVariable(array("domainname","action","user","pass","dbname","id",'confirm'));
		if($action=='') {
			$this->output.="userop: action not given <br>";return false;
		}
		switch($action){
			case "deletedb":
				if($confirm==''){
					$this->output.="<br>Are you sure to delete mysql db and related users ?  <a href='?op=domainop&action=deletedb&id=$id&confirm=1'>click here to delete</a><br><br>";
					$success=false;
				} else {
					$success=$this->deleteDB($id);
					$this->ok_err_text($success,'All db ops are successfull','some db ops are failed..');
					// yukardaki kodda, bircok success (basari) ile, her bir islemin sonucu ogrenilir. herhangi biri fail olsa, sonuc fail olur..
				}
				break;

			case "listdb":
				#$filter="panelusername='$this->activeuser'";
				$filter=$this->globalfilter;
				if($this->selecteddomain) $filter=andle($filter,"domainname='$this->selecteddomain'");

				$this->listTable("All mysql db's", 'mysqldbstable', $filter);
				$this->output.="<br> <a target=_blank href='/phpmyadmin/'><img src='/phpmyadmin/themes/original/img/logo_left.png' border=0></a><br>";
				$this->listTable("All mysql db users", 'mysqldbuserstable', $filter);
				$success=True;
				break;

			default: $this->output.="domainop: unknown action given: $action <br>";
		}
		$this->showSimilarFunctions('mysql');
		return $success;
	}//function

} # end of EroiApplication class

