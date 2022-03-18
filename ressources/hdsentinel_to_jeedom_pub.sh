#!/bin/bash
# created by Flobul
# This script send value to Jeedom by giving equipment_id and name
# Needed : APIkey from plugin hdsentinel + jeedom IP

#############################
# DECLARATION DES VARIABLES #
#############################
SCRIPT_VERSION='0.29'
HDSENTINEL=$(which hdsentinel)
if [ -f /usr/local/bin/hdsentinel ]; then
  HDSENTINEL='/usr/local/bin/hdsentinel'
else if [ -f /usr/bin/hdsentinel ]; then
  HDSENTINEL='/usr/bin/hdsentinel'
else if [ -f /bin/hdsentinel ]; then
  HDSENTINEL='/bin/hdsentinel'
else if [ -f /sbin/hdsentinel ]; then
  HDSENTINEL='/sbin/hdsentinel'
fi

#############################
# DECLARATION DES FONCTIONS #
#############################
function usage () {
   echo "Syntax: $(basename $0) [-a|i|v|o]"
   echo "options:"
   echo "a     jeedom API key."
   echo "i     jeedom address IP."
   echo "o     output file type. (mht, html or xml)"
   echo "v     Print software version and exit."
   echo
}

function help ()
{
   # Display Help
   echo "Send value to jeedom plugin : hdsentinel"
   echo
   usage
}

function command_check ()       # needs: command
{
  if command -v $1 > /dev/null; then
    return 0
  else
    return 1
  fi
}

function retour_erreur ()
{
  CODE_ERREUR=$?
  echo $1 "error code $CODE_ERREUR: Trying insecure http"
  if [[ ${CODE_ERREUR} -eq 60 || ${CODE_ERREUR} -eq 0  && $1 -eq curl ]]; then
    /usr/bin/curl -i ${URL_API}'?apikey='${API} -k --form file=@/tmp/hdsentinel.${OUTPUT} --header "Content-Type:text/${OUTPUT};charset=UTF-8"
  elif [[ ${CODE_ERREUR} -eq 5 && $1 -eq wget ]]; then
    /usr/bin/wget ${URL_API}'?apikey='${API} --no-check-certificate --post-file=/tmp/hdsentinel.${OUTPUT} --header='Content-Type:text/${OUTPUT};charset=UTF-8'
  fi
}

function postRequest ()
{
  if command_check curl ; then
    /usr/bin/curl -i ${URL_API}'?apikey='${API} --form file=@/tmp/hdsentinel.${OUTPUT} --header "Content-Type:text/${OUTPUT};charset=UTF-8"
    retour_erreur curl
  elif command_check wget ; then
    /usr/bin/wget ${URL_API}'?apikey='${API} --post-file=/tmp/hdsentinel.${OUTPUT} --header='Content-Type:text/${OUTPUT};charset=UTF-8'
    retour_erreur wget
  fi
}

POSITIONAL_ARGS=()

while [[ $# -gt 0 ]]; do
  case $1 in
    -a|--api)
      API="$2"
      shift
      shift
      ;;
    -i|--ip)
      IP="$2"
      shift
      shift
      ;;
    -o|--output)
      OUTPUT="$2"
      shift
      shift
      ;;
    -h|--help)
      help
      exit 1
      ;;
    -v|--version)
      echo ${SCRIPT_VERSION}
      exit 1
      ;;
    -*|--*)
      echo "Unknown option $1"
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1") # save positional arg
      shift # past argument
      ;;
  esac
done

if [[ -z ${API} ]]; then
    echo "error: API key empty"
    exit 0
fi
if [[ -z ${IP} ]]; then
    echo "error: Address IP empty"
    exit 0
fi
if [[ -z ${OUTPUT} ]]; then
    echo "error: output file type empty";
    if [ "${OUTPUT}" != 'xml' ] && [ "${OUTPUT}" != 'html' ] && [ "${OUTPUT}" != 'mht' ]; then
        echo "error: output file type not recognized "${OUTPUT};
    fi
    exit 0;
fi


URL_API="${IP}/plugins/hdsentinel/core/api/hdsentinel.php"

result=$($HDSENTINEL -"${OUTPUT}" -r /tmp/hdsentinel)

if [[ ${result} =~ 'No hard disk devices found' ]]; then
    echo "result: disk not found"
    if command_check lsblk ; then
        disk=$(lsblk --output NAME | grep -v NAME| head -n 1)
    elif command_check parted ; then
        disk=$(parted -l | grep 'Disk /dev' | sed -e 's/Disk \(.*\):.*/\1/')
    fi
    echo "disk found: ${disk}"
    [[ ${disk} != '' ]] && $HDSENTINEL -dev /dev/"${disk}" -"${OUTPUT}" -r /tmp/hdsentinel
fi

postRequest
