#!/usr/bin/env bash
# Deploy del tema in produzione (procedura del README, in un solo comando).
# Uso: tools/deploy-prod.sh <commit-base>   # copia i file cambiati da <commit-base> a HEAD
# Esempio: tools/deploy-prod.sh 870b849
# Fa: backup sul server dei file che sta per sostituire, php -l su ogni PHP prima della copia, copia, purge LiteSpeed.
set -euo pipefail
BASE="${1:?commit base}"
KEY=/home/guido/.ssh/hostinger_mfm; PORT=65002; HOST=u528603797@76.13.12.182
D='domains/trovapulizie.it/public_html/wp-content/themes/trovapulizie'
cd "$(dirname "$0")/.."
FILES=$(git diff --name-only "$BASE"..HEAD | tr '\n' ' ')
[ -n "$FILES" ] || { echo "nessun file da copiare"; exit 0; }
echo "file: $(echo $FILES | wc -w)"
TGZ=$(mktemp /tmp/tp-deploy.XXXXXX.tgz)
tar czf "$TGZ" $FILES
scp -i "$KEY" -P "$PORT" -q "$TGZ" "$HOST:~/tp-deploy.tgz"
rm -f "$TGZ"
ssh -i "$KEY" -p "$PORT" -o BatchMode=yes "$HOST" "set -e
cd ~/$D && EX=\$(for f in $FILES; do [ -f \$f ] && echo \$f; done); if [ -n \"\$EX\" ]; then tar czf ~/tp-backup-\$(date +%Y%m%d-%H%M)-pre-deploy.tgz \$EX && echo 'backup ok'; else echo 'backup: nessun file esistente da salvare'; fi
rm -rf ~/tp-stage && mkdir ~/tp-stage && tar xzf ~/tp-deploy.tgz -C ~/tp-stage
cd ~/tp-stage && for f in \$(find . -name '*.php'); do php -l \$f > /dev/null; done && echo 'php -l: nessun errore'
cp -r ~/tp-stage/. ~/$D/ && echo 'copiato'
cd ~/domains/trovapulizie.it/public_html && wp litespeed-purge all 2>&1 | tail -1
grep -m1 '^Version' ~/$D/style.css
rm -rf ~/tp-stage ~/tp-deploy.tgz"
