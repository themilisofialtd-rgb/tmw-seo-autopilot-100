# TMW SEO Autopilot 100

Phase 1 of the **TMW SEO Autopilot 100** plugin delivers Serper-driven keyword intelligence and OpenAI-assisted outline generation directly within WordPress.

## Features
- **Serper Integration** – Transform SERP data into keyword clusters and questions.
- **OpenAI Outlines** – Draft ready-to-edit content briefs with a single click.
- **REST API Endpoints** – Automate workflows through `/tmw-sa100/v1` endpoints.
- **Diagnostics Dashboard** – Monitor integration health and configuration.

## Requirements
- WordPress 6.0+
- PHP 7.4+
- Valid Serper API key
- Valid OpenAI API key

## Installation
1. Upload the plugin folder `tmw-seo-autopilot-100` to `/wp-content/plugins/`.
2. Activate the plugin via the WordPress Admin → Plugins screen.
3. Navigate to **SEO Autopilot → Integrations** to store your API credentials.
4. Launch the **Keyword Engine** and **Content Draft** tools from the admin menu.

## REST Endpoints
| Method | Route | Description |
| --- | --- | --- |
| `POST` | `/tmw-sa100/v1/keyword-plan` | Build keyword clusters for provided keywords. |
| `POST` | `/tmw-sa100/v1/content-draft` | Generate an OpenAI-powered outline draft. |
| `GET/POST` | `/tmw-sa100/v1/settings` | Retrieve or update plugin integration settings. |

## Development
The plugin uses a lightweight autoloader registered in `tmw-seo-autopilot-100.php`. Classes live inside the `core/` and `classes/` directories, while admin templates can be found under `admin/views/`.

## Uninstall
Removing the plugin triggers the uninstall script to clean up saved options.
