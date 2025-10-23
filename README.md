# tgtinygate aka TGTG
A tiny PHP gateway for receiving and forwarding Telegram bot webhooks to a private backend. 

## System requirements

  * Apache 2.4 web server
  * PHP 5.3-8.4 + CURL extension

## Configuration

After downloading the files, edit `index.php` and update the following settings:

```php
$botHookUrl = 'http://yourhost.com/billing/?module=claptrapbot&auth=changeme';
$connectTimeout = 5;
$timeout = 10;
```

**Required changes:**
- **`$botHookUrl`** - Change this to your actual backend URL where Telegram webhooks should be forwarded

**Optional settings:**
- **`$connectTimeout`** - Connection timeout in seconds (default: 5)
- **`$timeout`** - Total request timeout in seconds (default: 10)

## Security Notice

**Important:** Use a random folder name instead of `tgtinygate_changeme` for better security. The folder name becomes part of your webhook URL, so using a random name makes it harder for attackers to guess your endpoint.

Example: `tgtinygate_a7b19c2d4e1f3` or just `tgtg_9W8k2m5p7q`

## Setup Instructions

# FreeBSD quick setup

```
# cd /usr/local/www/apache24/data/
# mkdir tgtinygate_changeme
# cd tgtinygate_changeme
# fetch https://raw.githubusercontent.com/nightflyza/tgtinygate/refs/heads/main/index.php
```


# Linux quick setup

```
# cd /var/www/html/
# mkdir tgtinygate_changeme
# cd tgtinygate_changeme
# wget https://raw.githubusercontent.com/nightflyza/tgtinygate/refs/heads/main/index.php
```

