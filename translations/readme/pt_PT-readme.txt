=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pesquisa de produtos WooCommerce instantânea e tolerante a gralhas, na cloud — resultados de qualidade Amazon, com facetas, sem abrandar a sua loja.

== Description ==

**Os seus clientes não podem comprar o que não encontram.** A pesquisa nativa do WooCommerce é lenta, não perdoa gralhas e cede perante catálogos grandes — e os clientes que pesquisam e não encontram nada simplesmente vão-se embora. O NitroSearch substitui-a por uma pesquisa rápida, tolerante e com a qualidade da Amazon, que corre nos nossos servidores, não nos seus.

Instale o plugin, ligue a sua loja e, a partir desse momento, cada pesquisa que os seus clientes escrevem é respondida em **cerca de um décimo de segundo** — gralhas incluídas — diretamente pelo nosso motor. Sem reconstruir o tema. Sem carga de pesquisa no alojamento do seu WordPress. Sem abrandar a sua loja.

= Porque é que os donos de lojas mudam para o NitroSearch =

* **Instantânea e tolerante a gralhas** — os resultados aparecem enquanto o cliente escreve, e «sapatilhas de corida» continua a encontrar as suas sapatilhas de corrida.
* **Não abranda a sua loja** — a pesquisa corre diretamente entre o navegador do cliente e o nosso motor, por isso o seu servidor WordPress nunca está no caminho da pesquisa. O widget na página é levíssimo e está isolado no seu próprio shadow DOM, pelo que nunca entra em conflito com o seu tema nem com a sua pontuação de velocidade.
* **Filtros, facetas e uma página de resultados completa** — facetas de categoria, marca, promoções e stock, uma grelha de resultados completa com paginação e adição ao carrinho diretamente a partir dos resultados.
* **Todo o seu site, não apenas a loja** — opcionalmente, indexe também as suas páginas e artigos do blog, apresentados numa secção própria por baixo dos produtos, para que o seu catálogo nunca fique enterrado. O conteúdo integral das páginas nunca é copiado, e conteúdo privado, protegido por palavra-passe ou com *noindex* fica sempre de fora.
* **Sincronização em que pode confiar** — o plugin mantém a sua própria fila de alterações e volta a tentar até cada alteração chegar ao destino, com cada atualização assinada e versionada para que nada chegue fora de ordem. Um ecrã de estado da sincronização em tempo real mostra exatamente o que está indexado — a confiança é algo que pode verificar, não uma promessa.
* **Preços honestos e simples** — um escalão gratuito a sério, todas as funcionalidades de pesquisa em todos os planos (a pesquisa em si nunca é limitada por escalões) e preços que crescem apenas com o tamanho do catálogo. Sem custos por pesquisa, sem faturas-surpresa.
* **Veja o que os clientes realmente pesquisam** — todos os planos pagos incluem estatísticas de pesquisa: as pesquisas mais frequentes, o que foi clicado e adicionado ao carrinho e as pesquisas que não encontraram *nada* (uma lista pronta de procura que ainda não tem em stock). Um resumo fica mesmo no wp-admin; os painéis completos estão na sua conta NitroSearch. Sem cookies e sem identificadores de clientes — nunca.
* **Nada é adicionado ao seu site sem que o peça** — o crédito opcional «Com tecnologia NitroSearch» está desativado por predefinição. Se o ativar, é adicionado um pequeno crédito com ligação; se o deixar desativado, a sua loja não ganha nada além da pesquisa.
* **Configuração em minutos** — melhora automaticamente a caixa de pesquisa *existente* do seu tema. Sem shortcodes, sem editar modelos, sem reconstruir nada.

= Como funciona =

O NitroSearch é um serviço de pesquisa alojado; este plugin é o seu conector oficial para WooCommerce. Faz duas coisas, que nunca se atrapalham uma à outra:

1. **Mantém atualizada a nossa cópia do seu catálogo.** À medida que os produtos, os preços e o stock mudam, o plugin envia discretamente as atualizações para o NitroSearch em segundo plano — agrupadas numa fila de alterações local, assinadas e reenviadas até cada alteração chegar ao destino.
2. **Responde às pesquisas, instantaneamente.** Quando um cliente pesquisa, o widget comunica *diretamente* com o nosso motor, usando uma chave só de pesquisa limitada aos produtos públicos da sua loja — nunca através do seu servidor WordPress, e é por isso que se mantém rápido mesmo sob carga.

= Gratuito para começar =

O escalão gratuito funciona assim que instala o plugin — até 100 resultados de pesquisa, com todas as funcionalidades de pesquisa incluídas. A conta NitroSearch é opcional; crie uma para gerir o seu plano e fazer upgrade a partir do seu painel. Saiba mais em [nitrosearch.io](https://nitrosearch.io).

== Serviços externos ==

Este plugin liga-se ao **serviço de pesquisa alojado NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) para indexar o seu catálogo e servir os resultados de pesquisa. Este é o propósito central do plugin.

* **O que é enviado e quando:** quando clica em **«Ligar loja»**, o plugin regista o seu site no NitroSearch (o URL do seu site e um identificador de instalação gerado aleatoriamente). Depois de ligar, os dados dos seus produtos — nomes, descrições, SKUs, preços, estado de stock, categorias, atributos, imagens e ligações permanentes — são enviados para poderem ser indexados para pesquisa. As alterações aos produtos são enviadas à medida que acontecem.
* **Termos de pesquisa:** depois de ligar, os termos pesquisados pelos clientes são enviados do navegador deles diretamente para o motor NitroSearch, que devolve os resultados.
* **Scripts carregados na sua loja:** depois de ligar, o plugin carrega o JavaScript do widget de pesquisa a partir de `api.nitrosearch.io` (um pequeno carregador e, à primeira intenção de pesquisa, o próprio widget), para que os resultados possam ser apresentados no navegador do cliente. Nada é carregado antes de ligar.
* **Contagens de utilização da pesquisa:** depois de ligar, o widget de pesquisa envia também eventos de utilização anónimos e sem cookies — o termo escrito, quantos resultados apareceram e os cliques nesses resultados — para `api.nitrosearch.io`. Não transportam identificadores de clientes, cookies nem perfis baseados em IP, e os registos em bruto são eliminados de forma rotativa. Pode desativar isto a qualquer momento em **NitroSearch → Aspeto → Dados de utilização da pesquisa**.
* **Onde:** a API e o motor de pesquisa NitroSearch, em `api.nitrosearch.io` e no endpoint de pesquisa dedicado da sua loja.
* **Nada sai do seu site até clicar em Ligar loja.**

Termos de utilização do serviço: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Política de privacidade: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Instale e ative o **WooCommerce**.
2. Instale e ative o **NitroSearch for WooCommerce** (em Plugins → Adicionar plugin, ou enviando o ZIP).
3. Abra o menu **NitroSearch** no wp-admin e clique em **«Ligar loja»**.
4. E está feito — o seu catálogo começa a sincronizar e a caixa de pesquisa do seu tema é melhorada automaticamente.

== Frequently Asked Questions ==

= É mesmo gratuito? =

Sim. O escalão gratuito cobre até 100 resultados de pesquisa com todas as funcionalidades de pesquisa incluídas, para sempre — a pesquisa em si nunca é limitada por escalões. Os planos pagos aumentam o limite (e incluem extras, como os relatórios por loja que estamos a construir); paga apenas pela quantidade que indexa, nunca por pesquisa.

= Preciso de criar uma conta? =

Não é necessária nenhuma conta para usar o escalão gratuito — instalar, ligar, pronto. A conta é opcional e permite-lhe gerir o seu plano e fazer upgrade.

= Vai abrandar a minha loja? =

Não — é exatamente essa a ideia. As pesquisas correm diretamente entre o navegador do cliente e o nosso motor, por isso o seu servidor WordPress/WooCommerce nunca faz o trabalho de pesquisa. O widget na página é minúsculo e só carrega quando um cliente começa a pesquisar.

= Tenho de alterar o meu tema ou adicionar um shortcode? =

Não. O NitroSearch melhora a caixa de pesquisa *existente* do seu tema, no próprio lugar. Não há nada para reconstruir. (Se o seu tema usa um campo de pesquisa invulgar, pode indicá-lo ao NitroSearch com um seletor CSS opcional nas definições de Aspeto.)

= Que dados saem do meu site, e quando? =

Nada, até clicar em **Ligar loja**. Depois disso, o seu catálogo de produtos é enviado para o NitroSearch para ser indexado, e as atualizações são enviadas à medida que os produtos mudam. Consulte a secção **Serviços externos** acima para ver a lista completa, com ligações para os nossos Termos e para a Política de privacidade.

= Como se mantém sincronizado com o meu catálogo? =

O plugin mantém uma fila de alterações local e esvazia-a de forma fiável em segundo plano — por isso continua a funcionar mesmo em sites com pouco tráfego e por trás de cache agressiva — ao seu próprio ritmo, para deixar a sua loja sempre pronta a responder aos clientes. Cada atualização é assinada e transporta uma versão, para que as alterações não possam chegar fora de ordem. Um ecrã de estado da sincronização em tempo real mostra exatamente o que está indexado. A reparação automática de desvios, todas as noites, está no nosso plano de desenvolvimento.

= O WooCommerce é obrigatório? =

Sim. O NitroSearch indexa e pesquisa produtos WooCommerce, por isso o WooCommerce tem de estar instalado e ativo. É compatível com o High-Performance Order Storage (HPOS) do WooCommerce.

= Mostra um selo «Com tecnologia NitroSearch» na minha loja? =

Só se assim o escolher. O crédito está **desativado por predefinição**, e nada é adicionado à sua loja a menos que o ative nas definições de **Aspeto** do plugin. Se o fizer, aparece como um pequeno crédito com ligação na caixa de pesquisa e uma linha no rodapé do seu site, ambos a apontar para nitrosearch.io. Obrigado, se o ativar — mas o plugin funciona exatamente da mesma forma em qualquer dos casos.

= O que acontece se desativar o plugin? =

A sua loja volta à pesquisa normal do WooCommerce. Os seus dados no NitroSearch são geridos a partir da sua conta; pode desligar a loja a qualquer momento no ecrã do plugin.

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
