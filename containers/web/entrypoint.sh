
set -e
set -x


echo "Wait for DB before starting site.";

php scripts/wait_for_db.php

echo "DB, site should be available."

php -S 0.0.0.0:80 -t www/


