#!/bin/bash
# Contributor: Flobul <flobul.jeedom@gmail.com>

SCRIPT_VERSION='0.11'

function main ()
{
  echo 10 "Vérification du système" $1
  arch=`arch`;
  bits=$(getconf LONG_BIT);
  uncompress="gunzip";

  hash hdsentinel 2>/dev/null;
  if [ $? -eq 0 ] && [ ! "$1" = "force" ]
    then
    version_installed=$(hdsentinel -h | head -n 1 | awk -F ' ' '{print $7 }');
    echo 'Hdsentinel déjà installé : version' $version_installed;
    echo 100 "Installation annulée";
    exit 1
  fi
  echo "ARCH="$arch"; BITS:"$bits;

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
  echo "URL=" $url;

  echo 80 "Téléchargement et installation"
  if [ "$uncompress" == "none" ]
  then
    wget -q -O /usr/bin/hdsentinel "$url";
  else
    wget -q -O /tmp/hdsentinel.$extension "$url";
    bash -c "$uncompress -c /tmp/hdsentinel.$extension > /usr/bin/hdsentinel"
  fi
  chmod +x /usr/bin/hdsentinel;
  end=" en erreur";

  if [ -f /usr/bin/hdsentinel ]
    then
    end=" avec succès"
  fi
  echo 100 "Installation"$end
}

function usage()
{
    echo "usage: $(basename $0) [-v|--version] [-h|--help] [-f|--force]"
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