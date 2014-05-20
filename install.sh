#!/bin/bash
# ehcp - Easy Hosting Control Panel install/remove by info@ehcp.net (actually, no remove yet)
# this is a very basic shell installer, real installation in install_lib.php, which is called by install_1.php, install_2.php
#
# please contact me if you made any modifications.. or you need help
# msn/email: info@ehcp.net
# skype/yahoo/gtalk: bvidinli

# Marcel <marcelbutucea@gmail.com>
#	   - added initial support for yum (RedHat/CentOS)
#	   - some code ordering, documentation and cleanup
#


ehcpversion="0.30.8"

echo
echo
#echo "Please Wait... initializing.a	  If something is very slow, please report on our site."
echo
echo
chmod -Rf a+r *

if [ "$1" == "noapt" ] ; then
	noapt="noapt"
fi

################################################################################################
# Function Definitions																		 #
################################################################################################

# Stub function for apt-get

function installaptget () {
	echo "now let's try to install apt-get on your system."
	echo "Not yet implemented"
	exit
}

# Stub function fot yum

function installyum () {
	echo "now let's try to install yum on your system."
	echo "Not yet implemented"
}

# Initial Welcome Screen

function ehcpHeader() {
	echo
	echo
	echo "STAGE 1"
	echo "====================================================================="
	echo
	echo "--------------------EHCP PRE-INSTALLER $ehcpversion -------------------------"
	echo "-----Easy Hosting Control Panel for Ubuntu, Debian and alikes--------"
	echo "-------------------------www.ehcp.net--------------------------------"
	echo "---------------------------------------------------------------------"
	echo
	echo
	echo "Now, ehcp pre-installer begins, a series of operations will be performed and main installer will be invoked. "
	echo "if any problem occurs, refer to www.ehcp.net forum section, or contact me, mail/msn: info@ehcp.net"

	echo "Pleaes be patient, press enter to continue"
	read
	echo
	echo "Note that ehcp can only be installed automatically on Debian based Linux OS'es or Linux'es with apt-get enabled..(Ubuntu, Kubuntu, debian and so on) Do not try to install ehcp with this installer on redhat, centos and non-debian Linux's... To use ehcp on no-debian systems, you need to manually install.. "
	echo "press enter to continue"
	read
}

# Check for yum

function checkyum () {
	which yum > /dev/null 2>&1
	if [ "$?" == "0"  ]; then
		echo "yum is available"
		return 0
	else
		# This should never happen
		echo "Please install yum"
		installyum
	fi
}

# Check for apt-get

function checkAptget(){

	sayi=`which apt-get | wc -w`
	if [ $sayi -eq 0 ] ; then
		ehco "apt-get is not found."
		installaptget
	fi

	echo "apt-get seems to be installed on your system."


	sayi=`grep -v "#" /etc/apt/sources.list | wc -l`

	if [ $sayi -lt 10 ] ; then
		echo
		echo "WARNING ! Your /etc/apt/sources.list  file contains very few sources, This may cause problems installing some packages.. see http://www.ehcp.net/?q=node/389 for an example file"
		echo "This may be normal for some versions of debian"
		echo "press enter to continue or Ctrl-C to cancel and fix that file"
		read
	fi

}


# Retrieve statistics
# Marcel: This freezed the installer on one of my Centos Servers (needs more investigating)
# bvidinli:answer: this infomail may be disabled, only for statistical purposes... may hang if for 10 second if user is not connected to internet, or something is wrong with wget or dns resolution...
# no hanging longer than 10 sec should occur... i think.. btw, your code is perfect, Marcel

function infoMail(){
	ip=`ifconfig | grep "inet addr" | grep -v "127.0.0.1" | awk '{print $2}' `
	wget -q -O /dev/null --timeout=10 http://www.iyibirisi.com/diger/msg.php?msg=$1.$ip > /dev/null 2>&1 &
	# echo "(infoMail) your ip is: $ip"
}

# Function to be called when installing packages, by Marcel <marcelbutucea@gmail.com>

function installPack(){

	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get install for:$1"
		return
	fi

	if [ $distro == "ubuntu" ] || [ $distro == "debian" ];then
		# first, try to install without any prompt, then if anything goes wrong, normal install..
		apt-get -y --no-remove --allow-unauthenticated install $1
		if [ $? -ne 0 ]; then
				apt-get --allow-unauthenticated install $1
		fi
	else
		# Yum is nice, you don't get prompted :)
		yum -y -t install $1
	fi
}

function logToFile(){
	logfile="ehcp-apt-get-install.log"
	echo "$1" >> $logfile
}

function aptget_Update(){
	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get update"
		return
	fi

	apt-get update
}

function aptgetInstall(){

	if [ -n "$noapt" ] ; then  # skip install
		echo "skipping apt-get install for:$1"
		return
	fi

	# first, try to install without any prompt, then if anything goes wrong, normal install..
	cmd="apt-get -y --no-remove --allow-unauthenticated install $1"
	logToFile "$cmd"
	$cmd

	if [ $? -ne 0 ]; then
		cmd="apt-get --allow-unauthenticated install $1"
		logToFile "$cmd"
		$cmd
	fi

}

# Get distro name , by Marcel <marcelbutucea@gmail.com>, thanks to marcel for fixing whole code syntax
function checkDistro() {
		cat /etc/*release | grep -i ubuntu &> /dev/null && distro="ubuntu"
		#cat /etc/*release | grep -i red  &> /dev/null && distro="redhat" # not yet supported
		#cat /etc/*release | grep -i centos && distro="centos"
		cat /etc/*release | grep -i debian &> /dev/null && distro="debian"
		echo "Your distro is $distro"
}

# Check if the running user is root, if not restart with sudo
function checkUser() {
		if [ `whoami` != "root" ];then
				echo "you are $who, you have to be root to use ehcp installation program.  switching to root mode, please enter password  or re-run install.sh as root"
				sudo $0 # restart this with superuser-root privileges
				exit
		fi
}

# Function to kill any running ehcp / php daemons
function killallEhcp() {
		for i in `ps aux | grep ehcpdaemon.sh | grep -v grep | awk -F " " '{ print $2 }'`;do
				kill -9 $i
		done

		for i in `ps aux | grep 'php index.php' | grep -v grep | awk -F " " '{ print $2 }'`;do
				kill -9 $i
		done
}


function checkPhp(){
	which php
	if [ $? -eq 0 ] ; then
		echo "php seems installed. This is good.."
	else
		echo "PHP IS STILL NOT INSTALLED. THIS IS A SERIOUS PROBLEM.  MOST PROBABLY, YOU WILL NOT BE ABLE TO CONTINUE. TRY TO INSTLL PHP yourself."
		echo "if rest of install is successfull, then, this is a false alarm, just ignore"
	fi
}

function launchPanel(){
	firefox=`which firefox`
	if [ -n "$firefox" ] ; then
		echo "now, you should be able to navigate to your"
		echo "panel admin username: admin "
		echo "now will try to launch your control panel, if it is on local computer.. "
		echo -e "\nwill use firefox as browser...\n\n"
		$firefox "http://localhost" &
	fi
}

#############################################################
# End Functions & Start Install							 #
#############################################################


#echo "`date`: initializing.b"  # i added these echo's because on some system, some stages are very slow. i need to investigate that.
#infoMail "ehcp_1_installstarted_ver_$ehcpversion"
checkUser
#echo "`date`: initializing.c"
ehcpHeader
/etc/init.d/apparmor stop & > /dev/null  # apparmor causes many problems..
#echo "`date`: initializing.d"
checkDistro
#echo "`date`: initializing.e"
killallEhcp
#echo "`date`: initializing.f"

if [ -z "$distro" ];then
		echo "Your system detected to be $distro, You may not install ehcp automatically on this system, anyway, to continue press enter"
		read
fi


checkAptget

#----------------------start some install --------------------------------------
#echo "`date`: initializing.g"
mkdir /etc/ehcp


aptget_Update

aptgetInstall python-software-properties # for add-apt-repository
#add-apt-repository ppa:brianmercer/php  # for nginx

aptget_Update # for above ppa


#echo "`date`: initializing.h"
# apt-get upgrade  # may be cancelled later... this may be dangerous... server owner should do it manually...
aptgetInstall php5
aptgetInstall php5-mysql
aptgetInstall php5-cli
aptgetInstall sudo
aptgetInstall wget
aptgetInstall aptitude

#outside_ip=`echo "" > ip ; wget -q -O ip http://ehcp.net/diger/myip.php ; echo ip`
#rm -f ip


echo
echo

checkPhp
echo
echo
echo
echo "STAGE 2"
echo "====================================================================="
echo "now running install_1.php "
#infoMail ehcp_2_install-starting-install_1.php
php install_1.php $noapt

echo
echo
echo "STAGE 3"
echo "====================================================================="
echo "now running install_2.php "
#infoMail ehcp_2_2_install-starting-install_2.php

#php install_2.php $noapt || php /etc/ehcp/install_2.php $noapt  # start install_2.php if first install is successfull at php level. to prevent many errors.
php install_2.php $noapt


mv /var/www/new/ehcp/install_?.php /etc/ehcp/   # move it, to prevent later unauthorized access of installer from web


echo "now running ehcp daemon.."
cd /var/log
/etc/init.d/ehcp restart
echo "ehcp run/restart complete.."

launchPanel


infoMail "ehcp_8_install-finished-install.sh_ver_$ehcpversion.$outside_ip"

# you may disable following lines, these are for debug/check purposes.

ps aux > debug.txt
echo "============================================"  >> debug.txt
tail -100 /var/log/syslog >> debug.txt

cat debug.txt | sendmail bvidinli@gmail.com > /dev/null 2>&1

echo "ehcp : Finished all operations.. go to your panel at http://yourip/ now..."
