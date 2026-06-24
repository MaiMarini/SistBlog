<?php
/**
 * RECIPE BLOCKS — Helpers de blocos editoriais de receita (Fase R-C1.1).
 *
 * Cada bloco tem:
 *   - block_type:    ENUM (steps, tip, step_photos, stitch_guide_inline, notes, color_guide)
 *   - block_data:    JSON com dados específicos do tipo
 *   - display_order: int — calculado a partir do tipo + posição relativa
 *
 * Ordem fixa pública (1..6) — primeiros 2 implementados nesta fase:
 *   1. steps
 *   2. step_photos          (R-C1.2)
 *   3. stitch_guide_inline  (R-C1.3)
 *   4. color_guide          (R-C1.2)
 *   5. tip
 *   6. notes                (R-C1.3)
 */

require_once __DIR__ . '/functions.php';

/**
 * Ordem fixa dos tipos (= ordem de renderização pública).
 * Usado pra calcular display_order ao salvar.
 */
function getBlockTypesOrder(): array {
    return [
        'steps'               => 1,
        'step_photos'         => 2,
        'stitch_guide_inline' => 3,
        'color_guide'         => 4,
        'tip'                 => 5,
        'notes'                => 6,
    ];
}

/**
 * Configurações visuais dos 3 tipos de "tip card" (público + admin).
 */
function getTipTypes(): array {
    return [
        'tip' => [
            'label_br'   => 'Dica',
            'label_en'   => 'Tip',
            'icon'       => 'ph-light ph-lightbulb',
            'icon_emoji' => '💡',
            'bg'         => '#E0EDD8', // sage
            'text'       => '#6B8552',
        ],
        'alert' => [
            'label_br'   => 'Alerta',
            'label_en'   => 'Warning',
            'icon'       => 'ph-light ph-warning',
            'icon_emoji' => '⚠️',
            'bg'         => '#FCEAC4', // honey
            'text'       => '#B5853A',
        ],
        'important' => [
            'label_br'   => 'Importante',
            'label_en'   => 'Important',
            'icon'       => 'ph-light ph-info',
            'icon_emoji' => '❗',
            'bg'         => '#F0D5D5', // rose escurecido
            'text'       => '#943838',
        ],
    ];
}

/**
 * Busca todos os blocos de uma receita, ordenados por display_order.
 * Cada item ganha a chave `data` com o JSON já decodificado.
 */
function getRecipeBlocks(int $pageId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, block_type, block_data, display_order
        FROM recipe_blocks
        WHERE page_id = ?
        ORDER BY display_order ASC, id ASC
    ");
    $stmt->execute([$pageId]);
    $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($blocks as &$b) {
        $decoded = json_decode($b['block_data'], true);
        $b['data'] = is_array($decoded) ? $decoded : [];
    }
    unset($b);

    return $blocks;
}

/**
 * Substitui (delete + insert) todos os blocos de uma receita.
 *
 * $blocks é uma lista de:
 *   ['type' => 'steps', 'data' => ['section' => '...', 'content' => '...']]
 *   ['type' => 'tip',   'data' => ['type' => 'tip', 'text' => '...']]
 *
 * display_order é calculado como (typeOrder * 100 + posição) — assim mantém
 * agrupamento por tipo + preserva a ordem relativa dentro do grupo.
 */
function saveRecipeBlocks(int $pageId, array $blocks): void {
    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $pdo->prepare("DELETE FROM recipe_blocks WHERE page_id = ?")->execute([$pageId]);

        $insert = $pdo->prepare("
            INSERT INTO recipe_blocks (page_id, block_type, block_data, display_order)
            VALUES (?, ?, ?, ?)
        ");

        $typeOrder = getBlockTypesOrder();
        $perTypeCounter = [];  // posição relativa dentro de cada tipo

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            $data = $block['data'] ?? null;

            if (!$type || !is_array($data)) continue;
            if (!array_key_exists($type, $typeOrder)) continue;

            $perTypeCounter[$type] = ($perTypeCounter[$type] ?? 0) + 1;
            $order = $typeOrder[$type] * 100 + $perTypeCounter[$type];

            $insert->execute([
                $pageId,
                $type,
                json_encode($data, JSON_UNESCAPED_UNICODE),
                $order,
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Detecta padrão de carreira numa linha solta.
 *
 * Retorna ['row', 'instruction', 'total'] se bater no formato Rxx: ... (total)
 * ou null se for linha livre.
 *
 * Exemplos reconhecidos:
 *   R1: 6 pb no anel (6)
 *   R2: aum em todos (12)
 *   R6-9: 30 pb (4 voltas)
 *   R10. 1 pb, dim em x (12)    [aceita `.` como separador]
 */
function parseStepLine(string $line): ?array {
    $line = trim($line);
    if ($line === '') return null;

    if (preg_match('/^(R\d+(?:-\d+)?)[:.\s]+(.+?)\s*\(([^)]+)\)\s*$/u', $line, $m)) {
        return [
            'row'         => trim($m[1]),
            'instruction' => trim($m[2]),
            'total'       => '(' . trim($m[3]) . ')',
        ];
    }

    return null;
}

/**
 * Parseia o textarea de "linhas da receita" em estrutura renderizável.
 * Linhas estruturadas viram ['row','instruction','total'].
 * Linhas que não batem viram ['free' => '...'].
 */
function parseStepsContent(string $content): array {
    $lines = preg_split('/[\r\n]+/', $content);
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $parsed = parseStepLine($line);
        $out[] = $parsed ?? ['free' => $line];
    }
    return $out;
}
