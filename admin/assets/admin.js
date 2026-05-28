// ============================================
// KALLME ADMIN - ADMIN SCRIPTS
// ============================================

// Gerar slug a partir do título
function generateSlug(text) {
    const slugInput = document.getElementById('slug');
    if (!slugInput) return;

    // Só gerar automaticamente se o slug estiver vazio ou não foi editado manualmente
    if (slugInput.dataset.manual === 'true') return;

    let slug = text.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .replace(/[^a-z0-9\s-]/g, '')    // Remove caracteres especiais
        .replace(/[\s-]+/g, '-')          // Espaços e hífens múltiplos para um
        .replace(/^-|-$/g, '');           // Remove hífens no início/fim

    slugInput.value = slug;
}

// Marcar slug como editado manualmente
document.addEventListener('DOMContentLoaded', function() {
    const slugInput = document.getElementById('slug');
    if (slugInput) {
        slugInput.addEventListener('input', function() {
            this.dataset.manual = 'true';
        });
    }
});

// Adicionar comentário fictício
function addComment() {
    const container = document.getElementById('comments-container');
    if (!container) return;

    const index = container.children.length;
    const row = document.createElement('div');
    row.className = 'comment-row';
    row.dataset.index = index;

    row.innerHTML = `
        <div class="comment-fields">
            <input type="text" name="comment_name[]" placeholder="Nome" value="">
            <input type="text" name="comment_date[]" placeholder="Data (dd/mm/aaaa)" value="">
            <textarea name="comment_text[]" placeholder="Texto do comentário" rows="2"></textarea>
        </div>
        <button type="button" class="btn btn-sm btn-delete" onclick="this.closest('.comment-row').remove()">🗑️</button>
    `;

    container.appendChild(row);

    // Focar no primeiro campo
    row.querySelector('input[name="comment_name[]"]').focus();
}

// Auto-fechar alertas após 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
