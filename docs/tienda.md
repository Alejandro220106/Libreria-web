# Store: plugins and security checklist

Brief section 5.4. State of the store checked on **2026-11-16** on the team's Local site (`https://libreria.local`), which the instructor accepted in place of a public host (see #5).

Platform: WordPress **7.1.4**, WooCommerce **11.3.0**, PHP 8.5, MySQL 8.4. Active theme: `libreria-child` (child of Twenty Twenty-Five).

## Installed plugins

WooCommerce and the team's own plugin (`Libreria Author`, in `tienda/plugins/`) are left out, as the brief asks.

Vulnerabilities were looked up in the Patchstack database on 2026-11-16. "None" means Patchstack lists no vulnerability that affects the installed version; every listed issue was fixed in an earlier version.

| Plugin | Version | Status | Purpose | Last update (wordpress.org) | Vulnerabilities (Patchstack, 2026-11-16) |
|---|---|---|---|---|---|
| Yoast SEO | 28.8 | Active | SEO: titles, meta descriptions, canonical URLs and the XML sitemap | 2026-10-27 | None (21 past issues, all fixed) |
| Wordfence Security | 9.1.1 | Active | Security: firewall, malware scan and login attempt limits | 2026-11-10 | None (14 past issues, all fixed) |
| UpdraftPlus - Backup/Restore | 1.26.14 | Active | Backup of files and database (see `docs/respaldo.md`) | 2026-10-26 | None (18 past issues, all fixed) |
| Elementor | 4.4.1 | Active | Page builder. Installed for page design, but no published page uses it yet (checked on the home, shop, cart, account and checkout pages) | 2026-11-16 | None (49 past issues, all fixed) |
| Novamira | 1.12.15 | Active | Development tool: lets the team's AI assistant inspect the local site. Development only, never on a public host | Not in the wordpress.org directory | Not listed in Patchstack (no entry found) |
| Stock Manager for WooCommerce | 3.12.0 | Inactive | Bulk stock and price editing | 2026-11-11 | None (4 past issues, all fixed) |
| WPForms Lite | 2.0.4 | Inactive | Contact form | 2026-11-05 | None (28 past issues, all fixed) |

Sources: `https://patchstack.com/database/wordpress/plugin/<slug>` and `https://wordpress.org/plugins/<slug>/` for each plugin.

## Security checklist

| Item | Applied | How it was checked |
|---|---|---|
| No user called `admin` | Yes | Users screen: only `gestor_libreria` and the instructor's account |
| File editing from the dashboard is disabled | Yes | `define( 'DISALLOW_FILE_EDIT', true );` in `wp-config.php` |
| XML-RPC is disabled | Yes | Child theme `functions.php` turns it off and answers 403; `POST /xmlrpc.php` returns 403 |
| Login attempts are limited | Yes | Wordfence brute force protection on: lockout after 20 failures within 4 hours, for 4 hours |
| WordPress, theme and plugins are up to date | Yes | Updates screen shows nothing pending (backup taken with UpdraftPlus first) |
| Readable permalinks | Yes | Post name structure, for example `https://libreria.local/tienda/` |
| XML sitemap published | Yes | `https://libreria.local/sitemap_index.xml` (Yoast SEO) |

## Pending

- Stock Manager for WooCommerce and WPForms Lite are inactive. Brief 5.4.3 does not allow unused inactive plugins, so they must be activated for a real use or deleted.
- Wordfence shows "You must install a license to continue using Wordfence". The free license has to be registered with a team email so the firewall and login limits keep working.
- Elementor is active but no page uses it. The team should either build a page with it or remove it.
- Novamira must be removed before the store is deployed to a public host.
