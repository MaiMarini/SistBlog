# Guia do Painel Admin — Kallme

Como usar o painel para criar páginas estáticas e artigos, configurar tracking global e gerenciar o site.

> O sistema de páginas está em **modo transitório** após a remoção do legado página. O formulário atual permite criar/editar páginas com campos básicos (title, slug, content, excerpt, featured_image, SEO). O sistema editorial completo com blocos modulares chega em breve.

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

Tela inicial mostra:
- **Total de Páginas** (todas)
- **Publicadas**
- **Rascunhos**
- **Páginas Recentes** (últimas 5)

Atalho: "+ Nova Página" no topo direito.

---

## 📝 Lista de Páginas (`/admin/pages.php`)

Tabela com todas as páginas (publicadas + rascunhos).

**Colunas:**
- Título
- Slug (URL)
- Template (structured, advertorial, blog-personal, landing)
- Status (Publicada / Rascunho)
- Data de criação
- Ações: ✏️ Editar · 👁️ Ver (só publicadas) · 🗑️ Excluir

---

## ➕ Criar / Editar Página

Acesse via "+ Nova Página" ou clique em ✏️ na lista.

### Conteúdo principal (sempre visível)

| Campo | Obrigatório | Notas |
|-------|-------------|-------|
| **Título** | Sim | Aparece como `<title>` e h1 do template |
| **Subtítulo** | Não | Logo abaixo do título |
| **Slug (URL)** | Sim | Auto-gerado a partir do título. URL final: `kallme.online/br/<slug>` ou `kallme.online/br/<categoria>/<slug>` |

### Sidebar do form

#### 📅 Publicação
- **Status**: Rascunho / Publicada
- **Data de Publicação**: exibida nos templates (pode ser fictícia)
- **Botão "Criar/Atualizar Página"** — salva no banco
- **Botão "👁️ Visualizar"** — preview em nova aba sem salvar

#### 🎨 Template
- **Estruturado (4 Seções)** — padrão, mais flexível
- **Advertorial / Notícia** — formato editorial
- **Blog Pessoal** — depoimento
- **Landing Page** — comercial puro

#### 🔗 Link de Afiliado
- URL do afiliado
- Texto do botão CTA (ex: "Compre Agora")
- Cor do botão CTA (color picker + HEX)

#### 👤 Autor
- Nome (fictício)
- Avatar (upload)

#### 🔍 SEO
- Meta Title
- Meta Description

#### 📊 Código de Rastreamento
Tags específicas desta página (Google Ads, Pixel, Pinterest tag, etc.) — coladas no formato completo (`<script>...</script>` ou `<meta>`). Injetadas no `<head>` da página.

> Trackings **globais** (que se aplicam em todas as páginas) ficam em `/admin/settings.php` aba **Tracking Global**.

---

### Campos do Template "Estruturado"

#### 1. Cabeçalho
- Tipo de fundo: Sólido / Linear gradient / Radial gradient
- Direção (se gradient): `to bottom`, `to right`, etc.
- Cor 1, 2, 3 (gradient stops ou cor sólida)
- Texto Acima da Imagem (Quill rich text)
- Imagem do Cabeçalho
- Texto Abaixo da Imagem (Quill rich text)

#### 2. Conteúdo 1 (texto + imagem)
- Texto à esquerda (Quill)
- Imagem à direita
- Imagem de fundo (opcional, atrás de tudo)
- Cor de fundo (fallback)

#### 3. Conteúdo 2 (galeria + CTA)
- Até 5 imagens em grid responsivo
- Texto (Quill)
- Texto do CTA / Cor de fundo do CTA / Cor do texto do CTA
- Imagem de fundo (opcional)
- Cor de fundo (fallback)

#### 4. Rodapé
- Cor de fundo
- Texto de Alertas (Quill) — disclaimers
- 3 Botões: cada um com texto + URL
- Cor do Texto dos Botões / Tamanho (10-24px)

---

## ✍️ Usando o editor Quill

Toolbar de cada campo de texto rich:

- **Cor do Texto (HEX)** — botão `A#` — abre prompt para hex
- **Cor de Fundo do Texto (HEX)** — botão `🖌#` — idem
- **Fonte** — 19 opções (Arial, Georgia, Roboto, Open Sans, Playfair Display, ...)
- **Tamanho** — por px, 8px → 96px
- **Cabeçalho** — H1, H2, H3
- **Negrito / Itálico / Sublinhado / Tachado**
- **Cor / Background** (paleta padrão)
- **Alinhamento** — esquerda, centro, direita, justificado
- **Listas** — ordenada / não ordenada
- **Citação / Link**
- **Limpar formatação**

### Colar texto sem trazer formatação ruim

O Quill remove automaticamente `background-color` de conteúdo colado (de Word, Google Docs, sites) para evitar blocos brancos atrás do texto.

---

## 🖼 Upload de imagens

- Aceita: **JPG, PNG, GIF, WebP**
- Tamanho máximo: **5MB**
- Recomendado: **< 200KB** (use [TinyPNG](https://tinypng.com))

Imagens vão para `uploads/` com nome aleatório. Caminho salvo é relativo.

---

## 💬 Comentários fictícios

Em cada página, card "Comentários Fictícios":
1. Clique em "+ Adicionar"
2. Preencha Nome, Data (`dd/mm/aaaa`), Texto
3. Repita

Salvos como JSON, renderizados nos templates Advertorial/Blog/Landing.

---

## 👁️ Preview

Botão **"👁️ Visualizar"** na sidebar do form.

- Abre em **nova aba**
- Renderiza o template com **dados atuais do form** (incluindo uploads novos)
- **Não salva no banco**
- Funciona mesmo se ainda não publicou
- Usa `uploads/preview/` (limpa essa pasta no início)

Use sempre antes de publicar.

---

## 🗑 Excluir página

Botão 🗑️ na lista → pede confirmação. Remove:
- Registro do banco
- Imagens associadas (main_image, author_avatar)
- **Invalida cache** de categorias automaticamente

---

## ⚙️ Configurações globais (`/admin/settings.php`)

Tela em **4 abas** (deep-link via hash: `#general`, `#tracking`, `#social`, `#seo`).

### Aba "Geral"
- Nome do site
- Tagline (BR/EN)
- E-mail de contato
- Idioma padrão (br/en)

### Aba "📊 Tracking Global"
Códigos que entram no `<head>` de **todas as páginas estáticas e artigos** (páginas continuam com seu `tracking_code` próprio):
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

## 🎯 Fluxo recomendado para criar uma página

1. Crie em **Rascunho** primeiro
2. Preencha tudo, faça upload das imagens
3. Use **Visualizar** para conferir
4. Ajuste o que precisar
5. Mude status para **Publicada**
6. Salve
7. Acesse `kallme.online/br/<seu-slug>` para conferir ao vivo
8. Compartilhe o link nas campanhas

---

## ⚠️ Boas práticas

- **Slug**: prefira slugs curtos e legíveis (`brain-song-review` > `produto-incrivel-2026-melhor`)
- **Imagens**: comprima antes do upload, alvo < 200KB
- **CTA**: verbos de ação ("Compre Agora", "Garanta o Seu" — não "Saiba Mais")
- **Cores**: respeite o design system da campanha (não misture paletas)
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
