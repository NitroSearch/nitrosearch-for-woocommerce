=== NitroSearch for WooCommerce ===
Contributors: nitrosearch
Tags: woocommerce search, product search, instant search, autocomplete, faceted search
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Busca instantânea e tolerante a erros de digitação para WooCommerce, na nuvem — resultados no nível da Amazon e filtros, sem deixar sua loja lenta.

== Description ==

**Seus clientes não podem comprar o que não conseguem encontrar.** A busca nativa do WooCommerce é lenta, não perdoa erros de digitação e sofre com catálogos grandes — e quem busca e não encontra nada simplesmente vai embora. O NitroSearch a substitui por uma busca rápida, tolerante e no nível da Amazon, que roda nos nossos servidores, não nos seus.

Instale o plugin, conecte sua loja e, a partir desse momento, cada busca digitada pelos seus clientes é respondida em **cerca de um décimo de segundo** — mesmo com erros de digitação — direto do nosso motor de busca. Sem reconstruir o tema. Sem carga de busca na sua hospedagem WordPress. Sem deixar sua loja lenta.

= Por que lojistas migram para o NitroSearch =

* **Instantânea e tolerante a erros de digitação** — os resultados aparecem enquanto o cliente digita, e “tenis de corida” ainda encontra seus tênis de corrida.
* **Não vai deixar sua loja lenta** — a busca acontece diretamente entre o navegador do cliente e o nosso motor, então seu servidor WordPress nunca fica no caminho da busca. O widget na página é levíssimo e isolado no próprio shadow DOM, então nunca briga com seu tema nem com sua nota de velocidade.
* **Filtros, facetas e uma página de resultados completa** — facetas de categoria, marca, promoção e estoque, uma grade de resultados completa com paginação e adição ao carrinho direto dos resultados.
* **Seu site inteiro, não só a loja** — opcionalmente, indexe também suas páginas e posts do blog, exibidos em uma seção própria abaixo dos produtos, para que seu catálogo nunca fique soterrado. O conteúdo completo das páginas nunca é copiado, e conteúdo privado, protegido por senha ou *noindex* fica sempre de fora.
* **Sincronização em que você pode confiar** — o plugin mantém sua própria fila de alterações e tenta de novo até cada alteração chegar, com cada atualização assinada e versionada para que nada chegue fora de ordem. Uma tela de status de sincronização ao vivo mostra exatamente o que está indexado — confiança é algo que você pode conferir, não só uma promessa.
* **Preços honestos e simples** — um plano gratuito de verdade, todos os recursos de busca em todos os planos (a busca em si nunca é limitada por plano) e preços que escalam apenas com o tamanho do catálogo. Sem tarifas por busca, sem surpresas na fatura.
* **Veja o que os clientes realmente buscam** — todos os planos pagos incluem análises de busca: principais buscas, o que foi clicado e adicionado ao carrinho e as buscas que não encontraram *nada* (uma lista pronta de demanda que você ainda não tem em estoque). Um resumo fica direto no wp-admin; os painéis completos ficam na sua conta NitroSearch. Sem cookies e sem identificadores de clientes — nunca.
* **Nada é adicionado ao seu site sem você pedir** — o crédito opcional “Com tecnologia NitroSearch” vem desativado por padrão. Ative-o e um pequeno crédito com link é adicionado; deixe desativado e sua loja não ganha nada além da busca.
* **Configuração em minutos** — aprimora automaticamente a caixa de busca *existente* do seu tema. Sem shortcodes, sem editar templates, sem reconstruir nada.

= Como funciona =

O NitroSearch é um serviço de busca hospedado; este plugin é seu conector oficial para WooCommerce. Ele faz duas coisas, e uma nunca atrapalha a outra:

1. **Mantém a nossa cópia do seu catálogo atualizada.** Conforme produtos, preços e estoque mudam, o plugin envia as atualizações ao NitroSearch discretamente, em segundo plano — agrupadas em uma fila local de alterações, assinadas e reenviadas até cada alteração chegar.
2. **Responde às buscas, instantaneamente.** Quando um cliente busca, o widget fala *diretamente* com o nosso motor usando uma chave somente de busca, restrita aos produtos públicos da sua loja — nunca passa pelo seu servidor WordPress, e é por isso que continua rápido mesmo sob carga.

= Gratuito para começar =

O plano gratuito funciona no momento em que você instala o plugin — até 100 resultados de busca, com todos os recursos de busca incluídos. Uma conta NitroSearch é opcional; crie uma para gerenciar seu plano e fazer upgrade pelo seu painel. Saiba mais em [nitrosearch.io](https://nitrosearch.io).

== Serviços externos ==

Este plugin se conecta ao **serviço de busca hospedado NitroSearch** ([nitrosearch.io](https://nitrosearch.io)) para indexar seu catálogo e servir os resultados de busca. Esse é o propósito central do plugin.

* **O que é enviado, e quando:** quando você clica em **“Conectar loja”**, o plugin registra seu site no NitroSearch (a URL do site e um identificador de instalação gerado aleatoriamente). Depois de conectar, os dados dos seus produtos — nomes, descrições, SKUs, preços, situação de estoque, categorias, atributos, imagens e links permanentes — são enviados para serem indexados para a busca. As alterações de produtos são enviadas conforme acontecem.
* **Consultas de busca:** depois de conectado, as consultas de busca dos clientes são enviadas do navegador deles diretamente ao motor do NitroSearch para retornar os resultados.
* **Scripts carregados na sua loja:** depois de conectado, o plugin carrega o JavaScript do widget de busca a partir de `api.nitrosearch.io` (um pequeno carregador, mais o próprio widget na primeira intenção de busca) para que os resultados possam ser renderizados no navegador do cliente. Nada é carregado antes de você conectar.
* **Contagens de uso da busca:** depois de conectado, o widget de busca também envia eventos de uso anônimos e sem cookies — a consulta digitada, quantos resultados apareceram e os cliques nesses resultados — para `api.nitrosearch.io`. Eles não carregam identificadores de clientes, cookies nem perfis baseados em IP, e os registros brutos são excluídos em um ciclo contínuo. Desative isso a qualquer momento em **NitroSearch → Aparência → Dados de uso da busca**.
* **Onde:** a API e o motor de busca do NitroSearch, em `api.nitrosearch.io` e no endpoint de busca dedicado da sua loja.
* **Nada sai do seu site até você clicar em Conectar.**

Termos de Uso do serviço: [https://nitrosearch.io/legal/terms](https://nitrosearch.io/legal/terms)
Política de Privacidade: [https://nitrosearch.io/legal/privacy](https://nitrosearch.io/legal/privacy)

== Installation ==

1. Instale e ative o **WooCommerce**.
2. Instale e ative o **NitroSearch for WooCommerce** (em Plugins → Adicionar novo plugin, ou envie o arquivo ZIP).
3. Abra o menu **NitroSearch** no wp-admin e clique em **“Conectar loja”**.
4. Pronto — seu catálogo começa a sincronizar, e a caixa de busca do seu tema é aprimorada automaticamente.

== Frequently Asked Questions ==

= É gratuito mesmo? =

Sim. O plano gratuito cobre até 100 resultados de busca com todos os recursos de busca incluídos, para sempre — a busca em si nunca é limitada por plano. Os planos pagos aumentam o limite (e incluem extras, como os relatórios por loja que estamos construindo); você paga apenas pelo quanto indexa, nunca por busca.

= Preciso criar uma conta? =

Nenhuma conta é necessária para usar o plano gratuito — instale, conecte, pronto. A conta é opcional e permite gerenciar seu plano e fazer upgrade.

= Vai deixar minha loja lenta? =

Não — essa é justamente a questão. As buscas acontecem diretamente entre o navegador do cliente e o nosso motor, então seu servidor WordPress/WooCommerce nunca faz o trabalho de busca. O widget na página é minúsculo e só carrega quando um cliente começa a buscar.

= Preciso alterar meu tema ou adicionar um shortcode? =

Não. O NitroSearch aprimora a caixa de busca *existente* do seu tema, no lugar onde ela está. Não há nada para reconstruir. (Se o seu tema usa um campo de busca fora do comum, você pode apontar o NitroSearch para ele com um seletor CSS opcional nas configurações de Aparência.)

= Quais dados saem do meu site, e quando? =

Nada até você clicar em **Conectar**. Depois disso, seu catálogo de produtos é enviado ao NitroSearch para ser indexado, e as atualizações são enviadas conforme os produtos mudam. Veja a seção **Serviços externos** acima para a lista completa, com links para nossos Termos e nossa Política de Privacidade.

= Como ele se mantém sincronizado com meu catálogo? =

O plugin mantém uma fila local de alterações e a esvazia de forma confiável em segundo plano — assim continua funcionando mesmo em sites de pouco tráfego e atrás de cache agressivo — controlando o próprio ritmo para deixar sua loja responsiva para os clientes. Cada atualização é assinada e carrega uma versão, então as alterações não podem chegar fora de ordem. Uma tela de status de sincronização ao vivo mostra exatamente o que está indexado. O reparo automático noturno de divergências está em nosso roadmap.

= O WooCommerce é obrigatório? =

Sim. O NitroSearch indexa e busca produtos do WooCommerce, então o WooCommerce precisa estar instalado e ativo. É compatível com o armazenamento de pedidos de alta performance do WooCommerce (HPOS).

= Ele exibe um selo “Com tecnologia NitroSearch” na minha loja? =

Só se você quiser. O crédito vem **desativado por padrão**, e nada é adicionado à sua loja a menos que você o ative nas configurações de **Aparência** do plugin. Se ativar, ele aparece como um pequeno crédito com link na caixa de busca e uma linha no rodapé do site, ambos apontando para nitrosearch.io. Obrigado se você ativar — mas o plugin funciona exatamente igual de qualquer forma.

= O que acontece se eu desativar o plugin? =

Sua loja volta a usar a busca normal do WooCommerce. Seus dados no NitroSearch são gerenciados pela sua conta; você pode desconectar a qualquer momento pela tela do plugin.

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
