# MPS Care Plan Support Widget

A small WordPress plugin that loads a [Help Scout Beacon](https://www.helpscout.com/platform/beacon/) inside the WordPress admin, so administrators can start a support chat or open a ticket without leaving their dashboard.

It is built and maintained by [My Project Solution](https://myprojectsolution.com) for sites on an MPS Care Plan, but there is nothing MPS-specific in the code — see [Using this on your own site](#using-this-on-your-own-site) below.

## What it does

- Loads the Help Scout Beacon **only in the WordPress admin**, never on the public-facing site.
- Loads it **only for logged-in users with the Administrator role**, so editors, authors, and customers never see it.
- Enables Help Scout **Secure Mode**: the plugin signs the current administrator's email address with an HMAC-SHA256 signature, so Help Scout can verify who they are. This ties conversations to the right person and lets them see their own past tickets across devices, while preventing anyone from impersonating another user in chat.
- Identifies the user by display name, falling back to first/last name, then to their username.
- Does nothing at all until both a Beacon ID and a Secure Mode key are configured. Without them, no Beacon script is loaded.

## Requirements

- WordPress 5.8 or later
- PHP 7.2 or later
- A [Help Scout](https://www.helpscout.com) account with a Beacon

## Installation

1. Download `mps-care-plan-widget.zip` from the [latest release](https://github.com/My-Project-Solution/MPS-Care-Plan-Support-Widget/releases/latest).
2. In WP Admin, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate it.
4. Configure it under **Settings → MPS Care Plan** (see below).

Once installed, the plugin checks this repository for new releases and updates itself through the normal WordPress **Plugins → Updates** screen. No separate updater plugin is needed.

> Install the release zip rather than a clone of this repository — the zip has the folder name WordPress expects, which is what lets updates work.

## Configuration

You need two values from Help Scout, both specific to a single Beacon.

**Beacon ID** — a public identifier, safe to appear in page source.

1. In Help Scout, go to **Manage → Beacons** and select your Beacon.
2. Open the **Installation** tab.
3. The ID is the UUID passed to `Beacon('init', '...')` in the embed snippet.

**Secure Mode key** — a secret. Treat it like a password.

1. In Help Scout, go to **Manage → Beacons** and select your Beacon.
2. Click **Contact** in the left sidebar.
3. Under _Contact Form_, turn on **Enable cross-device history**. The key is revealed once this is enabled.
4. There is a **Regenerate key** link on the same page if you ever need to rotate it.

Then enter both under **Settings → MPS Care Plan** and save.

### Defining the keys in `wp-config.php`

If you'd rather keep the keys out of the database — useful when deploying across several sites — define either or both as constants:

```php
define( 'MPS_CARE_BEACON_ID',  'your-beacon-id' );
define( 'MPS_CARE_SECURE_KEY', 'your-secure-mode-key' );
```

A constant takes precedence over the saved setting, and the settings page will show the value as config-defined rather than offering a field for it.

### A note on the Secure Mode key

The Secure Mode key is unique per Beacon and shared by every site using that Beacon. Anyone holding it can generate a valid signature for **any** email address, which means they can impersonate any of your Help Scout customers in chat.

The plugin never renders the key back into the settings page — once saved, the field stays blank and shows only that a key exists. Leave it blank to keep the saved key, enter a new value to replace it, or tick **Remove the saved key** to clear it. Even so, anyone who is already an administrator on the site can read the key from the database or from `wp-config.php`, so only install this on sites whose administrators you trust with that Beacon. If you deploy across sites with different trust levels, give each its own Beacon so a leak is contained to one.

## Using this on your own site

You're welcome to. Nothing here is tied to My Project Solution's Help Scout account — the Beacon ID and Secure Mode key are entirely yours, and a fresh install with your own credentials sends support conversations to **your** Help Scout inbox, not ours.

Two things to know:

- **Support requests reach whoever owns the Beacon.** Configure it with your own Beacon and your own team answers them. My Project Solution does not provide support for third-party installations of this plugin, and MPS cannot see conversations from Beacons it doesn't own.
- **This is not on wordpress.org and isn't intended to be.** It's published here mainly so MPS client sites can receive updates. It's provided as-is, and MPS may change it to suit its own needs without notice. If you depend on it, forking is the safer path.

## Updates and releases

Every push to `main` triggers [a GitHub Actions workflow](.github/workflows/release.yml) that bumps the patch version, tags it, and publishes a release with a built zip attached. Installed sites pick that up as an available update.

Version numbers are the source of truth for updates, not commits — the workflow's version bump is what makes a new commit visible to sites. To skip a release for a docs-only change, include `[skip ci]` in the commit message.

Updates are delivered by [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), which is vendored in `lib/`.

## License

GPL-2.0-or-later. See the plugin header for details.

Plugin Update Checker in `lib/plugin-update-checker/` is MIT licensed, © Jānis Elsts.
