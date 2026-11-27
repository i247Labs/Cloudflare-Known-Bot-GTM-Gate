# Cloudflare Known Bot GTM Gate

This plugin prevents GTM4WP from outputting the Google Tag Manager container when Cloudflare identifies a request as a verified bot.

## 1. Install the Plugin

This is a WordPress Must-Use (MU) plugin. It does not need activation from the WordPress dashboard.

### Step 1: Open your WordPress files

Open your hosting File Manager and navigate to:

`public_html/wp-content/mu-plugins/`

If the `mu-plugins` folder does not exist, create it.

### Step 2: Upload the plugin

Upload this file into the `mu-plugins` folder:

`cloudflare-known-bot-gtm-gate.php`

Your file structure should look like this:

```text
wp-content/
└── mu-plugins/
    └── cloudflare-known-bot-gtm-gate.php
```

### Step 3: Confirm installation

Go to your WordPress Dashboard.

Navigate to:

**Plugins → Must-Use**

You should see:

**i247Labs Cloudflare Known Bot GTM Gate**

<text>There is no Activate button for MU-plugins.</text>

---

## 2. Configure Cloudflare

The plugin depends on a custom request header provided by Cloudflare.

### Step 1: Remove the incoming header

Go to:

**Cloudflare Dashboard → Your Domain → Rules → Transform Rules → Modify Request Header**

Create a rule:

- Action: Remove
- Header name: `X-I247Labs-CF-Known-Bot`
- Apply to: All incoming requests

### Step 2: Set the header for verified bots

Create another Request Header Transform Rule.

**Rule name:**

`Set i247Labs Known Bot Header`

**Expression:**

```text
(cf.client.bot)
```

**Action:**

- Action: Set static
- Header name: `X-I247Labs-CF-Known-Bot`
- Value: `1`

### Step 3: Check rule order

Ensure the rules execute in this order:

1. Remove the incoming `X-I247Labs-CF-Known-Bot` header.
2. Set the header to `1` when `(cf.client.bot)` is true.

This is important to prevent visitors from spoofing the trusted header.

---

## 3. How It Works

```text
Cloudflare receives request
          |
          v
Is it a verified bot?
          |
      +---+---+
      |       |
     YES      NO
      |       |
 Set Header   No Header
      |       |
      v       v
 WordPress   WordPress
      |       |
      v       v
 Suppress    Allow GTM4WP
 GTM4WP      output
```

When the trusted header is present, the plugin returns `false` through the GTM4WP output filter.

When the header is absent, normal GTM4WP output remains unchanged.

---

## 4. Verify the Plugin

### Check normal visitor output

Open your website normally.

1. Open the website in your browser.
2. Right-click and select **View Page Source**.
3. Search for your GTM container ID, for example:

```text
GTM-XXXXXXX
```

Your normal visitor page should contain the GTM container if GTM4WP is configured to output it.

### Check bot suppression

A request classified as a verified bot by Cloudflare should receive the custom header.

The plugin should suppress GTM4WP container output for that request.

To confirm suppression, inspect the HTML response generated for that request and check that the GTM4WP container loader and noscript iframe are absent.

---

## 5. Debug Logging

The plugin does not permanently generate diagnostic logs.

You can temporarily enable WordPress debug logging to check whether the plugin detects the trusted header and suppresses GTM4WP.

### Step 1: Enable WordPress debug logging

Open your `wp-config.php` file.

Add or update the following settings before:

`/* That's all, stop editing! Happy publishing. */`

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

If these constants already exist, update their values instead of adding duplicates.

### Step 2: Add temporary diagnostic logs

Open:

`cloudflare-known-bot-gtm-gate.php`

Inside the function `i247labs_bot_tracking_gate_send_headers()`, immediately after the header detection check, add:

```php
error_log(
    '[i247Labs BOT GATE] Cloudflare known bot detected. Ray ID: ' .
    ( $_SERVER['HTTP_CF_RAY'] ?? 'unknown' )
);
```

Inside the function `i247labs_bot_tracking_gate_disable_gtm()`, immediately before `return false;`, add:

```php
error_log(
    '[i247Labs BOT GATE] GTM SUPPRESSED. Ray ID: ' .
    ( $_SERVER['HTTP_CF_RAY'] ?? 'unknown' )
);
```

### Step 3: Check the log file

Open:

`wp-content/debug.log`

If your hosting redirects PHP error logs to a separate file, check your hosting error log instead.

Look for entries similar to:

```text
[i247Labs BOT GATE] Cloudflare known bot detected. Ray ID: abc123
[i247Labs BOT GATE] GTM SUPPRESSED. Ray ID: abc123
```

### What do the logs mean?

| Log | Meaning |
|---|---|
| Cloudflare known bot detected | The plugin recognized the trusted custom header. |
| GTM SUPPRESSED | The GTM4WP suppression filter was executed. |
| Both logs appear | The detection and suppression code paths were reached. |

The same Ray ID may appear more than once if GTM4WP calls its output filter multiple times.

Logs confirm the PHP execution path. Inspecting the actual HTML response is still necessary for HTML-level verification.

### Step 4: Disable debug logging

After testing, remove the temporary diagnostic log code from the plugin.

In `wp-config.php`, disable debugging if it is not needed:

```php
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
```

If these constants already exist, update them instead of adding duplicates.

---

## 6. Troubleshooting

### GTM is still visible for bots

Check:

- Cloudflare rules are enabled.
- The header removal rule executes before the header-setting rule.
- The header name is identical in Cloudflare and the plugin.
- The custom header reaches WordPress.
- GTM is not being injected independently by another plugin or theme.

### GTM is missing for normal visitors

Check:

- The header is not being set for all requests.
- The Cloudflare expression is correctly configured.
- Your caching system is not serving a bot-specific HTML response to normal visitors.
- GTM4WP is configured correctly.

### No debug log entries

Check:

- `WP_DEBUG` is enabled.
- `WP_DEBUG_LOG` is enabled.
- The temporary diagnostic code is correctly inserted.
- Your hosting server's PHP logging configuration.

---

## 7. Limitations

- Detects only requests identified through Cloudflare's verified bot classification.
- Does not detect every bot or automated visitor.
- Does not block bots from accessing your website.
- Suppresses GTM4WP output only.
- Does not suppress GTM injected independently by other integrations.
- Requires Cloudflare to securely manage the custom header.
- Cache behavior depends on your complete website and CDN cache configuration.

## License

GPL-2.0-or-later

## Author

i247Labs

GitHub: https://github.com/i247labs
