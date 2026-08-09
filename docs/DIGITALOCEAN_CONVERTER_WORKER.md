# DigitalOcean Converter Worker

Updated: August 7, 2026

This runbook creates an external DigitalOcean worker for ETOGO digital twin conversion. Laravel Cloud remains the web app, database owner, queue owner, and private object-storage owner. DigitalOcean runs only the heavy converter process.

There are two supported ways to run the worker:

- Native Ubuntu worker: install PHP 8.2, Blender, Node conversion helpers, and PDAL directly on a Droplet. Use this first for the lower-cost DigitalOcean setup.
- Docker worker: build a self-contained image with PHP, Blender, and PDAL. Use this when the converter host needs to be fully reproducible or moved between servers.

## What This Worker Does

- Listens only to the Laravel `digital-twin` queue.
- Runs `ProcessMatterPakToGlb` jobs created by Laravel Cloud.
- Uses Blender for MatterPak OBJ/MTL/texture to GLB conversion.
- Includes PDAL through conda-forge/micromamba so the same host can later run LAS/LAZ/E57/XYZ point-cloud conversion work.
- Reads original uploads from the same private object-storage disk used by Laravel Cloud.
- Writes generated GLBs and point-cloud previews back to the same private object-storage disk.

Do not run the normal `default` queue on this worker yet. Keep billing, notifications, email, Stripe, and ordinary app jobs on Laravel Cloud.

This Droplet does need a clone of the Laravel app code, but it is not hosting the public website. It is only running the queue worker command. Laravel Cloud still handles the browser app, routes, login, admin screens, uploads, database ownership, and normal queues. The Droplet clone gives PHP access to the same `App\Jobs\ProcessMatterPakToGlb` class, models, filesystem config, and storage paths so it can pick up jobs from Laravel Cloud's database queue and write finished GLB files back to the same private bucket.

The current Laravel app requires PHP 8.2 or newer. The native setup below uses PHP 8.2 explicitly. The Docker image starts from official PHP 8.3, then installs Blender and PDAL.

## Droplet Size

Start with:

```text
Ubuntu 24.04 LTS Droplet
4 vCPU
8 GB RAM
160 GB SSD
```

Minimum for testing:

```text
2 vCPU
4 GB RAM
80 GB SSD
```

Use 8 GB RAM for real MatterPak files. Use 16 GB RAM if office/commercial MatterPak files are large or Blender exits with memory errors.

Temporary disk rule: keep at least three times the uploaded MatterPak ZIP size free on the Droplet. A 2 GB ZIP may need 6-10 GB free during extraction and conversion.

A 1 vCPU / 2 GB RAM / 70 GB disk Droplet can be used for first online testing if swap is enabled, but keep one worker process and expect large MatterPak files to be slow. Upgrade to 4-8 GB RAM if conversion fails with memory errors.

## Laravel Cloud Settings

In Laravel Cloud, keep the web app and normal workers. Make sure no Laravel Cloud worker is consuming the `digital-twin` queue unless Blender is installed there.

Laravel Cloud and this worker must run the same Git commit or at least compatible code. If Laravel Cloud auto-deploys `property-twin-current-work`, check out the same branch on the Droplet. If Laravel Cloud deploys `main`, merge this work to `main` before updating the worker.

Recommended production env values in Laravel Cloud:

```env
FILESYSTEM_DISK=private
DIGITAL_TWIN_DISK=private
QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=4200
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS=30000
DIGITAL_TWIN_UPLOAD_MAX_KB=512000
```

## External Worker Env

Copy the template:

```bash
cp deploy/digitalocean/digital-twin-worker/.env.worker.example deploy/digitalocean/digital-twin-worker/.env.worker
```

Fill these from Laravel Cloud:

- `APP_KEY`: exactly the same app key as Laravel Cloud.
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Laravel Cloud database public connection values.
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_URL`: the same private object-storage bucket credentials used by Laravel Cloud.

The `.env.worker` file stays only on the Droplet. It is ignored by Docker builds and must not be committed.

The container checks for required env values before starting the queue worker. If any required value is missing, it exits and prints the missing key names in the Docker logs.

Keep:

```env
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=4200
FILESYSTEM_DISK=private
DIGITAL_TWIN_DISK=private
DIGITAL_TWIN_BLENDER_BINARY=blender
```

If you use DigitalOcean Spaces instead of Laravel Cloud Object Storage later, point both Laravel Cloud and this worker at the same Spaces bucket.

## Native Ubuntu Worker

Use this path for a simple low-cost Droplet without Docker. It installs the converter tools directly on Ubuntu 24.04.

Current production behavior:

- Blender is the active converter used by `ProcessMatterPakToGlb` for MatterPak OBJ/MTL/textures to GLB.
- PHP preserves MatterPak JPG, PDF, XYZ, and other source records during extraction.
- PDAL is installed for future LAS/LAZ/E57/XYZ point-cloud conversion.
- `obj2gltf` and `gltf-pipeline` are installed for a future lightweight OBJ conversion path, but the Laravel job does not use them until the app code is updated.

### 1. Server Prep

SSH into the new Droplet:

```bash
ssh root@YOUR_DROPLET_IP
```

Update Ubuntu:

```bash
apt update
apt upgrade -y
apt install -y software-properties-common ca-certificates lsb-release apt-transport-https curl gnupg git unzip zip bzip2 build-essential
```

Add swap for the 2 GB Droplet:

```bash
fallocate -l 4G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
cp /etc/fstab /etc/fstab.bak
echo '/swapfile none swap sw 0 0' >> /etc/fstab
free -h
df -h
```

### 2. Install PHP 8.2 And Composer

Ubuntu 24.04 defaults to a newer PHP, so use the Ondrej PPA when PHP 8.2 is required explicitly:

```bash
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-mysql php8.2-bcmath php8.2-intl php8.2-gd php8.2-opcache
update-alternatives --set php /usr/bin/php8.2 || true
php8.2 -v
```

Install Composer:

```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
php8.2 composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

### 3. Install Converter Binaries

Install Blender:

```bash
apt install -y blender
blender --background --version
```

Install Node.js and lightweight GLTF tools:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x -o nodesource_setup.sh
bash nodesource_setup.sh
apt install -y nodejs
npm install -g obj2gltf gltf-pipeline
node -v
npm -v
obj2gltf --help | head -n 5
gltf-pipeline --help | head -n 5
```

Install PDAL through micromamba:

```bash
curl -Ls https://micro.mamba.pm/api/micromamba/linux-64/latest | tar -xvj -C /usr/local/bin --strip-components=1 bin/micromamba
mkdir -p /opt/micromamba/envs
env MAMBA_ROOT_PREFIX=/opt/micromamba micromamba create -y -p /opt/micromamba/envs/pdal -c conda-forge pdal
ln -sf /opt/micromamba/envs/pdal/bin/pdal /usr/local/bin/pdal
pdal --version
```

Optional image helpers for future texture optimization:

```bash
apt install -y imagemagick webp jpegoptim pngquant
convert -version | head -n 2
```

### 4. Clone The Same App Code As Laravel Cloud

```bash
mkdir -p /opt/etogo
cd /opt/etogo
git clone https://github.com/lordreignera/emuriapropertycare.git app
cd app
git checkout property-twin-current-work
```

Use the same branch or commit that Laravel Cloud is auto-deploying. The worker and Laravel Cloud app must agree on the job class names, database tables, and storage paths.

### 5. Connect The Worker To Laravel Cloud

Create the worker `.env` on the Droplet:

```bash
cp .env.example .env
nano .env
```

Set these values from Laravel Cloud:

```env
APP_NAME="Emuria Regenerative Property Care"
APP_ENV=production
APP_KEY=base64:PASTE_THE_SAME_APP_KEY_FROM_LARAVEL_CLOUD
APP_DEBUG=false
APP_URL=https://etogo.laravel.cloud

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=PASTE_LARAVEL_CLOUD_DATABASE_PUBLIC_HOST
DB_PORT=3306
DB_DATABASE=PASTE_DATABASE_NAME
DB_USERNAME=PASTE_DATABASE_USERNAME
DB_PASSWORD=PASTE_DATABASE_PASSWORD
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

QUEUE_CONNECTION=database
DB_QUEUE=default
DB_QUEUE_RETRY_AFTER=4200

FILESYSTEM_DISK=private
DIGITAL_TWIN_DISK=private
DIGITAL_TWIN_PROCESSING_QUEUE=digital-twin
DIGITAL_TWIN_CONVERSION_TIMEOUT=3600
DIGITAL_TWIN_WORKER_MEMORY_MB=1536
DIGITAL_TWIN_WORKER_SLEEP_SECONDS=3
DIGITAL_TWIN_WORKER_TRIES=1
DIGITAL_TWIN_BLENDER_BINARY=blender
DIGITAL_TWIN_POINT_CLOUD_PREVIEW_POINTS=30000
DIGITAL_TWIN_UPLOAD_MAX_KB=512000

AWS_ACCESS_KEY_ID=PASTE_OBJECT_STORAGE_ACCESS_KEY
AWS_SECRET_ACCESS_KEY=PASTE_OBJECT_STORAGE_SECRET
AWS_DEFAULT_REGION=PASTE_OBJECT_STORAGE_REGION
AWS_BUCKET=PASTE_OBJECT_STORAGE_BUCKET
AWS_URL=PASTE_OBJECT_STORAGE_URL_IF_LARAVEL_CLOUD_SHOWS_ONE
AWS_ENDPOINT=PASTE_OBJECT_STORAGE_ENDPOINT
AWS_USE_PATH_STYLE_ENDPOINT=false
```

In Laravel Cloud:

- Copy `APP_KEY` from the production environment variables.
- Copy the database public connection values from the production database resource. Allow the Droplet IP if the database screen asks for external access rules.
- Copy object storage credentials from the attached bucket credentials. The Droplet must read and write the same bucket used by Laravel Cloud.
- Keep the normal Laravel Cloud worker on the `default` queue only. This Droplet should consume only the `digital-twin` queue.

### 6. Install App Dependencies

```bash
cd /opt/etogo/app
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php8.2 artisan config:clear
php8.2 artisan config:cache
php8.2 artisan about
```

Confirm `.env` already has the exact same `APP_KEY` as Laravel Cloud before running the worker.

### 7. Run A Manual Worker Test

```bash
cd /opt/etogo/app
php8.2 artisan queue:work database --queue=digital-twin --sleep=3 --timeout=3600 --tries=1 --memory=1536
```

Upload a MatterPak ZIP through Laravel Cloud while this command is running. The Droplet should pick up `App\Jobs\ProcessMatterPakToGlb`, run Blender, and write the GLB back to the private object storage bucket.

Stop the manual worker with `Ctrl+C` after the first test.

### 8. Run The Worker With Systemd

Create a service:

```bash
nano /etc/systemd/system/etogo-digital-twin-worker.service
```

Paste:

```ini
[Unit]
Description=ETOGO Digital Twin Converter Worker
After=network.target

[Service]
User=root
WorkingDirectory=/opt/etogo/app
ExecStart=/usr/bin/php8.2 artisan queue:work database --queue=digital-twin --sleep=3 --timeout=3600 --tries=1 --memory=1536
Restart=always
RestartSec=5
KillSignal=SIGTERM
TimeoutStopSec=90

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
systemctl daemon-reload
systemctl enable --now etogo-digital-twin-worker
systemctl status etogo-digital-twin-worker --no-pager
journalctl -u etogo-digital-twin-worker -f
```

### 9. Update The Native Worker After Each Deploy

```bash
cd /opt/etogo/app
git pull
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php8.2 artisan config:clear
php8.2 artisan config:cache
systemctl restart etogo-digital-twin-worker
```

## Install Docker On The Droplet

SSH into the Droplet:

```bash
ssh root@YOUR_DROPLET_IP
```

Install Docker and the Compose plugin:

```bash
apt update
apt install -y ca-certificates curl gnupg git
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" > /etc/apt/sources.list.d/docker.list
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker
```

## Deploy The Worker

Clone the repo:

```bash
mkdir -p /opt/etogo
cd /opt/etogo
git clone https://github.com/lordreignera/emuriapropertycare.git app
cd app
git checkout property-twin-current-work
```

Create the worker env:

```bash
cp deploy/digitalocean/digital-twin-worker/.env.worker.example deploy/digitalocean/digital-twin-worker/.env.worker
nano deploy/digitalocean/digital-twin-worker/.env.worker
```

Build and start:

```bash
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml up -d --build
```

The first build downloads PHP dependencies, Blender packages, micromamba, and PDAL. On a fresh Droplet this can take several minutes.

Check converter binaries:

```bash
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml exec digital-twin-worker blender --version
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml exec digital-twin-worker pdal --version
```

Watch queue logs:

```bash
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml logs -f --tail=100 digital-twin-worker
```

The worker should print PHP, Blender, and PDAL versions before it starts listening to the queue.

## Update The Worker After Each Push

```bash
cd /opt/etogo/app
git pull
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml up -d --build
```

## Online Test

1. Confirm the worker is running:

```bash
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml ps
```

2. Upload a MatterPak ZIP through Laravel Cloud.
3. Watch the logs for `App\Jobs\ProcessMatterPakToGlb`.
4. Confirm the job reaches `DONE`.
5. Open the inspection digital twin page and verify:

- Capture status is `Ready`.
- The GLB layer appears.
- The card shows `Browser model`, `Texture coverage`, `Source textures`, and `Point preview` when available.

## Troubleshooting

If jobs fail immediately with a DB connection error, check Laravel Cloud's public database credentials and firewall/access rules.

If the ZIP downloads but GLB upload fails, check object-storage credentials and confirm the worker uses the same bucket/disk as Laravel Cloud.

If Blender fails with memory errors, increase the Droplet to 16 GB RAM and keep one worker process.

If jobs run twice, confirm `DB_QUEUE_RETRY_AFTER=4200` is greater than `DIGITAL_TWIN_CONVERSION_TIMEOUT=3600`.

If no jobs run, confirm Laravel Cloud is inserting jobs into the database queue and that no other worker consumes `digital-twin` first.

If the container exits during startup, run:

```bash
docker compose -f deploy/digitalocean/digital-twin-worker/docker-compose.yml logs --tail=200 digital-twin-worker
```

Common startup causes are missing `APP_KEY`, wrong database credentials, or a wrong object-storage endpoint.
