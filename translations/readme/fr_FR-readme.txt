=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Recherche WooCommerce hébergée, instantanée et tolérante aux fautes — résultats dignes d’Amazon, avec facettes, sans ralentir votre boutique.

== Description ==

**Vos clients ne peuvent pas acheter ce qu’ils ne trouvent pas.** La recherche intégrée de WooCommerce est lente, rate les fautes de frappe et s’effondre sur les gros catalogues — les clients qui cherchent sans rien trouver s’en vont, tout simplement. NitroSearch la remplace par une recherche rapide, indulgente, digne d’Amazon, qui tourne sur nos serveurs, pas sur les vôtres.

Installez l’extension, connectez votre boutique, et dès cet instant chaque recherche tapée par vos clients obtient sa réponse en **environ un dixième de seconde** — fautes de frappe comprises — directement depuis notre moteur. Pas de refonte de thème. Aucune charge de recherche sur votre hébergement WordPress. Aucun ralentissement de votre boutique.

= Pourquoi les marchands passent à NitroSearch =

* **Instantanée et tolérante aux fautes de frappe** — les résultats s’affichent à mesure que votre client tape, et « chausures de course » trouve quand même vos chaussures de course.
* **Elle ne ralentira pas votre boutique** — la recherche s’effectue directement entre le navigateur du client et notre moteur : votre serveur WordPress n’est donc jamais sur le chemin de la recherche. Le widget affiché sur la page est ultra-léger et isolé dans son propre shadow DOM : il n’entre jamais en conflit avec votre thème ni avec votre score de vitesse.
* **Filtres, facettes et page de résultats complète** — des facettes catégorie, marque, en promotion et en stock, une grille de résultats complète avec pagination, et l’ajout au panier directement depuis les résultats.
* **Tout votre site, pas seulement la boutique** — indexez aussi, si vous le souhaitez, vos pages et articles de blog, affichés dans leur propre section sous les produits pour que votre catalogue ne soit jamais noyé. Le contenu intégral des pages n’est jamais copié, et le contenu privé, protégé par mot de passe ou marqué *noindex* est toujours exclu.
* **Une synchronisation digne de confiance** — l’extension tient sa propre file de modifications et réessaie jusqu’à ce que chaque changement soit bien arrivé ; chaque mise à jour est signée et versionnée, si bien que rien n’arrive dans le désordre. Un écran d’état de synchronisation en direct montre exactement ce qui est indexé : la confiance, vous pouvez la vérifier plutôt que de nous croire sur parole.
* **Des tarifs simples et honnêtes** — une offre gratuite qui l’est vraiment, toutes les fonctionnalités de recherche dans toutes les offres (la recherche elle-même n’est jamais bridée), et un prix qui n’évolue qu’avec la taille du catalogue. Pas de frais à la recherche, pas de facture surprise.
* **Découvrez ce que vos clients cherchent vraiment** — chaque offre payante inclut les statistiques de recherche : les requêtes les plus fréquentes, ce qui a été cliqué et ajouté au panier, et les recherches qui n’ont *rien* trouvé (une liste toute prête de la demande que vous ne stockez pas encore). Un résumé s’affiche directement dans wp-admin ; les tableaux de bord complets sont dans votre compte NitroSearch. Sans cookie, sans identifiant client — jamais.
* **Rien n’est ajouté à votre site sans votre accord** — la mention facultative « Propulsé par NitroSearch » est désactivée par défaut. Activez-la et une petite mention avec lien s’ajoute ; laissez-la désactivée et votre boutique ne gagne rien d’autre que la recherche.
* **Installée en quelques minutes** — améliore automatiquement le champ de recherche *existant* de votre thème. Pas de code court, pas de modification de modèle, pas de refonte.

= Comment ça fonctionne =

NitroSearch est un service de recherche hébergé ; cette extension en est le connecteur officiel pour WooCommerce. Elle fait deux choses, qui ne se gênent jamais l’une l’autre :

1. **Elle maintient à jour notre copie de votre catalogue.** À mesure que les produits, les prix et le stock changent, l’extension envoie discrètement les mises à jour à NitroSearch en arrière-plan — regroupées dans une file de modifications locale, signées, et renvoyées jusqu’à ce que chaque changement soit bien arrivé.
2. **Elle répond aux recherches, instantanément.** Quand un client lance une recherche, le widget dialogue *directement* avec notre moteur à l’aide d’une clé limitée à la recherche et restreinte aux produits publics de votre boutique — jamais via votre serveur WordPress, et c’est pour cela que la recherche reste rapide même en cas de forte affluence.

= Gratuit pour commencer =

L’offre gratuite fonctionne dès l’installation de l’extension — jusqu’à 100 résultats de recherche, avec toutes les fonctionnalités de recherche incluses. Un compte NitroSearch est facultatif ; créez-en un pour gérer votre offre et mettre à niveau depuis votre tableau de bord. En savoir plus sur [nitrosearch.io](https://nitrosearch.io).

== Services externes ==

Cette extension se connecte au **service de recherche hébergé NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) pour indexer votre catalogue et servir les résultats de recherche. C’est la raison d’être de l’extension.

* **Ce qui est envoyé, et quand :** lorsque vous cliquez sur **« Connecter la boutique »**, l’extension enregistre votre site auprès de NitroSearch (l’URL de votre site et un identifiant d’installation généré aléatoirement). Une fois la connexion établie, vos données produits — noms, descriptions, UGS, prix, état du stock, catégories, attributs, images et permaliens — sont envoyées afin d’être indexées pour la recherche. Les modifications de produits sont envoyées au fur et à mesure.
* **Requêtes de recherche :** une fois la boutique connectée, les requêtes de recherche des clients sont envoyées depuis leur navigateur directement au moteur NitroSearch afin de renvoyer les résultats.
* **Scripts chargés sur votre boutique :** une fois la boutique connectée, l’extension charge le JavaScript du widget de recherche depuis `api.nitrosearch.io` (un petit chargeur, puis le widget lui-même à la première intention de recherche) afin que les résultats puissent s’afficher dans le navigateur du client. Rien n’est chargé avant la connexion.
* **Comptages d’utilisation de la recherche :** une fois la boutique connectée, le widget de recherche envoie aussi des évènements d’utilisation anonymes et sans cookie — la requête tapée, le nombre de résultats affichés et les clics sur ces résultats — à `api.nitrosearch.io`. Ils ne comportent aucun identifiant client, aucun cookie ni aucun profil basé sur l’adresse IP, et les enregistrements bruts sont supprimés selon un calendrier glissant. Désactivez cet envoi à tout moment dans **NitroSearch → Apparence → Données d’utilisation de la recherche**.
* **Où :** l’API et le moteur de recherche NitroSearch, sur `api.nitrosearch.io` et le point de terminaison de recherche dédié à votre boutique.
* **Rien ne quitte votre site tant que vous n’avez pas cliqué sur « Connecter ».**

Conditions d’utilisation du service : [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Politique de confidentialité : [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Installez et activez **WooCommerce**.
2. Installez et activez **NitroSearch for WooCommerce** (depuis Extensions → Ajouter une extension, ou en téléversant le fichier ZIP).
3. Ouvrez le menu **NitroSearch** dans wp-admin et cliquez sur **« Connecter la boutique »**.
4. C’est tout — votre catalogue commence à se synchroniser, et le champ de recherche de votre thème est amélioré automatiquement.

== Frequently Asked Questions ==

= Est-ce vraiment gratuit ? =

Oui. L’offre gratuite couvre jusqu’à 100 résultats de recherche avec toutes les fonctionnalités de recherche incluses, pour toujours — la recherche elle-même n’est jamais bridée. Les offres payantes relèvent la limite (et incluent des extras comme les rapports par boutique que nous sommes en train de construire) ; vous payez uniquement pour le volume indexé, jamais à la recherche.

= Dois-je créer un compte ? =

Aucun compte n’est requis pour utiliser l’offre gratuite — installez, connectez, c’est terminé. Un compte est facultatif et vous permet de gérer votre offre et de mettre à niveau.

= Est-ce que cela va ralentir ma boutique ? =

Non — c’est justement tout l’intérêt. Les recherches s’effectuent directement entre le navigateur du client et notre moteur : votre serveur WordPress/WooCommerce n’effectue jamais le travail de recherche. Le widget affiché sur la page est minuscule et ne se charge que lorsqu’un client commence à chercher.

= Dois-je modifier mon thème ou ajouter un code court ? =

Non. NitroSearch améliore le champ de recherche *existant* de votre thème, là où il se trouve. Il n’y a rien à reconstruire. (Si votre thème utilise un champ de recherche inhabituel, vous pouvez l’indiquer à NitroSearch grâce à un sélecteur CSS facultatif dans les réglages Apparence.)

= Quelles données quittent mon site, et quand ? =

Rien tant que vous n’avez pas cliqué sur **« Connecter »**. Ensuite, votre catalogue de produits est envoyé à NitroSearch pour y être indexé, et les mises à jour sont envoyées au fil des modifications de produits. Consultez la section **Services externes** ci-dessus pour la liste complète, ainsi que les liens vers nos conditions d’utilisation et notre politique de confidentialité.

= Comment l’extension reste-t-elle synchronisée avec mon catalogue ? =

L’extension tient une file de modifications locale et la vide de façon fiable en arrière-plan — elle continue donc de fonctionner même sur les sites à faible trafic et derrière une mise en cache agressive — en régulant son rythme pour que votre boutique reste réactive pour vos clients. Chaque mise à jour est signée et porte un numéro de version, de sorte que les changements ne peuvent pas arriver dans le désordre. Un écran d’état de synchronisation en direct montre exactement ce qui est indexé. La réparation automatique nocturne des écarts est sur notre feuille de route.

= WooCommerce est-il requis ? =

Oui. NitroSearch indexe et recherche des produits WooCommerce : WooCommerce doit donc être installé et actif. L’extension est compatible avec le stockage haute performance des commandes de WooCommerce (HPOS).

= Un badge « Propulsé par NitroSearch » s’affiche-t-il sur ma boutique ? =

Uniquement si vous le décidez. La mention est **désactivée par défaut**, et rien n’est ajouté à votre boutique tant que vous ne l’activez pas dans les réglages **Apparence** de l’extension. Si vous l’activez, elle apparaît sous la forme d’une petite mention avec lien dans le champ de recherche et d’une ligne dans le pied de page de votre site, toutes deux pointant vers nitrosearch.io. Merci si vous l’activez — mais l’extension fonctionne exactement de la même façon dans les deux cas.

= Que se passe-t-il si je désactive l’extension ? =

Votre boutique revient à sa recherche WooCommerce normale. Vos données sur NitroSearch se gèrent depuis votre compte ; vous pouvez déconnecter la boutique à tout moment depuis l’écran de l’extension.

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
