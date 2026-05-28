# Design System — Kallme

Identidade visual completa do projeto. Use este documento como referência única para qualquer decisão de design.

---

## 🎨 Paleta de Cores

### Cores Base

| Cor | HEX | Nome | Uso |
|-----|-----|------|-----|
| ![#FAF6F0](https://placehold.co/16x16/FAF6F0/FAF6F0.png) | `#FAF6F0` | Off-white Pêssego | Background principal |
| ![#F5E1DC](https://placehold.co/16x16/F5E1DC/F5E1DC.png) | `#F5E1DC` | Rosa Pétala Claro | Background de cards |
| ![#3D4F6E](https://placehold.co/16x16/3D4F6E/3D4F6E.png) | `#3D4F6E` | Navy Médio | Cor primária (cabeçalhos) |
| ![#23314A](https://placehold.co/16x16/23314A/23314A.png) | `#23314A` | Navy Escuro | Texto principal |
| ![#DB8084](https://placehold.co/16x16/DB8084/DB8084.png) | `#DB8084` | Coral Rosé | Acento principal (links) |
| ![#E98A99](https://placehold.co/16x16/E98A99/E98A99.png) | `#E98A99` | Rosa Empoeirado | Acento secundário |
| ![#9BB2C6](https://placehold.co/16x16/9BB2C6/9BB2C6.png) | `#9BB2C6` | Azul-Cinza Claro | Detalhes, ícones |
| ![#D03B47](https://placehold.co/16x16/D03B47/D03B47.png) | `#D03B47` | Cherry Pop | CTA (botões) |
| ![#C03A3E](https://placehold.co/16x16/C03A3E/C03A3E.png) | `#C03A3E` | Berry Pop | Hover de botões |
| ![#7E8AA0](https://placehold.co/16x16/7E8AA0/7E8AA0.png) | `#7E8AA0` | Cinza-Azulado | Texto secundário |

### Cores Auxiliares

| Cor | HEX | Nome | Uso |
|-----|-----|------|-----|
| ![#FFFFFF](https://placehold.co/16x16/FFFFFF/FFFFFF.png) | `#FFFFFF` | Branco puro | Áreas limpas |
| ![#E8DCD3](https://placehold.co/16x16/E8DCD3/E8DCD3.png) | `#E8DCD3` | Bege claro | Bordas suaves |
| ![#C8B89C](https://placehold.co/16x16/C8B89C/C8B89C.png) | `#C8B89C` | Bege médio | Bordas visíveis |
| ![#A02830](https://placehold.co/16x16/A02830/A02830.png) | `#A02830` | Berry Profundo | Botões pressionados |
| ![#6B8E5A](https://placehold.co/16x16/6B8E5A/6B8E5A.png) | `#6B8E5A` | Verde Musgo | Sucesso |
| ![#B53D2B](https://placehold.co/16x16/B53D2B/B53D2B.png) | `#B53D2B` | Vermelho Tijolo | Erro |

### CSS Variables (uso obrigatório)

```css
:root {
    /* Cores base */
    --color-bg: #FAF6F0;
    --color-card-bg: #F5E1DC;
    --color-primary: #3D4F6E;
    --color-text: #23314A;
    --color-accent: #DB8084;
    --color-accent-2: #E98A99;
    --color-detail: #9BB2C6;
    --color-cta: #D03B47;
    --color-cta-hover: #C03A3E;
    --color-text-muted: #7E8AA0;

    /* Cores auxiliares */
    --color-white: #FFFFFF;
    --color-border-soft: #E8DCD3;
    --color-border: #C8B89C;
    --color-cta-active: #A02830;
    --color-success: #6B8E5A;
    --color-error: #B53D2B;
}
```

---

## ✒️ Tipografia

### Combinação Editorial Romântico

- **Playfair Display** — Títulos (H1, H2, H3, headlines, cabeçalhos)
- **Inter** — Corpo de texto, navegação, botões, formulários, interface

### Instalação (Google Fonts)

Cole no `<head>` do HTML:

```html
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
```

### Hierarquia de Tamanhos

| Elemento | Fonte | Tamanho | Peso | Line-height |
|----------|-------|---------|------|-------------|
| H1 | Playfair Display | 42px | 600 | 1.2 |
| H2 | Playfair Display | 32px | 600 | 1.25 |
| H3 | Playfair Display | 24px | 600 | 1.3 |
| Corpo | Inter | 17px | 400 | 1.75 |
| Pequeno | Inter | 14px | 400 | normal |
| Mínimo | Inter | 12px | 500 | normal |

### Uso por Elemento

| Elemento | Fonte / Detalhes |
|----------|------------------|
| Títulos de artigo | Playfair Display |
| Subtítulos / seções | Playfair Display |
| Corpo de texto | Inter |
| Navegação do site | Inter — peso 500 |
| Botões CTA | Inter — peso 600 — UPPERCASE — letter-spacing 1px |
| Badges / tags | Inter — peso 700 — UPPERCASE — letter-spacing 1.5px |
| Metadados (autor, data) | Inter — peso 400 |
| Formulários (labels, inputs) | Inter — peso 500 (label), 400 (input) |
| Rodapé | Inter — peso 400 |

---

## 📐 Espaçamentos

### Sistema Base

**Múltiplos de 8px.** Sempre use as variáveis CSS, nunca pixels soltos.

### Escala

| Token | Valor | Nome |
|-------|-------|------|
| XS | 4px | Extra pequeno |
| SM | 8px | Pequeno |
| MD | 16px | Médio |
| LG | 24px | Grande |
| XL | 32px | Extra grande |
| 2XL | 48px | 2x extra grande |
| 3XL | 64px | 3x extra grande |
| 4XL | 96px | 4x extra grande |

### CSS Variables

```css
:root {
    --space-xs: 4px;
    --space-sm: 8px;
    --space-md: 16px;
    --space-lg: 24px;
    --space-xl: 32px;
    --space-2xl: 48px;
    --space-3xl: 64px;
    --space-4xl: 96px;
}
```

### Quando Usar Cada Um

| Token | Uso |
|-------|-----|
| **XS** 4px | Entre ícone e texto · Padding de badges pequenas · Border-radius pequeno |
| **SM** 8px | Entre título e subtítulo · Padding de tags · Espaço entre linhas em listas |
| **MD** 16px | Entre parágrafos · Padding de botões · Entre elementos relacionados |
| **LG** 24px | Entre título e corpo · Margem inferior do CTA · Padding lateral de cards |
| **XL** 32px | Padding interno de cards · Entre seções dentro de uma página · Lateral de containers |
| **2XL** 48px | Entre seções principais · Padding vertical de hero/destaques · Artigo↔comentários |
| **3XL** 64px | Padding vertical de páginas inteiras · Header↔conteúdo · Hero sections |
| **4XL** 96px | Padding vertical de landing pages · Espaço gigante entre seções de impacto |

### Regras de Ouro

1. **Nunca** use valores aleatórios (15px, 22px, 37px). Sempre múltiplos de 8.
2. Elementos relacionados ficam **próximos** (MD). Diferentes ficam **afastados** (XL+).
3. Padding interno de cards é maior que o espaço entre elementos do card.
4. **Mobile** pode reduzir espaçamentos grandes em 50% (3XL → XL+), mas mantém os pequenos.

---

## 🎯 Botões

### Os 3 Tipos

| Tipo | Quando usar |
|------|-------------|
| **Primário** — Cherry Pop preenchido | Ação principal da página |
| **Secundário** — Contorno Navy | Ações importantes não principais |
| **Texto** — Sem fundo, sublinhado no hover | Ações terciárias |

### Especificações Detalhadas

#### Primário

```css
.btn-primary {
    background: #D03B47;
    color: #FAF6F0;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 8px;
    transition: all 0.2s ease;
}
.btn-primary:hover {
    background: #C03A3E;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(208, 59, 71, 0.3);
}
.btn-primary:active {
    background: #A02830;
    transform: translateY(0);
}
.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
```

#### Secundário

```css
.btn-secondary {
    background: transparent;
    color: #23314A;
    border: 1.5px solid #23314A;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-radius: 8px;
}
.btn-secondary:hover {
    background: #23314A;
    color: #FAF6F0;
    transform: translateY(-1px);
}
.btn-secondary:active {
    background: #3D4F6E;
}
```

#### Texto

```css
.btn-text {
    background: transparent;
    color: #D03B47;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
}
.btn-text:hover {
    color: #C03A3E;
    border-bottom: 2px solid #C03A3E;
}
```

### Tamanhos

| Tamanho | Padding | Font-size | Uso |
|---------|---------|-----------|-----|
| **SM** | `8px 18px` | 11px | Ações inline, cards compactos |
| **MD** | `14px 28px` | 13px | **PADRÃO** |
| **LG** | `18px 36px` | 14px | Hero, captura, momentos de foco |

### Botão de Bloco

```css
.btn-block { width: 100%; text-align: center; }
```

Use em formulários, cards estreitos, e mobile.

### Hierarquia na Prática

| Contexto | Combinação |
|----------|------------|
| Card de artigo | 1 Primário (Ler artigo) + 1 Texto (Salvar) |
| Hero homepage | 1 Primário LG + 1 Secundário LG |
| Final de artigo | 1 Primário (Próximo) + 2 Texto (Compartilhar, Salvar) |
| Newsletter | 1 Primário (Inscrever) + 1 Texto (Agora não) |

### Regras de Ouro

1. **Só UM primário** por decisão. Sempre.
2. **Texto do botão = ação concreta**. Use "Baixar guia" em vez de "Clique aqui".
3. **Tamanho proporcional** à importância. Hero = LG. Card = MD. Inline = SM.
4. **Estados sempre programados**: hover, active, disabled.
5. Em mobile, use `btn-block` em CTAs principais.

### Erros Comuns a Evitar

- Dois primários competindo na mesma seção
- Botão grande pra ação pequena (desproporcional)
- Texto vago ("Clique aqui", "OK", "Saiba mais")
- Botão sem feedback visual no hover

---

## 🧱 Cards e Blocos

### Sistema Base

**Border-radius:**

| Token | Valor | Uso |
|-------|-------|-----|
| SM | 6px | Badges, tags, inputs |
| MD | 8px | Botões, imagens dentro de cards |
| LG | 12px | Cards de artigo, blocos |
| XL | 16px | Cards de destaque, seções grandes |

**Sombras:**

```css
--shadow-sm: 0 2px 8px rgba(35, 49, 74, 0.06);   /* Cards padrão */
--shadow-md: 0 4px 20px rgba(35, 49, 74, 0.08);  /* Hover / destaque */
--shadow-lg: 0 8px 30px rgba(35, 49, 74, 0.12);  /* Modais (raro) */
```

### Cards de Artigo

#### Card Padrão (Listagem em Grid)

- Background: `#FFFFFF`
- Border: `1px solid #E8DCD3`
- Border-radius: 12px (LG)
- Box-shadow: `shadow-sm`
- Padding interno: 24px (LG)
- **Hover**: `translateY(-4px)` + `shadow-md`
- Transition: 0.25s ease
- Imagem topo: altura 200px
- Estrutura: imagem + badge + título (Playfair 22px) + meta + excerpt + link

#### Card Destaque (Featured Post)

- Background: `#23314A` (Navy Escuro)
- Color: `#FAF6F0`
- Border-radius: 16px (XL)
- Box-shadow: `shadow-md`
- Grid: 2 colunas (imagem + conteúdo)
- Padding: `48px 32px` (2XL + XL)
- Imagem mínimo 280px altura
- Badge: Cherry Pop
- Título: Playfair 30px
- CTA: botão primário (Cherry Pop)

#### Card Compacto (Related / Sidebar)

- Background: `#FFFFFF`
- Border: `1px solid #E8DCD3`
- Border-radius: 12px (LG)
- Sem sombra (só no hover: `shadow-sm`)
- Grid: imagem 120px + conteúdo
- Padding conteúdo: `16px 24px`
- Título: Playfair 16px
- Meta pequena: 12px

### Blocos Dentro de Artigos

#### Bloco Citação

```css
.block-quote {
    border-left: 4px solid #D03B47;
    background: #F5E1DC;
    padding: 32px;
    border-radius: 0 8px 8px 0;
    font-family: 'Playfair Display', serif;
    font-style: italic;
    font-size: 20px;
}
.block-quote-author {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    color: #7E8AA0;
}
```

#### Bloco Dica

- Background: gradient `#F5E1DC` → `#FFE8DD`
- Border-left: 4px solid `#DB8084` (Coral Rosé)
- Padding: 24px (LG)
- Border-radius: 12px (LG)
- Label `💡 Dica`: background coral, texto off-white, uppercase
- Texto: Inter 15px

#### Bloco Atenção

- Background: `#FFF3F0`
- Border-left: 4px solid `#B53D2B` (Vermelho Tijolo)
- Padding: 24px (LG)
- Border-radius: 12px (LG)
- Label `⚠️ Atenção`: background tijolo, texto off-white, uppercase

#### Bloco Newsletter

- Background: `#23314A` (Navy Escuro)
- Color: `#FAF6F0`
- Padding: 48px (2XL)
- Border-radius: 12px (LG)
- Text-align: center
- Título: Playfair 24px
- Subtítulo: cinza claro 15px
- Form: input + botão Cherry Pop, max-width 400px

#### Bloco Produto Afiliado

- Background: `#FFFFFF`
- Border: `2px solid #E98A99` (Rosa Empoeirado) — **visível por exigência ética/legal**
- Border-radius: 12px (LG)
- Padding: 32px (XL)
- Grid: 100px (imagem) + flex (info) + auto (CTA)
- Imagem: 100x100px, radius 8px
- Tag "Recomendação": background rosé, uppercase
- Título produto: Playfair 18px
- Descrição: Inter 13px
- CTA: botão primário pequeno

### Regras de Ouro

1. Cards na homepage devem ter **mesma altura** em grid (use flexbox/grid).
2. **Hover só em elementos clicáveis**. Cards estáticos não têm hover.
3. Use blocos com **moderação** dentro de artigos. 2-3 por post no máximo.
4. **Bloco produto afiliado** deve ter borda visível pra diferenciar do conteúdo editorial.
5. Sombras suaves > sombras dramáticas.

---

## 📊 Layout e Grid

### Containers (Largura Máxima)

| Token | Largura | Uso |
|-------|---------|-----|
| **NARROW** | 680px | Artigos (corpo de texto), páginas legais |
| **MEDIUM** | 900px | Páginas institucionais (about, contact) |
| **WIDE** | 1100px | Homepage, listagens, categorias **(PADRÃO)** |
| **FULL** | 1280px | Hero com imagem de fundo, banners |

- Todos centralizados: `margin: 0 auto`
- Padding lateral mínimo: **16px mobile**, **32px desktop**

### Breakpoints (Responsividade)

| Breakpoint | Faixa | Layout |
|------------|-------|--------|
| **Mobile** | até 640px | 1 coluna sempre, padding 16px, botões em block |
| **Tablet** | 641-1024px | Grid 2 colunas, sidebar empilha |
| **Desktop** | 1025-1440px | Layout completo, 3 colunas, sidebar lateral |
| **Wide** | acima 1440px | Mesmo do desktop, conteúdo centralizado |

**Mobile first**: projete primeiro pro celular, depois expanda.
70%+ do tráfego virá de mobile (Pinterest BR).

### Grids Comuns

| Tipo | Quando usar |
|------|-------------|
| **1 coluna** | Leitura, artigos, formulários |
| **2 colunas** | About (foto+texto), comparativos, categoria |
| **3 colunas** | Homepage, listagens (**MAIS USADO**) |
| **4 colunas** | Galerias compactas, ícones de categoria |
| **2/3 + 1/3** | Conteúdo principal + sidebar |

- Gap entre items: `var(--space-md)` a `var(--space-lg)` (16-24px)
- Em mobile, todos colapsam pra 1 coluna automaticamente

### Estrutura por Tipo de Página

| Página | Container | Grid | Sidebar |
|--------|-----------|------|---------|
| Homepage | WIDE | 3 colunas | Não |
| Artigo | NARROW | 1 coluna | Não |
| Categoria | WIDE | 2 colunas + filtros | Sim |
| About | MEDIUM | 2 colunas (foto+texto) | Não |
| Contact | MEDIUM | 1 ou 2 colunas | Não |
| Privacy / Terms / Disclosure | NARROW | 1 coluna | Não |
| Busca | WIDE | 2 colunas + filtros | Sim |

### Regras de Ouro

1. **Artigos sempre em NARROW** (680px). Linha de leitura ideal: 60-75 caracteres.
2. **Mobile first**.
3. **Padding lateral mínimo**: 16px mobile, 32px desktop.
4. **Grids colapsam pra 1 coluna em mobile** sempre.
5. Use **flexbox ou CSS Grid**. Esqueça float, table-layout.

---

## 🖼 Imagens

### Proporções Padrão

| Proporção | Dimensão | Uso |
|-----------|----------|-----|
| **16:9** Widescreen | 1200×675 | Hero de artigo, imagens inline |
| **4:3** Padrão | 800×600 | Cards de artigo, listagens |
| **1:1** Quadrado | 800×800 | Produtos afiliados, ícones, perfil pequeno |
| **3:4** Retrato | 600×800 | Foto da autora, pessoas |
| **2:3** Pinterest | 1000×1500 | Pins (uso só na plataforma) |

### Onde Usar Cada Uma

| Local | Proporção | Border-radius | Margem |
|-------|-----------|---------------|--------|
| Hero de artigo | 16:9 (1200×675) | 12px (LG) | 24px inferior |
| Imagem inline | 16:9 (1200×675) | 12px (LG) | 24px vertical + legenda Playfair italic 13px |
| Cards (homepage) | 4:3 (800×600) | 0 (container já tem) | `object-fit: cover` |
| Foto autora (About) | 3:4 (600×800) | 12px (LG) | — |
| Avatar (final de artigo) | 1:1 (200×200) | 50% (circular) | — |
| Produto afiliado | 1:1 (800×800) | 8px (MD) | — |
| Pins Pinterest | 2:3 (1000×1500) | 0 | — |

### Tratamento Visual

- **`object-fit: cover` SEMPRE** em imagens com tamanho fixo. Mantém proporção, recorta excesso.
- **Sombras**: imagens dentro de cards **NÃO** têm sombra. Standalone podem ter `shadow-sm`.

### Otimização

| Item | Regra |
|------|-------|
| Formato | **WebP** sempre que possível. Fallback: JPG (fotos), PNG (transparência) |
| Tamanho do arquivo | Máximo **200KB**. Hero até 300KB. Use [tinypng.com](https://tinypng.com) ou [squoosh.app](https://squoosh.app) |
| Dimensões | Exporte no tamanho final, nunca amplie |
| Lazy loading | `loading="lazy"` em imagens abaixo do fold (não no hero) |
| Alt text | **Sempre** descritivo. Não escreva "imagem" |
| Nome do arquivo | Descritivo com hífens (`agulhas-croche-bambu.jpg`). Nunca `IMG_4823.jpg` |

### Bancos Gratuitos

- [Unsplash](https://unsplash.com) — maior banco gratuito
- [Pexels](https://pexels.com) — alternativa
- [Pixabay](https://pixabay.com) — fotos, ilustrações, vetores
- [Burst](https://burst.shopify.com) — focado em e-commerce, DIY
- [Canva](https://canva.com) — pra criar imagens próprias

Busque em **inglês** para mais resultados ("crochet", "knitting", "gardening", "craft").

**NUNCA pegue imagens do Google.** Quase todas têm copyright.

### Erros Comuns a Evitar

- Imagem distorcida (esquecer `object-fit: cover`)
- Misturar estilos (filtros/paletas diferentes)
- Imagens pesadas (acima de 200KB sem motivo)
- Imagens pixeladas (amplificar pequenas)
- Falta de alt text
- Nome de arquivo sem sentido

### Regras de Ouro

1. Sempre `object-fit: cover` em imagens com tamanho fixo
2. Toda imagem precisa de `alt=""` descritivo
3. Comprima TUDO antes do upload (meta: <200KB)
4. Use `loading="lazy"` abaixo do fold
5. Mantenha estética consistente (mesma paleta, mesmo estilo de luz)
6. Nunca pegue do Google. Use Unsplash/Pexels/Pixabay

---

## 🎯 Ícones (Phosphor)

Sistema de ícones: **[Phosphor Icons](https://phosphoricons.com)** carregado via CDN. Dois pesos disponíveis no site:

| Peso | Uso | Classe |
|------|-----|--------|
| **Light** (`ph-light`) | Conteúdo (drawer, contato, footer, 404) — delicado, editorial | `<i class="ph-light ph-NOME"></i>` |
| **Regular** (`ph`) | Header (hambúrguer, Pinterest, busca) — mais presença visual | `<i class="ph ph-NOME"></i>` |

### Carregamento (no `<head>`)

```html
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/light/style.css">
```

> Phosphor é **font-based** — não precisa de JS (`createIcons()`). Renderiza só com CSS.

### Tamanhos (`assets/css/site.css`)

```css
.icon-xs  { font-size: 14px; }   /* metadados, badges */
.icon-sm  { font-size: 18px; }   /* nav, links inline */
.icon-md  { font-size: 24px; }   /* botões médios, hambúrguer */
.icon-lg  { font-size: 32px; }   /* ícones de seção */
.icon-xl  { font-size: 48px; }   /* heros, cards de destaque */
.icon-2xl { font-size: 64px; }   /* 404, error states */
```

Ícones herdam `currentColor` do elemento pai automaticamente.

### Ícones usados hoje

| Local | Ícone | Peso |
|-------|-------|------|
| Hambúrguer header | `ph-list` | regular |
| Pinterest header | `ph-pinterest-logo` | regular |
| Busca header | `ph-magnifying-glass` | regular |
| Fechar drawer | `ph-x` | light |
| Categorias | `ph-hand-heart`, `ph-flower-tulip`, `ph-palette`, `ph-books` | light |
| Tempo de leitura | `ph-clock` | light |
| Setas | `ph-arrow-right` | light |
| Pinterest footer | `ph-pinterest-logo` | light |
| Email contato | `ph-envelope` | light |
| 404 (lupa) | `ph-magnifying-glass-minus` | light |
| Home (404 CTA) | `ph-house` | light |

> Detalhes técnicos (helper de mapeamento legacy, render dinâmico de SVG customizado para categorias) em [07-categorias.md](07-categorias.md).
