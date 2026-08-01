=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ricerca prodotti WooCommerce istantanea e a prova di refusi, dal cloud: risultati di livello Amazon con filtri, senza rallentare il tuo negozio.

== Description ==

**I tuoi clienti non possono comprare ciò che non trovano.** La ricerca integrata di WooCommerce è lenta, non perdona gli errori di battitura e va in crisi sui cataloghi grandi — così chi cerca e non trova, semplicemente se ne va. NitroSearch la sostituisce con una ricerca veloce, tollerante e di livello Amazon, che gira sui nostri server, non sui tuoi.

Installa il plugin, collega il negozio e da quel momento ogni ricerca digitata dai tuoi clienti riceve risposta in **circa un decimo di secondo** — refusi compresi — direttamente dal nostro motore. Nessun tema da rifare. Nessun carico di ricerca sul tuo hosting WordPress. Nessun rallentamento per il tuo negozio.

= Perché i negozianti passano a NitroSearch =

* **Istantanea e a prova di refusi** — i risultati compaiono mentre il cliente digita, e «scarpe da ginastica» trova comunque le tue scarpe da ginnastica.
* **Non rallenta il tuo negozio** — la ricerca viaggia direttamente tra il browser del cliente e il nostro motore, quindi il tuo server WordPress non è mai sul percorso della ricerca. Il widget in pagina è leggerissimo e sigillato nel suo shadow DOM, così non litiga mai con il tuo tema né con il tuo punteggio di velocità.
* **Filtri, faccette e una pagina dei risultati completa** — faccette per categoria, marchio, offerte e disponibilità, una griglia dei risultati completa con paginazione, e l’aggiunta al carrello direttamente dai risultati.
* **Tutto il tuo sito, non solo lo shop** — se vuoi, puoi indicizzare anche pagine e articoli del blog, mostrati in una sezione dedicata sotto i prodotti, così il tuo catalogo non resta mai sepolto. Il contenuto completo delle pagine non viene mai copiato, e i contenuti privati, protetti da password o *noindex* restano sempre fuori.
* **Una sincronizzazione di cui fidarti** — il plugin mantiene una propria coda delle modifiche e riprova finché ogni modifica non arriva a destinazione; ogni aggiornamento è firmato e versionato, così nulla arriva nell’ordine sbagliato. Una schermata con lo stato della sincronizzazione in tempo reale mostra esattamente cosa è indicizzato: la fiducia è qualcosa che puoi verificare, non una promessa.
* **Prezzi onesti e semplici** — un piano gratuito vero, tutte le funzioni di ricerca su tutti i piani (la ricerca in sé non è mai divisa in livelli) e prezzi che crescono solo con la dimensione del catalogo. Nessun costo per singola ricerca, nessuna sorpresa in fattura.
* **Scopri cosa cercano davvero i tuoi clienti** — ogni piano a pagamento include le statistiche di ricerca: le ricerche più frequenti, cosa è stato cliccato e aggiunto al carrello, e le ricerche che non hanno trovato *nulla* (un elenco già pronto della domanda che non hai ancora a catalogo). Un riepilogo è direttamente in wp-admin; le dashboard complete sono nel tuo account NitroSearch. Senza cookie e senza identificatori dei clienti — mai.
* **Nulla viene aggiunto al tuo sito se non lo chiedi tu** — il credito facoltativo «Powered by NitroSearch» è disattivato per impostazione predefinita. Se lo attivi, aggiunge un piccolo credito con link; se lo lasci disattivato, la tua vetrina guadagna soltanto la ricerca.
* **Attivo in pochi minuti** — potenzia automaticamente il campo di ricerca *già presente* nel tuo tema. Niente shortcode, niente modifiche ai template, niente da ricostruire.

= Come funziona =

NitroSearch è un servizio di ricerca ospitato nel cloud; questo plugin è il suo connettore ufficiale per WooCommerce. Fa due cose, e non si intralciano mai a vicenda:

1. **Tiene aggiornata la nostra copia del tuo catalogo.** Quando prodotti, prezzi e scorte cambiano, il plugin invia in silenzio gli aggiornamenti a NitroSearch in background — raggruppati in una coda locale delle modifiche, firmati e ritentati finché ogni modifica non arriva a destinazione.
2. **Risponde alle ricerche, all’istante.** Quando un cliente cerca, il widget parla *direttamente* con il nostro motore usando una chiave di sola ricerca limitata ai prodotti pubblici del tuo negozio — mai attraverso il tuo server WordPress, ed è per questo che resta veloce anche sotto carico.

= Gratis per iniziare =

Il piano gratuito funziona dal momento in cui installi il plugin — fino a 100 risultati di ricerca, con tutte le funzioni di ricerca incluse. L’account NitroSearch è facoltativo; creane uno per gestire il tuo piano e fare l’upgrade dalla tua dashboard. Scopri di più su [nitrosearch.io](https://nitrosearch.io).

== Servizi esterni ==

Questo plugin si collega al **servizio di ricerca in cloud NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) per indicizzare il tuo catalogo e servire i risultati di ricerca. È lo scopo principale del plugin.

* **Cosa viene inviato, e quando:** quando fai clic su **«Collega il negozio»**, il plugin registra il tuo sito su NitroSearch (l’URL del sito e un identificativo di installazione generato casualmente). Dopo il collegamento vengono inviati i dati dei tuoi prodotti — nomi, descrizioni, COD (SKU), prezzi, stato delle scorte, categorie, attributi, immagini e permalink — perché possano essere indicizzati per la ricerca. Le modifiche ai prodotti vengono inviate man mano che avvengono.
* **Query di ricerca:** una volta collegato il negozio, le ricerche digitate dai clienti vengono inviate dal loro browser direttamente al motore NitroSearch per restituire i risultati.
* **Script caricati sulla tua vetrina:** una volta collegato, il plugin carica il JavaScript del widget di ricerca da `api.nitrosearch.io` (un piccolo loader, più il widget vero e proprio al primo segnale di ricerca), così i risultati possono essere mostrati nel browser del cliente. Nulla viene caricato prima del collegamento.
* **Conteggi di utilizzo della ricerca:** una volta collegato, il widget di ricerca invia anche eventi di utilizzo anonimi e senza cookie — il termine digitato, quanti risultati sono comparsi e i clic su quei risultati — a `api.nitrosearch.io`. Non contengono identificatori dei clienti, cookie né profili basati sull’IP, e i dati grezzi vengono eliminati su base periodica. Puoi disattivarli in qualsiasi momento da **NitroSearch → Design → Dati di utilizzo della ricerca**.
* **Dove:** l’API e il motore di ricerca NitroSearch, su `api.nitrosearch.io` e sull’endpoint di ricerca dedicato del tuo negozio.
* **Nulla lascia il tuo sito finché non fai clic su «Collega il negozio».**

Termini di utilizzo del servizio: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Informativa sulla privacy: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Installa e attiva **WooCommerce**.
2. Installa e attiva **NitroSearch for WooCommerce** (da Plugin → Aggiungi nuovo plugin, oppure caricando lo ZIP).
3. Apri il menu **NitroSearch** in wp-admin e fai clic su **«Collega il negozio»**.
4. Tutto qui — il tuo catalogo inizia a sincronizzarsi e il campo di ricerca del tuo tema viene potenziato automaticamente.

== Frequently Asked Questions ==

= È davvero gratuito? =

Sì. Il piano gratuito copre fino a 100 risultati di ricerca con tutte le funzioni di ricerca incluse, per sempre — la ricerca in sé non è mai divisa in livelli. I piani a pagamento alzano il limite (e includono extra come i report per negozio a cui stiamo lavorando); paghi solo per quanto indicizzi, mai per singola ricerca.

= Devo creare un account? =

Per usare il piano gratuito non serve alcun account — installi, colleghi, fatto. L’account è facoltativo e ti permette di gestire il piano e fare l’upgrade.

= Rallenterà il mio negozio? =

No — è proprio questo il punto. Le ricerche viaggiano direttamente tra il browser del cliente e il nostro motore, quindi il tuo server WordPress/WooCommerce non fa mai il lavoro di ricerca. Il widget in pagina è minuscolo e si carica solo quando un cliente inizia a cercare.

= Devo modificare il tema o aggiungere uno shortcode? =

No. NitroSearch potenzia il campo di ricerca *già presente* nel tuo tema, lì dov’è. Non c’è nulla da ricostruire. (Se il tuo tema usa un campo di ricerca insolito, puoi indicarlo a NitroSearch con un selettore CSS facoltativo nelle impostazioni Design.)

= Quali dati lasciano il mio sito, e quando? =

Nulla, finché non fai clic su **Collega il negozio**. Dopo, il tuo catalogo prodotti viene inviato a NitroSearch per essere indicizzato, e gli aggiornamenti vengono inviati man mano che i prodotti cambiano. Consulta la sezione **Servizi esterni** qui sopra per l’elenco completo, con i link ai nostri Termini e all’Informativa sulla privacy.

= Come resta sincronizzato con il mio catalogo? =

Il plugin mantiene una coda locale delle modifiche e la smaltisce in modo affidabile in background — così continua a funzionare anche su siti con poco traffico e dietro caching aggressivo — dosando il proprio ritmo per lasciare la vetrina reattiva per i clienti. Ogni aggiornamento è firmato e porta con sé una versione, così le modifiche non possono arrivare nell’ordine sbagliato. Una schermata con lo stato della sincronizzazione in tempo reale mostra esattamente cosa è indicizzato. La riparazione automatica notturna delle discrepanze è nella nostra roadmap.

= WooCommerce è obbligatorio? =

Sì. NitroSearch indicizza e cerca i prodotti WooCommerce, quindi WooCommerce deve essere installato e attivo. È compatibile con WooCommerce High-Performance Order Storage (HPOS).

= Mostra un badge «Powered by NitroSearch» sul mio negozio? =

Solo se lo scegli tu. Il credito è **disattivato per impostazione predefinita** e nulla viene aggiunto alla tua vetrina finché non lo attivi nelle impostazioni **Design** del plugin. Se lo fai, compare come un piccolo credito con link nel campo di ricerca e una riga nel footer del sito, entrambi verso nitrosearch.io. Grazie se lo attivi — ma il plugin funziona in modo identico in entrambi i casi.

= Cosa succede se disattivo il plugin? =

Il tuo negozio torna alla normale ricerca di WooCommerce. I tuoi dati su NitroSearch si gestiscono dal tuo account; puoi scollegare il negozio in qualsiasi momento dalla schermata del plugin.

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
