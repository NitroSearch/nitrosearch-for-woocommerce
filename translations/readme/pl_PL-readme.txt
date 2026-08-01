=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Błyskawiczna, odporna na literówki wyszukiwarka produktów WooCommerce z chmury — wyniki na poziomie Amazona, z filtrami, bez spowalniania sklepu.

== Description ==

**Klienci nie kupią tego, czego nie mogą znaleźć.** Wbudowana wyszukiwarka WooCommerce jest wolna, nie radzi sobie z literówkami i wymięka przy dużych katalogach — klienci, którzy szukają i nic nie znajdują, po prostu wychodzą. NitroSearch zastępuje ją szybką, wyrozumiałą wyszukiwarką na poziomie Amazona, która działa na naszych serwerach, nie na Twoich.

Zainstaluj wtyczkę, połącz sklep i od tej chwili każde wyszukiwanie Twoich klientów otrzymuje odpowiedź w **około jedną dziesiątą sekundy** — razem z literówkami — prosto z naszego silnika. Bez przebudowy motywu. Bez obciążania Twojego hostingu WordPressa wyszukiwaniem. Bez spowalniania sklepu.

= Dlaczego właściciele sklepów przechodzą na NitroSearch =

* **Błyskawiczna i odporna na literówki** — wyniki pojawiają się w trakcie pisania, a „buty do bigania” i tak znajdą Twoje buty do biegania.
* **Nie spowolni Twojego sklepu** — wyszukiwanie odbywa się bezpośrednio między przeglądarką klienta a naszym silnikiem, więc Twój serwer WordPressa nigdy nie znajduje się na ścieżce wyszukiwania. Widżet na stronie jest lekki jak piórko i zamknięty we własnym shadow DOM, więc nigdy nie wchodzi w konflikt z motywem ani z wynikiem szybkości strony.
* **Filtry, fasety i pełna strona wyników** — filtry kategorii, marki, promocji i dostępności, kompletna siatka wyników z paginacją oraz dodawanie do koszyka wprost z wyników.
* **Cała witryna, nie tylko sklep** — opcjonalnie indeksuj także strony i wpisy na blogu; pojawiają się w osobnej sekcji pod produktami, więc Twój katalog nigdy nie schodzi na dalszy plan. Pełna treść stron nigdy nie jest kopiowana, a treści prywatne, chronione hasłem i oznaczone *noindex* są zawsze pomijane.
* **Synchronizacja, której można zaufać** — wtyczka prowadzi własną kolejkę zmian i ponawia wysyłkę, aż każda zmiana dotrze na miejsce; każda aktualizacja jest podpisana i wersjonowana, więc nic nie dociera w złej kolejności. Ekran stanu synchronizacji na żywo pokazuje dokładnie, co jest zaindeksowane — zaufanie można więc sprawdzić, zamiast wierzyć na słowo.
* **Uczciwe, proste ceny** — naprawdę darmowy plan, wszystkie funkcje wyszukiwania w każdym planie (samo wyszukiwanie nigdy nie jest limitowane pakietami), a cena rośnie wyłącznie z wielkością katalogu. Bez opłat za wyszukiwanie, bez zaskakujących rachunków.
* **Zobacz, czego naprawdę szukają klienci** — każdy płatny plan zawiera statystyki wyszukiwania: najczęstsze zapytania, co było klikane i dodawane do koszyków oraz wyszukiwania, które nie znalazły *niczego* (gotowa lista popytu, którego jeszcze nie masz w ofercie). Podsumowanie znajdziesz bezpośrednio w wp-admin, a pełne raporty w Twoim koncie NitroSearch. Bez plików cookie i bez identyfikatorów klientów — nigdy.
* **Nic nie jest dodawane do Twojej witryny bez Twojej zgody** — opcjonalne oznaczenie „Działa dzięki NitroSearch” jest domyślnie wyłączone. Włącz je, a pojawi się niewielki podlinkowany dopisek; zostaw wyłączone, a Twój sklep zyska wyłącznie wyszukiwarkę.
* **Konfiguracja w kilka minut** — automatycznie ulepsza *istniejące* pole wyszukiwania Twojego motywu. Bez shortcode'ów, bez edycji szablonów, bez przebudowy.

= Jak to działa =

NitroSearch to hostowana usługa wyszukiwania, a ta wtyczka jest jej oficjalnym łącznikiem z WooCommerce. Robi dwie rzeczy, które nigdy nie wchodzą sobie w drogę:

1. **Dba o aktualność naszej kopii Twojego katalogu.** Gdy zmieniają się produkty, ceny i stany magazynowe, wtyczka po cichu wysyła aktualizacje do NitroSearch w tle — scalone w lokalnej kolejce zmian, podpisane i ponawiane, aż każda zmiana dotrze na miejsce.
2. **Odpowiada na wyszukiwania — natychmiast.** Gdy klient szuka, widżet komunikuje się *bezpośrednio* z naszym silnikiem przy użyciu klucza tylko do wyszukiwania, ograniczonego do publicznych produktów Twojego sklepu — nigdy przez Twój serwer WordPressa, dlatego wyszukiwarka pozostaje szybka nawet pod obciążeniem.

= Zacznij za darmo =

Darmowy plan działa od chwili instalacji wtyczki — do 100 wyników wyszukiwania, ze wszystkimi funkcjami wyszukiwania. Konto NitroSearch jest opcjonalne; załóż je, aby zarządzać planem i ulepszać go z poziomu swojego panelu. Więcej na [nitrosearch.io](https://nitrosearch.io).

== Usługi zewnętrzne ==

Ta wtyczka łączy się z **hostowaną usługą wyszukiwania NitroSearch** ([nitrosearch.io](https://nitrosearch.io)), aby indeksować Twój katalog i dostarczać wyniki wyszukiwania. To podstawowy cel wtyczki.

* **Co jest wysyłane i kiedy:** gdy klikniesz **„Połącz sklep”**, wtyczka rejestruje Twoją witrynę w NitroSearch (adres URL witryny i losowo wygenerowany identyfikator instalacji). Po połączeniu wysyłane są dane produktów — nazwy, opisy, numery SKU, ceny, stany magazynowe, kategorie, atrybuty, obrazki i bezpośrednie odnośniki — aby można je było zaindeksować na potrzeby wyszukiwania. Zmiany produktów są wysyłane na bieżąco.
* **Zapytania wyszukiwania:** po połączeniu zapytania klientów są wysyłane z ich przeglądarek bezpośrednio do silnika NitroSearch w celu zwrócenia wyników.
* **Skrypty wczytywane w Twoim sklepie:** po połączeniu wtyczka wczytuje JavaScript widżetu wyszukiwania z `api.nitrosearch.io` (mały loader, a sam widżet przy pierwszej próbie wyszukiwania), aby wyniki mogły być renderowane w przeglądarce klienta. Przed połączeniem nic nie jest wczytywane.
* **Statystyki korzystania z wyszukiwarki:** po połączeniu widżet wyszukiwania wysyła też anonimowe zdarzenia bez plików cookie — wpisane zapytanie, liczbę zwróconych wyników i kliknięcia w te wyniki — do `api.nitrosearch.io`. Nie zawierają one identyfikatorów klientów, plików cookie ani profili opartych na adresach IP, a surowe zapisy są usuwane według stałego harmonogramu. Możesz to w każdej chwili wyłączyć w **NitroSearch → Wygląd → Dane o korzystaniu z wyszukiwarki**.
* **Dokąd:** do API i silnika wyszukiwania NitroSearch pod adresem `api.nitrosearch.io` oraz do dedykowanego punktu końcowego wyszukiwania Twojego sklepu.
* **Nic nie opuszcza Twojej witryny, dopóki nie klikniesz „Połącz sklep”.**

Warunki korzystania z usługi: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Polityka prywatności: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Zainstaluj i włącz **WooCommerce**.
2. Zainstaluj i włącz **NitroSearch for WooCommerce** (z menu Wtyczki → Dodaj nową wtyczkę lub przez wgranie pliku ZIP).
3. Otwórz menu **NitroSearch** w wp-admin i kliknij **„Połącz sklep”**.
4. To wszystko — katalog zaczyna się synchronizować, a pole wyszukiwania Twojego motywu zostaje ulepszone automatycznie.

== Frequently Asked Questions ==

= Czy to naprawdę darmowe? =

Tak. Darmowy plan obejmuje do 100 wyników wyszukiwania ze wszystkimi funkcjami wyszukiwania, bezterminowo — samo wyszukiwanie nigdy nie jest limitowane pakietami. Płatne plany podnoszą limit (i zawierają dodatki, takie jak budowane właśnie raporty dla sklepów); płacisz tylko za to, ile indeksujesz, nigdy za pojedyncze wyszukiwania.

= Czy muszę zakładać konto? =

Do korzystania z darmowego planu konto nie jest wymagane — instalujesz, łączysz, gotowe. Konto jest opcjonalne i pozwala zarządzać planem oraz go ulepszać.

= Czy spowolni to mój sklep? =

Nie — o to właśnie chodzi. Wyszukiwania odbywają się bezpośrednio między przeglądarką klienta a naszym silnikiem, więc Twój serwer WordPress/WooCommerce nigdy nie wykonuje pracy związanej z wyszukiwaniem. Widżet na stronie jest maleńki i wczytuje się dopiero wtedy, gdy klient zaczyna szukać.

= Czy muszę zmieniać motyw albo dodawać shortcode? =

Nie. NitroSearch ulepsza *istniejące* pole wyszukiwania Twojego motywu tam, gdzie ono jest. Nie ma nic do przebudowywania. (Jeśli Twój motyw używa nietypowego pola wyszukiwania, możesz wskazać je NitroSearch opcjonalnym selektorem CSS w ustawieniach na karcie „Wygląd”.)

= Jakie dane opuszczają moją witrynę i kiedy? =

Żadne, dopóki nie klikniesz **„Połącz sklep”**. Potem Twój katalog produktów jest wysyłany do NitroSearch w celu zaindeksowania, a aktualizacje są wysyłane wraz ze zmianami produktów. Pełną listę oraz odnośniki do naszych warunków i polityki prywatności znajdziesz w sekcji **Usługi zewnętrzne** powyżej.

= Jak wyszukiwarka pozostaje zsynchronizowana z moim katalogiem? =

Wtyczka prowadzi lokalną kolejkę zmian i niezawodnie opróżnia ją w tle — działa więc nawet na witrynach o małym ruchu i za agresywnym buforowaniem — dawkując pracę tak, aby sklep pozostawał responsywny dla klientów. Każda aktualizacja jest podpisana i ma numer wersji, więc zmiany nie mogą dotrzeć w złej kolejności. Ekran stanu synchronizacji na żywo pokazuje dokładnie, co jest zaindeksowane. Automatyczna nocna naprawa rozbieżności jest w naszych planach.

= Czy WooCommerce jest wymagany? =

Tak. NitroSearch indeksuje i przeszukuje produkty WooCommerce, więc WooCommerce musi być zainstalowany i włączony. Wtyczka jest zgodna z WooCommerce High-Performance Order Storage (HPOS).

= Czy w moim sklepie pojawia się oznaczenie „Działa dzięki NitroSearch”? =

Tylko jeśli tak zdecydujesz. Oznaczenie jest **domyślnie wyłączone** i nic nie jest dodawane do Twojego sklepu, dopóki nie włączysz go w ustawieniach wtyczki na karcie **Wygląd**. Po włączeniu pojawia się jako niewielki podlinkowany dopisek w polu wyszukiwania oraz jedna linijka w stopce witryny — oba prowadzą do nitrosearch.io. Dziękujemy, jeśli je włączysz, ale wtyczka działa identycznie w obu przypadkach.

= Co się stanie, gdy wyłączę wtyczkę? =

Sklep wraca do zwykłej wyszukiwarki WooCommerce. Danymi w NitroSearch zarządzasz ze swojego konta; sklep możesz w każdej chwili rozłączyć z poziomu ekranu wtyczki.

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
