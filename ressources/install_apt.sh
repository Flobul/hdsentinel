#!/bin/bash
# Contributor: Flobul <flobul.jeedom@gmail.com>

SCRIPT_VERSION='0.17'
if [ -e  /etc/synoinfo.conf ]; then
  arch=`uname -m`;
  pwd=`pwd`;
  bits=${arch#*_};
else
  arch=`arch`;
  pwd=`pwd`;
  bits=$(getconf LONG_BIT);
fi

function main ()
{
  echo 10 "Vérification du système" $1
  if command_check $uncompress ; then
    uncompress="gzip";
  else
    apt install gzip -f
  fi
  echo "ARCH="$arch"; BITS="$bits"; USER="$USER"; PWD="$pwd"; SCRIPT_VERSION="$SCRIPT_VERSION;

  echo 30 "Suppression ancienne installation"
  rm /usr/local/bin/hdsentinel;

  echo 50 "Récupération URL"
  if [ "$arch" == "armv6l" ]
  then
    url="https://www.hdsentinel.com/hdslin/hdsentinel-018-arm.gz";
  elif [ "$arch" == "armv5" ]
  then
    url="https://www.hdsentinel.com/hdslin/armv5/hdsentinelarm";
    uncompress="none";
  elif [[ "$arch" == "armv7" || "$arch" == "armv7l" ]]
  then
    url="https://www.hdsentinel.com/hdslin/hdsentinel-armv7.gz";
  elif [ "$arch" == "armv8" ]
  then
    uncompress="bzip2";
    url="https://www.hdsentinel.com/hdslin/hdsentinel-armv8.bz2";
  elif [ "$arch" == "aarch64" ]
  then
    uncompress="bzip2";
    url="https://www.hdsentinel.com/hdslin/hdsentinel-armv8.bz2";
  elif [ "$arch" == "x86_64" ]
  then
    url="https://www.hdsentinel.com/hdslin/hdsentinel-019c-x64.gz";
  else
    if [ "$bits" -eq "32" ]
    then
      url="https://www.hdsentinel.com/hdslin/hdsentinel-019b.gz";
    elif [ "$bits" -eq "64" ]
    then
      url="https://www.hdsentinel.com/hdslin/hdsentinel-019c-x64.gz";
    fi
  fi
  extension="${url##*.}";
  echo 70 "URL récupérée="$url"; uncompress="$uncompress"; extension="$extension;

  echo 80 "Téléchargement et installation"
  if [ "$uncompress" == "none" ]
  then
    wget -O /usr/local/bin/hdsentinel "$url";
  else
    wget -O $pwd/hdsentinel.$extension "$url";
    bash -c "$uncompress -d $pwd/hdsentinel.$extension"
    mv $pwd/hdsentinel /usr/local/bin/hdsentinel
    ##rm $pwd/hdsentinel.$extension
  fi
  chmod +x /usr/local/bin/hdsentinel;
  end=" en erreur";

  if [ -f /usr/local/bin/hdsentinel ]
    then
    end=" avec succès"
  fi
  echo 100 "Installation"$end
}

function usage()
{
    echo "usage: $(basename $0) [-v|--version] [-h|--help] [-f|--force]"
}

function command_check ()
{
  if command -v $1 > /dev/null; then
    return 0
  else
    return 1
  fi
}

if [[ ( $@ == "--help") || ( $@ == "-h" ) ]];
  then
  usage
  exit 0
elif [[ ( $@ == "--version") || ( $@ == "-v" ) ]];
  then
  echo "$SCRIPT_VERSION"
  exit 0
elif [[ ( $@ == "--force") || ( $@ == "-f" ) ]];
  then
  main "force"
else
  main
fi
