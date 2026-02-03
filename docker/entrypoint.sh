# Créez le dossier docker s'il n'existe pas
mkdir -p docker

# Créez le fichier entrypoint.sh
cat > docker/entrypoint.sh << 'EOF'
#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
until mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1" &> /dev/null
do
  echo "MySQL is unavailable - sleeping"
  sleep 2
done

echo "MySQL is up - executing command"
exec "$@"
EOF

# Rendez-le exécutable
chmod +x docker/entrypoint.sh