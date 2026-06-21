#!/bin/bash
# Contributor: Flobul <flobul.jeedom@gmail.com>

SCRIPT_VERSION='0.31'
INSTALL_PATH='/usr/local/bin/hdsentinel'
TMP_DIR="${TMPDIR:-/tmp}/hdsentinel-install.$$"

find_command()
{
  for cmd in "$@"; do
    path="$(command -v "$cmd" 2>/dev/null)"
    if [ -n "$path" ]; then
      echo "$path"
      return 0
    fi
    for dir in /usr/local/bin /usr/bin /bin /usr/sbin /sbin /opt/bin /usr/syno/bin; do
      if [ -x "$dir/$cmd" ]; then
        echo "$dir/$cmd"
        return 0
      fi
    done
  done
  return 1
}

command_check()
{
  find_command "$1" >/dev/null 2>&1
}

progress()
{
  echo "$1" "$2"
}

usage()
{
  echo "usage: $(basename "$0") [-v|--version] [-h|--help] [-f|--force]"
}

cleanup()
{
  rm -rf "$TMP_DIR"
}

install_package()
{
  package="$1"
  if command_check apt-get; then
    apt-get update
    apt-get install -y "$package"
  elif command_check apt; then
    apt install -y "$package"
  else
    return 1
  fi
}

require_command()
{
  cmd="$1"
  package="${2:-$1}"
  if command_check "$cmd"; then
    return 0
  fi
  progress 20 "Installation du paquet ${package}"
  install_package "$package" && command_check "$cmd"
}

get_synology_platform()
{
  for file in /etc.defaults/synoinfo.conf /etc/synoinfo.conf; do
    if [ -r "$file" ]; then
      platform=$(awk -F'=' '/^(unique|upnpmodelname|cpu_arch)=/ {gsub(/"/, "", $2); print $2; exit}' "$file")
      if [ -n "$platform" ]; then
        echo "$platform" | sed -E 's/^synology_([^_]+).*/\1/'
        return 0
      fi
    fi
  done
  return 1
}

get_hdsentinel_arch()
{
  arch="$(uname -m)"
  bits="$(getconf LONG_BIT 2>/dev/null || echo '')"

  if [ -e /etc/synoinfo.conf ] || [ -e /etc.defaults/synoinfo.conf ]; then
    syno_platform="$(get_synology_platform)"
    case "$syno_platform" in
      r1000|v1000|denverton|geminilake|broadwellnk|broadwellnkv2|apollolake|grantley|broadwell|dockerx64|kvmx64|braswell|avoton|cedarview|bromolow|epyc7002|x86)
        echo "x64"
        return 0
        ;;
      armada37xx|rtd1293|rtd1296|rtd1619|rtd1619b)
        echo "armv8"
        return 0
        ;;
      armada38x|alpine|alpine4k|armada375|armada370|armadaxp|comcerto2k|hi3535|monaco|ipq806x|northstarplus)
        echo "armv7"
        return 0
        ;;
      88f5281|88f6281|88f6282)
        echo "armv5"
        return 0
        ;;
      evansport)
        echo "x86"
        return 0
        ;;
      qoriq|ppc824x|ppc853x|ppc854x|powerpc|ppc*)
        echo "unsupported:${syno_platform}"
        return 0
        ;;
    esac
  fi

  case "$arch" in
    x86_64|amd64)
      echo "x64"
      ;;
    i386|i486|i586|i686)
      echo "x86"
      ;;
    armv5*|armv5)
      echo "armv5"
      ;;
    armv6*|armv6)
      echo "armv6"
      ;;
    armv7*|armv7)
      echo "armv7"
      ;;
    armv8*|aarch64)
      echo "armv8"
      ;;
    *)
      if [ "$bits" = "64" ]; then
        echo "x64"
      elif [ "$bits" = "32" ]; then
        echo "x86"
      else
        echo "unsupported:${arch}"
      fi
      ;;
  esac
}

get_download_url()
{
  case "$1" in
    x64)
      echo "https://www.hdsentinel.com/hdslin/hdsentinel-020c-x64.zip"
      ;;
    x86)
      echo "https://www.hdsentinel.com/hdslin/hdsentinel-019b.gz"
      ;;
    armv5)
      echo "https://www.hdsentinel.com/hdslin/armv5/hdsentinelarm"
      ;;
    armv6)
      echo "https://www.hdsentinel.com/hdslin/hdsentinel-020-arm.gz"
      ;;
    armv7)
      echo "https://www.hdsentinel.com/hdslin/hdsentinel-armv7.gz"
      ;;
    armv8)
      echo "https://www.hdsentinel.com/hdslin/hdsentinel-armv8.bz2"
      ;;
    *)
      return 1
      ;;
  esac
}

download_file()
{
  url="$1"
  output="$2"
  if command_check wget; then
    wget -O "$output" "$url"
  elif command_check curl; then
    curl -L -o "$output" "$url"
  else
    progress 20 "Installation de wget"
    require_command wget wget && wget -O "$output" "$url"
  fi
}

select_extracted_binary()
{
  directory="$1"

  find "$directory" -type f -print | while IFS= read -r file; do
    base="$(basename "$file")"
    lower="$(printf '%s' "$base" | tr '[:upper:]' '[:lower:]')"
    case "$lower" in
      hdsentinel|hdsentinel-*|hdsentinel_*|hdsentinelarm)
        case "$lower" in
          *.txt|*.pdf|*.htm|*.html|*.xml|*.ini|*.cfg|*.md)
            ;;
          *)
            echo "$file"
            break
            ;;
        esac
        ;;
    esac
  done | sed -n '1p'
}

select_first_payload_file()
{
  directory="$1"

  find "$directory" -type f -print | while IFS= read -r file; do
    base="$(basename "$file")"
    lower="$(printf '%s' "$base" | tr '[:upper:]' '[:lower:]')"
    case "$lower" in
      *.txt|*.pdf|*.htm|*.html|*.xml|*.ini|*.cfg|*.md)
        ;;
      *)
        echo "$file"
        break
        ;;
    esac
  done | sed -n '1p'
}

extract_zip_binary()
{
  archive="$1"
  output="$2"
  destination="$TMP_DIR/zip"
  rm -rf "$destination"
  mkdir -p "$destination" || return 1

  extractor="$(find_command unzip 2>/dev/null || true)"
  if [ -n "$extractor" ]; then
    "$extractor" -q "$archive" -d "$destination" >/dev/null 2>&1 \
      || "$extractor" "$archive" -d "$destination" >/dev/null 2>&1 \
      || return 1
  else
    extractor="$(find_command 7z 7za 2>/dev/null || true)"
    if [ -n "$extractor" ]; then
      "$extractor" x -y "-o$destination" "$archive" >/dev/null 2>&1 || return 1
    else
      extractor="$(find_command bsdtar 2>/dev/null || true)"
      if [ -n "$extractor" ]; then
        "$extractor" -xf "$archive" -C "$destination" >/dev/null 2>&1 || return 1
      else
        extractor="$(find_command python3 python 2>/dev/null || true)"
        if [ -n "$extractor" ]; then
          "$extractor" - "$archive" "$destination" <<'PY' || return 1
import sys
import zipfile

archive = zipfile.ZipFile(sys.argv[1])
try:
    archive.extractall(sys.argv[2])
finally:
    archive.close()
PY
        elif command_check apt-get || command_check apt; then
          require_command unzip unzip || return 1
          extractor="$(find_command unzip 2>/dev/null || true)"
          [ -n "$extractor" ] || return 1
          "$extractor" -q "$archive" -d "$destination" >/dev/null 2>&1 \
            || "$extractor" "$archive" -d "$destination" >/dev/null 2>&1 \
            || return 1
        else
          echo "Aucun extracteur ZIP disponible : installez unzip, 7z, bsdtar ou python3"
          return 1
        fi
      fi
    fi
  fi

  candidate="$(select_extracted_binary "$destination")"
  if [ -z "$candidate" ]; then
    candidate="$(select_first_payload_file "$destination")"
  fi
  if [ -z "$candidate" ]; then
    echo "Contenu ZIP extrait :"
    find "$destination" -type f -print
    return 1
  fi

  echo "90 Binaire extrait=$(basename "$candidate")"
  cp "$candidate" "$output"
}

extract_binary()
{
  archive="$1"
  extension="$2"
  output="$3"

  case "$extension" in
    gz)
      require_command gzip gzip || return 1
      gzip -dc "$archive" > "$output"
      ;;
    bz2)
      require_command bzip2 bzip2 || return 1
      bzip2 -dc "$archive" > "$output"
      ;;
    zip)
      extract_zip_binary "$archive" "$output"
      ;;
    hdsentinelarm|bin|none)
      cp "$archive" "$output"
      ;;
    *)
      return 1
      ;;
  esac
}

main()
{
  trap cleanup EXIT
  mkdir -p "$TMP_DIR"

  progress 10 "Vérification du système"
  arch="$(uname -m)"
  bits="$(getconf LONG_BIT 2>/dev/null || echo 'unknown')"
  hds_arch="$(get_hdsentinel_arch)"

  if echo "$hds_arch" | grep -q '^unsupported:'; then
    echo "ARCH=$arch; BITS=$bits; HDS_ARCH=$hds_arch; SCRIPT_VERSION=$SCRIPT_VERSION"
    echo "100 Installation en erreur : architecture non supportée par HD Sentinel"
    exit 1
  fi

  url="$(get_download_url "$hds_arch")"
  if [ -z "$url" ]; then
    echo "100 Installation en erreur : URL introuvable pour $hds_arch"
    exit 1
  fi

  extension="${url##*.}"
  [ "$extension" = "$url" ] && extension="none"
  archive="$TMP_DIR/hdsentinel.$extension"
  binary="$TMP_DIR/hdsentinel"

  echo "ARCH=$arch; BITS=$bits; HDS_ARCH=$hds_arch; USER=$USER; PWD=$(pwd); SCRIPT_VERSION=$SCRIPT_VERSION"
  echo "50 URL=$url; EXTENSION=$extension"

  progress 70 "Téléchargement"
  if ! download_file "$url" "$archive"; then
    echo "100 Installation en erreur : téléchargement impossible"
    exit 1
  fi

  progress 80 "Décompression et installation"
  if ! extract_binary "$archive" "$extension" "$binary"; then
    echo "100 Installation en erreur : décompression impossible (${extension})"
    exit 1
  fi

  chmod +x "$binary"
  rm -f "$INSTALL_PATH"
  if ! mv "$binary" "$INSTALL_PATH"; then
    echo "100 Installation en erreur : impossible d'écrire $INSTALL_PATH"
    exit 1
  fi
  chmod +x "$INSTALL_PATH"

  if "$INSTALL_PATH" -r >/dev/null 2>&1 || [ -x "$INSTALL_PATH" ]; then
    echo "100 Installation avec succès"
  else
    echo "100 Installation en erreur"
    exit 1
  fi
}

case "$1" in
  -h|--help)
    usage
    exit 0
    ;;
  -v|--version)
    echo "$SCRIPT_VERSION"
    exit 0
    ;;
  -f|--force|'')
    main "$1"
    ;;
  *)
    usage
    exit 1
    ;;
esac
