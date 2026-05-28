# Kallme — Blog editorial bilíngue + Sistema de Presell

Plataforma editorial em PHP/MySQL que combina **blog público bilíngue** (BR/EN, foco editorial em crochê, jardinagem, DIY e livros) com um **sistema de presell pages dinâmicas** para afiliados. Hospedado em **kallme.online** (HostGator).

---

## 🚀 O que o projeto faz hoje

### Lado público (blog)
1. **Roteamento bilíngue** — `/br/...` / `/en/...` (EN preparado, conteúdo na Fase futura)
2. **Homepage com hero banner** (imagem horizontal + "Kallme" sobreposto) + grid de últimos artigos
3. **7 páginas estáticas BR** (home, sobre, contato, política de privacidade, termos, divulgação de afiliados, 404)
4. **Header horizontal sticky** rosa pétala (estilo Charlotte) com hambúrguer + nav + Pinterest + busca
5. **Drawer lateral** (slide-in da esquerda) com navegação + 4 categorias (croche, jardinagem, diy, minha-estante) com lógica "disponível vs em breve"
6. **Footer 4 colunas**: Marca + Site + Legal + Redes
7. **Ícones**: Phosphor Icons (peso `light` no conteúdo, `regular` no header)
8. **Favicons completos** + PWA manifest

### Lado afiliado (presell)
1. **Painel admin restrito** (`/admin/`) — criar/editar/publicar/excluir páginas presell
2. **4 templates de presell** — Estruturado (4 seções), Advertorial, Blog Pessoal, Landing Page
3. **Editor Quill** (rich text) com 19 fontes, tamanhos por px, cor HEX
4. **Preview** antes de publicar (abre em nova aba sem salvar)
5. **Botão flutuante CTA** que segue a rolagem
6. **Tag de rastreamento** por página (Google Ads, Pixel, Analytics)
7. **Trackings globais** configuráveis em `/admin/settings.php`

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
├── page.php                           # LEGADO (não usado pelo router)
├── page-router.php                    # Router principal: lang/slug/categoria
├── install.php                        # Endpoint de DEPLOY HTTP POST
├── migrate.php                        # Migrações idempotentes do banco
├── deploy.sh                          # Script de deploy local → servidor
├── site.webmanifest                   # PWA manifest
├── favicon.ico, favicon.svg, ...      # Favicons na raiz
│
├── config/
│   └── database.php                   # PDO singleton, BASE_URL
│
├── includes/
│   ├── auth.php                       # Login, sessão, CSRF
│   ├── functions.php                  # CRUD de páginas, slugify, e()
│   ├── settings.php                   # Settings globais (cache em memória)
│   ├── categories.php                 # Categorias + cache de 1h + render de ícones
│   ├── site-helpers.php               # url(), getCategory(), formatDate()...
│   ├── site-header.php                # Header HTML (head + body open + sticky)
│   ├── site-drawer.php                # Drawer lateral
│   └── site-footer.php                # Footer HTML
│
├── admin/
│   ├── index.php                      # Dashboard
│   ├── login.php / logout.php
│   ├── pages.php                      # Lista de páginas
│   ├── page-form.php                  # CRUD (Quill, upload, preview)
│   ├── page-delete.php
│   ├── preview.php                    # Renderiza preview sem salvar
│   ├── settings.php                   # 4 abas: Geral/Tracking/Social/SEO
│   ├── includes/sidebar.php           # Sidebar do admin (reutilizável)
│   └── assets/
│       ├── admin.css                  # Tema dark do painel
│       └── admin.js                   # Slug auto, Quill helpers
│
├── templates/                         # Templates de PRESELL (não-blog)
│   ├── structured.php                 # 4 seções customizáveis (padrão)
│   ├── advertorial.php                # Estilo notícia
│   ├── blog-personal.php              # Estilo depoimento
│   └── landing.php                    # Estilo comercial
│
├── pages/
│   ├── br/                            # Páginas estáticas BR (incluem site-header/footer)
│   │   ├── home.php                   # Hero banner + grid de artigos
│   │   ├── sobre.php                  # Conteúdo editorial real
│   │   ├── contato.php
│   │   ├── politica-de-privacidade.php
│   │   ├── termos.php
│   │   ├── divulgacao-afiliados.php
│   │   └── 404.php
│   └── en/                            # Vazio (preparado para Fase EN)
│
├── assets/
│   ├── css/
│   │   ├── site.css                   # Design system + blog público
│   │   └── presell.css                # CSS legado das presells (não mexer)
│   ├── js/
│   │   ├── header.js                  # Drawer + busca placeholder
│   │   └── presell.js                 # Floating CTA das presells
│   └── img/
│       ├── logo-icon.png              # Logo da sidebar (drawer)
│       ├── logo-full.png              # Logo do footer
│       └── hero-banner.jpg            # Imagem horizontal da homepage
│
├── uploads/                           # Imagens enviadas pelo admin
│
└── docs/
    ├── 01-design-system.md            # Paleta Sereno Romântico, tipografia, espaçamentos
    ├── 02-architecture.md             # Roteamento bilíngue, schema, fluxos
    ├── 03-deployment.md               # Deploy via HTTP POST, troubleshooting
    ├── 04-admin-guide.md              # Como usar o painel
    ├── 05-templates.md                # Templates de presell
    ├── 06-blog-frontend.md            # Páginas estáticas, header/drawer/footer
    └── 07-categorias.md               # Sistema de categorias do blog
```

---

## 📖 Documentação técnica

| Arquivo | Conteúdo |
|---------|----------|
| [docs/01-design-system.md](docs/01-design-system.md) | Paleta "Sereno Romântico", tipografia (Playfair + Inter), espaçamentos, botões, cards |
| [docs/02-architecture.md](docs/02-architecture.md) | Roteamento bilíngue, schema do banco, fluxos de requisição |
| [docs/03-deployment.md](docs/03-deployment.md) | Como funciona o deploy via HTTP POST, migrações, troubleshooting |
| [docs/04-admin-guide.md](docs/04-admin-guide.md) | Como usar o painel: páginas, settings, tracking, preview |
| [docs/05-templates.md](docs/05-templates.md) | Os 4 templates de presell e quais campos cada um usa |
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
- **Editor admin**: Quill 2.x (rich text gratuito)
- **Ícones**: Phosphor Icons (peso light + regular, via CDN)
- **Fontes**: Playfair Display (display) + Inter (body) via Google Fonts
- **Hospedagem**: HostGator (cPanel + FTP + MySQL)
- **Roteamento**: Apache mod_rewrite (`.htaccess`)
- **Deploy**: HTTP POST via `install.php` (FTP burlando cache da HostGator)

---

## 🗄 Schema do banco (visão rápida)

4 tabelas:
- **`users`** — login do admin
- **`pages`** — presells + (futuramente) artigos editoriais
- **`categories`** — bilíngue (name_br/name_en) com is_active + display_order
- **`settings`** — chaves globais (tracking, social, seo, general)

Detalhe completo em [docs/02-architecture.md](docs/02-architecture.md).

---

## ⚠️ Notas importantes

- O arquivo `install.php` no servidor é o **endpoint de DEPLOY** (não o instalador original). Não substituir.
- `page.php` é **legado** — o router principal é `page-router.php`.
- Pasta `uploads/` é populada pelo painel; faça backup periódico.
- Schema do banco evolui via `migrate.php` — **idempotente**, pode rodar várias vezes.
- O footer do projeto antigo (logo Maíra Marini Ateliê) precisa de `assets/img/logo-full.png` (já configurado, basta subir o arquivo).
- A homepage usa `assets/img/hero-banner.jpg` como fundo do hero — fallback rosa pétala se o arquivo não existir.
