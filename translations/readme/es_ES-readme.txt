=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Búsqueda instantánea y tolerante a erratas para WooCommerce desde la nube: resultados a la altura de Amazon, con facetas y sin frenar tu tienda.

== Description ==

**Tus compradores no pueden comprar lo que no encuentran.** La búsqueda integrada de WooCommerce es lenta, no perdona las erratas y se ahoga con los catálogos grandes, así que los clientes que buscan y no encuentran nada sencillamente se van. NitroSearch la sustituye por una búsqueda rápida, tolerante y a la altura de Amazon que funciona en nuestros servidores, no en los tuyos.

Instala el plugin, conecta tu tienda y, desde ese momento, cada búsqueda que tecleen tus clientes se responde en **alrededor de una décima de segundo** —erratas incluidas— directamente desde nuestro motor. Sin rehacer el tema. Sin carga de búsqueda en tu alojamiento de WordPress. Sin frenar tu tienda.

= Por qué los dueños de tiendas se pasan a NitroSearch =

* **Instantánea y tolerante a erratas**: los resultados aparecen mientras tu cliente escribe, y «zapatilas de correr» sigue encontrando tus zapatillas de correr.
* **No frenará tu tienda**: la búsqueda va directamente del navegador del comprador a nuestro motor, así que tu servidor de WordPress nunca está en el camino de la búsqueda. El widget de la página es ligero como una pluma y va sellado en su propio shadow DOM, así que nunca se pelea con tu tema ni con tu puntuación de velocidad.
* **Filtros, facetas y una página de resultados completa**: facetas de categoría, marca, ofertas y stock, una cuadrícula de resultados completa con paginación y «Añadir al carrito» directamente desde los resultados.
* **Todo tu sitio, no solo la tienda**: si quieres, indexa también tus páginas y entradas del blog, que se muestran en su propia sección debajo de los productos, para que tu catálogo nunca quede enterrado. El contenido completo de las páginas nunca se copia, y el contenido privado, protegido con contraseña o marcado como *noindex* siempre se queda fuera.
* **Una sincronización en la que puedes confiar**: el plugin mantiene su propia cola de cambios y reintenta hasta que cada cambio llega, con cada actualización firmada y versionada para que nada llegue desordenado. Una pantalla de estado de sincronización en vivo muestra exactamente qué está indexado: la confianza es algo que puedes comprobar, no una promesa.
* **Precios honestos y sencillos**: un nivel gratuito de verdad, todas las funciones de búsqueda en todos los planes (la búsqueda en sí nunca se limita por plan) y precios que escalan solo con el tamaño del catálogo. Sin tarifas por búsqueda y sin facturas sorpresa.
* **Descubre qué buscan realmente tus compradores**: todos los planes de pago incluyen analítica de búsqueda, con las consultas más repetidas, lo que se ha clicado y añadido al carrito, y las búsquedas que no encontraron *nada* (una lista ya hecha de demanda que aún no tienes en stock). Un resumen vive en el propio wp-admin; los paneles completos están en tu cuenta de NitroSearch. Sin cookies y sin identificadores de compradores, nunca.
* **No se añade nada a tu sitio si no lo pides**: el crédito opcional «Con la tecnología de NitroSearch» está desactivado por defecto. Si lo activas, añade un pequeño crédito con enlace; si lo dejas desactivado, tu tienda no gana nada más que la búsqueda.
* **Listo en minutos**: mejora automáticamente la caja de búsqueda *existente* de tu tema. Sin shortcodes, sin editar plantillas, sin rehacer nada.

= Cómo funciona =

NitroSearch es un servicio de búsqueda alojado; este plugin es su conector oficial para WooCommerce. Hace dos cosas, y nunca se estorban entre sí:

1. **Mantiene fresca nuestra copia de tu catálogo.** A medida que cambian los productos, los precios y el stock, el plugin envía discretamente las actualizaciones a NitroSearch en segundo plano: agrupadas en una cola de cambios local, firmadas y reintentadas hasta que cada cambio llega.
2. **Responde a las búsquedas al instante.** Cuando un comprador busca, el widget habla *directamente* con nuestro motor usando una clave de solo búsqueda limitada a los productos públicos de tu tienda; nunca pasa por tu servidor de WordPress, y por eso la búsqueda sigue siendo rápida incluso con mucha carga.

= Gratis para empezar =

El nivel gratuito funciona desde el momento en que instalas el plugin: hasta 100 resultados de búsqueda, con todas las funciones de búsqueda incluidas. La cuenta de NitroSearch es opcional; crea una para gestionar tu plan y mejorar desde tu panel. Más información en [nitrosearch.io](https://nitrosearch.io).

== Servicios externos ==

Este plugin se conecta al **servicio de búsqueda alojado de NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) para indexar tu catálogo y servir los resultados de búsqueda. Ese es el propósito principal del plugin.

* **Qué se envía y cuándo:** cuando haces clic en **«Conectar tienda»**, el plugin registra tu sitio en NitroSearch (la URL de tu sitio y un identificador de instalación generado aleatoriamente). Tras conectar, se envían los datos de tus productos —nombres, descripciones, SKU, precios, estado de stock, categorías, atributos, imágenes y enlaces permanentes— para que puedan indexarse para la búsqueda. Los cambios en los productos se envían a medida que se producen.
* **Consultas de búsqueda:** una vez conectada la tienda, las consultas de búsqueda de los compradores se envían desde su navegador directamente al motor de NitroSearch para devolver los resultados.
* **Scripts que se cargan en tu tienda:** una vez conectada, el plugin carga el JavaScript del widget de búsqueda desde `api.nitrosearch.io` (un pequeño cargador, más el propio widget con la primera intención de búsqueda) para que los resultados puedan mostrarse en el navegador del comprador. No se carga nada antes de conectar.
* **Recuentos de uso de la búsqueda:** una vez conectada, el widget de búsqueda también envía eventos de uso anónimos y sin cookies —la consulta escrita, cuántos resultados aparecieron y los clics en esos resultados— a `api.nitrosearch.io`. No llevan identificadores de compradores, ni cookies, ni perfiles basados en la IP, y los registros en bruto se borran de forma periódica. Desactívalo cuando quieras en **NitroSearch → Apariencia → Datos de uso de la búsqueda**.
* **Dónde:** en la API y el motor de búsqueda de NitroSearch, en `api.nitrosearch.io` y en el punto de conexión de búsqueda dedicado de tu tienda.
* **Nada sale de tu sitio hasta que haces clic en «Conectar».**

Términos de uso del servicio: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Política de privacidad: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Instala y activa **WooCommerce**.
2. Instala y activa **NitroSearch for WooCommerce** (desde Plugins → Añadir un nuevo plugin, o subiendo el ZIP).
3. Abre el menú **NitroSearch** en wp-admin y haz clic en **«Conectar tienda»**.
4. Eso es todo: tu catálogo empieza a sincronizarse y la caja de búsqueda de tu tema se mejora automáticamente.

== Frequently Asked Questions ==

= ¿Es gratis de verdad? =

Sí. El nivel gratuito cubre hasta 100 resultados de búsqueda con todas las funciones de búsqueda incluidas, para siempre: la búsqueda en sí nunca se limita por plan. Los planes de pago amplían el límite (e incluyen extras como los informes por tienda que estamos construyendo); pagas solo por cuánto indexas, nunca por búsqueda.

= ¿Necesito crear una cuenta? =

No hace falta ninguna cuenta para usar el nivel gratuito: instalas, conectas y listo. La cuenta es opcional y te permite gestionar tu plan y mejorarlo.

= ¿Ralentizará mi tienda? =

No, esa es justo la idea. Las búsquedas van directamente del navegador del comprador a nuestro motor, así que tu servidor de WordPress/WooCommerce nunca hace el trabajo de búsqueda. El widget de la página es diminuto y solo se carga cuando un comprador empieza a buscar.

= ¿Tengo que cambiar mi tema o añadir un shortcode? =

No. NitroSearch mejora la caja de búsqueda *existente* de tu tema, allí donde está. No hay nada que rehacer. (Si tu tema usa un campo de búsqueda poco habitual, puedes indicárselo a NitroSearch con un selector CSS opcional en los ajustes de «Apariencia»).

= ¿Qué datos salen de mi sitio y cuándo? =

Nada hasta que haces clic en **«Conectar»**. Después, tu catálogo de productos se envía a NitroSearch para indexarlo, y las actualizaciones se envían a medida que cambian los productos. Consulta la sección **Servicios externos** de más arriba para ver la lista completa, junto con los enlaces a nuestros términos y a nuestra política de privacidad.

= ¿Cómo se mantiene sincronizado con mi catálogo? =

El plugin mantiene una cola de cambios local y la vacía de forma fiable en segundo plano —así sigue funcionando incluso en sitios con poco tráfico y detrás de cachés agresivas—, regulando su propio ritmo para que tu tienda siga respondiendo a los compradores. Cada actualización va firmada y lleva una versión, de modo que los cambios no pueden llegar desordenados. Una pantalla de estado de sincronización en vivo muestra exactamente qué está indexado. La reparación automática nocturna de desviaciones está en nuestra hoja de ruta.

= ¿Es necesario WooCommerce? =

Sí. NitroSearch indexa y busca productos de WooCommerce, así que WooCommerce debe estar instalado y activo. Es compatible con el almacenamiento de pedidos de alto rendimiento (HPOS) de WooCommerce.

= ¿Muestra una insignia de «Con la tecnología de NitroSearch» en mi tienda? =

Solo si tú quieres. El crédito está **desactivado por defecto**, y no se añade nada a tu tienda a menos que lo actives en los ajustes de **Apariencia** del plugin. Si lo haces, aparece como un pequeño crédito con enlace en la caja de búsqueda y una línea en el pie de página de tu sitio, ambos apuntando a nitrosearch.io. Gracias si lo activas, pero el plugin funciona exactamente igual en ambos casos.

= ¿Qué pasa si desactivo el plugin? =

Tu tienda vuelve a su búsqueda normal de WooCommerce. Tus datos en NitroSearch se gestionan desde tu cuenta; puedes desconectarla en cualquier momento desde la pantalla del plugin.

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
