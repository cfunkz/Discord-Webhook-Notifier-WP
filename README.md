# Discord Webhook Notifier for WordPress

Send WordPress post notifications to Discord channels with full control over the embed layout.

![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-blue) ![License](https://img.shields.io/badge/license-GPL2-green)

<img width="1320" height="769" alt="image" src="https://github.com/user-attachments/assets/f3d0c367-5626-4c87-81fa-afa07803c8c4" />
<img width="1138" height="773" alt="image" src="https://github.com/user-attachments/assets/60fdacb3-b70a-48dc-b540-b3a452c4b1ef" />
<img width="1117" height="776" alt="image" src="https://github.com/user-attachments/assets/26b7512f-dc58-4dfd-92f9-d12946e7f214" />

## Installation

1. Download [discord-webhook-wp.zip](https://github.com/cfunkz/Discord-Webhook-Notifier-WP/releases/download/v2.0.1/discord-webhook-wp.zip)
2. WordPress Admin → **Plugins → Add New → Upload Plugin**
3. Upload the zip → **Activate**
4. Go to **Discord** in the sidebar

## Features

- **Multiple webhooks** — each pointing to a different Discord channel
- **Embed controls** — toggle author, excerpt, categories, tags on/off per webhook
- **Featured image** — large, thumbnail, both, or none
- **Custom colors** — separate hex colors for new posts vs updates
- **Message templates** — text above the embed with variables like `{title}`, `{author}`, `{tags}`
- **Post filters** — send only posts from specific categories, tags, or authors
- **No duplicate messages** — safe to use with Gutenberg block editor

## Getting a Webhook URL

1. Open Discord → Server Settings → **Integrations → Webhooks**
2. Click **New Webhook**, choose a channel
3. Copy the URL and paste it into the plugin

## Template Variables

| Variable | Output |
|---|---|
| `{title}` | Post title |
| `{url}` | Post permalink |
| `{author}` | Author name |
| `{excerpt}` | First ~55 words |
| `{cats}` | Category list |
| `{tags}` | Tag list |
| `{site}` | Site name |
| `{date}` | Published date |

## Requirements

- WordPress 5.6+
- PHP 7.4+
