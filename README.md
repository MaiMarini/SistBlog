# Kallme — Blog editorial bilíngue

Plataforma editorial em PHP/MySQL para um **blog público bilíngue** (BR/EN) sobre crochê, jardinagem, DIY e livros, hospedado em **kallme.online** (HostGator).

> O projeto começou como um sistema de presell pages para afiliados; em maio/2026 todo o legado presell foi removido e o foco passou a ser 100% editorial. O sistema de blocos modulares de artigo será adicionado na próxima fase.

---

## 🚀 O que o projeto faz hoje

### Lado público (blog)
1. **Roteamento bilíngue** — `/br/...` / `/en/...` (EN preparado, conteúdo na fase futura)
2. **Homepage com hero banner** (imagem horizontal + "Kallme" sobreposto) + grid de últimos artigos
3. **7 páginas estáticas BR** (home, sobre, contato, política de privacidade, termos, divulgação de afiliados, 404)
4. **Header horizontal sticky** rosa pétala (estilo Charlotte) com hambúrguer + nav + Pinterest + busca
5. **Drawer lateral** (slide-in da esquerda) com navegação + 4 categorias (croche, jardinagem, diy, minha-estante) com lógica "disponível vs em breve"
6. **Footer 4 colunas**: Marca + Site + Legal + Redes
7. **Ícones**: Phosphor Icons (peso `light` no conteúdo, `regular` no header)
8. **Favicons completos** + PWA manifest

### Lado admin (`/admin/`)
1. **Painel restrito** com login + sessão + CSRF
2. **Dashboard** com contagem de páginas
3. **CRUD de páginas** com validação de coerência (artigos exigem categoria, estáticas não têm)
4. **Configurações globais** em 4 abas: Geral, Tracking Global, Redes Sociais, SEO
5. **Editor TinyMCE 6** (Tiny Cloud) com toolbar de 10 blocos editoriais modulares (citação, dica, atenção, galeria, produto afiliado, etc.)
6. **Sistema de categorias** bilíngue com 1h de cache

### Infraestrutura
1. **Deploy via HTTP POST** (FTP da HostGator tem cache de filesystem; HTTP escreve via PHP)
2. **Migrações idempotentes** via `migrate.php`
3. **Cache de categorias** (1h em arquivo) com invalidação automática ao salvar/excluir página

---

## 📂 Estrutura completa do projeto

```
kallme.online/
├── .htaccess                          # Roteamento bilíngue (mod_rewrite)
├── index.php                          # Fallback 301 → /br/
├── page-router.php                    # Router principal: lang/slug/categoria
├── install.php                        # Instalador original (sobrescrito por endpoint de DEPLOY no servidor)
├── migrate.php                        # Migrações idempotentes do banco
├── deploy.sh                          # Script de deploy local → servidor (gitignored)
├── site.webmanifest                   # PWA manifest
├── favicon.ico, favicon.svg, ...      # Favicons na raiz (subir manualmente no servidor)
│
├── config/
│   ├── database.php                   # PDO + DEPLOY_KEY/MIGRATE_KEY (gitignored)
│   └── database.example.php           # Template (vai pro git)
│
├── includes/
│   ├── auth.php                       # Login, sessão, CSRF
│   ├── functions.php                  # CRUD de páginas + helpers básicos
│   ├── settings.php                   # Settings globais (cache em memória)
│   ├── categories.php                 # Categorias + cache 1h + render de ícones
│   ├── site-helpers.php               # url(), getCategory(), formatDate()...
│   ├── site-header.php                # Header HTML (head + body open + sticky)
│   ├── site-drawer.php                # Drawer lateral
│   └── site-footer.php                # Footer HTML
│
├── admin/
│   ├── index.php                      # Dashboard
│   ├── login.php / logout.php
│   ├── pages.php                      # Lista de páginas
│   ├── page-form.php                  # CRUD editorial (TinyMCE 6 + blocos modulares)
│   ├── page-delete.php
│   ├── preview.php                    # Placeholder (preview reescrito junto com blocos modulares)
│   ├── settings.php                   # 4 abas: Geral/Tracking/Social/SEO
│   ├── includes/sidebar.php           # Sidebar do admin
│   └── assets/
│       ├── admin.css                  # Tema dark do painel
│       ├── admin.js
│       └── page-form.js               # Slug auto, toggle article/static, blocos editoriais
│
├── pages/
│   ├── br/                            # Páginas estáticas BR
│   │   ├── home.php                   # Hero banner + grid de artigos
│   │   ├── sobre.php                  # Conteúdo editorial real
│   │   ├── contato.php
│   │   ├── politica-de-privacidade.php
│   │   ├── termos.php
│   │   ├── divulgacao-afiliados.php
│   │   └── 404.php
│   └── en/                            # Preparado para Fase EN (vazio)
│
├── assets/
│   ├── css/
│   │   └── site.css                   # Design system + blog público
│   ├── js/
│   │   └── header.js                  # Drawer + busca placeholder
│   └── img/
│       ├── logo-icon.png              # Logo do drawer (subir no servidor)
│       ├── logo-full.png              # Logo do footer (subir no servidor)
│       └── hero-banner.jpg            # Imagem horizontal da homepage
│
├── uploads/                           # Imagens enviadas pelo admin (gitignored)
│
└── docs/
    ├── 01-design-system.md            # Paleta Sereno Romântico, tipografia, espaçamentos
    ├── 02-architecture.md             # Roteamento bilíngue, schema, fluxos
    ├── 03-deployment.md               # Deploy via HTTP POST, troubleshooting
    ├── 04-admin-guide.md              # Como usar o painel
    ├── 06-blog-frontend.md            # Páginas estáticas, header/drawer/footer
    └── 07-categorias.md               # Sistema de categorias do blog
```

> **Nota**: a pasta `templates/` foi removida junto com o sistema presell. Será recriada quando o sistema editorial de blocos modulares for implementado (com `templates/article.php` e `templates/static.php`).

---

## 📖 Documentação técnica

| Arquivo | Conteúdo |
|---------|----------|
| [docs/01-design-system.md](docs/01-design-system.md) | Paleta "Sereno Romântico", tipografia (Playfair + Inter), espaçamentos, botões, cards, ícones |
| [docs/02-architecture.md](docs/02-architecture.md) | Roteamento bilíngue, schema do banco, fluxos de requisição |
| [docs/03-deployment.md](docs/03-deployment.md) | Como funciona o deploy via HTTP POST, migrações, troubleshooting |
| [docs/04-admin-guide.md](docs/04-admin-guide.md) | Como usar o painel admin |
| [docs/06-blog-frontend.md](docs/06-blog-frontend.md) | Header/drawer/footer compartilhados, páginas estáticas, hero banner |
| [docs/07-categorias.md](docs/07-categorias.md) | Tabela `categories`, cache, drawer dinâmico, `renderCategoryIcon` |

---

## ⚡ Quickstart (mudança rápida → produção)

1. Edite arquivos localmente em `d:/Trabalhos/SistVendas-docs/`
2. No terminal:
   ```bash
   bash deploy.sh
   ```
3. Se mudou schema do banco, rode no navegador:
   ```
   https://kallme.online/migrate.php?key=<MIGRATE_KEY>
   ```
4. **Ctrl+Shift+R** no navegador para limpar cache

### Acessos rápidos
| Item | URL |
|------|-----|
| Site público | https://kallme.online/br/ |
| Painel admin | https://kallme.online/admin/ |
| Configurações globais | https://kallme.online/admin/settings.php |
| Migrações | https://kallme.online/migrate.php?key=<MIGRATE_KEY> |

### Credenciais admin
- Usuário: `admin`
- Senha: `admin123` (**TROCAR em produção** — instruções em [04-admin-guide.md](docs/04-admin-guide.md))

---

## 🛠 Tecnologias

- **Backend**: PHP 8+ (nativo, sem framework)
- **Banco**: MySQL 8 (HostGator shared hosting)
- **Editor admin**: TinyMCE 6 via Tiny Cloud (chave API gratuita) + toolbar custom de blocos editoriais
- **Ícones**: Phosphor Icons (peso light + regular, via CDN)
- **Fontes**: Playfair Display (display) + Inter (body) via Google Fonts
- **Hospedagem**: HostGator (cPanel + FTP + MySQL)
- **Roteamento**: Apache mod_rewrite (`.htaccess`)
- **Deploy**: HTTP POST via `install.php` (FTP burlando cache da HostGator)

---

## 🗄 Schema do banco (visão rápida)

4 tabelas:
- **`users`** — login do admin
- **`pages`** — páginas estáticas e artigos editoriais (19 colunas, schema editorial enxuto)
- **`categories`** — bilíngue (name_br/name_en) com is_active + display_order
- **`settings`** — chaves globais (tracking, social, seo, general)

Detalhe completo em [docs/02-architecture.md](docs/02-architecture.md).

### `page_type` (ENUM)
- `'article'` — artigo editorial (a renderização de bloco será implementada na próxima fase)
- `'static'` — página estática institucional

---

## ⚠️ Notas importantes

- O arquivo `install.php` no servidor é o **endpoint de DEPLOY** (não o instalador original). Não substituir.
- Pasta `uploads/` é populada pelo painel; faça backup periódico.
- Schema do banco evolui via `migrate.php` — **idempotente**, pode rodar várias vezes.
- O footer usa `assets/img/logo-full.png` (subir no servidor) — fallback de texto se ausente.
- A homepage usa `assets/img/hero-banner.jpg` — fallback rosa pétala se ausente.

---

## 🧭 Roadmap

- **Fase atual**: editor com TinyMCE + blocos editoriais modulares (10 tipos) funcionando. Listing e dashboard com URL bilíngue. Validação de coerência page_type ⇄ category.
- **Próxima fase**: templates públicos (`templates/article.php` + `templates/static.php`) que renderizam o HTML salvo + páginas de categoria (`pages/<lang>/_category.php`) com listagem.
- **Depois**: busca pública, conteúdo EN, sistema de comentários reais.
