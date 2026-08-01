=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sofortige, tippfehlertolerante WooCommerce-Produktsuche aus der Cloud – Ergebnisse auf Amazon-Niveau mit Facetten, ohne deinen Shop zu bremsen.

== Description ==

**Deine Kundschaft kann nicht kaufen, was sie nicht findet.** Die eingebaute WooCommerce-Suche ist langsam, übersieht Tippfehler und geht bei großen Katalogen in die Knie – wer sucht und nichts findet, geht einfach wieder. NitroSearch ersetzt sie durch schnelle, fehlerverzeihende Suche auf Amazon-Niveau, die auf unseren Servern läuft, nicht auf deinen.

Installiere das Plugin, verbinde deinen Shop – und ab diesem Moment wird jede Suchanfrage deiner Kundschaft in **rund einer Zehntelsekunde** beantwortet, Tippfehler inklusive, direkt aus unserer Engine. Kein Theme-Umbau. Keine Suchlast auf deinem WordPress-Hosting. Kein Ausbremsen deines Shops.

= Warum Shopbetreiber zu NitroSearch wechseln =

* **Sofort & tippfehlertolerant** – Ergebnisse erscheinen schon beim Tippen, und „Laufschue“ findet trotzdem deine Laufschuhe.
* **Es bremst deinen Shop nicht aus** – die Suche läuft direkt zwischen dem Browser deiner Kundschaft und unserer Engine, dein WordPress-Server steht also nie im Suchpfad. Das Widget auf der Seite ist federleicht und in sein eigenes Shadow DOM gekapselt, sodass es deinem Theme und deinem Speed-Score nie in die Quere kommt.
* **Filter, Facetten & eine vollständige Ergebnisseite** – Facetten für Kategorie, Marke, Angebote und Lagerbestand, ein komplettes Ergebnisraster mit Seitennummerierung und „In den Warenkorb“ direkt aus den Ergebnissen.
* **Deine ganze Website, nicht nur der Shop** – indexiere optional auch deine Seiten und Blogbeiträge, angezeigt in einem eigenen Bereich unterhalb der Produkte, damit dein Katalog nie untergeht. Vollständige Seiteninhalte werden nie kopiert, und private, passwortgeschützte sowie mit *noindex* markierte Inhalte bleiben immer außen vor.
* **Synchronisierung, der du vertrauen kannst** – das Plugin führt eine eigene Änderungswarteschlange und wiederholt Übertragungen, bis jede Änderung ankommt; jedes Update ist signiert und versioniert, damit nichts in falscher Reihenfolge eintrifft. Eine Live-Statusseite zeigt genau, was indexiert ist – Vertrauen ist hier etwas zum Nachprüfen, kein bloßes Versprechen.
* **Ehrliche, einfache Preise** – ein wirklich kostenloser Tarif, jede Suchfunktion in jedem Tarif (die Suche selbst ist nie gestaffelt) und Preise, die nur mit der Kataloggröße wachsen. Keine Gebühren pro Suche, keine Überraschungsrechnungen.
* **Sieh, wonach wirklich gesucht wird** – jeder Bezahltarif enthält die Suchanalyse: Top-Suchanfragen, was angeklickt und in den Warenkorb gelegt wurde und die Suchen, die *nichts* fanden (eine fertige Liste der Nachfrage, die du noch nicht im Sortiment hast). Eine Zusammenfassung steht direkt in wp-admin, die vollständigen Dashboards liegen in deinem NitroSearch-Konto. Cookielos, ohne Kennungen deiner Kundschaft – niemals.
* **Nichts wird deiner Website hinzugefügt, wenn du es nicht willst** – der optionale Hinweis „Powered by NitroSearch“ ist standardmäßig aus. Schaltest du ihn ein, kommt ein kleiner verlinkter Hinweis hinzu; lässt du ihn aus, bekommt dein Shop nichts außer der Suche.
* **In Minuten eingerichtet** – erweitert automatisch das *vorhandene* Suchfeld deines Themes. Keine Shortcodes, keine Template-Änderungen, kein Umbau.

= So funktioniert es =

NitroSearch ist ein gehosteter Suchdienst; dieses Plugin ist sein offizieller WooCommerce-Connector. Es macht zwei Dinge, und die kommen sich nie in die Quere:

1. **Es hält unsere Kopie deines Katalogs frisch.** Wenn sich Produkte, Preise und Lagerbestände ändern, sendet das Plugin die Updates still im Hintergrund an NitroSearch – gebündelt in einer lokalen Änderungswarteschlange, signiert und so lange wiederholt, bis jede Änderung ankommt.
2. **Es beantwortet Suchanfragen, sofort.** Sucht jemand in deinem Shop, spricht das Widget *direkt* mit unserer Engine – über einen Nur-Suche-Schlüssel, der auf die öffentlichen Produkte deines Shops beschränkt ist. Nie über deinen WordPress-Server, und genau deshalb bleibt die Suche auch unter Last schnell.

= Kostenlos starten =

Der kostenlose Tarif funktioniert ab dem Moment, in dem du das Plugin installierst – bis zu 100 Suchergebnisse, mit allen Suchfunktionen. Ein NitroSearch-Konto ist optional; erstelle eines, um deinen Tarif zu verwalten und über dein Dashboard zu upgraden. Mehr erfährst du auf [nitrosearch.io](https://nitrosearch.io).

== Externe Dienste ==

Dieses Plugin verbindet sich mit dem **gehosteten Suchdienst NitroSearch** ([nitrosearch.io](https://nitrosearch.io)), um deinen Katalog zu indexieren und Suchergebnisse auszuliefern. Das ist der Kernzweck des Plugins.

* **Was wann gesendet wird:** Wenn du auf **„Shop verbinden“** klickst, registriert das Plugin deine Website bei NitroSearch (deine Website-URL und eine zufällig erzeugte Installations-Kennung). Nach dem Verbinden werden deine Produktdaten – Namen, Beschreibungen, Artikelnummern, Preise, Lagerstatus, Kategorien, Attribute, Bilder und Permalinks – gesendet, damit sie für die Suche indexiert werden können. Produktänderungen werden gesendet, sobald sie passieren.
* **Suchanfragen:** Nach dem Verbinden werden die Suchanfragen deiner Kundschaft direkt aus deren Browser an die NitroSearch-Engine gesendet, um Ergebnisse zu liefern.
* **Skripte, die in deinen Shop geladen werden:** Nach dem Verbinden lädt das Plugin das JavaScript des Such-Widgets von `api.nitrosearch.io` (ein kleiner Loader, plus das Widget selbst bei der ersten Suchabsicht), damit Ergebnisse im Browser der Kundschaft dargestellt werden können. Vor dem Verbinden wird nichts geladen.
* **Suchnutzungszahlen:** Nach dem Verbinden sendet das Such-Widget außerdem anonyme, cookielose Nutzungsereignisse – die eingetippte Suchanfrage, wie viele Ergebnisse erschienen und Klicks auf diese Ergebnisse – an `api.nitrosearch.io`. Sie enthalten keine Kennungen deiner Kundschaft, keine Cookies und keine IP-basierten Profile, und Rohdaten werden nach einem rollierenden Zeitplan gelöscht. Abschalten kannst du das jederzeit unter **NitroSearch → Design → Suchnutzungsdaten**.
* **Wohin:** an die NitroSearch-API und -Suchmaschine unter `api.nitrosearch.io` sowie an den dedizierten Such-Endpunkt deines Shops.
* **Nichts verlässt deine Website, bevor du auf „Shop verbinden“ klickst.**

Nutzungsbedingungen des Dienstes: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Datenschutzerklärung: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Installiere und aktiviere **WooCommerce**.
2. Installiere und aktiviere **NitroSearch for WooCommerce** (über Plugins → Installieren oder per ZIP-Upload).
3. Öffne das Menü **NitroSearch** in wp-admin und klicke auf **„Shop verbinden“**.
4. Das war’s – dein Katalog beginnt zu synchronisieren, und das Suchfeld deines Themes wird automatisch aufgewertet.

== Frequently Asked Questions ==

= Ist es wirklich kostenlos? =

Ja. Der kostenlose Tarif umfasst bis zu 100 Suchergebnisse mit allen Suchfunktionen, dauerhaft – die Suche selbst ist nie gestaffelt. Bezahltarife erhöhen das Limit (und enthalten Extras wie die Shop-Auswertungen, an denen wir gerade bauen); du zahlst nur dafür, wie viel du indexierst, nie pro Suche.

= Muss ich ein Konto erstellen? =

Für den kostenlosen Tarif ist kein Konto nötig – installieren, verbinden, fertig. Ein Konto ist optional; damit verwaltest du deinen Tarif und kannst upgraden.

= Bremst es meinen Shop aus? =

Nein – genau das ist der Punkt. Suchanfragen laufen direkt zwischen dem Browser deiner Kundschaft und unserer Engine, dein WordPress-/WooCommerce-Server erledigt also nie die Sucharbeit. Das Widget auf der Seite ist winzig und lädt erst, wenn jemand zu suchen beginnt.

= Muss ich mein Theme ändern oder einen Shortcode einfügen? =

Nein. NitroSearch erweitert das *vorhandene* Suchfeld deines Themes an Ort und Stelle. Es gibt nichts umzubauen. (Falls dein Theme ein ungewöhnliches Suchfeld verwendet, kannst du NitroSearch in den Design-Einstellungen mit einem optionalen CSS-Selektor darauf ausrichten.)

= Welche Daten verlassen meine Website – und wann? =

Nichts, bevor du auf **Shop verbinden** klickst. Danach wird dein Produktkatalog zur Indexierung an NitroSearch gesendet, und Aktualisierungen folgen, sobald sich Produkte ändern. Die vollständige Liste sowie Links zu unseren Nutzungsbedingungen und zur Datenschutzerklärung findest du oben im Abschnitt **Externe Dienste**.

= Wie bleibt es mit meinem Katalog synchron? =

Das Plugin führt eine lokale Änderungswarteschlange und arbeitet sie zuverlässig im Hintergrund ab – so funktioniert es auch auf Websites mit wenig Traffic und hinter aggressivem Caching – und dosiert sich dabei selbst, damit dein Shop für deine Kundschaft flott bleibt. Jedes Update ist signiert und trägt eine Version, sodass Änderungen nicht in falscher Reihenfolge landen können. Eine Live-Statusseite zeigt genau, was indexiert ist. Eine automatische nächtliche Korrektur von Abweichungen steht auf unserer Roadmap.

= Ist WooCommerce erforderlich? =

Ja. NitroSearch indexiert und durchsucht WooCommerce-Produkte, daher muss WooCommerce installiert und aktiv sein. Kompatibel mit WooCommerce High-Performance Order Storage (HPOS).

= Zeigt es in meinem Shop ein „Powered by NitroSearch“-Badge an? =

Nur wenn du das möchtest. Der Hinweis ist **standardmäßig aus**, und deinem Shop wird nichts hinzugefügt, solange du ihn nicht in den **Design**-Einstellungen des Plugins einschaltest. Tust du es, erscheint er als kleiner verlinkter Hinweis im Suchfeld und als eine Zeile im Footer deiner Website, beide mit Ziel nitrosearch.io. Danke, falls du ihn einschaltest – das Plugin funktioniert aber so oder so identisch.

= Was passiert, wenn ich das Plugin deaktiviere? =

Dein Shop fällt auf seine normale WooCommerce-Suche zurück. Deine Daten bei NitroSearch verwaltest du über dein Konto; die Verbindung kannst du jederzeit auf der Plugin-Seite trennen.

== Screenshots ==

1. The NitroSearch admin screen — connection status, live sync health, and sync-performance metrics, all in one place.
2. Instant, typo-tolerant search enhancing your theme's own search box, with brand, category and availability filters.
3. The full search results page — a fast product grid with faceted filtering and pagination.

== Changelog ==

= 1.8.0 =
* New: a **Design tab**. Choose a layout — Roomy, Compact, Big pictures or Text only — a colour scheme (Light, Dark, Automatic to follow each shopper's device, or your own colours), corner style, and the font. Set how many products appear, where the filters go, and how wide the drop-down opens. Everything is stored on your own site and applied to the search box; none of it enlarges what your shoppers download.
* Fixed: **product names were cut off mid-word.** The drop-down was never allowed to be wider than your theme's search box, so on a narrow header the filters column left almost no room for names. It now opens wide enough to read, names wrap to two lines instead of truncating, and the filters move above the results when the panel is genuinely tight.
* Fixed: the search box now uses **your store's font**. It was falling back to the browser's default serif on every site, because the widget renders in an isolated shadow root that inherits nothing from the page.
* Fixed: pale accent colours (yellows, light pastels) rendered white text on top of themselves. Label text on your accent colour is now automatically black or white, whichever stays readable.
* Fixed: with pages and posts switched on, their icons and labels had no background — a colour the stylesheet referenced but never defined.
* The NitroSearch mark now appears in the wp-admin menu, and follows your admin colour scheme.

= 1.7.0 =
* New: **see the revenue your search drives.** When a shopper adds a product to their basket from search results and goes on to buy it (within 7 days), the plugin reports that order's search-attributed value to NitroSearch — a "Search-attributed revenue" figure appears in your analytics dashboards. Attribution happens entirely inside your store's own session; the order number is hashed before it leaves your site, and no shopper details are ever sent. Respects the same **Search usage data** toggle — switch it off and nothing is reported.

= 1.6.0 =
* New: a **Search analytics** card on the NitroSearch screen — last-30-days searches, zero-result rate, click-through rate, your top searches, and the searches that found nothing. On paid plans; the free tier sees its monthly search count and what upgrading unlocks. The full dashboards live in your NitroSearch account.
* The card is cached for six hours and never slows wp-admin — a slow connection simply shows "couldn't load" and retries.

= 1.5.0 =
* New: anonymous, cookieless **search usage counts**. The widget now tells NitroSearch what was searched, how many results appeared, and what got clicked — with no shopper identifiers, no cookies, and nothing stored in the shopper's browser. It improves result ranking for your store; per-store reporting on your NitroSearch dashboard is on the roadmap. On by default, disclosed in **External services** above, and yours to switch off under **NitroSearch → Appearance → Search usage data**.
* A one-time notice in wp-admin explains the change and links to the toggle.
* Honesty pass on this listing: our pricing promise now reads "every **search** feature on every plan" — search itself is never tiered, and the limit counts search results (products, plus any pages and posts you switch on), matching how plans have counted since 1.4.0.

= 1.4.0 =
* Improved: the optional "Powered by NitroSearch" credit now links to nitrosearch.io and also appears once in your site footer. Still off by default — nothing is added to your site unless you turn it on.
* New: **search your pages and blog posts too.** Shoppers looking for "delivery times" or "how to care for leather" now find the page that answers them, shown in their own "Pages & posts" section beneath the products — never mixed in with your catalogue. Only titles, a short summary, categories, the featured image and the publish date are indexed; full page content is never copied.
* New: choose what gets searched under **NitroSearch → Appearance → What to search**. Products are always indexed; pages and blog posts are yours to switch on or off. They share the same allowance as your products, so turning them off frees it up for your catalogue — and when you do, they're removed from your index.
* Privacy, by default: private, password-protected, draft, scheduled and trashed content is never indexed, and *noindex* set in Yoast SEO or Rank Math is honoured (per item, per content type, or site-wide). Membership and paywall plugins can exclude anything else through the `nitrosearch_content_is_searchable` filter.
* Your products always come first. If your plan is full, pages and posts are what gets held back — never your catalogue.
* Existing stores are unaffected until you opt in: pages and posts start switched **off** on an existing install, and on for a brand-new one.
* Improved: scheduled products and posts now index the moment they go live. Previously a post published on a schedule could sit unindexed until something else happened to edit it.
* Improved: switching a content type on no longer re-walks your entire catalogue — only the new content is enumerated, so a big store stays responsive.
* Fix: starting a new page or post no longer sends a needless removal for something that was never indexed.

= 1.3.1 =
* Fix: the plugin screen offered "search analytics" on your NitroSearch dashboard. That feature isn't built yet, so the wording is corrected — it's on the roadmap, and we'd rather say so than imply otherwise.

= 1.3.0 =
* Improved: gentler, faster first-time catalogue sync. Large catalogues now sync in the background in resumable batches instead of all at once, so connecting a big store no longer risks slowing down or timing out your site — and the sync paces itself to leave your storefront responsive for shoppers.
* Fix: corrected the Terms of Service and Privacy Policy links in the plugin description.

= 1.2.3 =
* Fix: the plugin and author links in the plugin header now point to separate pages, as the plugin directory requires.

= 1.2.2 =
* Documentation and directory assets refreshed.

= 1.2.1 =
* New: an optional "Powered by NitroSearch" credit in the search box — off by default; turn it on in Appearance if you'd like to show your support.
* Compatibility: tested up to WordPress 7.0.
* Housekeeping: hardened the catalogue-sync database queries.

= 1.2.0 =
* New: a Sync performance panel on the admin screen — average and most-recent batch speed, how many products have synced, batches sent, and when the next sync runs.
* New: a clear heads-up when your catalogue reaches your plan's product limit, with a prompt to upgrade. Your existing search keeps running — only brand-new products wait until you upgrade.

= 1.1.0 =
* New: filters and a full results page — category, brand, price, on-sale and in-stock facets, a complete results grid with pagination, and add-to-cart right from the results.
* New: appearance settings — set an accent colour for prices, highlights and selected filters, and optionally point the widget at your theme's search box.
* New: "Manage / Upgrade" — link your store to a NitroSearch account to manage your plan, without re-indexing or losing your search.
* Improved: a refreshed, clearer admin screen showing connection status and live sync health.
* Improved: faster, more accessible instant-search dropdown, with full keyboard navigation and recent searches.
* Improved: more reliable and more secure catalogue sync, with clearer connection and verification status.

= 1.0.0 =
* Initial release.
* One-click connect: link your store to NitroSearch from the WordPress admin.
* Automatic catalogue sync — products, prices, stock, categories, and attributes stay in sync as they change, with reliable background processing that keeps working even on low-traffic sites.
* Instant search widget — enhances your theme's existing search box with fast, typo-tolerant product results as customers type. No theme rebuild required.
* Sync status screen so you can see exactly what is indexed.

== Upgrade Notice ==

= 1.8.0 =
Adds a Design tab (layouts, colour schemes, fonts, widths) and fixes the drop-down cutting product names off, the search box ignoring your store's font, and unreadable text on pale accent colours.

= 1.7.0 =
Adds search-attributed revenue to your analytics (hashed order reference only, no shopper details; honours the usage-data toggle). No storefront changes.

= 1.6.0 =
Adds the Search analytics card to the NitroSearch screen (paid plans; free tier sees its search count). No storefront changes.

= 1.5.0 =
Adds anonymous, cookieless search usage counts (no shopper identifiers — improves ranking; reporting is on the roadmap). On by default with a settings toggle under Appearance; details in External services.

= 1.4.0 =
NitroSearch can now search your pages and blog posts alongside your products, in their own section. Off by default on existing stores — switch it on under Appearance → What to search. Nothing changes until you do.

= 1.3.1 =
Corrects wording on the plugin screen that offered a search-analytics feature which isn't built yet. No functional changes.

= 1.3.0 =
Gentler, faster first-time sync for large catalogues, plus corrected Terms/Privacy links.

= 1.2.3 =
Minor plugin-header fix for the WordPress.org directory. No functional changes.

= 1.2.2 =
Refreshed documentation and directory listing. No functional changes.

= 1.2.1 =
The "Powered by NitroSearch" credit is now optional and off by default. Tested up to WordPress 7.0.

= 1.2.0 =
Adds a sync-performance panel and a plan-limit heads-up on the admin screen.
