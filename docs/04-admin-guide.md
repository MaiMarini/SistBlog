# Guia do Painel Admin — Kallme

Como usar o painel para criar artigos e páginas estáticas, inserir blocos editoriais, configurar tracking global e gerenciar o site.

---

## 🔑 Acesso

URL: **https://kallme.online/admin/**

Login padrão:
- Usuário: `admin`
- Senha: `admin123`

**Trocar a senha** após o primeiro acesso (instruções no final).

---

## 🧭 Estrutura do painel

A sidebar do admin tem 4 itens:

| Ícone | Item | URL |
|-------|------|-----|
| 📊 | Dashboard | `/admin/` |
| 📝 | Páginas | `/admin/pages.php` |
| ➕ | Nova Página | `/admin/page-form.php` |
| ⚙️ | Configurações | `/admin/settings.php` |

---

## 📊 Dashboard (`/admin/`)

Cards no topo:
- **Total de Páginas**
- **Publicadas**
- **Rascunhos**

Tabela "Páginas Recentes" (últimas 5) com as mesmas colunas do listing — Título, URL bilíngue, Tipo (com categoria), Status, Data, Ações.

Atalho: "+ Nova Página" no canto superior direito.

---

## 📝 Lista de Páginas (`/admin/pages.php`)

Tabela com todas as páginas (publicadas + rascunhos).

**Colunas:**
- **Título**
- **URL** — caminho bilíngue completo, ex.: `/br/croche/como-comecar` para artigos, `/br/sobre` para estáticas
- **Tipo** — badge "📝 Artigo · Crochê" ou "📄 Estática"
- **Status** — ✅ Publicada / 📝 Rascunho
- **Idioma** — BR ou EN
- **Data** — criação
- **Ações** — ✏️ Editar · 👁️ Ver pública (só publicadas) · 🗑️ Excluir

---

## ➕ Criar / Editar Página

Acesse via "+ Nova Página" no topo ou clique em ✏️ na lista.

### Tipo de página (decisão #1)

O select **Tipo** controla todo o resto do formulário:

| Tipo | Quando usar | Campos obrigatórios |
|------|-------------|---------------------|
| 📝 **Artigo** (default) | Posts editoriais — receitas, tutoriais, resenhas | Título, slug, **categoria**, conteúdo |
| 📄 **Estática** | Páginas institucionais — sobre, contato, termos | Título, slug, conteúdo |

**Coerência automatica**:
- **Artigo precisa de categoria.** Se você tentar salvar sem categoria, o backend recusa com mensagem clara.
- **Estática nunca tem categoria.** Se mudar de artigo→estática, o campo categoria fica oculto e o valor é zerado no save (silenciosamente).
- O **prefixo do slug** muda em tempo real conforme a escolha:
  - Estática + idioma BR → `/br/`
  - Artigo + idioma BR + categoria Crochê → `/br/croche/`

### Conteúdo principal

| Campo | Obrigatório | Notas |
|-------|-------------|-------|
| **Título** | Sim | Aparece como `<title>` e h1 |
| **Slug (URL)** | Sim | Auto-gerado a partir do título (acentos removidos). Você pode sobrescrever — uma vez editado manualmente, deixa de auto-gerar |
| **Resumo (excerpt)** | Não | Texto curto que aparece em cards/listagens |
| **Conteúdo** | Recomendado | Editor TinyMCE 6 + toolbar de blocos editoriais (ver abaixo) |

### Sidebar do form

#### 📅 Publicação
- **Status** — Rascunho / Publicada
- **Tipo** — 📝 Artigo / 📄 Estática (default: Artigo)
- **Idioma** — Português (br) / English (en)
- **Categoria** *** — visível só para artigos. Lista vinda da tabela `categories`
- **Data de Publicação** — exibida nos templates (pode ser fictícia)
- **Autor** — nome (fictício ou real)
- **Tempo de leitura (min)** — preenchido manualmente; se vazio, o backend calcula automaticamente para artigos (250 palavras/min)
- **Botão "Criar/Atualizar"** — salva no banco

#### 🔍 SEO
- **Meta Title**
- **Meta Description**

#### 📊 Tracking (só desta página)
Tags específicas (Google Ads, Pixel, etc.) coladas no formato completo (`<script>...</script>`). Injetadas no `<head>` desta página.

> Trackings **globais** (que se aplicam em todas as páginas) ficam em `/admin/settings.php` → aba **Tracking Global**.

### 🖼 Imagem destacada (Featured Image)

Card separado abaixo do conteúdo. Aceita JPG/PNG/GIF/WebP, máximo 5MB. Aparece como capa do artigo e em cards do blog.

---

## ✍️ Editor TinyMCE

O conteúdo é editado em **TinyMCE 6** (carregado via Tiny Cloud com chave da conta gratuita registrada).

### Toolbar nativa
- Undo / Redo
- Blocks (parágrafo / H1 / H2 / H3)
- Bold / Italic / Underline
- Listas (ordenada / não ordenada)
- Link / Image / Blockquote
- View source (`code`)
- Fullscreen

### Preview interno
O editor já estiliza os blocos editoriais **dentro do editor** (via `content_style`), então você vê quase o mesmo visual que sairá no site público.

---

## 🧱 Blocos editoriais modulares

Acima do editor há uma **toolbar rosa-pêssego** com 10 botões. Cada um insere um bloco pré-formatado no cursor do TinyMCE:

| Botão | Bloco | Classe HTML | Quando usar |
|-------|-------|-------------|-------------|
| 🖼 Imagem | `<figure class="editorial-image">` | Imagem com legenda em itálico |
| ❝ Citação | `<blockquote class="editorial-quote">` | Citação destacada com fonte (cite) |
| 💡 Dica | `<aside class="editorial-tip">` | Caixa rosa pétala com dica útil |
| ⚠ Atenção | `<aside class="editorial-warning">` | Caixa laranja com aviso/cuidado |
| ≡ Lista | `<ul class="editorial-list">` | Lista com bullets espaçados |
| 🖼🖼 Galeria | `<div class="editorial-gallery">` | Grid responsivo de 3 imagens com legenda |
| 🛒 Produto | `<div class="editorial-product">` | Card de produto afiliado (imagem + título + preço + CTA `rel="sponsored noopener"`) |
| ★ Destaque | `<p class="editorial-highlight">` | Frase grande em Playfair vermelha, centralizada |
| — Divisor | `<hr class="editorial-divider">` | Linha bege com ❀ no centro |
| ⊞ Tabela | `<table class="editorial-table">` | Tabela 2×3 com cabeçalho rosa |

### Fluxo recomendado para um artigo

1. Escreva os parágrafos no editor normalmente
2. Onde quiser variar o ritmo, clique no botão do bloco
3. O HTML é inserido + um `<p></p>` vazio depois (cursor sai pronto pra continuar)
4. Edite os placeholders (textos, src de imagens, links, preços)
5. Você pode editar o HTML cru a qualquer momento pelo botão **`<>`** (code) da toolbar do TinyMCE

### Imagens nos blocos

Os templates de imagem apontam por padrão para:
- `/assets/img/articles/exemplo.jpg` (imagem isolada e galeria)
- `/assets/img/products/exemplo.jpg` (bloco de produto)

Você precisa subir as imagens reais via cPanel → Gerenciador de Arquivos (para `assets/img/articles/` ou `assets/img/products/`) e atualizar o `src` na hora de editar. Não há uploader integrado no bloco ainda — o uploader do TinyMCE (botão **image** na toolbar) usa o painel padrão dele, que aceita URL ou upload base64 inline.

### Tempo de leitura ao vivo

Embaixo do editor um pequeno texto cinza mostra **"Tempo estimado: X min (Y palavras)"** atualizado a cada 500ms enquanto você digita. O valor salvo no banco é calculado no backend no momento do save (200-250 palavras/min) — o display é só visual.

---

## 🖼 Upload de imagens

### Imagem destacada (campo dedicado)
- Aceita: **JPG, PNG, GIF, WebP**
- Tamanho máximo: **5 MB**
- Recomendado: **< 200 KB** (use [TinyPNG](https://tinypng.com))
- Imagens vão para `uploads/` com nome aleatório

### Imagens dentro do conteúdo
- Via toolbar do TinyMCE (botão **image**) → cole URL ou faça upload (Tiny salva inline base64)
- Para imagens nos blocos editoriais: suba via cPanel em `assets/img/articles/` ou `assets/img/products/` e referencie pelo caminho

---

## 👁 Preview

`admin/preview.php` ainda é placeholder. O preview "ao vivo" hoje é o próprio editor do TinyMCE — o `content_style` já estiliza os blocos editoriais visualmente próximos do que sairá no front.

Um preview de página final (com header/drawer/footer) só virá depois que `templates/article.php` e `templates/static.php` existirem.

---

## 🗑 Excluir página

Botão 🗑️ na lista → pede confirmação. Remove:
- Registro do banco
- **Invalida cache** de categorias automaticamente (a próxima visita ao site regenera)

> Imagens em `uploads/` não são deletadas junto — ficam órfãs no servidor. Limpar manualmente via cPanel quando precisar.

---

## ⚙️ Configurações globais (`/admin/settings.php`)

Tela em **4 abas** (deep-link via hash: `#general`, `#tracking`, `#social`, `#seo`).

### Aba "Geral"
- Nome do site
- Tagline (BR/EN)
- E-mail de contato
- Idioma padrão (br/en)

### Aba "📊 Tracking Global"
Códigos que entram no `<head>` de **todas as páginas** (páginas continuam com seu `tracking_code` próprio):
- Google Analytics 4 (gtag)
- Google Ads (gtag)
- Pinterest Tag
- Facebook Pixel
- TikTok Pixel
- Outros (Clarity, Hotjar, etc.)

Cada campo é uma textarea monoespaçada — cole o código completo (incluindo `<script>...</script>`).

### Aba "🔗 Redes Sociais"
URLs dos perfis (Pinterest, Instagram, Facebook, YouTube, X/Twitter). Aparecem no footer e no header (Pinterest).

### Aba "🔍 SEO"
Meta descriptions padrão por idioma (usadas quando uma página não tem meta description própria).

> Todas as settings são salvas em batch pelo botão "💾 Salvar todas as configurações".

---

## 🎯 Fluxo recomendado para criar um artigo

1. **Nova Página** → confirme que o tipo está em "📝 Artigo" (default)
2. Escolha **categoria** (obrigatória pra artigos) — `/br/<categoria>/` aparece no prefixo do slug ao vivo
3. Preencha **título** → slug é gerado sozinho
4. Suba a **imagem destacada**
5. Escreva o corpo no TinyMCE; vá inserindo **blocos editoriais** onde fizer sentido (citação, dica, galeria, produto afiliado)
6. Preencha **resumo (excerpt)**, **SEO** e **autor**
7. Crie em **Rascunho** primeiro, salve, confira no editor que tudo está coerente
8. Mude para **Publicada** e salve de novo
9. Acesse `kallme.online/br/<categoria>/<slug>` para conferir ao vivo (Ctrl+Shift+R)
10. Compartilhe o link

---

## ⚠️ Boas práticas

- **Slug**: prefira slugs curtos e legíveis (`tomate-cereja-vaso` > `como-plantar-tomate-cereja-em-vaso-no-apartamento`)
- **Categoria**: cada artigo numa categoria só — o slug fica `/<cat>/<artigo>`, não há multi-categoria
- **Imagens**: comprima antes do upload, alvo < 200 KB; suba pra `/assets/img/articles/` para reuso entre artigos
- **CTA em bloco Produto**: verbos de ação ("Ver na loja", "Comprar agora" — não "Saiba mais"). Lembrar de manter `rel="sponsored noopener"` (já vem no template)
- **SEO**: meta_title 50-60 chars, meta_description 140-160 chars
- **Tracking**: teste com Pixel Helper / Tag Assistant após publicar

---

## 🔓 Trocar a senha do admin

Não há tela ainda. Para trocar manualmente, **abra phpMyAdmin no cPanel**:

1. Gere o hash bcrypt da nova senha em https://bcrypt-generator.com (Round count 10)
2. phpMyAdmin → tabela `users` → linha `admin` → coluna `password` → cole o hash
3. Salve

Ou via script PHP temporário (suba como `change-pwd.php`, rode, **apague**):

```php
<?php
require __DIR__ . '/config/database.php';
$hash = password_hash('NOVA_SENHA', PASSWORD_BCRYPT);
getDB()->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$hash]);
echo 'OK';
```

---

## 🧊 Cache de categorias (info técnica)

O drawer do site público usa um cache de **1 hora** com a contagem de artigos publicados por categoria. Esse cache **é invalidado automaticamente** quando você:

- Salva uma página (`page-form.php`)
- Exclui uma página (`page-delete.php`)

Não precisa fazer nada manualmente — apenas saiba que a 1ª visita ao site após uma alteração pode ser ligeiramente mais lenta (regenera o cache). As próximas são instantâneas.

Para forçar invalidação manual: delete `/tmp/kallme_categories_cache.json` no servidor via Gerenciador de Arquivos do cPanel.

---

## 🔑 Chave do TinyMCE

O editor usa a **Tiny Cloud** (versão gratuita até 1000 carregamentos/dia, sem cartão). A chave atual está hardcoded em [admin/page-form.php](../admin/page-form.php) (linhas do `<script src>` e do `language_url`).

Se precisar trocar (limite atingido, nova conta, etc.):
1. Pegue a chave em https://www.tiny.cloud/my-account/
2. Substituir os dois lugares onde aparece (script src + language_url)
3. Deploy (`bash deploy.sh`)

> Para endurecer segurança: no painel da Tiny Cloud, registre `kallme.online` em **Approved domains** — o editor só carrega se o `Referer` bater.
