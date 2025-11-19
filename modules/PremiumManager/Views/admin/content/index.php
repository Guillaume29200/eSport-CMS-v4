<?php
/**
 * View: Liste des contenus premium (Admin)
 * 
 * Variables disponibles:
 * - $contents: array de PremiumContent
 * - $stats: statistiques
 * - $page, $perPage, $total: pagination
 */

// Protection
if (!defined('ESPORT_CMS')) die('Access denied');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contenus Premium - Admin</title>
</head>
<body>

<div class="admin-container">
    <!-- Header -->
    <div class="page-header">
        <h1>💎 Contenus Premium</h1>
        <div class="actions">
            <a href="/admin/premium/content/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau contenu premium
            </a>
        </div>
    </div>

    <!-- Stats rapides -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total contenus premium</div>
            <div class="stat-value"><?= $stats['total_premium_content'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Achats uniques</div>
            <div class="stat-value"><?= $stats['one_time_count'] ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Abonnements requis</div>
            <div class="stat-value"><?= $stats['subscription_count'] ?></div>
        </div>
    </div>

    <!-- Filtres -->
    <form method="GET" class="filters-form">
        <div class="filter-group">
            <label>Type de contenu</label>
            <select name="type">
                <option value="">Tous</option>
                <option value="article" <?= ($_GET['type'] ?? '') === 'article' ? 'selected' : '' ?>>Article</option>
                <option value="page" <?= ($_GET['type'] ?? '') === 'page' ? 'selected' : '' ?>>Page</option>
                <option value="module" <?= ($_GET['type'] ?? '') === 'module' ? 'selected' : '' ?>>Module</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Type d'accès</label>
            <select name="access">
                <option value="">Tous</option>
                <option value="one_time" <?= ($_GET['access'] ?? '') === 'one_time' ? 'selected' : '' ?>>Achat unique</option>
                <option value="subscription" <?= ($_GET['access'] ?? '') === 'subscription' ? 'selected' : '' ?>>Abonnement</option>
                <option value="plan_required" <?= ($_GET['access'] ?? '') === 'plan_required' ? 'selected' : '' ?>>Plan spécifique</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Filtrer</button>
        <a href="/admin/premium/content" class="btn btn-light">Réinitialiser</a>
    </form>

    <!-- Liste des contenus -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Contenu</th>
                    <th>Type</th>
                    <th>Accès</th>
                    <th>Prix</th>
                    <th>Preview</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contents)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">
                            Aucun contenu premium trouvé
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contents as $content): ?>
                        <tr>
                            <td><?= $content->id ?></td>
                            <td>
                                <strong><?= htmlspecialchars($content->content_title ?? 'N/A') ?></strong><br>
                                <small class="text-muted">ID: <?= $content->contentId ?></small>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= htmlspecialchars($content->getFormattedContentType()) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($content->getFormattedAccessType()) ?></td>
                            <td>
                                <?php if ($content->price): ?>
                                    <strong><?= htmlspecialchars($content->getFormattedPrice()) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($content->previewEnabled): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-eye"></i> <?= $content->previewLength ?> car.
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Non</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($content->active): ?>
                                    <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/admin/premium/content/<?= $content->id ?>/edit" 
                                       class="btn btn-sm btn-primary" 
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" 
                                          action="/admin/premium/content/<?= $content->id ?>/delete" 
                                          style="display:inline"
                                          onsubmit="return confirm('Supprimer ce contenu premium ?')">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total > $perPage): ?>
        <nav class="pagination-nav">
            <?php
            $totalPages = ceil($total / $perPage);
            $currentPage = $page;
            ?>
            <ul class="pagination">
                <?php if ($currentPage > 1): ?>
                    <li><a href="?page=<?= $currentPage - 1 ?>">&laquo; Précédent</a></li>
                <?php endif; ?>

                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <li class="<?= $i === $currentPage ? 'active' : '' ?>">
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li><a href="?page=<?= $currentPage + 1 ?>">Suivant &raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}
.stat-label {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 5px;
}
.stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #212529;
}
.filters-form {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    align-items: flex-end;
}
.filter-group {
    display: flex;
    flex-direction: column;
}
.badge-gold {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #000;
}
</style>

</body>
</html>
