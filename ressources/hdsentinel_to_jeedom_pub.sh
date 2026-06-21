#!/bin/bash
# created by Flobul
# This script sends HD Sentinel reports to the Jeedom hdsentinel plugin.

SCRIPT_VERSION='0.40'
REPORT_PREFIX='/tmp/hdsentinel'

find_command()
{
  for path in "/usr/local/bin/$1" "/usr/bin/$1" "/bin/$1" "/sbin/$1" "/opt/bin/$1"; do
    if [ -x "$path" ]; then
      echo "$path"
      return 0
    fi
  done
  command -v "$1" 2>/dev/null
}

command_check()
{
  command -v "$1" >/dev/null 2>&1
}

usage()
{
  echo "Syntax: $(basename "$0") [-a|--api APIKEY] [-i|--ip URL] [-o|--output xml|html|mht]"
  echo "options:"
  echo "  -a, --api       Jeedom API key."
  echo "  -i, --ip        Jeedom URL or IP address."
  echo "  -o, --output    Output file type: xml, html or mht."
  echo "  -v, --version   Print software version and exit."
  echo "  -h, --help      Display this help."
}

normalize_jeedom_url()
{
  url="$1"
  case "$url" in
    http://*|https://*)
      echo "$url"
      ;;
    *)
      echo "http://$url"
      ;;
  esac
}

post_request()
{
  report_file="${REPORT_PREFIX}.${OUTPUT}"
  url_api="${URL_API}?apikey=${API}"
  content_type="text/${OUTPUT};charset=UTF-8"

  if [ ! -s "$report_file" ]; then
    echo "error: report file not found or empty: $report_file"
    exit 1
  fi

  if [ -n "$CURL" ]; then
    "$CURL" -fsS "$url_api" --data-binary "@${report_file}" --header "Content-Type:${content_type}" \
      || "$CURL" -fsSk "$url_api" --data-binary "@${report_file}" --header "Content-Type:${content_type}"
  elif [ -n "$WGET" ]; then
    "$WGET" -q -O - "$url_api" --post-file="$report_file" --header="Content-Type:${content_type}" \
      || "$WGET" -q -O - "$url_api" --no-check-certificate --post-file="$report_file" --header="Content-Type:${content_type}"
  else
    echo "error: curl or wget is required"
    exit 1
  fi
}

find_first_disk()
{
  if command_check lsblk; then
    disk=$(lsblk -ndo NAME,TYPE | awk '$2 == "disk" {print $1; exit}')
    [ -n "$disk" ] && echo "/dev/$disk" && return 0
  fi

  if command_check parted; then
    parted -l 2>/dev/null | awk -F: '/^Disk \/dev\// {print $1; exit}' | sed 's/^Disk //'
  fi
}

HDSENTINEL="$(find_command hdsentinel)"
WGET="$(find_command wget)"
CURL="$(find_command curl)"

POSITIONAL_ARGS=()
while [ $# -gt 0 ]; do
  case "$1" in
    -a|--api)
      API="$2"
      shift 2
      ;;
    -i|--ip)
      IP="$2"
      shift 2
      ;;
    -o|--output)
      OUTPUT="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    -v|--version)
      echo "$SCRIPT_VERSION"
      exit 0
      ;;
    -*|--*)
      echo "Unknown option $1"
      usage
      exit 1
      ;;
    *)
      POSITIONAL_ARGS+=("$1")
      shift
      ;;
  esac
done

if [ -z "$HDSENTINEL" ]; then
  echo "error: hdsentinel command not found"
  exit 1
fi
if [ -z "$API" ]; then
  echo "error: API key empty"
  exit 1
fi
if [ -z "$IP" ]; then
  echo "error: Jeedom address empty"
  exit 1
fi
case "$OUTPUT" in
  xml|html|mht)
    ;;
  '')
    echo "error: output file type empty"
    exit 1
    ;;
  *)
    echo "error: output file type not recognized: $OUTPUT"
    exit 1
    ;;
esac

JEEDOM_URL="$(normalize_jeedom_url "$IP")"
URL_API="${JEEDOM_URL%/}/plugins/hdsentinel/core/api/hdsentinel.php"

rm -f "${REPORT_PREFIX}.${OUTPUT}"
result=$("$HDSENTINEL" -"${OUTPUT}" -r "$REPORT_PREFIX" 2>&1)
echo "$result"

if echo "$result" | grep -q 'No hard disk devices found'; then
  echo "result: disk not found"
  disk="$(find_first_disk)"
  echo "disk found: ${disk}"
  if [ -n "$disk" ]; then
    "$HDSENTINEL" -dev "$disk" -"${OUTPUT}" -r "$REPORT_PREFIX"
  fi
fi

post_request
