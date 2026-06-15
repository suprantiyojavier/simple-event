# Simple Event

A WordPress plugin to create events with registration forms, replay forms, QR e-tickets, and submission management.

## Features

- **Event Management** — Custom post type with start/end date, time, location, and event categories.
- **Registration Form** — Built-in registration form with configurable fields (Text, Email, Phone, Number, Textarea, Dropdown, Checkbox, Radio Button).
- **Replay Form** — "Watch the Replay" form for ended events with embedded YouTube video.
- **QR E-Ticket** — QR code ticket generation for each registrant with a verification page.
- **Email Notifications** — Automatic confirmation emails with site logo, event details, and QR e-ticket.
- **Speakers & Moderators** — Repeatable fields for speakers/moderators with photo, name, title, and role.
- **Target Audience** — Pill badges to display target audience on the frontend.
- **Custom Form Fields** — Configure form fields per event from WP Admin. Name & Email are locked/required.
- **Google Form Embed** — Option to embed a Google Form instead of the built-in registration form.
- **Phone Country Code** — International phone input with country code selector using intl-tel-input.
- **Share Buttons** — Share events to Facebook, LinkedIn, WhatsApp, X (Twitter), and copy link.
- **Until Finished** — Checkbox to automatically close the event on the start date with "Finished" badge.
- **CSV Export** — Export registrant data to CSV from the admin panel.
- **Event List Shortcode** — `[event_list]` shortcode with category filter, grid columns, status filter, and pagination.
- **Elementor Widget** — Event Grid widget for Elementor with customizable layout and filters.
- **GitHub Auto-Updater** — Automatic plugin updates from GitHub releases.
- **Event-based Email Blocking** - Email Automatic Disallow email after blocking email.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Elementor (optional, for the Event Grid widget)

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/Gioidstar/simple-event/releases).
2. Upload the `simple-event` folder to `/wp-content/plugins/`.
3. Activate the plugin through the **Plugins** menu in WordPress.

## Auto-Updates

This plugin supports automatic updates from GitHub. Once installed, you will receive update notifications in the WordPress admin when a new release is published.

## Usage

### Shortcode

Display events on any page or post:

```
[event_list]
[event_list category="webinar" columns="3" per_page="6" status="upcoming"]
[event_list category="workshop,seminar" columns="2" per_page="-1" status="all" orderby="date"]
[event_list per_page="6" columns="3"]
```

```
[event_compact]
[event_compact columns="2" per_page="4" status="upcoming"]
[event_compact category="webinar" columns="3" per_page="6"]
```

**Attributes:**

| Attribute     | Default | Options                          |
|---------------|---------|----------------------------------|
| `category`    | (all)   | Category slug (comma-separated)  |
| `columns`     | `3`     | `1`, `2`, `3`, `4`              |
| `per_page`    | `-1`    | Number of events, `-1` = all    |
| `status`      | `all`   | `upcoming`, `past`, `all`       |
| `orderby`     | `date`  | `date`, `title`                 |
| `order`       | `DESC`  | `ASC`, `DESC`                   |
| `show_filter` | `no`    | `yes`, `no`                     |

### Elementor Widget

Search for **Event Grid** in the Elementor widget panel and drag it into your page.

## Changelog

See [readme.txt](readme.txt) for the full changelog.

## License

GPL v2 or later.
