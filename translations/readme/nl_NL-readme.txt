=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Direct, typefout-tolerant productzoeken voor WooCommerce uit de cloud — resultaten van Amazon-kwaliteit met filters, zonder je winkel te vertragen.

== Description ==

**Je shoppers kunnen niet kopen wat ze niet kunnen vinden.** De ingebouwde zoekfunctie van WooCommerce is traag, mist typefouten en bezwijkt onder grote catalogi — klanten die zoeken en niets vinden, vertrekken gewoon. NitroSearch vervangt hem door een snelle, vergevingsgezinde zoekfunctie van Amazon-kwaliteit die op onze servers draait, niet op de jouwe.

Installeer de plugin, verbind je winkel, en vanaf dat moment wordt elke zoekopdracht die je klanten typen beantwoord in **ongeveer een tiende van een seconde** — typefouten en al — rechtstreeks vanuit onze engine. Geen thema ombouwen. Geen zoekbelasting op je WordPress-host. Geen vertraging voor je winkel.

= Waarom winkeleigenaren overstappen op NitroSearch =

* **Direct en typefout-tolerant** — resultaten verschijnen terwijl je klant typt, en ‘hardloopschonen’ vindt gewoon je hardloopschoenen.
* **Het vertraagt je winkel niet** — zoeken loopt rechtstreeks tussen de browser van de shopper en onze engine, dus je WordPress-server zit nooit in het zoekpad. De widget op de pagina is vederlicht en afgeschermd in zijn eigen shadow DOM, zodat hij nooit botst met je thema of je snelheidsscore.
* **Filters, facetten en een volledige resultatenpagina** — facetten voor categorie, merk, aanbiedingen en voorraad, een compleet resultatenraster met paginering, en direct vanuit de resultaten toevoegen aan de winkelwagen.
* **Je hele site, niet alleen de shop** — indexeer desgewenst ook je pagina's en blogberichten, getoond in hun eigen sectie onder de producten, zodat je catalogus nooit ondersneeuwt. Volledige paginacontent wordt nooit gekopieerd, en privé, met een wachtwoord beveiligde en *noindex*-content blijft altijd buiten beschouwing.
* **Synchronisatie die je kunt vertrouwen** — de plugin houdt zijn eigen wijzigingswachtrij bij en blijft opnieuw proberen tot elke wijziging aankomt; elke update is ondertekend en geversioneerd, zodat niets in de verkeerde volgorde binnenkomt. Een live synchronisatiestatus-scherm laat precies zien wat er geïndexeerd is — vertrouwen kun je dus controleren, in plaats van dat het een belofte blijft.
* **Eerlijke, eenvoudige prijzen** — een écht gratis abonnement, elke zoekfunctie op elk abonnement (zoeken zelf is nooit opgesplitst in niveaus), en prijzen die alleen meegroeien met de omvang van je catalogus. Geen kosten per zoekopdracht, geen verrassingen op je factuur.
* **Zie waar shoppers écht naar zoeken** — elk betaald abonnement bevat zoekstatistieken: de populairste zoekopdrachten, wat er is aangeklikt en in winkelwagens is beland, en de zoekopdrachten die *niets* opleverden (een kant-en-klare lijst met vraag waar je nog geen aanbod voor hebt). Een samenvatting staat gewoon in wp-admin; de volledige dashboards vind je in je NitroSearch-account. Cookieloos, zonder identificerende gegevens van shoppers — nooit.
* **Er wordt niets aan je site toegevoegd tenzij je erom vraagt** — de optionele vermelding ‘Mogelijk gemaakt door NitroSearch’ staat standaard uit. Zet je hem aan, dan verschijnt er een kleine vermelding met link; laat je hem uit, dan krijgt je winkel niets anders dan de zoekfunctie.
* **In een paar minuten ingesteld** — verbetert automatisch het *bestaande* zoekveld van je thema. Geen shortcodes, geen template-aanpassingen, geen ombouw.

= Hoe het werkt =

NitroSearch is een gehoste zoekdienst; deze plugin is de officiële WooCommerce-koppeling. Hij doet twee dingen, en die zitten elkaar nooit in de weg:

1. **Houdt onze kopie van je catalogus actueel.** Als producten, prijzen en voorraad veranderen, stuurt de plugin de updates stilletjes op de achtergrond naar NitroSearch — samengevoegd in een lokale wijzigingswachtrij, ondertekend, en opnieuw geprobeerd tot elke wijziging aankomt.
2. **Beantwoordt zoekopdrachten, direct.** Wanneer een shopper zoekt, praat de widget *rechtstreeks* met onze engine, met een sleutel die alleen kan zoeken en alleen bij de openbare producten van je winkel kan — nooit via je WordPress-server, en daarom blijft het ook onder belasting snel.

= Gratis om te beginnen =

Het gratis abonnement werkt vanaf het moment dat je de plugin installeert — tot 100 zoekresultaten, met elke zoekfunctie inbegrepen. Een NitroSearch-account is optioneel; maak er een aan om je abonnement te beheren en te upgraden vanuit je dashboard. Meer weten? Kijk op [nitrosearch.io](https://nitrosearch.io).

== Externe diensten ==

Deze plugin maakt verbinding met de **gehoste zoekdienst NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) om je catalogus te indexeren en zoekresultaten te leveren. Dit is het kerndoel van de plugin.

* **Wat wordt er verstuurd, en wanneer:** Wanneer je op **‘Winkel verbinden’** klikt, registreert de plugin je site bij NitroSearch (je site-URL en een willekeurig gegenereerde installatie-identifier). Na het verbinden worden je productgegevens — namen, beschrijvingen, artikelnummers (SKU's), prijzen, voorraadstatus, categorieën, eigenschappen, afbeeldingen en permalinks — verstuurd zodat ze voor zoeken geïndexeerd kunnen worden. Productwijzigingen worden verstuurd zodra ze plaatsvinden.
* **Zoekopdrachten:** Eenmaal verbonden worden de zoekopdrachten van shoppers rechtstreeks vanuit hun browser naar de NitroSearch-engine gestuurd om resultaten terug te geven.
* **Scripts die in je winkel worden geladen:** eenmaal verbonden laadt de plugin de JavaScript van de zoekwidget vanaf `api.nitrosearch.io` (een kleine loader, plus de widget zelf zodra een shopper wil gaan zoeken), zodat resultaten in de browser van de shopper weergegeven kunnen worden. Er wordt niets geladen voordat je verbindt.
* **Tellingen van zoekgebruik:** eenmaal verbonden stuurt de zoekwidget ook anonieme, cookieloze gebruiksgebeurtenissen — de getypte zoekopdracht, hoeveel resultaten er verschenen, en kliks op die resultaten — naar `api.nitrosearch.io`. Ze bevatten geen identificerende gegevens van shoppers, geen cookies en geen profielen op basis van IP-adressen, en ruwe records worden volgens een doorlopend schema verwijderd. Zet dit op elk moment uit via **NitroSearch → Weergave → Zoekgebruiksgegevens**.
* **Waar:** de NitroSearch-API en zoekmachine, op `api.nitrosearch.io` en het eigen zoekendpoint van je winkel.
* **Er verlaat niets je site totdat je op Verbinden klikt.**

Gebruiksvoorwaarden van de dienst: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Privacybeleid: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Installeer en activeer **WooCommerce**.
2. Installeer en activeer **NitroSearch for WooCommerce** (via Plugins → Nieuwe plugin toevoegen, of upload het ZIP-bestand).
3. Open het menu **NitroSearch** in wp-admin en klik op **‘Winkel verbinden’**.
4. Dat is alles — je catalogus begint te synchroniseren en het zoekveld van je thema wordt automatisch verbeterd.

== Frequently Asked Questions ==

= Is het echt gratis? =

Ja. Het gratis abonnement dekt tot 100 zoekresultaten met elke zoekfunctie inbegrepen, voor altijd — zoeken zelf is nooit opgesplitst in niveaus. Betaalde abonnementen verhogen de limiet (en bevatten extra's zoals de winkelrapportage waaraan we bouwen); je betaalt alleen voor hoeveel je indexeert, nooit per zoekopdracht.

= Moet ik een account aanmaken? =

Voor het gratis abonnement is geen account nodig — installeren, verbinden, klaar. Een account is optioneel en laat je je abonnement beheren en upgraden.

= Maakt het mijn winkel trager? =

Nee — dat is juist het punt. Zoekopdrachten lopen rechtstreeks tussen de browser van de shopper en onze engine, dus je WordPress/WooCommerce-server doet nooit het zoekwerk. De widget op de pagina is piepklein en laadt pas wanneer een shopper begint te zoeken.

= Moet ik mijn thema aanpassen of een shortcode toevoegen? =

Nee. NitroSearch verbetert het *bestaande* zoekveld van je thema, op zijn eigen plek. Er hoeft niets omgebouwd te worden. (Gebruikt je thema een ongebruikelijk zoekveld, dan kun je NitroSearch ernaar laten wijzen met een optionele CSS-selector in de Weergave-instellingen.)

= Welke gegevens verlaten mijn site, en wanneer? =

Niets, totdat je op **Verbinden** klikt. Daarna wordt je productcatalogus naar NitroSearch gestuurd om geïndexeerd te worden, en worden updates verstuurd wanneer producten veranderen. Zie de sectie **Externe diensten** hierboven voor de volledige lijst, plus links naar onze gebruiksvoorwaarden en ons privacybeleid.

= Hoe blijft het synchroon met mijn catalogus? =

De plugin houdt een lokale wijzigingswachtrij bij en werkt die betrouwbaar op de achtergrond weg — het blijft dus ook werken op sites met weinig bezoekers en achter agressieve caching — en doseert zichzelf zodat je winkel vlot blijft reageren voor shoppers. Elke update is ondertekend en heeft een versienummer, zodat wijzigingen niet in de verkeerde volgorde kunnen aankomen. Een live synchronisatiestatus-scherm laat precies zien wat er geïndexeerd is. Automatisch nachtelijk herstel van afwijkingen staat op onze roadmap.

= Is WooCommerce vereist? =

Ja. NitroSearch indexeert en doorzoekt WooCommerce-producten, dus WooCommerce moet geïnstalleerd en actief zijn. De plugin is compatibel met WooCommerce High-Performance Order Storage (HPOS).

= Toont het een vermelding ‘Mogelijk gemaakt door NitroSearch’ in mijn winkel? =

Alleen als je daarvoor kiest. De vermelding staat **standaard uit**, en er wordt niets aan je winkel toegevoegd tenzij je hem aanzet in de **Weergave**-instellingen van de plugin. Doe je dat, dan verschijnt er een kleine vermelding met link in het zoekveld en één regel in de footer van je site, beide verwijzend naar nitrosearch.io. Alvast bedankt als je hem aanzet — maar de plugin werkt in beide gevallen precies hetzelfde.

= Wat gebeurt er als ik de plugin deactiveer? =

Je winkel valt terug op de normale WooCommerce-zoekfunctie. Je gegevens bij NitroSearch beheer je vanuit je account; je kunt op elk moment de verbinding verbreken vanaf het pluginscherm.

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
